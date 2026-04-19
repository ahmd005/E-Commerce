<?php

namespace App\Http\Requests;

class PermissionRequest extends ManagementRequest
{
    protected function resourceTable(): string
    {
        return 'permissions';
    }

    protected function resourceRouteName(): string
    {
        return 'permission';
    }

    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'description' => $this->descriptionRules(),
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['required', 'uuid', 'exists:roles,uuid'],
        ];
    }
}
