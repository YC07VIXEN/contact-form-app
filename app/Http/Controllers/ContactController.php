<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * お問い合わせ入力画面の表示
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * お問い合わせ確認画面の表示（バリデーション実行）
     */
    public function confirm(ContactRequest $request)
    {
        // ★ここを完全に「validated」に統一して修正します
        $validated = $request->validated();

        $category = Category::find($validated['category_id']);
        $selectedTags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();

        $request->session()->put('contact_inputs', $validated);

        return view('contact.confirm', compact('validated', 'category', 'selectedTags'));
    }

    /**
     * お問い合わせの保存処理
     */
    public function store(Request $request)
    {
        $inputs = $request->session()->get('contact_inputs');

        if (!$inputs || $request->has('back')) {
            return redirect()->route('contacts.create')->withInput($inputs);
        }

        $contact = Contact::create($inputs);

        if (isset($inputs['tag_ids'])) {
            $contact->tags()->sync($inputs['tag_ids']);
        }

        $request->session()->forget('contact_inputs');

        return redirect()->route('contacts.thanks');
    }

    /**
     * 送信完了画面の表示
     */
    public function thanks()
    {
        return view('contact.thanks');
    }
}
