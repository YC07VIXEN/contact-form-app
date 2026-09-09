<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 画面の3つの入力欄（tel1, tel2, tel3）を結合してバリデーションにかける
        if ($this->filled(['tel1', 'tel2', 'tel3'])) {
            $this->merge([
                'tel' => $this->tel1.$this->tel2.$this->tel3,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'integer', 'in:1,2,3'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'tel' => ['required', 'string', 'regex:/^[0-9]{10,11}$/'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'detail' => ['required', 'string', 'max:120'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。',
            'string' => ':attributeは文字列で入力してください。',
            'integer' => ':attributeを正しく選択してください。',
            'email' => ':attributeは「ユーザー名@ドメイン」の形式で入力してください。',
            'max' => ':attributeは:max文字以内で入力してください。',
            'in' => ':attributeを正しく選択してください。',
            'regex' => ':attributeは半角数字のみ、ハイフンなしの10桁または11桁で入力してください。',
            'exists' => '選択された:attributeは無効です。',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => '姓',
            'last_name' => '名',
            'gender' => '性別',
            'email' => 'メールアドレス',
            'tel' => '電話番号',
            'address' => '住所',
            'building' => '建物名',
            'category_id' => 'お問い合わせの種類',
            'detail' => 'お問い合わせ内容',
        ];
    }
}
