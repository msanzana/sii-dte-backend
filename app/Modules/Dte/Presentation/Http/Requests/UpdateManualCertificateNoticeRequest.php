<?php
namespace App\Modules\Dte\Presentation\Http\Requests;
class UpdateManualCertificateNoticeRequest
{
    public function authorize():bool
    {
        return true;
    }
    public function rules():array
    {
        return [
            'type' => ['required','string','max:20'],
            'tittle' => ['required','string','max:150'],
            'message' => ['required','string'],
            'is_active' => ['required','boolean']
        ];
    }
}
