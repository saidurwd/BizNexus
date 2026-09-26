<?php

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\Company;
use Modules\Core\Models\ScheduledTaskRun;
use Modules\Core\Support\SystemSchedule;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = companyUser(['core.system.view', 'core.system.manage'], $this->company);
});

function failedJob(string $uuid): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid, 'connection' => 'database', 'queue' => 'default',
        'payload' => json_encode(['displayName' => 'Modules\\Finance\\Jobs\\SendNotificationJob', 'job' => 'x', 'data' => []]),
        'exception' => "RuntimeException: SMTP connection refused\n#0 trace", 'failed_at' => now(),
    ]);
}

test('the system screens render for administrators', function () {
    failedJob((string) Str::uuid());

    actingInCompany($this->admin, $this->company)->get(route('core.system.health'))->assertOk()->assertSee('Database')->assertSee('PHP extensions');
    actingInCompany($this->admin, $this->company)->get(route('core.system.queue'))->assertOk()->assertSee('SendNotificationJob')->assertSee('SMTP connection refused');
    actingInCompany($this->admin, $this->company)->get(route('core.system.schedule'))->assertOk()->assertSee(SystemSchedule::RECURRING_JOURNALS)->assertSee(SystemSchedule::VERIFY_AUDIT_LOG);
    actingInCompany($this->admin, $this->company)->get(route('core.system.about'))->assertOk()->assertSee('laravel/framework')->assertSee('Finance');
});

test('failed jobs can be discarded by someone allowed to manage the system', function () {
    $uuid = (string) Str::uuid();
    failedJob($uuid);

    actingInCompany(companyUser(['core.system.view'], $this->company), $this->company)
        ->delete(route('core.system.queue.forget', $uuid))
        ->assertForbidden();

    actingInCompany($this->admin, $this->company)->delete(route('core.system.queue.forget', $uuid))->assertRedirect();

    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

test('scheduled task runs are recorded with their outcome', function () {
    $schedule = new Schedule;
    $task = $schedule->call(fn () => null)->name('Nightly check');

    event(new ScheduledTaskStarting($task));
    event(new ScheduledTaskFinished($task, 1.5));

    $failing = $schedule->call(fn () => null)->name('Broken task');
    event(new ScheduledTaskStarting($failing));
    event(new ScheduledTaskFailed($failing, new RuntimeException('Disk full')));
    event(new ScheduledTaskFinished($failing, 0.1));

    expect(ScheduledTaskRun::where('task', 'Nightly check')->sole()->status)->toBe(ScheduledTaskRun::STATUS_SUCCEEDED)
        ->and(ScheduledTaskRun::where('task', 'Broken task')->sole())
        ->status->toBe(ScheduledTaskRun::STATUS_FAILED)
        ->output->toBe('Disk full');
});
