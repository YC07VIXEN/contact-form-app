<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSearchRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index(AdminSearchRequest $request)
    {
        $categories = Category::all();
        $tags = Tag::all();

        // ★N+1問題を回避するEager Loading
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('detail', 'like', "%{$keyword}%");
            });
        }
        if ($request->filled('gender') && $request->input('gender') != 0) {
            $query->where('gender', $request->input('gender'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $contacts = $query->latest()->paginate(7)->withQueryString();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    public function show($id)
    {
        $contact = Contact::with(['category', 'tags'])->findOrFail($id);

        return view('admin.show', compact('contact'));
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return redirect()->route('admin.index');
    }

    public function export(AdminSearchRequest $request): StreamedResponse
    {
        $query = Contact::with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('detail', 'like', "%{$keyword}%");
            });
        }
        if ($request->filled('gender') && $request->input('gender') != 0) {
            $query->where('gender', $request->input('gender'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $contacts = $query->latest()->get();

        return response()->stream(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'お名前', '性別', 'メールアドレス', '電話番号', '住所', '建物名', 'お問い合わせの種類', '詳細内容', '登録日時']);

            foreach ($contacts as $contact) {
                $genderText = match ((int) $contact->gender) {
                    1 => '男性', 2 => '女性', 3 => 'その他', default => '不明'
                };
                fputcsv($handle, [
                    $contact->id, $contact->first_name.' '.$contact->last_name, $genderText, $contact->email, $contact->tel, $contact->address, $contact->building, $contact->category?->content, $contact->detail, $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts_'.date('YmdHis').'.csv"',
        ]);
    }

    public function storeTag(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:50', 'unique:tags,name']]);
        Tag::create(['name' => $request->name]);

        return redirect()->route('admin.index');
    }

    public function editTag($id)
    {
        $tag = Tag::findOrFail($id);

        return view('admin.edit', compact('tag'));
    }

    public function updateTag(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $request->validate(['name' => ['required', 'string', 'max:50', 'unique:tags,name,'.$tag->id]]);
        $tag->update(['name' => $request->name]);

        return redirect()->route('admin.index');
    }

    public function destroyTag($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return redirect()->route('admin.index');
    }
}
