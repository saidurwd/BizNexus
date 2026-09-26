@extends('layouts.erp')

@use('Modules\Assets\Models\AssetCategory')
@use('Modules\Assets\Models\FixedAsset')

@php
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
    $inUse = $asset->status !== FixedAsset::STATUS_DISPOSED;
@endphp

@section('title', $asset->asset_number.' '.$asset->name)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ $asset->name }} <small class="text-body-secondary">{{ $asset->asset_number }}</small>
            <span class="badge fs-6 text-bg-{{ ['ACTIVE' => 'primary', 'FULLY_DEPRECIATED' => 'secondary', 'DISPOSED' => 'dark'][$asset->status] ?? 'light' }}">{{ FixedAsset::statusLabels()[$asset->status] ?? $asset->status }}</span>
        </h1>
        @can('assets.manage')
            <div class="d-flex gap-2">
                <a href="{{ route('assets.assets.edit', $asset->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
                @if ($asset->transactions->whereNotNull('journal_id')->isEmpty() && $asset->transactions->whereIn('type', ['depreciation', 'impairment', 'disposal'])->isEmpty())
                    <form method="POST" action="{{ route('assets.assets.destroy', $asset->id) }}" onsubmit="return confirm(@js(__('Delete this asset?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                    </form>
                @endif
            </div>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Category') }}</dt>
                        <dd class="col-sm-8">{{ $asset->category?->name }}</dd>
                        <dt class="col-sm-4">{{ __('Serial number') }} / {{ __('Asset tag') }}</dt>
                        <dd class="col-sm-8">{{ $asset->serial_number ?? '—' }} / {{ $asset->tag ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Location') }}</dt>
                        <dd class="col-sm-8">{{ collect([$asset->branch?->name, $asset->department?->name, $asset->location])->filter()->implode(' · ') ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Custodian') }}</dt>
                        <dd class="col-sm-8">{{ $asset->custodian ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Supplier') }}</dt>
                        <dd class="col-sm-8">{{ $asset->supplier?->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Acquired') }} / {{ __('In service from') }}</dt>
                        <dd class="col-sm-8">{{ Formatter::date($asset->acquisition_date) }} / {{ Formatter::date($asset->in_service_date) }}</dd>
                        <dt class="col-sm-4">{{ __('Depreciation') }}</dt>
                        <dd class="col-sm-8">
                            {{ AssetCategory::methodLabels()[$asset->depreciation_method] ?? $asset->depreciation_method }}
                            · {{ trans_choice(':count month|:count months', $asset->useful_life_months, ['count' => $asset->useful_life_months]) }}
                            @if ($asset->declining_rate) · {{ Formatter::percent($asset->declining_rate) }}%@endif
                        </dd>
                        @if ($asset->description)
                            <dt class="col-sm-4">{{ __('Description') }}</dt>
                            <dd class="col-sm-8">{!! nl2br(e($asset->description)) !!}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Cost') }}</th><td class="text-end">{{ Formatter::amount($asset->cost) }}</td></tr>
                    <tr><th>{{ __('Accumulated depreciation') }}</th><td class="text-end">{{ Formatter::amount($asset->accumulated_depreciation) }}</td></tr>
                    <tr class="fw-bold"><th>{{ __('Carrying amount') }}</th><td class="text-end">{{ Formatter::amount($asset->bookValue()) }}</td></tr>
                    <tr><th>{{ __('Residual value') }}</th><td class="text-end">{{ Formatter::amount($asset->residual_value) }}</td></tr>
                    <tr><th>{{ __('Depreciated up to') }}</th><td class="text-end">{{ $asset->depreciated_until ? $asset->depreciated_until->translatedFormat('M Y') : '—' }}</td></tr>
                    <tr><th>{{ __('Remaining life') }}</th><td class="text-end">{{ trans_choice(':count month|:count months', $asset->remainingLifeMonths(), ['count' => $asset->remainingLifeMonths()]) }}</td></tr>
                    @if ($asset->status === FixedAsset::STATUS_ACTIVE)
                        <tr><th>{{ __('Next month\'s depreciation') }}</th><td class="text-end">{{ Formatter::amount($asset->nextMonthDepreciation()) }}</td></tr>
                    @endif
                    @if ($asset->disposed_on)
                        <tr><th>{{ __('Disposed of') }}</th><td class="text-end">{{ Formatter::date($asset->disposed_on) }} · {{ Formatter::amount($asset->disposal_proceeds) }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if ($inUse)
        <div class="d-flex flex-wrap gap-2 mb-3">
            @can('assets.manage')
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#transfer-form"><i class="bi bi-arrow-left-right"></i> {{ __('Transfer') }}</button>
            @endcan
            @can('assets.dispose')
                @if ($asset->status === FixedAsset::STATUS_ACTIVE)
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#impair-form"><i class="bi bi-graph-down-arrow"></i> {{ __('Impair') }}</button>
                @endif
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#dispose-form"><i class="bi bi-box-arrow-right"></i> {{ __('Sell or scrap') }}</button>
            @endcan
        </div>

        @can('assets.manage')
            <form method="POST" action="{{ route('assets.assets.transfer', $asset->id) }}" class="card mb-3 collapse" id="transfer-form">
                @csrf
                <div class="card-header"><h3 class="card-title">{{ __('Transfer') }}</h3></div>
                <div class="card-body row g-2 align-items-end">
                    <div class="col-md-2"><label class="form-label">{{ __('Date') }}</label><input type="date" name="transfer_date" value="{{ $today }}" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">{{ __('Branch') }}</label>
                        <select name="branch_id" class="form-select"><option value="">—</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected($asset->branch_id === $branch->id)>{{ $branch->name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2"><label class="form-label">{{ __('Department') }}</label>
                        <select name="department_id" class="form-select"><option value="">—</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected($asset->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2"><label class="form-label">{{ __('Location') }}</label><input type="text" name="location" value="{{ $asset->location }}" class="form-control" maxlength="255"></div>
                    <div class="col-md-2"><label class="form-label">{{ __('Custodian') }}</label><input type="text" name="custodian" value="{{ $asset->custodian }}" class="form-control" maxlength="255"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">{{ __('Transfer') }}</button></div>
                </div>
            </form>
        @endcan

        @can('assets.dispose')
            <form method="POST" action="{{ route('assets.assets.impair', $asset->id) }}" class="card mb-3 collapse" id="impair-form" onsubmit="return confirm(@js(__('Post this impairment?')))">
                @csrf
                <div class="card-header"><h3 class="card-title">{{ __('Impairment') }}</h3></div>
                <div class="card-body row g-2 align-items-end">
                    <div class="col-12 form-text">{{ __('Write the asset down when its recoverable amount has fallen below its carrying amount (IAS 36). Later depreciation spreads the lower amount over the remaining life.') }}</div>
                    <div class="col-md-2"><label class="form-label">{{ __('Date') }}</label><input type="date" name="impairment_date" value="{{ $today }}" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">{{ __('Impairment loss') }}</label><input type="number" name="amount" step="any" min="0" max="{{ (float) $asset->depreciableAmount() }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Reason') }}</label><input type="text" name="reason" class="form-control" maxlength="1000"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-warning w-100">{{ __('Post') }}</button></div>
                </div>
            </form>

            <form method="POST" action="{{ route('assets.assets.dispose', $asset->id) }}" class="card mb-3 collapse" id="dispose-form" onsubmit="return confirm(@js(__('Dispose of this asset? The disposal is posted at once.')))">
                @csrf
                <div class="card-header"><h3 class="card-title">{{ __('Sell or scrap') }}</h3></div>
                <div class="card-body row g-2 align-items-end">
                    <div class="col-12 form-text">{{ __('Depreciation still owed up to the end of the previous month is charged first; the difference between the proceeds and the carrying amount is posted as a gain or loss.') }}</div>
                    <div class="col-md-2"><label class="form-label">{{ __('Date') }}</label><input type="date" name="disposal_date" value="{{ $today }}" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">{{ __('Proceeds') }}</label><input type="number" name="proceeds" step="any" min="0" value="0" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">{{ __('Proceeds received into') }}</label>
                        <select name="proceeds_account_id" class="form-select" data-searchable><option value="">{{ __('Not sold (scrapped)') }}</option>@foreach ($proceedsAccounts as $account)<option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2"><label class="form-label">{{ __('Notes') }}</label><input type="text" name="notes" class="form-control" maxlength="1000"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-danger w-100">{{ __('Dispose') }}</button></div>
                </div>
            </form>
        @endcan
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('History') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>{{ __('Date') }}</th><th>{{ __('Event') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Journal') }}</th><th>{{ __('Notes') }}</th></tr></thead>
                <tbody>
                    @foreach ($asset->transactions as $transaction)
                        <tr>
                            <td>{{ Formatter::date($transaction->transaction_date) }}</td>
                            <td>{{ $transaction->typeLabel() }}</td>
                            <td class="text-end">{{ $transaction->type === 'transfer' ? '—' : Formatter::amount($transaction->amount) }}</td>
                            <td>@if ($transaction->journal)<a href="{{ route('finance.journals.show', $transaction->journal_id) }}">{{ $transaction->journal->journal_number }}</a>@else — @endif</td>
                            <td class="small">
                                {{ $transaction->notes }}
                                @if ($transaction->type === 'disposal' && isset($transaction->details['gain']))
                                    {{ bccomp((string) $transaction->details['gain'], '0', 4) >= 0 ? __('Gain :amount', ['amount' => Formatter::amount($transaction->details['gain'])]) : __('Loss :amount', ['amount' => Formatter::amount(ltrim((string) $transaction->details['gain'], '-'))]) }}
                                @endif
                                @if ($transaction->type === 'transfer')
                                    {{ collect($transaction->details['to'] ?? [])->filter()->map(fn ($value, $key) => in_array($key, ['branch_id', 'department_id'], true) ? null : $value)->filter()->implode(' · ') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
