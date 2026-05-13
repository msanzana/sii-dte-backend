<?php
namespace App\Modules\Dte\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PauseDteServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'reason' => ['required','string','max:255'],
        ];
    }
}
