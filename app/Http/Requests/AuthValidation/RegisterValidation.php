<?php

namespace App\Http\Requests\AuthValidation;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\AuthValidation\AuthenticationValidation;
class RegisterValidation extends FormRequest
{
    use AuthenticationValidation;
    public function authorize(): bool
    {
        return true;
    }
 
    public function rules(): array
    {
        return array_merge(
            $this->getPrimaryRules(true),
            $this->getSecondaryRules()
        );
    }

    public function messages(): array
    {
        return $this->getValidationMessages();
    }
}
