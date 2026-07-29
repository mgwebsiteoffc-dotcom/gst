<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.posts.index', [
            'posts' => Post::with('category', 'author')
                ->when($request->q, fn ($q, $s) => $q->where('title', 'like', "%$s%"))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.posts.form', [
            'post' => new Post,
            'categories' => PostCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $post = Post::create($this->validated($request));

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post created.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', [
            'post' => $post,
            'categories' => PostCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $post->update($this->validated($request, $post));

        return back()->with('success', 'Post saved.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }

    /** Inline image upload for the rich-text editor. */
    public function upload(Request $request)
    {
        $request->validate(['file' => ['required', 'image', 'max:3072']]);

        $path = $request->file('file')->store('blog', 'public');

        return response()->json(['location' => Storage::url($path), 'url' => Storage::url($path)]);
    }

    protected function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'post_category_id' => ['nullable', 'exists:post_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:3072'],
            'cover_alt' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'noindex' => ['boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:250'],
            'faqs.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);

        // 'nullable' keys are absent entirely when the field is not submitted,
        // so always read them with a default before use.
        $data['slug'] = Str::slug(($data['slug'] ?? null) ?: $data['title']);
        $data['noindex'] = (bool) ($data['noindex'] ?? false);
        $data['author_id'] = $post?->author_id ?? $request->user()->id;
        $data['reading_minutes'] = Post::estimateReadingMinutes($data['body'] ?? '');

        // Publishing without an explicit date means "now".
        if ($data['status'] === 'published' && empty($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        // Drop blank FAQ rows so empty inputs never reach the schema markup.
        $data['faqs'] = collect($data['faqs'] ?? [])
            ->filter(fn ($f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))
            ->values()->all() ?: null;

        if ($request->hasFile('cover')) {
            if ($post?->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $data['cover_image'] = $request->file('cover')->store('blog', 'public');
        }

        unset($data['cover']);

        return $data;
    }
}
