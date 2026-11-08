<?php

namespace Database\Factories;

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
            'ordering' => 0,
        ];
    }
}
