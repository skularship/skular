<?php

use App\Http\Controllers\QueueHealthController;
use App\Jobs\EvaluatePracticeFeedback;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['queue.default' => 'database']);
});

/**
 * Put one pending job on the database queue, made available $seconds_ago.
 */
function pendingJob(int $seconds_ago): void
{
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->subSeconds($seconds_ago)->getTimestamp(),
        'created_at' => now()->subSeconds($seconds_ago)->getTimestamp(),
    ]);
}

test('the queue probe is healthy when no job is waiting', function () {
    $this->get(route('health.queue'))
        ->assertOk()
        ->assertJson(['status' => 'ok', 'checks' => ['queue' => 'ok'], 'oldest_pending_seconds' => 0]);
});

test('the queue probe is healthy while jobs are picked up in time', function () {
    pendingJob(10);

    $this->get(route('health.queue'))->assertOk()->assertJsonPath('checks.queue', 'ok');
});

test('the queue probe fails when a job waits longer than the stall window', function () {
    pendingJob(QueueHealthController::STALLED_AFTER_SECONDS + 30);

    $this->get(route('health.queue'))
        ->assertStatus(503)
        ->assertJsonPath('checks.queue', 'stalled')
        ->assertJsonMissingPath('payload')
        ->assertJsonMissingPath('exception');
});

test('the queue probe skips queues it cannot inspect', function () {
    config(['queue.default' => 'sync']);

    $this->get(route('health.queue'))->assertOk()->assertJsonPath('checks.queue', 'not_monitored');
});

test('ai evaluations stay on the default queue unless a dedicated queue is configured', function () {
    expect((new EvaluatePracticeFeedback(1, 1, 1))->queue)->toBeNull();

    config(['queue.ai_feedback_queue' => 'ai']);

    expect((new EvaluatePracticeFeedback(1, 1, 1))->queue)->toBe('ai');
});
