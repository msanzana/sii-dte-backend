<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class StoreManualCertificateNoticeRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }

    public function rules():array
    {
        return [
            'type'=> ['required','string','max:20'],
            'tittle' => ['required','string','max:150'],
            'message' => ['required','string'],
        ];
    }
}
