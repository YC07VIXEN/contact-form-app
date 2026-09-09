<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:0,1,2,3'], // 0:すべて
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => ':attributeは文字列で指定してください。',
            'max' => ':attributeは:max文字以内で指定してください。',
            'in' => '選択された:attributeは無効です。',
            'exists' => '選択された:attributeは登録されていません。',
            'date' => ':attributeは正しい日付形式で指定してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword' => '検索キーワード',
            'gender' => '性別',
            'category_id' => 'お問い合わせの種類',
            'date' => '日付',
        ];
    }
}
