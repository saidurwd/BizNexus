@extends('layouts.erp')

@section('title', 'Consolidation')

@section('content_header')
    <h1>Consolidation</h1>
    <p class="text-muted mb-0">Groups owned by this company. Reports are presented in this company's functional currency.</p>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Group</th><th>Members (ownership)</th><th></th></tr></thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td>{{ $group->name }}</td>
                            <td>{{ $group->members->map(fn ($member) => $member->code.' ('.rtrim(rtrim($member->pivot->ownership_percent, '0'), '.').'%)')->implode(', ') }}</td>
                            <td class="text-right"><a href="{{ route('finance.consolidation.show', $group->id) }}" class="btn btn-sm btn-primary">Consolidated trial balance</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No consolidation groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('finance.consolidation.manage')
        <div class="card">
            <div class="card-header"><h3 class="card-title">New group (this company is the parent)</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.consolidation.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name">Group name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <table class="table table-sm">
                        <thead><tr><th>Include</th><th>Company</th><th style="width: 180px">Ownership %</th></tr></thead>
                        <tbody>
                            @foreach ($companies as $index => $company)
                                <tr>
                                    <td><input type="checkbox" name="members[{{ $index }}][company_id]" value="{{ $company->id }}"></td>
                                    <td>{{ $company->code }} — {{ $company->name }}</td>
                                    <td><input type="number" class="form-control form-control-sm" name="members[{{ $index }}][ownership_percent]" value="100" min="0" max="100" step="0.0001"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @error('members')<div class="text-danger mb-2">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary">Create group</button>
                </form>
            </div>
        </div>
    @endcan
@endsection
