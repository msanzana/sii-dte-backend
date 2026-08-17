<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSignedDteToSiiRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }

    protected function prepareForValidation():void
    {
        $this->merge([
            'document_id' => $this->route('documentId'),
        ]);
    }

    public function rules():array
    {
        return [
            'document_id' => ['required','integer','exists:dte_documents,id'],
        ];
    }
}
