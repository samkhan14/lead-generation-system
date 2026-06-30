<?php

namespace App\Http\Requests\Admin;

use App\Domains\BusinessKnowledge\Models\Service;

class UpdateServiceRequest extends StoreServiceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('services.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $this->serviceRules($service->id);
    }
}
