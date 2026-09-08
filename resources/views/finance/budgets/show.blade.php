@extends('layouts.erp')

@section('title', 'Budget Details')

@section('content_header')
    <h1>Budget: {{ $budget->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Budget Information</h3>
            <div class="card-tools">
                @if($budget->isDraft())
                    <a href="{{ route('finance.budgets.edit', $budget->id) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('finance.budgets.destroy', $budget->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Name</th>
                    <td>{{ $budget->name }}</td>
                </tr>
                <tr>
                    <th>Fiscal Year</th>
                    <td>{{ $budget->fiscalYear?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @php
                            $badgeClass = 'secondary';
                            if ($budget->status === 'APPROVED') $badgeClass = 'success';
                            elseif ($budget->status === 'SUBMITTED') $badgeClass = 'info';
                            elseif ($budget->status === 'REJECTED') $badgeClass = 'danger';
                            elseif ($budget->status === 'ACTIVE') $badgeClass = 'primary';
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">
                            {{ $budget->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $budget->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ $budget->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Budget Lines</h3>
        @if($budget->isDraft())
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addLineModal">
                <i class="bi bi-plus-circle"></i> Add Line
            </button>
        @endif
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Cost Center</th>
                    <th>Period</th>
                    <th class="text-right">Budget Amount</th>
                    @if($budget->isDraft())
                        <th class="text-center">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($budget->lines as $line)
                    <tr>
                        <td>{{ $line->account?->account_code ?? '-' }} - {{ $line->account?->account_name ?? '-' }}</td>
                        <td>{{ $line->costCenter?->name ?? '-' }}</td>
                        <td>{{ $line->period }}</td>
                        <td class="text-right">{{ number_format($line->budget_amount, 2) }}</td>
                        @if($budget->isDraft())
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning edit-line-btn"
                                    data-budget_id="{{ $budget->id }}"
                                    data-id="{{ $line->id }}"
                                    data-account_id="{{ $line->account_id }}"
                                    data-cost_center_id="{{ $line->cost_center_id }}"
                                    data-period="{{ $line->period }}"
                                    data-budget_amount="{{ $line->budget_amount }}"
                                    data-toggle="modal" data-target="#editLineModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('finance.budgets.lines.destroy', [$budget->id, $line->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this line?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $budget->isDraft() ? 5 : 4 }}" class="text-center">No budget lines found</td>
                    </tr>
                @endforelse
            </tbody>
            @if($budget->lines->isNotEmpty())
                <tfoot>
                    <tr class="table-active">
                        <td colspan="3" class="text-right font-weight-bold">Total:</td>
                        <td class="text-right font-weight-bold">{{ number_format($budget->lines->sum('budget_amount'), 2) }}</td>
                        @if($budget->isDraft())
                            <td></td>
                        @endif
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    </div>

    <div class="mt-4 mb-3">
        <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary">Back</a>

        @if($budget->isDraft())
            <form action="{{ route('finance.budgets.submit', $budget->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Submit this budget for approval?')">
                    <i class="bi bi-send"></i> Submit for Approval
                </button>
            </form>
        @endif

        @if($budget->status === \Modules\Finance\Models\Budget::STATUS_SUBMITTED)
            <form action="{{ route('finance.budgets.approve', $budget->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this budget?')">
                    <i class="bi bi-check-circle"></i> Approve
                </button>
            </form>
            <form action="{{ route('finance.budgets.reject', $budget->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this budget?')">
                    <i class="bi bi-x-circle"></i> Reject
                </button>
            </form>
        @endif
    </div>

    @if($budget->isDraft())
        <div class="modal fade" id="addLineModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form action="{{ route('finance.budgets.lines.store', $budget->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Budget Line</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Account</label>
                            <select class="form-control" name="account_id" required>
                                <option value="">Select Account</option>
                                @php
                                    $expenseAccounts = \Modules\Finance\Models\Account::where('company_id', $budget->company_id)
                                        ->where('account_type', 'EXPENSE')
                                        ->orderBy('account_code')
                                        ->get(['id', 'account_code', 'account_name']);
                                @endphp
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cost Center</label>
                            <select class="form-control" name="cost_center_id">
                                <option value="">Select Cost Center</option>
                                @php
                                    $costCenters = \Modules\Core\Models\CostCenter::where('company_id', $budget->company_id)->get(['id', 'name']);
                                @endphp
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Period (1-12)</label>
                            <input type="number" class="form-control" name="period" min="1" max="12" required>
                        </div>
                        <div class="form-group">
                            <label>Budget Amount</label>
                            <input type="number" step="0.0001" class="form-control" name="budget_amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Line</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editLineModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form action="" method="POST" class="modal-content" id="editLineForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Budget Line</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Account</label>
                            <select class="form-control" name="account_id" required>
                                <option value="">Select Account</option>
                                @php
                                    $expenseAccounts = \Modules\Finance\Models\Account::where('company_id', $budget->company_id)
                                        ->where('account_type', 'EXPENSE')
                                        ->orderBy('account_code')
                                        ->get(['id', 'account_code', 'account_name']);
                                @endphp
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cost Center</label>
                            <select class="form-control" name="cost_center_id">
                                <option value="">Select Cost Center</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Period (1-12)</label>
                            <input type="number" class="form-control" name="period" min="1" max="12" required>
                        </div>
                        <div class="form-group">
                            <label>Budget Amount</label>
                            <input type="number" step="0.0001" class="form-control" name="budget_amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Line</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

    @section('scripts')
    @if($budget->isDraft())
        <script>
            document.querySelectorAll('.edit-line-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const form = document.getElementById('editLineForm');
                    form.action = "{{ url('finance/budgets') }}/" + this.dataset.budget_id + "/lines/" + this.dataset.id;
                    form.querySelector('[name="account_id"]').value = this.dataset.account_id;
                    form.querySelector('[name="cost_center_id"]').value = this.dataset.cost_center_id;
                    form.querySelector('[name="period"]').value = this.dataset.period;
                    form.querySelector('[name="budget_amount"]').value = this.dataset.budget_amount;
                });
            });
        </script>
    @endif
@endsection
