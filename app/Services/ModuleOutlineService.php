<?php

namespace App\Services;

use App\Contracts\ModuleOutlineServiceInterface;
use App\Data\ModuleOutlineStepData;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The single source of truth for the Skular learning loop:
 * pembukaan → pahami → quick question → praktik → checkpoint.
 *
 * Both the mentee module player and the admin curriculum outline read from
 * here, so the preview an admin approves can never drift from what the mentee
 * experiences.
 */
class ModuleOutlineService implements ModuleOutlineServiceInterface
{
    /**
     * Human readable labels for every learning step.
     *
     * @var array<string, string>
     */
    public const STEP_LABELS = [
        'opening' => 'Pembukaan',
        'pahami' => 'Pahami',
        'quick' => 'Quick Question',
        'praktik' => 'Praktik',
        'checkpoint' => 'Checkpoint',
    ];

    /**
     * Get the learning loop steps of a module, in the order mentees see them.
     *
     * @return array<int, ModuleOutlineStepData>
     */
    public function steps(Module $module): array
    {
        $questions = $this->activeQuestions($module);
        $forms = $this->activeForms($module);

        $steps = [
            new ModuleOutlineStepData(
                key: 'opening',
                label: self::STEP_LABELS['opening'],
                purpose: 'Mentee melihat tujuan modul, estimasi waktu, dan hasil yang harus dibuat.',
                present: true,
                items: $this->openingItems($module, $forms),
            ),
            new ModuleOutlineStepData(
                key: 'pahami',
                label: self::STEP_LABELS['pahami'],
                purpose: 'Materi utama yang dibaca mentee sebelum berlatih.',
                present: true,
                items: $this->materialItems($module),
            ),
        ];

        if ($questions->isNotEmpty()) {
            $steps[] = new ModuleOutlineStepData(
                key: 'quick',
                label: self::STEP_LABELS['quick'],
                purpose: 'Pertanyaan Benar/Salah untuk menguji pemahaman setelah membaca materi.',
                present: true,
                items: $questions
                    ->map(fn (QuickQuestion $question): string => Str::limit($question->question, 90))
                    ->values()
                    ->all(),
            );
        }

        if ($forms->isNotEmpty()) {
            $steps[] = new ModuleOutlineStepData(
                key: 'praktik',
                label: self::STEP_LABELS['praktik'],
                purpose: 'Latihan tertulis yang dinilai AI, memakai kata pengantar sebagai konteks.',
                present: true,
                items: $forms
                    ->map(fn (PracticeForm $form): string => $form->title)
                    ->values()
                    ->all(),
            );
        }

        $steps[] = new ModuleOutlineStepData(
            key: 'checkpoint',
            label: self::STEP_LABELS['checkpoint'],
            purpose: 'Mentee mengunci progres modul dan membuka modul berikutnya.',
            present: true,
            items: ['Konfirmasi penyelesaian modul'],
        );

        return $steps;
    }

    /**
     * Get the step labels keyed by step key.
     *
     * This is the hot path of the mentee player, so it only resolves what it
     * needs instead of building the full outline.
     *
     * @return array<string, string>
     */
    public function stepLabels(Module $module): array
    {
        $labels = [
            'opening' => self::STEP_LABELS['opening'],
            'pahami' => self::STEP_LABELS['pahami'],
        ];

        if ($this->activeQuestions($module)->isNotEmpty()) {
            $labels['quick'] = self::STEP_LABELS['quick'];
        }

        if ($this->activeForms($module)->isNotEmpty()) {
            $labels['praktik'] = self::STEP_LABELS['praktik'];
        }

        $labels['checkpoint'] = self::STEP_LABELS['checkpoint'];

        return $labels;
    }

    /**
     * Describe what the opening step shows the mentee.
     *
     * @param  Collection<int, PracticeForm>  $forms
     * @return array<int, string>
     */
    private function openingItems(Module $module, Collection $forms): array
    {
        $items = [];

        $items[] = filled($module->goal)
            ? 'Tujuan: '.$module->goal
            : 'Tujuan modul belum diisi.';

        $items[] = $module->duration
            ? 'Estimasi waktu: '.$module->duration.' menit'
            : 'Estimasi waktu belum diisi.';

        if ($forms->isNotEmpty()) {
            $items[] = 'Output latihan: '.$forms->pluck('title')->implode(', ');
        } else {
            $items[] = 'Belum ada latihan tertulis pada modul ini.';
        }

        return $items;
    }

    /**
     * Describe what the Pahami step shows the mentee.
     *
     * @return array<int, string>
     */
    private function materialItems(Module $module): array
    {
        $items = [];

        if (filled($module->link_video)) {
            $items[] = 'Video materi';
        }

        if (filled($module->summary_material)) {
            $items[] = 'Ringkasan materi';
        }

        $insights = array_filter($module->insights, filled(...));

        if ($insights !== []) {
            $items[] = count($insights).' poin penting';
        }

        if (filled($module->inti_material)) {
            $items[] = 'Inti materi';
        }

        if ($items === []) {
            $items[] = 'Materi belum diisi.';
        }

        return $items;
    }

    /**
     * Get the active quick questions of the module.
     *
     * @return Collection<int, QuickQuestion>
     */
    private function activeQuestions(Module $module): Collection
    {
        $questions = $module->relationLoaded('quickQuestions')
            ? $module->quickQuestions
            : $module->quickQuestions()->get();

        return $questions
            ->filter(fn (QuickQuestion $question): bool => $question->is_active)
            ->sortBy(['ordering', 'id'])
            ->values();
    }

    /**
     * Get the active practice forms of the module.
     *
     * @return Collection<int, PracticeForm>
     */
    private function activeForms(Module $module): Collection
    {
        $forms = $module->relationLoaded('practiceForms')
            ? $module->practiceForms
            : $module->practiceForms()->get();

        return $forms
            ->filter(fn (PracticeForm $form): bool => $form->is_active)
            ->sortBy(['ordering', 'id'])
            ->values();
    }
}
