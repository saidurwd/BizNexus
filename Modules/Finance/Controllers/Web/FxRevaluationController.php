<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\FxRevaluation;
use Modules\Finance\Services\FxRevaluationService;

class FxRevaluationController extends Controller
{
    public function index(): View
    {
        return view('finance.fx-revaluations.index', [
            'revaluations' => FxRevaluation::with(['journal', 'reversalJournal'])->orderByDesc('revaluation_date')->paginate(20),
        ]);
    }

    public function store(Request $request, FxRevaluationService $revaluations): RedirectResponse
    {
        $validated = $request->validate(['revaluation_date' => 'required|date']);

        try {
            $revaluation = $revaluations->revalue($this->companyContext->getActiveCompany(), Carbon::parse($validated['revaluation_date']));
        } catch (InvalidAccountingTransactionException|MissingExchangeRateException|MissingAccountMappingException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.fx-revaluations.index')
            ->with('success', "Revaluation posted. Net unrealised result: {$revaluation->net_gain_loss}.");
    }
}
