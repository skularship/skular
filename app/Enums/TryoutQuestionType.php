<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * How a Try Out question is answered. Values equal `tipe_ui` of the bank
 * specification so the JSON bank imports without mapping. Stored values; never rename.
 */
enum TryoutQuestionType: string implements HasLabel
{
    use HasSelectOptions;

    case ShortText = 'teks_isian';
    case SingleChoice = 'pilihan_tunggal';
    case MatchingDropdown = 'dropdown_pasangan';
    case MapLabel = 'peta_klik';
    case HeadingDropdown = 'dropdown_judul';
    case Essay = 'teks_panjang';
    case AudioRecording = 'rekam_audio';

    /**
     * Get the Indonesian label shown in the admin.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ShortText => 'Teks isian',
            self::SingleChoice => 'Pilihan ganda (satu jawaban)',
            self::MatchingDropdown => 'Dropdown pasangan',
            self::MapLabel => 'Peta (pilih huruf)',
            self::HeadingDropdown => 'Dropdown judul paragraf',
            self::Essay => 'Esai',
            self::AudioRecording => 'Rekam audio',
        };
    }

    /**
     * Whether the system checks the answer against the key (no AI).
     */
    public function isAutoScored(): bool
    {
        return ! in_array($this, [self::Essay, self::AudioRecording], true);
    }

    /**
     * Whether the mentee picks one of the configured options.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SingleChoice, self::MatchingDropdown, self::MapLabel, self::HeadingDropdown], true);
    }

    /**
     * Ideal option count (single choice: A to D). Null = no recommendation.
     */
    public function recommendedOptions(): ?int
    {
        return $this === self::SingleChoice ? (int) config('tryout.options.recommended', 4) : null;
    }

    /**
     * Fewest options an option-based question may have.
     */
    public function minOptions(): int
    {
        return $this->hasOptions() ? (int) config('tryout.options.min', 2) : 0;
    }

    /**
     * The question types that belong to a skill, for the admin form.
     *
     * @return array<string, string>
     */
    public static function optionsFor(?TryoutSkill $skill): array
    {
        $cases = match ($skill) {
            TryoutSkill::Writing => [self::Essay],
            TryoutSkill::Speaking => [self::AudioRecording],
            TryoutSkill::Listening, TryoutSkill::Reading => [self::SingleChoice, self::ShortText, self::MatchingDropdown, self::MapLabel, self::HeadingDropdown],
            null => self::cases(),
        };

        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
