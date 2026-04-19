<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

abstract class ManagementRequest extends FormRequest
{
    abstract protected function resourceTable(): string;

    abstract protected function resourceRouteName(): string;

    public function authorize(): bool
    {
        return true;
    }

    protected function nameRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            $this->uniqueNameRule(),
        ];
    }

    protected function descriptionRules(): array
    {
        return [
            'nullable',
            'string',
            'max:1000',
        ];
    }

    protected function uniqueNameRule(): Rule
    {
        return Rule::unique($this->resourceTable(), 'name')
            ->ignore($this->ignoreId());
    }

    protected function ignoreId(): ?int
    {
        $resource = $this->route($this->resourceRouteName());

        if ($resource instanceof Model) {
            return $resource->id;
        }

        if (is_string($resource)) {
            return DB::table($this->resourceTable())
                ->where('uuid', $resource)
                ->orWhere('name', $resource)
                ->value('id');
        }

        return null;
    }
}
