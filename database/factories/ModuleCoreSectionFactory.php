<?php

namespace Database\Factories;

use App\Enums\MaterialFormat;
use App\Models\Module;
use App\Models\ModuleCoreSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleCoreSection>
 */
class ModuleCoreSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'subtitle' => fake()->randomElement([
                'Kenali dulu apa yang dinilai',
                'Susun kerangkanya',
                'Contoh yang bisa kamu tiru',
            ]),
            'body' => fake()->paragraphs(2, true),
            'body_format' => MaterialFormat::Markdown,
            'ordering' => 0,
        ];
    }

    /**
     * A column written in the admin rich editor (HTML body).
     *
     * @return $this
     */
    public function html(): static
    {
        return $this->state(fn (): array => [
            'body' => '<h2>Poin utama</h2><p><strong>Label:</strong> '.fake()->sentence().'</p>',
            'body_format' => MaterialFormat::Html,
        ]);
    }
}
