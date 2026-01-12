<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Поддержка legacy формата данных
        if ($this->has('request') && $this->input('request') === 'feedback') {
            $this->merge([
                'name' => $this->input('name'),
                'email' => $this->input('email'),
                'phone' => $this->input('phone'),
                'message' => $this->input('message'),
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => __('MSG_MSG_CONTACTS_ZAPOLNITE_OBYAZATELINYE_POLYA'),
            'message.required' => __('MSG_MSG_CONTACTS_ZAPOLNITE_OBYAZATELINYE_POLYA'),
            'email.email' => __('MSG_MSG_CONTACTS_EMAIL_UKAZAN_NEVERNO'),
        ];
    }
}
