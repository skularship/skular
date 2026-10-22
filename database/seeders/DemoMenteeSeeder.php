<?php

namespace Database\Seeders;

use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Enums\CheckpointStatus;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\Checkpoint;
use App\Models\Cluster;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoMenteeSeeder extends Seeder
{
    /**
     * Seed demo mentees that showcase each stage of the Skular journey.
     */
    public function run(): void
    {
        $technology = Cluster::query()->where('name', 'teknologi')->first();
        $business = Cluster::query()->where('name', 'bisnis')->first();

        // Fresh mentee: has never completed profiling.
        User::query()->updateOrCreate(
            ['email' => 'mentee.baru@skular.test'],
            ['name' => 'Rani Mentee Baru', 'password' => 'password', 'email_verified_at' => now()],
        );

        // Profiled mentee with an active roadmap in progress.
        $in_progress = User::query()->updateOrCreate(
            ['email' => 'mentee.progress@skular.test'],
            [
                'name' => 'Bagas Sedang Belajar',
                'password' => 'password',
                'email_verified_at' => now(),
                'level' => EducationLevel::S1,
                'age' => 21,
                'gpa' => 3.75,
                'has_sktm' => false,
                'desired_level' => EducationLevel::S2,
                'desired_location' => LocationScope::LuarNegeri,
                'cluster_id' => $technology?->id,
                'phone' => '081234567891',
                'profile_completed_at' => now(),
            ],
        );

        $this->seedProgress($in_progress, completed_modules: 3);

        // Mentee who already reported an accepted result (twibbon demo).
        $accepted = User::query()->updateOrCreate(
            ['email' => 'mentee.lulus@skular.test'],
            [
                'name' => 'Sinta Lulus Beasiswa',
                'password' => 'password',
                'email_verified_at' => now(),
                'level' => EducationLevel::S1,
                'age' => 23,
                'gpa' => 3.90,
                'has_sktm' => true,
                'desired_level' => EducationLevel::S1,
                'desired_location' => LocationScope::DalamNegeri,
                'cluster_id' => $business?->id,
                'phone' => '081234567892',
                'profile_completed_at' => now(),
            ],
        );

        $this->seedProgress($accepted, completed_modules: 6);

        $accepted_scholarship = Scholarship::query()->where('status', 'active')->first();

        if ($accepted_scholarship !== null) {
            ScholarshipApplication::query()->updateOrCreate(
                ['user_id' => $accepted->id, 'scholarship_id' => $accepted_scholarship->id],
                [
                    'status' => ApplicationStatus::ResultReported,
                    'is_primary' => true,
                    'selected_at' => now()->subDays(40),
                    'submitted_at' => now()->subDays(20),
                    'submission_proof' => null,
                    'submission_confirmed_at' => now()->subDays(20),
                    'result' => ApplicationResult::Accepted,
                    'result_reported_at' => now()->subDays(2),
                ],
            );
        }
    }

    /**
     * Mark a number of modules as completed for the given mentee.
     */
    private function seedProgress(User $user, int $completed_modules): void
    {
        $modules = Module::query()
            ->with('fase')
            ->orderBy('fase_id')
            ->orderBy('modul_order')
            ->get();

        foreach ($modules as $index => $module) {
            $is_completed = $index < $completed_modules;

            Checkpoint::query()->updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $module->id],
                [
                    'fase_id' => $module->fase_id,
                    'status' => $is_completed ? CheckpointStatus::Completed : CheckpointStatus::Available,
                    'score' => $is_completed ? 80 + ($index % 15) : null,
                    'attempts' => $is_completed ? 1 : 0,
                    'available_at' => now(),
                    'completed_at' => $is_completed ? now()->subDays($completed_modules - $index) : null,
                ],
            );
        }
    }
}
