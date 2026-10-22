<?php

use App\Data\BrainstormOptionData;
use App\Enums\BrainstormQuestionType;
use App\Models\Module;
use App\Services\BrainstormingConfigService;

/**
 * Build one raw admin question for the configuration tests.
 *
 * @param  array<int, array<string, mixed>>  $options
 * @return array<string, mixed>
 */
function rawBrainstormingQuestion(array $options = [], string $question = 'Apa peran utamamu?'): array
{
    return [
        'id' => 'q_role',
        'question' => $question,
        'type' => 'single_choice',
        'required' => true,
        'active' => true,
        'hint' => null,
        'options' => $options,
    ];
}

test('normalisation assigns identifiers, order and a stable option value', function () {
    $service = app(BrainstormingConfigService::class);

    $config = $service->normaliseForStorage([
        rawBrainstormingQuestion([
            ['label' => 'Ketua', 'active' => true],
            ['label' => 'Anggota', 'active' => true],
        ]),
        [
            'id' => 'q_moment',
            'question' => 'Momen apa yang paling kamu ingat?',
            'type' => 'short_text',
            'required' => false,
            'active' => true,
            'options' => [],
        ],
    ]);

    expect($config)->toHaveCount(2)
        ->and($config[0]['order'])->toBe(0)
        ->and($config[1]['order'])->toBe(1)
        ->and($config[0]['id'])->toBe('q_role')
        ->and($config[0]['options'][0]['value'])->toBe($config[0]['options'][0]['id'])
        ->and($config[1]['options'])->toBe([]);
});

test('configuration validation rejects empty questions, missing options and duplicate option ids', function () {
    $service = app(BrainstormingConfigService::class);

    expect($service->validationMessages([rawBrainstormingQuestion([], '')]))->not->toBeEmpty()
        ->and($service->validationMessages([rawBrainstormingQuestion([])]))->not->toBeEmpty()
        ->and($service->validationMessages([rawBrainstormingQuestion([
            ['id' => 'o_same', 'label' => 'Ketua', 'active' => true],
            ['id' => 'o_same', 'label' => 'Anggota', 'active' => true],
        ])]))->not->toBeEmpty()
        ->and($service->validationMessages([rawBrainstormingQuestion([
            ['id' => 'o_chair', 'label' => 'Ketua', 'active' => true],
            ['id' => 'o_member', 'label' => 'Anggota', 'active' => true],
        ])]))->toBe([]);
});

test('the module returns only active questions and options', function () {
    $module = Module::factory()->make([
        'brainstorming_questions' => [
            rawBrainstormingQuestion([
                ['id' => 'o_chair', 'label' => 'Ketua', 'active' => true],
                ['id' => 'o_hidden', 'label' => 'Tidak tampil', 'active' => false],
            ]),
            [
                ...rawBrainstormingQuestion([
                    ['id' => 'o_other', 'label' => 'Lainnya', 'active' => true],
                ]),
                'id' => 'q_inactive',
                'question' => 'Pertanyaan nonaktif',
                'active' => false,
            ],
        ],
    ]);

    $questions = app(BrainstormingConfigService::class)->questions($module);

    expect($questions)->toHaveCount(1)
        ->and($questions[0]->type)->toBe(BrainstormQuestionType::SingleChoice)
        ->and($questions[0]->options)->toHaveCount(1)
        ->and($questions[0]->options[0])->toBeInstanceOf(BrainstormOptionData::class)
        ->and($questions[0]->options[0]->label)->toBe('Ketua');
});

test('legacy questions without identifiers get deterministic identifiers', function () {
    $module = Module::factory()->make([
        'brainstorming_questions' => [
            [
                'question' => 'Apa peran utamamu?',
                'type' => 'single_choice',
                'required' => true,
                'active' => true,
                'options' => [
                    ['label' => 'Ketua', 'active' => true],
                    ['label' => 'Anggota', 'active' => true],
                ],
            ],
        ],
    ]);

    $service = app(BrainstormingConfigService::class);
    $first = $service->questions($module);
    $second = $service->questions($module);

    expect($first[0]->id)->toBe($second[0]->id)
        ->and($first[0]->options[0]->id)->toBe($second[0]->options[0]->id)
        ->and($first[0]->id)->toStartWith('q_')
        ->and($first[0]->options[0]->id)->toStartWith('o_');
});

test('answer resolution rejects forged options and returns configured labels', function () {
    $module = Module::factory()->make([
        'brainstorming_questions' => [
            rawBrainstormingQuestion([
                ['id' => 'o_chair', 'label' => 'Ketua', 'active' => true],
                ['id' => 'o_member', 'label' => 'Anggota', 'active' => true],
            ]),
        ],
    ]);

    $service = app(BrainstormingConfigService::class);

    $valid = $service->resolveAnswers($module, ['q_role' => 'o_chair']);
    $forged = $service->resolveAnswers($module, ['q_role' => 'o_forged']);

    expect($valid['errors'])->toBe([])
        ->and($valid['answers'][0]['answer'])->toBe('Ketua')
        ->and($forged['errors'])->toHaveKey('q_role')
        ->and($forged['answers'])->toBe([]);
});
