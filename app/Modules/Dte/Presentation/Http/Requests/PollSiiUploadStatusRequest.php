<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class PollSiiUploadStatusRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }
    #[Override]
    protected function prepareForValidation():void
    {
        $this->merge([
            'dispatch_id' => $this->route('dispatchId'),
        ]);
    }

    public function rules():array
    {
        return [
            'dispatch_id' => ['required', 'integer','exists:sii_dispatches,id'],
        ];
    }
}
