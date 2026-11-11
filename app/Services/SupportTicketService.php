<?php

namespace App\Services;

use App\Contracts\SupportTicketServiceInterface;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stores "Bantuan" tickets with a server-side daily limit, keeps their
 * screenshots private, and records how admins handle them.
 */
class SupportTicketService implements SupportTicketServiceInterface
{
    /**
     * Store a ticket after validating, sanitising and rate limiting it.
     *
     * @param  User  $user  The mentee asking for help.
     * @param  array<string, mixed>  $input  Keys: category, subject, description, page_url.
     * @param  UploadedFile|null  $screenshot  Optional, already validated image.
     * @param  string|null  $user_agent  Browser string from the request.
     * @return SupportTicket The stored ticket with status "new".
     *
     * @throws ValidationException When the input is invalid or the daily limit is reached.
     */
    public function submit(User $user, array $input, ?UploadedFile $screenshot = null, ?string $user_agent = null): SupportTicket
    {
        $data = $this->validated([
            'category' => $this->plainText($input['category'] ?? null),
            'subject' => $this->plainText($input['subject'] ?? null),
            'description' => $this->plainText($input['description'] ?? null),
            'page_url' => $this->plainText($input['page_url'] ?? null),
        ]);

        // The screenshot is written before the transaction so a slow upload
        // never holds the row lock; it is removed again if storing fails.
        $path = $screenshot?->store(self::SCREENSHOT_FOLDER.'/'.$user->id, 'local');
        $path = is_string($path) ? $path : null;

        try {
            return DB::transaction(function () use ($user, $data, $path, $user_agent): SupportTicket {
                // Serialise concurrent submissions of the same mentee so the
                // daily cap cannot be raced past.
                User::query()->whereKey($user->id)->lockForUpdate()->first();

                $recent = SupportTicket::query()
                    ->where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->count();

                if ($recent >= self::DAILY_LIMIT) {
                    throw ValidationException::withMessages([
                        'ticket' => __('Kamu sudah mengirim :count laporan dalam 24 jam terakhir. Untuk hal mendesak, hubungi kami lewat WhatsApp ya.', ['count' => self::DAILY_LIMIT]),
                    ]);
                }

                return SupportTicket::query()->create([
                    'user_id' => $user->id,
                    'category' => SupportTicketCategory::from($data['category']),
                    'subject' => $data['subject'],
                    'description' => $data['description'],
                    'page_url' => $data['page_url'],
                    'screenshot_path' => $path,
                    'user_agent' => $user_agent === null ? null : Str::limit($this->plainText($user_agent), 500, ''),
                    'status' => SupportTicketStatus::New,
                ]);
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Record an admin status change on a ticket.
     *
     * @param  SupportTicket  $ticket  The ticket being handled.
     * @param  User  $admin  The handling admin.
     * @param  SupportTicketStatus  $status  New status.
     * @param  string|null  $admin_note  Optional internal note; blank keeps the previous note.
     * @return SupportTicket The updated ticket.
     */
    public function updateStatus(SupportTicket $ticket, User $admin, SupportTicketStatus $status, ?string $admin_note = null): SupportTicket
    {
        $note = $this->plainText($admin_note);

        $ticket->forceFill([
            'status' => $status,
            'handled_by' => $admin->id,
            'resolved_at' => $status === SupportTicketStatus::Resolved ? now() : null,
            'admin_note' => $note === '' ? $ticket->admin_note : mb_substr($note, 0, self::MAX_DESCRIPTION_LENGTH),
        ])->save();

        return $ticket;
    }

    /**
     * Build the wa.me link of the help line with a short prefilled message.
     *
     * @param  User|null  $user  Signed-in mentee, used to greet by name.
     * @return string|null The link, or null when no number is configured.
     */
    public function whatsappUrl(?User $user = null): ?string
    {
        $number = $this->whatsappNumber();

        if ($number === null) {
            return null;
        }

        $message = $user instanceof User
            ? __('Halo tim Skular, saya :name. Saya butuh bantuan terkait: ', ['name' => $user->name])
            : __('Halo tim Skular, saya butuh bantuan terkait: ');

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    /**
     * Format the configured help number for display (e.g. "+62 896-0488-3024").
     *
     * @return string|null Display number, or null when none is configured.
     */
    public function whatsappDisplayNumber(): ?string
    {
        $number = $this->whatsappNumber();

        if ($number === null) {
            return null;
        }

        // Indonesian mobile numbers: +62 8xx-xxxx-xxxx.
        if (str_starts_with($number, '62') && strlen($number) >= 11) {
            $local = substr($number, 2);

            return '+62 '.substr($local, 0, 3).'-'.substr($local, 3, 4).'-'.substr($local, 7);
        }

        return '+'.$number;
    }

    /**
     * Get the configured help number as digits only.
     */
    private function whatsappNumber(): ?string
    {
        $configured = config('services.support.whatsapp');
        $digits = is_scalar($configured) ? (string) preg_replace('/\D+/', '', (string) $configured) : '';

        return $digits === '' ? null : $digits;
    }

    /**
     * Validate sanitised input.
     *
     * @param  array<string, string>  $input
     * @return array{category: string, subject: string, description: string, page_url: string|null}
     *
     * @throws ValidationException
     */
    private function validated(array $input): array
    {
        $input['page_url'] = $input['page_url'] === '' ? null : $input['page_url'];

        /** @var array{category: string, subject: string, description: string, page_url?: string|null} $data */
        $data = Validator::make($input, [
            'category' => ['required', Rule::enum(SupportTicketCategory::class)],
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:'.self::MAX_DESCRIPTION_LENGTH],
            'page_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
        ], [], [
            'category' => __('jenis masalah'),
            'subject' => __('judul singkat'),
            'description' => __('penjelasan'),
            'page_url' => __('tautan halaman'),
        ])->validate();

        return [
            'category' => $data['category'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'page_url' => $data['page_url'] ?? null,
        ];
    }

    /**
     * Strip tags and control characters and collapse runs of blank space.
     */
    private function plainText(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = strip_tags((string) $value);
        $text = (string) preg_replace('/[^\P{C}\n]/u', '', $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }
}
