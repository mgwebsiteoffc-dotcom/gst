@extends('layouts.app')
@section('title', 'Blog Posts')

@section('content')
<x-page title="Blog Posts" subtitle="Write and publish articles for the marketing site.">
    <x-btn variant="primary" :href="route('admin.posts.create')">+ New post</x-btn>
</x-page>

<form class="mb-4 flex flex-wrap gap-2">
    <x-input name="q" value="{{ request('q') }}" placeholder="Search titles" class="!w-64" />
    <x-select name="status" class="!w-40" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="published" @selected(request('status') === 'published')>Published</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
    </x-select>
    <x-btn type="submit">Search</x-btn>
</form>

<x-card class="!p-0">
    @if ($posts->isEmpty())
        <x-empty title="No posts yet" message="Publish your first article to start attracting search traffic.">
            <x-btn variant="primary" :href="route('admin.posts.create')">+ New post</x-btn>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Title</th>
                        <th class="px-3 font-medium">Category</th>
                        <th class="px-3 font-medium">Status</th>
                        <th class="px-3 text-right font-medium">Views</th>
                        <th class="px-3 font-medium">Published</th>
                        <th class="px-5"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($posts as $post)
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $post->title }}</a>
                            <div class="text-xs text-slate-400">/blog/{{ $post->slug }}</div>
                        </td>
                        <td class="px-3 text-slate-600">{{ $post->category?->name ?? '—' }}</td>
                        <td class="px-3">
                            <x-badge tone="{{ $post->status === 'published' ? 'emerald' : 'slate' }}">{{ ucfirst($post->status) }}</x-badge>
                            @if ($post->noindex)<x-badge tone="amber">noindex</x-badge>@endif
                        </td>
                        <td class="px-3 text-right tabular-nums">{{ number_format($post->views) }}</td>
                        <td class="px-3 text-slate-500">{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($post->isPublished())
                                <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="mr-2 text-xs text-slate-500 hover:text-indigo-600">View</a>
                            @endif
                            <x-btn variant="ghost" size="sm" :href="route('admin.posts.edit', $post)">Edit</x-btn>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<div class="mt-4">{{ $posts->links() }}</div>
@endsection
