<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostJournalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'journal_id' => 'required|exists:journals,id',
        ];
    }
}
