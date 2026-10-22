<?php

namespace App\Services;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Data\PracticeFieldData;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use Illuminate\Support\Collection;

/**
 * Answers "is this module ready for mentees?" from the stored data only.
 *
 * Every check mirrors something the mentee player actually renders, so the
 * checklist never asks the curriculum team for information the learning
 * experience does not use.
 */
class ModuleReadinessService implements ModuleReadinessServiceInterface
{
    /**
     * Evaluate every completeness requirement of a module.
     *
     * @return array<int, ModuleReadinessCheckData>
     */
    public function checks(Module $module): array
    {
        $practice_forms = $this->practiceForms($module);
        $questions = $this->quickQuestions($module);
        $brainstorming = $module->brainstorming_questions;
        $has_brainstorming = is_array($brainstorming) && $brainstorming !== [];

        $checks = [
            // The fase relation is mandatory at the database level, so the
            // module identity itself is what can still be missing here.
            new ModuleReadinessCheckData(
                key: 'basic_info',
                label: 'Informasi dasar lengkap',
                hint: 'Isi judul modul dan pilih fase tempat modul ini diajarkan.',
                passed: filled($module->title),
            ),
            new ModuleReadinessCheckData(
                key: 'learning_goal',
                label: 'Tujuan pembelajaran dijelaskan',
                hint: 'Tuliskan tujuan modul agar mentee tahu apa yang akan mereka capai.',
                passed: filled($module->goal),
                required: false,
            ),
            new ModuleReadinessCheckData(
                key: 'introduction',
                label: 'Kata pengantar tersedia',
                hint: 'Kata pengantar tampil sebelum quick question dan sebelum latihan praktik.',
                passed: filled($module->introduction),
                required: false,
            ),
            new ModuleReadinessCheckData(
                key: 'material',
                label: 'Materi Pahami tersedia',
                hint: 'Isi minimal salah satu: ringkasan materi, inti materi, atau poin penting.',
                passed: $this->hasMaterial($module),
            ),
            new ModuleReadinessCheckData(
                key: 'quick_questions',
                label: 'Quick Question lengkap',
                hint: 'Setiap pertanyaan harus punya teks pertanyaan dan kunci jawaban.',
                passed: $this->quickQuestionsAreComplete($questions),
                required: $questions->isNotEmpty(),
            ),
            new ModuleReadinessCheckData(
                key: 'practice_forms',
                label: 'Latihan praktik dapat dikerjakan',
                hint: 'Setiap latihan aktif perlu minimal satu pertanyaan, atau nonaktifkan latihan yang belum siap.',
                passed: $this->practiceFormsAreUsable($practice_forms),
                required: false,
            ),
            new ModuleReadinessCheckData(
                key: 'brainstorming',
                label: 'Pertanyaan pemancing valid',
                hint: 'Setiap pertanyaan aktif wajib punya teks, tipe input, dan pilihan jawaban yang sah.',
                passed: app(BrainstormingConfigService::class)->validationMessages($brainstorming) === [],
                required: $has_brainstorming,
            ),
        ];

        if ($module->category === ModuleCategory::Template) {
            $checks[] = new ModuleReadinessCheckData(
                key: 'scholarship',
                label: 'Beasiswa tujuan dipilih',
                hint: 'Modul Template hanya tampil untuk mentee yang memilih beasiswa ini.',
                passed: $module->scholarship_id !== null,
            );
        }

        if ($module->type === ModuleType::Video || filled($module->link_video)) {
            $checks[] = new ModuleReadinessCheckData(
                key: 'video',
                label: 'Link video valid',
                hint: 'Isi link video (embed) yang bisa dibuka mentee.',
                passed: $this->hasValidVideoLink($module),
                required: $module->type === ModuleType::Video,
            );
        }

        return $checks;
    }

    /**
     * Get how complete the module is as a percentage of passed checks.
     */
    public function progress(Module $module): int
    {
        $checks = $this->checks($module);
        $passed = array_filter($checks, fn (ModuleReadinessCheckData $check): bool => $check->passed);

        if ($checks === []) {
            return 100;
        }

        return (int) round(count($passed) / count($checks) * 100);
    }

    /**
     * Determine whether the module satisfies every required check.
     */
    public function isReady(Module $module): bool
    {
        return $this->blockers($module) === [];
    }

    /**
     * Get the required checks that still fail, in checklist order.
     *
     * @return array<int, ModuleReadinessCheckData>
     */
    public function blockers(Module $module): array
    {
        return array_values(array_filter(
            $this->checks($module),
            fn (ModuleReadinessCheckData $check): bool => $check->isBlocking(),
        ));
    }

    /**
     * Determine whether the module has anything to show in the Pahami step.
     */
    private function hasMaterial(Module $module): bool
    {
        return filled($module->summary_material)
            || filled($module->inti_material)
            || array_filter($module->insights, filled(...)) !== [];
    }

    /**
     * Every configured question must be answerable by the mentee.
     *
     * @param  Collection<int, QuickQuestion>  $questions
     */
    private function quickQuestionsAreComplete(Collection $questions): bool
    {
        return $questions->every(fn (QuickQuestion $question): bool => filled($question->question));
    }

    /**
     * Every active exercise must expose at least one field the mentee can fill.
     *
     * @param  Collection<int, PracticeForm>  $forms
     */
    private function practiceFormsAreUsable(Collection $forms): bool
    {
        return $forms
            ->where('is_active', true)
            ->every(fn (PracticeForm $form): bool => PracticeFieldData::collectFromSchema($form->fields ?? []) !== []);
    }

    /**
     * A video module needs a usable link, and a filled link must be valid.
     */
    private function hasValidVideoLink(Module $module): bool
    {
        if (! filled($module->link_video)) {
            return false;
        }

        return filter_var($module->link_video, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get the practice forms of the module without re-querying loaded relations.
     *
     * @return Collection<int, PracticeForm>
     */
    private function practiceForms(Module $module): Collection
    {
        return $module->relationLoaded('practiceForms')
            ? $module->practiceForms
            : $module->practiceForms()->get();
    }

    /**
     * Get the quick questions of the module without re-querying loaded relations.
     *
     * @return Collection<int, QuickQuestion>
     */
    private function quickQuestions(Module $module): Collection
    {
        return $module->relationLoaded('quickQuestions')
            ? $module->quickQuestions
            : $module->quickQuestions()->get();
    }
}
