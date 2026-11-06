<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

test('the app icon renders the same markup as blade-icons with forwarded attributes', function () {
    $html = Blade::render('<x-icons.app-icon name="arrow-right" variant="s" class="size-4" data-test="next" x-show="open" />');

    $expected = svg('heroicon-s-arrow-right', 'shrink-0 size-4', [
        'data-test' => 'next',
        'x-show' => 'open',
    ])->toHtml();

    expect(trim($html))->toBe($expected);
});

test('custom app icons still render their own blade view', function () {
    $html = Blade::render('<x-icons.app-icon name="spinner" class="size-4" />');

    expect($html)->toContain('<svg')
        ->and($html)->toContain('shrink-0')
        ->and($html)->not->toContain('heroicon');
});

test('compiled form components still show request validation errors', function () {
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => ['Email wajib diisi.']]));

    view()->share('errors', $errors);

    $html = Blade::render('<x-forms.input name="email" label="Email" />');

    expect($html)->toContain('Email wajib diisi.');
});
