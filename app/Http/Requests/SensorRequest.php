<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor' => ['required','uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'sensor.required' => 'The UUID is required.',
            'sensor.uuid' => 'The UUID must be a valid UUID.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sensor' => $this->route('sensor'),
        ]);
    }
}
