<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class PollBoletaDispatchStatusRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }

    protected function prepareForValidation():void
    {
        $this->merge([
            'document_id' => $this->route('dispatchId'),
        ]);
    }

    public function rules():array
    {
        return [
            'document_id' => ['required','integer','exists:sii_dispatches,id'],
        ];
    }
}
