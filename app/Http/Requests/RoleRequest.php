<?php

namespace App\Http\Requests;

class RoleRequest extends ManagementRequest
{
    protected function resourceTable(): string
    {
        return 'roles';
    }

    protected function resourceRouteName(): string
    {
        return 'role';
    }

    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'description' => $this->descriptionRules(),
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'uuid', 'exists:permissions,uuid'],
        ];
    }
}
