<?php

namespace Modules\Core\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A business rule refused the action (wrong status, unbalanced entry, credit limit). Web requests go back to the
 * form with the message; API requests get 422.
 */
class InvalidAccountingTransactionException extends Exception
{
    protected array $errors;

    public function __construct(string $message, array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => $this->getMessage(), 'errors' => $this->errors], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
