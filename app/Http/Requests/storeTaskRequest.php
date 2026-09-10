<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class storeTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:project,id',
            'parent_task_id' => 'nullable|integer|exists:tasks,id',
            'status' => 'required|in:Draft,In Progress,Done',
            'weight' => 'required|integer|min:1|max:100',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'integer|exists:tasks,id'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama task tidak boleh kosong.',
            'project_id.required' => 'Project harus dipilih',
            'project_id.exists' => 'Project tidak valid',
            'status.in' => 'Status tidak valid',
            'weight.min' => 'Bobot minimal 1',
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422));
    }
}
