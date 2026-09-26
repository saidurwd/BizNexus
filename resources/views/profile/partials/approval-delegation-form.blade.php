<p class="text-muted mb-3">{{ __('Let a colleague approve on your behalf in this company for a limited period, for example while you are on leave. Only your approval and rejection permissions are delegated.') }}</p>

@if ($delegationsReceived->isNotEmpty())
    <div class="alert alert-info">
        You are currently approving on behalf of:
        {{ $delegationsReceived->map(fn ($delegation) => $delegation->delegator->name.' (until '.$delegation->ends_on->format('Y-m-d').')')->implode(', ') }}
    </div>
@endif

@if ($delegationsGiven->isNotEmpty())
    <table class="table table-sm">
        <thead><tr><th>{{ __('Delegate') }}</th><th>{{ __('From') }}</th><th>{{ __('Until') }}</th><th>{{ __('Reason') }}</th><th></th></tr></thead>
        <tbody>
            @foreach ($delegationsGiven as $delegation)
                <tr>
                    <td>{{ $delegation->delegate->name }}</td>
                    <td>{{ $delegation->starts_on->format('Y-m-d') }}</td>
                    <td>{{ $delegation->ends_on->format('Y-m-d') }}</td>
                    <td>{{ $delegation->reason }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('core.approval-delegations.destroy', $delegation->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Revoke') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($canDelegateApprovals)
    <form method="POST" action="{{ route('core.approval-delegations.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="delegate_id" class="form-label">{{ __('Delegate') }}</label>
                <select id="delegate_id" name="delegate_id" class="form-control" required>
                    <option value="">{{ __('Select a colleague') }}</option>
                    @foreach ($colleagues as $colleague)
                        <option value="{{ $colleague->id }}" @selected(old('delegate_id') == $colleague->id)>{{ $colleague->name }} ({{ $colleague->email }})</option>
                    @endforeach
                </select>
                @error('delegate_id')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-2 mb-3">
                <label for="starts_on" class="form-label">{{ __('From') }}</label>
                <input type="date" id="starts_on" name="starts_on" class="form-control" value="{{ old('starts_on', now()->toDateString()) }}" required>
                @error('starts_on')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-2 mb-3">
                <label for="ends_on" class="form-label">{{ __('Until') }}</label>
                <input type="date" id="ends_on" name="ends_on" class="form-control" value="{{ old('ends_on') }}" required>
                @error('ends_on')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="reason" class="form-label">{{ __('Reason') }}</label>
                <input type="text" id="reason" name="reason" class="form-control" value="{{ old('reason') }}" maxlength="255">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Delegate approvals') }}</button>
    </form>
@elseif ($delegationsGiven->isEmpty())
    <p class="mb-0">{{ __('You have no approval permissions to delegate in this company.') }}</p>
@endif
