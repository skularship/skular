<?php

/*
|--------------------------------------------------------------------------
| Curriculum content styling
|--------------------------------------------------------------------------
|
| The only text colors and font families an admin may apply to Inti Materi
| in the rich editor. The same list drives the Filament editor, the font
| family plugin, the HTML sanitizer (App\Services\RichContentRenderer) and
| the tests, so a value outside this list is never rendered to mentees.
|
| Colors keep WCAG AA contrast on white. Keys are stored in the HTML
| (data-color / data-font), so do not rename a key once it is in use.
|
*/

return [

    'material' => [

        'text_colors' => [
            'hitam' => ['label' => 'Hitam', 'color' => '#1b1b18'],
            'abu' => ['label' => 'Abu', 'color' => '#3f3f46'],
            'teal' => ['label' => 'Teal', 'color' => '#277c93'],
            'teal-tua' => ['label' => 'Teal tua', 'color' => '#1c5f73'],
            'oranye-tua' => ['label' => 'Oranye tua', 'color' => '#b45309'],
            'merah' => ['label' => 'Merah', 'color' => '#b91c1c'],
        ],

        'fonts' => [
            'rubik' => ['label' => 'Rubik', 'family' => 'Rubik', 'stack' => "'Rubik', ui-sans-serif, system-ui, sans-serif"],
            'plus-jakarta-sans' => ['label' => 'Plus Jakarta Sans', 'family' => 'Plus Jakarta Sans', 'stack' => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"],
            'nunito' => ['label' => 'Nunito', 'family' => 'Nunito', 'stack' => "'Nunito', ui-sans-serif, system-ui, sans-serif"],
            'lora' => ['label' => 'Lora', 'family' => 'Lora', 'stack' => "'Lora', ui-serif, Georgia, serif"],
        ],

    ],

];
