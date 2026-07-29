@extends('layouts.app')
@section('title', 'Marketing FAQs')

@section('content')
<x-page title="Marketing FAQs" subtitle="These appear on the public pages and are published as FAQ schema for answer engines." />

<div class="mb-5 flex flex-wrap gap-1 border-b border-slate-200">
    @foreach (\App\Http\Controllers\Admin\FaqController::PAGES as $key => $label)
        <a href="{{ route('admin.faqs.index', ['page_key' => $key]) }}"
           class="-mb-px border-b-2 px-4 py-2.5 text-sm {{ $pageKey === $key ? 'border-indigo-600 font-medium text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @forelse ($faqs as $faq)
            <x-card>
                <form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
                    @csrf @method('PUT')
                    <div class="grid gap-3">
                        <x-input name="question" value="{{ $faq->question }}" class="font-medium" />
                        <textarea name="answer" rows="3"
                                  class="block w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $faq->answer }}</textarea>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="text-xs text-slate-500">Order
                                <input type="number" name="position" value="{{ $faq->position }}" class="ml-1 w-16 rounded border-slate-300 text-sm">
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-slate-500">
                                <input type="checkbox" name="active" value="1" @checked($faq->active) class="rounded border-slate-300 text-indigo-600"> Visible
                            </label>
                            <x-btn type="submit" size="sm">Save</x-btn>
                            <x-btn type="submit" size="sm" variant="danger" form="del-{{ $faq->id }}"
                                   onclick="return confirm('Delete this FAQ?')">Delete</x-btn>
                        </div>
                    </div>
                </form>
            </x-card>
            <form id="del-{{ $faq->id }}" method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="hidden">
                @csrf @method('DELETE')
            </form>
        @empty
            <x-card><x-empty title="No FAQs on this page yet" message="Add one on the right." /></x-card>
        @endforelse
    </div>

    <x-card title="Add an FAQ" class="h-fit">
        <form method="POST" action="{{ route('admin.faqs.store') }}" class="grid gap-3">
            @csrf
            <input type="hidden" name="page" value="{{ $pageKey }}">
            <x-field label="Question" name="question" required>
                <x-input name="question" required placeholder="Is BikriBook free?" />
            </x-field>
            <x-field label="Answer" name="answer" required hint="Answer directly in 2–3 sentences — this is what AI assistants quote.">
                <textarea name="answer" rows="4" required
                          class="block w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </x-field>
            <x-btn variant="primary" type="submit">Add FAQ</x-btn>
        </form>
    </x-card>
</div>
@endsection
