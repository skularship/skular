<?php

/*
|--------------------------------------------------------------------------
| Curriculum content styling
|--------------------------------------------------------------------------
|
| The only text colors, font families and font sizes an admin may apply to Inti Materi
| in the rich editor. The same list drives the Filament editor, the font
| family plugin, the HTML sanitizer (App\Services\RichContentRenderer) and
| the tests, so a value outside this list is never rendered to mentees.
|
| Colors keep WCAG AA contrast on white. Keys are stored in the HTML
| (data-color / data-font / data-size), so do not rename a key once it is in use.
| The first font and the "normal" size are the defaults: choosing them in
| the editor removes the style instead of storing it. Sizes are relative
| (em) so they follow the 17px body text on every screen.
| Color keys must not equal a Filament palette name (red, teal, ...):
| Filament would store its own shade for such a key.
|
*/

return [

    'material' => [

        'text_colors' => [
            'hitam' => ['label' => 'Hitam', 'color' => '#1b1b18'],
            'abu' => ['label' => 'Abu', 'color' => '#3f3f46'],
            'teal-brand' => ['label' => 'Teal', 'color' => '#277c93'],
            'teal-tua' => ['label' => 'Teal tua', 'color' => '#1c5f73'],
            'oranye-tua' => ['label' => 'Oranye tua', 'color' => '#b45309'],
            'merah' => ['label' => 'Merah', 'color' => '#b91c1c'],
            'biru' => ['label' => 'Biru', 'color' => '#1d4ed8'],
            'hijau' => ['label' => 'Hijau', 'color' => '#15803d'],
            'ungu' => ['label' => 'Ungu', 'color' => '#6d28d9'],
        ],

        'fonts' => [
            'rubik' => ['label' => 'Rubik', 'family' => 'Rubik', 'stack' => "'Rubik', ui-sans-serif, system-ui, sans-serif"],
            'plus-jakarta-sans' => ['label' => 'Plus Jakarta Sans', 'family' => 'Plus Jakarta Sans', 'stack' => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"],
            'nunito' => ['label' => 'Nunito', 'family' => 'Nunito', 'stack' => "'Nunito', ui-sans-serif, system-ui, sans-serif"],
            'poppins' => ['label' => 'Poppins', 'family' => 'Poppins', 'stack' => "'Poppins', ui-sans-serif, system-ui, sans-serif"],
            'lora' => ['label' => 'Lora', 'family' => 'Lora', 'stack' => "'Lora', ui-serif, Georgia, serif"],
            'merriweather' => ['label' => 'Merriweather', 'family' => 'Merriweather', 'stack' => "'Merriweather', ui-serif, Georgia, serif"],
        ],

        'font_sizes' => [
            'kecil' => ['label' => 'Kecil', 'size' => '0.875em'],
            'normal' => ['label' => 'Normal', 'size' => '1em'],
            'besar' => ['label' => 'Besar', 'size' => '1.25em'],
            'sangat-besar' => ['label' => 'Sangat besar', 'size' => '1.5em'],
        ],

    ],

];
