@extends('layouts.erp')

@section('title', 'Create Journal Entry')

@section('content_header')
    <h1>Create Journal Entry</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.journals.store') }}" method="POST" id="journalForm">
                @csrf
                <input type="hidden" name="company_id" value="{{ $companyId }}">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="journal_date">Journal Date</label>
                            <input type="date" class="form-control @error('journal_date') is-invalid @enderror" 
                                   id="journal_date" name="journal_date" value="{{ old('journal_date', now()->format('Y-m-d')) }}" required>
                            @error('journal_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   value="{{ old('description') }}" placeholder="Enter description">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Journal Lines</h5>
                    <table class="table table-bordered" id="journalLinesTable">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            <tr class="line-row">
                                <td>
                                    <select class="form-control account-select" name="lines[0][account_id]" required>
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('lines.0.account_id') == $account->id)>
                                                {{ $account->account_code }} — {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="lines[0][description]" placeholder="Description" value="{{ old('lines.0.description') }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control text-right debit-input" name="lines[0][debit]" 
                                           step="0.01" min="0" value="{{ old('lines.0.debit', 0) }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control text-right credit-input" name="lines[0][credit]" 
                                           step="0.01" min="0" value="{{ old('lines.0.credit', 0) }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-line">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="line-row">
                                <td>
                                    <select class="form-control account-select" name="lines[1][account_id]" required>
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('lines.1.account_id') == $account->id)>
                                                {{ $account->account_code }} — {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="lines[1][description]" placeholder="Description" value="{{ old('lines.1.description') }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control text-right debit-input" name="lines[1][debit]" 
                                           step="0.01" min="0" value="{{ old('lines.1.debit', 0) }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control text-right credit-input" name="lines[1][credit]" 
                                           step="0.01" min="0" value="{{ old('lines.1.credit', 0) }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-line">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>Totals:</strong></td>
                                <td class="text-right">
                                    <strong id="totalDebit">0.00</strong>
                                </td>
                                <td class="text-right">
                                    <strong id="totalCredit">0.00</strong>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm" id="addLine">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <div id="balanceWarning" class="text-danger d-none">
                        <i class="bi bi-exclamation-triangle"></i> Journal is not balanced!
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        Create Journal
                    </button>
                    <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
    (function () {
        let lineIndex = 2;
        const accountOptions = `
            <option value="">Select Account</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
            @endforeach
        `;
        
        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;
            const rows = document.querySelectorAll('.line-row');
            rows.forEach(function(row) {
                const debitInput = row.querySelector('.debit-input');
                const creditInput = row.querySelector('.credit-input');
                totalDebit += parseFloat(debitInput.value) || 0;
                totalCredit += parseFloat(creditInput.value) || 0;
            });
            
            document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
            document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);
            
            const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;
            const warning = document.getElementById('balanceWarning');
            const submitBtn = document.getElementById('submitBtn');
            warning.classList.toggle('d-none', isBalanced);
            submitBtn.disabled = !isBalanced || totalDebit === 0;
        }
        
        document.querySelectorAll('.debit-input, .credit-input').forEach(function(input) {
            input.addEventListener('input', updateTotals);
        });
        
        document.getElementById('addLine').addEventListener('click', function() {
            const tbody = document.getElementById('linesBody');
            const newRow = document.createElement('tr');
            newRow.className = 'line-row';
            newRow.innerHTML = `
                <td>
                    <select class="form-control account-select" name="lines[${lineIndex}][account_id]" required>
                        ${accountOptions}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="lines[${lineIndex}][description]" placeholder="Description">
                </td>
                <td>
                    <input type="number" class="form-control text-right debit-input" name="lines[${lineIndex}][debit]" 
                           step="0.01" min="0" value="0">
                </td>
                <td>
                    <input type="number" class="form-control text-right credit-input" name="lines[${lineIndex}][credit]" 
                           step="0.01" min="0" value="0">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-line">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            newRow.querySelector('.debit-input').addEventListener('input', updateTotals);
            newRow.querySelector('.credit-input').addEventListener('input', updateTotals);
            lineIndex++;
            updateTotals();
        });
        
        document.getElementById('linesBody').addEventListener('click', function(e) {
            if (e.target.closest('.remove-line')) {
                const rows = document.querySelectorAll('.line-row');
                if (rows.length > 2) {
                    e.target.closest('.line-row').remove();
                    updateTotals();
                }
            }
        });
        
        document.getElementById('linesBody').addEventListener('change', function(e) {
            if (e.target.classList.contains('debit-input') && parseFloat(e.target.value) > 0) {
                e.target.closest('.line-row').querySelector('.credit-input').value = '0';
            }
            if (e.target.classList.contains('credit-input') && parseFloat(e.target.value) > 0) {
                e.target.closest('.line-row').querySelector('.debit-input').value = '0';
            }
            updateTotals();
        });
        
        updateTotals();
    })();
</script>
@endpush
