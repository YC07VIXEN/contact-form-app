<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(ContactRequest $request)
    {
        $inputs = $request->validated();
        $category = Category::find($inputs['category_id']);
        $selectedTags = isset($inputs['tag_ids']) ? Tag::whereIn('id', $inputs['tag_ids'])->get() : collect();

        $request->session()->put('contact_inputs', $inputs);

        return view('contact.confirm', compact('inputs', 'category', 'selectedTags'));
    }

    public function store(Request $request)
    {
        $inputs = $request->session()->get('contact_inputs');

        if (! $inputs || $request->has('back')) {
            return redirect()->route('contacts.create')->withInput($inputs);
        }

        $contact = Contact::create($inputs);

        if (isset($inputs['tag_ids'])) {
            $contact->tags()->sync($inputs['tag_ids']);
        }

        $request->session()->forget('contact_inputs');

        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
