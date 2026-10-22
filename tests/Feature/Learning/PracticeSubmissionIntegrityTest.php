<?php

use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;

test('submitting the same practice form twice keeps one row and increments attempts', function () {
    $user = User::factory()->profiled()->create();
    $form = PracticeForm::factory()->create([
        'module_id' => Module::factory(),
    ]);

    $feedback = new class implements AiFeedbackServiceInterface
    {
        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            return new AiFeedbackData(
                score: 80,
                strengths: ['Jawaban jelas.'],
                improvements: [],
                feedback: 'Sudah baik.',
                next_steps: [],
            );
        }
    };

    app()->instance(AiFeedbackServiceInterface::class, $feedback);

    $service = app(PracticeSubmissionServiceInterface::class);

    $first = $service->submit($user, $form, ['jawaban' => 'Jawaban pertama.']);
    $second = $service->submit($user, $form, ['jawaban' => 'Jawaban kedua.']);

    expect(UserPracticeResponse::query()
        ->where('user_id', $user->id)
        ->where('practice_form_id', $form->id)
        ->count())->toBe(1)
        ->and($first->id)->toBe($second->id)
        ->and($second->fresh()->attempts)->toBe(2)
        ->and($second->fresh()->answers['jawaban'])->toBe('Jawaban kedua.');
});
