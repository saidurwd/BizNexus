@extends('layouts.erp')

@section('title', __('Scheduled Jobs'))

@php $runColour = ['succeeded' => 'success', 'failed' => 'danger', 'running' => 'info', 'skipped' => 'secondary']; @endphp

@section('content_header')
    <h1>{{ __('Scheduled Jobs') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('These run when cron calls "php artisan schedule:run" every minute.') }}</p>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Task') }}</th><th>{{ __('Schedule') }}</th><th>{{ __('Next run') }}</th><th>{{ __('Last run') }}</th><th>{{ __('Result') }}</th><th class="text-end">{{ __('Duration') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $task)
                        <tr>
                            <td>
                                {{ $task['name'] }}
                                @if ($task['without_overlapping'])
                                    <span class="badge text-bg-light border" title="{{ __('A run does not start while the previous one is still running') }}">{{ __('no overlap') }}</span>
                                @endif
                            </td>
                            <td><code>{{ $task['expression'] }}</code> <small class="text-body-secondary">{{ $task['timezone'] }}</small></td>
                            <td class="text-nowrap">{{ $task['next_due']->format('Y-m-d H:i') }} <small class="text-body-secondary">({{ $task['next_due']->diffForHumans() }})</small></td>
                            <td class="text-nowrap">{{ $task['last_run']?->started_at->format('Y-m-d H:i') ?? __('Never') }}</td>
                            <td>
                                @if ($task['last_run'])
                                    <span class="badge text-bg-{{ $runColour[$task['last_run']->status] ?? 'secondary' }}">{{ __(ucfirst($task['last_run']->status)) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $task['last_run']?->duration_ms !== null ? Formatter::number($task['last_run']->duration_ms / 1000, 1).' s' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Recent runs') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Started') }}</th><th>{{ __('Task') }}</th><th>{{ __('Result') }}</th><th class="text-end">{{ __('Duration') }}</th><th>{{ __('Output') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentRuns as $run)
                        <tr>
                            <td class="text-nowrap">{{ $run->started_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $run->task }}</td>
                            <td><span class="badge text-bg-{{ $runColour[$run->status] ?? 'secondary' }}">{{ __(ucfirst($run->status)) }}</span></td>
                            <td class="text-end">{{ $run->duration_ms !== null ? Formatter::number($run->duration_ms / 1000, 1).' s' : '—' }}</td>
                            <td class="text-break"><small>{{ $run->output }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-3">{{ __('No runs recorded yet. Is cron calling schedule:run?') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
