<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Разрешаем всем пользователям отправлять заявку
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255|min:2',
            'phone' => 'nullable',
            'date' => 'nullable',
            'arrival' => 'nullable|min:1',
            'departure' => 'nullable|min:1',
            'comment' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __(''),'Введите ваше имя',
            'phone.required' => 'Введите номер телефона',
            'date.required' => 'Укажите дату',
            'from.required' => 'Введите место отправления',
            'to.required' => 'Введите место прибытия',
        ];
    }
}
