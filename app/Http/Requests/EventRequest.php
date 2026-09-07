<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'qr_prefix' => [
                'required',
                'string',
                'max:50',

                // QR nanti:
                // RogerVivier:TOKEN
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique('events', 'qr_prefix')
                    ->ignore($event),
            ],

            'event_date' => [
                'nullable',
                'date',
            ],

            'venue' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'Nama event wajib diisi.',

            'qr_prefix.required' =>
            'QR prefix wajib diisi.',

            'qr_prefix.regex' =>
            'QR prefix hanya boleh berisi huruf, angka, dash, dan underscore.',

            'qr_prefix.unique' =>
            'QR prefix sudah digunakan oleh event lain.',

            'event_date.date' =>
            'Format tanggal event tidak valid.',
        ];
    }
}
