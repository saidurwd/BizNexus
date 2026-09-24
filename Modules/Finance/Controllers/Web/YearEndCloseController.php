<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Modules\Core\Exceptions\ClosedPeriodException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\FiscalYear;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Services\YearEndCloseService;

class YearEndCloseController extends Controller
{
    public function close(int $id, YearEndCloseService $yearEnd): RedirectResponse
    {
        return $this->run(fn () => $yearEnd->close($this->findCompanyYear($id)), 'Fiscal year closed; profit or loss transferred to retained earnings.');
    }

    public function reopen(int $id, YearEndCloseService $yearEnd): RedirectResponse
    {
        return $this->run(fn () => $yearEnd->reopen($this->findCompanyYear($id)), 'Fiscal year reopened; the closing entry was reversed.');
    }

    protected function findCompanyYear(int $id): FiscalYear
    {
        return FiscalYear::where('company_id', $this->getActiveCompanyId())->findOrFail($id);
    }

    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidAccountingTransactionException|ClosedPeriodException|MissingAccountMappingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('core.periods.index')->with('success', $success);
    }
}
