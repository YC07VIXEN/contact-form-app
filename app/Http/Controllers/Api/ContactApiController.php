<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContactSaveRequest;
use App\Http\Requests\Api\ContactSearchRequest;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactApiController extends Controller
{
    public function index(ContactSearchRequest $request): JsonResponse
    {
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
        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        return response()->json($query->latest()->paginate($request->input('per_page', 10)));
    }

    public function show($id): JsonResponse
    {
        $contact = Contact::with(['category', 'tags'])->find($id);

        return $contact ? response()->json($contact) : response()->json(['message' => 'Not Found'], 404);
    }

    public function store(ContactSaveRequest $request): JsonResponse
    {
        $inputs = $request->validated();
        $contact = Contact::create($inputs);
        if (isset($inputs['tag_ids'])) {
            $contact->tags()->sync($inputs['tag_ids']);
        }

        return response()->json($contact->load(['category', 'tags']), 201);
    }

    public function update(ContactSaveRequest $request, $id): JsonResponse
    {
        $contact = Contact::find($id);
        if (! $contact) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        $inputs = $request->validated();
        $contact->update($inputs);
        $contact->tags()->sync($inputs['tag_ids'] ?? []);

        return response()->json($contact->load(['category', 'tags']), 200);
    }

    public function destroy($id): JsonResponse
    {
        $contact = Contact::find($id);
        if (! $contact) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        $contact->delete();

        return response()->json(null, 204);
    }
}
