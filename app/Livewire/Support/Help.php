<?php

namespace App\Livewire\Support;

use App\Contracts\SupportTicketServiceInterface;
use App\Enums\SupportTicketCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Bantuan" page (route support.index): WhatsApp help line plus a form to
 * report bugs, errors or other problems, and the mentee's own recent tickets.
 *
 * Validation, sanitising and the daily limit live in SupportTicketService.
 */
#[Title('Bantuan')]
class Help extends Component
{
    use WithFileUploads;

    /**
     * Selected problem kind (SupportTicketCategory value).
     */
    public string $category = '';

    /**
     * Short title of the problem.
     */
    public string $subject = '';

    /**
     * What happened, and the steps that led to it.
     */
    public string $description = '';

    /**
     * Page where the problem happened (prefilled from the referring page).
     */
    public string $page_url = '';

    /**
     * Optional screenshot of the problem.
     *
     * @var UploadedFile|string|null
     */
    public $screenshot;

    /**
     * Prefill the page link with the page the mentee came from, when it is on this site.
     */
    public function mount(): void
    {
        $previous = url()->previous();
        $app_host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($previous !== url()->current() && parse_url($previous, PHP_URL_HOST) === $app_host) {
            $this->page_url = $previous;
        }
    }

    /**
     * Send the help form as a support ticket.
     */
    public function submit(SupportTicketServiceInterface $ticket_service): void
    {
        $this->validate([
            'screenshot' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SupportTicketServiceInterface::MAX_SCREENSHOT_KB],
        ], [], ['screenshot' => __('tangkapan layar')]);

        try {
            $ticket_service->submit(
                $this->user(),
                [
                    'category' => $this->category,
                    'subject' => $this->subject,
                    'description' => $this->description,
                    'page_url' => $this->page_url,
                ],
                $this->screenshot instanceof UploadedFile ? $this->screenshot : null,
                request()->userAgent(),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }

            return;
        }

        $this->reset('category', 'subject', 'description', 'page_url', 'screenshot');
        $this->resetErrorBag();

        $this->dispatch('toast', variant: 'success', text: __('Terima kasih, laporanmu sudah kami terima. Tim Skular akan segera mengeceknya.'));
    }

    /**
     * Remove the selected screenshot before sending.
     */
    public function removeScreenshot(): void
    {
        $this->screenshot = null;
        $this->resetErrorBag('screenshot');
    }

    /**
     * Render the help page.
     *
     * @return View The livewire.support.help view.
     */
    public function render(SupportTicketServiceInterface $ticket_service): View
    {
        $user = $this->user();

        return view('livewire.support.help', [
            'categories' => SupportTicketCategory::options(),
            'whatsapp_url' => $ticket_service->whatsappUrl($user),
            'whatsapp_number' => $ticket_service->whatsappDisplayNumber(),
            // Only the mentee's own tickets, newest first; admin notes are never shown.
            'tickets' => $user->supportTickets()
                ->latest()
                ->limit(5)
                ->get(['id', 'category', 'subject', 'status', 'created_at']),
        ]);
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
