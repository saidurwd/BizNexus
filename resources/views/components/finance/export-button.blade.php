@props(['report'])

@can('finance.reports.export')
    <a href="{{ route('finance.reports.export', ['report' => $report, ...request()->query()]) }}" {{ $attributes->merge(['class' => 'btn btn-outline-success']) }}>
        <i class="bi bi-file-earmark-excel"></i> {{ __('Export to Excel') }}
    </a>
@endcan
