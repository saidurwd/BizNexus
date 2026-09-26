@extends('layouts.erp')

@section('title', __('Queue Monitor'))

@section('content_header')
    <h1>{{ __('Queue Monitor') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Connection: :connection. Jobs run in the background (emails, integrations, recurring journals) and need a running worker: php artisan queue:work.', ['connection' => $connection]) }}</p>
@endsection

@section('content')
    @if ($usesDatabase)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('Queues') }}</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('Queue') }}</th><th class="text-end">{{ __('Waiting') }}</th><th class="text-end">{{ __('Delayed') }}</th><th class="text-end">{{ __('Running') }}</th><th>{{ __('Oldest waiting since') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($queues as $queue)
                            <tr>
                                <td>{{ $queue->queue }}</td>
                                <td class="text-end">{{ $queue->waiting_count }}</td>
                                <td class="text-end">{{ $queue->delayed_count }}</td>
                                <td class="text-end">{{ $queue->running_count }}</td>
                                <td>{{ $queue->oldest_available_at ? now()->setTimestamp($queue->oldest_available_at)->diffForHumans() : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-3">{{ __('No jobs waiting.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($pendingJobs->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Next jobs') }}</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>{{ __('Job') }}</th><th>{{ __('Queue') }}</th><th>{{ __('State') }}</th><th class="text-end">{{ __('Attempts') }}</th><th>{{ __('Available') }}</th><th>{{ __('Queued') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingJobs as $job)
                                <tr>
                                    <td>{{ $job['id'] }}</td>
                                    <td><code>{{ class_basename($job['name']) }}</code></td>
                                    <td>{{ $job['queue'] }}</td>
                                    <td>{{ __(ucfirst($job['state'])) }}</td>
                                    <td class="text-end">{{ $job['attempts'] }}</td>
                                    <td>{{ $job['available_at']->diffForHumans() }}</td>
                                    <td>{{ $job['created_at']->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-info">{{ __('Waiting jobs are shown for the database queue driver only. Failed jobs are listed below for every driver.') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('Failed jobs') }} <span class="badge text-bg-{{ $failedJobs->isEmpty() ? 'success' : 'danger' }}">{{ $failedJobs->count() }}</span></h3>
            @can('core.system.manage')
                @if ($failedJobs->isNotEmpty())
                    <form method="POST" action="{{ route('core.system.queue.retry-all') }}" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> {{ __('Retry all') }}</button>
                    </form>
                @endif
            @endcan
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Failed at') }}</th><th>{{ __('Job') }}</th><th>{{ __('Queue') }}</th><th>{{ __('Error') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($failedJobs as $job)
                        <tr>
                            <td class="text-nowrap">{{ $job['failed_at'] }}</td>
                            <td><code>{{ class_basename($job['name']) }}</code></td>
                            <td>{{ $job['queue'] }}</td>
                            <td class="text-break"><small>{{ $job['error'] }}</small></td>
                            <td class="text-nowrap">
                                @can('core.system.manage')
                                    <form method="POST" action="{{ route('core.system.queue.retry', $job['uuid']) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Retry') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('core.system.queue.forget', $job['uuid']) }}" class="d-inline" onsubmit="return confirm(@js(__('Discard this failed job?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Discard') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-3">{{ __('No failed jobs.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
