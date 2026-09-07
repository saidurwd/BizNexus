@extends('adminlte::page')

@section('title', 'Recurring Journals')

@section('content_header')
    <h1>Recurring Journals</h1>
    <div class="mt-2">
        <a href="{{ route('finance.recurring-journals.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Recurring Journal
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Frequency</th>
                        <th>Next Run Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recurringJournals as $journal)
                        <tr>
                            <td>{{ $journal->name }}</td>
                            <td>{{ $journal->frequency }}</td>
                            <td>{{ $journal->next_run_date->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge bg-{{ $journal->status === 'active' ? 'success' : ($journal->status === 'paused' ? 'warning' : 'secondary') }}">
                                    {{ $journal->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.recurring-journals.edit', $journal->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.recurring-journals.destroy', $journal->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($journal->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No recurring journals found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
