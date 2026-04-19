<?php

namespace App\Http\Requests\AuthValidation;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\AuthValidation\AuthenticationValidation;
class LoginValidation extends FormRequest
{
    use AuthenticationValidation;
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->getPrimaryRules(false);
    }

    public function messages(): array
    {
        return $this->getPrimaryMessages();
    }
}
