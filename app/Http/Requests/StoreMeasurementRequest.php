<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor' => ['required','uuid'],
            'co2' => ['required', 'integer', 'min:0'],
            'time' => ['required', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }

    public function messages(): array
    {
        return [
            'sensor.required' => 'The UUID is required.',
            'sensor.uuid' => 'The UUID must be a valid UUID.',
            'co2.required' => 'The CO2 measurement is required.',
            'co2.integer' => 'The CO2 measurement must be an integer.',
            'co2.min' => 'The CO2 measurement must be at least 0.',
            'time.required' => 'The measurement time is required.',
            'time.date_format' => 'The time must be in the format: YYYY-MM-DDTHH:MM:SS+00:00',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sensor' => $this->route('sensor'),
        ]);
    }
}
