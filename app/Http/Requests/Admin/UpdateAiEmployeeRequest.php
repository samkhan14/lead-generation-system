<?php

namespace App\Http\Requests\Admin;

class UpdateAiEmployeeRequest extends StoreAiEmployeeRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.employees.update');
    }
}
