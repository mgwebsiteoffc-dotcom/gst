@extends('layouts.app')
@section('title', $post->exists ? 'Edit post' : 'New post')

@php
    $initialFaqs = old('faqs', $post->faqs ?? []);
    if (! $initialFaqs) $initialFaqs = [['question' => '', 'answer' => '']];
@endphp

@section('content')
<form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
      enctype="multipart/form-data"
      x-data="postEditor({
          title: @js(old('title', $post->title)),
          slug: @js(old('slug', $post->slug)),
          metaTitle: @js(old('meta_title', $post->meta_title)),
          metaDescription: @js(old('meta_description', $post->meta_description)),
          faqs: @js(array_values($initialFaqs)),
          isEdit: @js($post->exists),
      })">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <x-page :title="$post->exists ? 'Edit post' : 'New post'" :back="route('admin.posts.index')">
        @if ($post->exists && $post->isPublished())
            <x-btn variant="secondary" :href="route('blog.show', $post->slug)" target="_blank">View live</x-btn>
        @endif
        <x-btn variant="secondary" type="submit" name="status" value="draft">Save draft</x-btn>
        <x-btn variant="primary" type="submit" name="status" value="published">Publish</x-btn>
    </x-page>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Main editor --}}
        <div class="space-y-5 lg:col-span-2">
            <x-card title="Content">
                <div class="grid gap-4">
                    <x-field label="Title" name="title" required>
                        <x-input name="title" x-model="title" @input="syncSlug()" required
                                 placeholder="How to create a GST invoice in India" />
                    </x-field>

                    <x-field label="URL slug" name="slug" hint="Keep it short and keyword-rich.">
                        <div class="flex items-center gap-1 text-sm">
                            <span class="shrink-0 text-slate-400">/blog/</span>
                            <x-input name="slug" x-model="slug" />
                        </div>
                    </x-field>

                    <x-field label="Excerpt" name="excerpt" hint="1–2 sentences. Used on cards and as a meta fallback.">
                        <textarea name="excerpt" rows="2" maxlength="500"
                                  class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </x-field>

                    <x-field label="Body" name="body" hint="Use H2 headings — they build the table of contents automatically.">
                        {{-- Rich text editor; the textarea is the real form field --}}
                        <div wire:ignore>
                            <div id="editor-toolbar" class="flex flex-wrap gap-1 rounded-t-lg border border-b-0 border-slate-300 bg-slate-50 p-1.5">
                                @foreach ([
                                    ['bold','B','font-bold'], ['italic','I','italic'], ['underline','U','underline'],
                                ] as [$cmd,$label,$cls])
                                    <button type="button" @click="exec('{{ $cmd }}')" class="h-8 w-8 rounded text-sm hover:bg-slate-200 {{ $cls }}">{{ $label }}</button>
                                @endforeach
                                <span class="mx-1 w-px bg-slate-300"></span>
                                @foreach ([['h2','H2'],['h3','H3'],['p','P']] as [$tag,$label])
                                    <button type="button" @click="format('{{ $tag }}')" class="h-8 rounded px-2 text-xs font-medium hover:bg-slate-200">{{ $label }}</button>
                                @endforeach
                                <span class="mx-1 w-px bg-slate-300"></span>
                                <button type="button" @click="exec('insertUnorderedList')" class="h-8 rounded px-2 text-xs hover:bg-slate-200">• List</button>
                                <button type="button" @click="exec('insertOrderedList')" class="h-8 rounded px-2 text-xs hover:bg-slate-200">1. List</button>
                                <button type="button" @click="addLink()" class="h-8 rounded px-2 text-xs hover:bg-slate-200">Link</button>
                                <button type="button" @click="$refs.img.click()" class="h-8 rounded px-2 text-xs hover:bg-slate-200">Image</button>
                                <button type="button" @click="exec('formatBlock','blockquote')" class="h-8 rounded px-2 text-xs hover:bg-slate-200">Quote</button>
                                <button type="button" @click="exec('removeFormat')" class="h-8 rounded px-2 text-xs hover:bg-slate-200">Clear</button>
                            </div>

                            <div x-ref="editor" contenteditable="true" @input="syncBody()" @blur="syncBody()"
                                 class="prose prose-sm min-h-[420px] max-w-none rounded-b-lg border border-slate-300 bg-white p-4 focus:outline-none focus:ring-1 focus:ring-indigo-500">{!! old('body', $post->body) !!}</div>

                            <input type="file" x-ref="img" accept="image/*" class="hidden" @change="uploadImage($event)">
                        </div>

                        <textarea name="body" x-ref="bodyField" class="hidden">{{ old('body', $post->body) }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">
                            <span x-text="wordCount"></span> words · about <span x-text="readingTime"></span> min read
                        </p>
                    </x-field>
                </div>
            </x-card>

            {{-- FAQs → FAQPage schema --}}
            <x-card title="FAQs for this post" subtitle="Shown on the page and published as FAQ schema, which answer engines quote.">
                <template x-for="(faq, i) in faqs" :key="i">
                    <div class="mb-3 rounded-lg border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Question <span x-text="i + 1"></span></span>
                            <button type="button" @click="removeFaq(i)" class="text-xs text-rose-600 hover:underline">Remove</button>
                        </div>
                        <input :name="`faqs[${i}][question]`" x-model="faq.question" placeholder="What is a pakka bill?"
                               class="mb-2 block w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <textarea :name="`faqs[${i}][answer]`" x-model="faq.answer" rows="2" placeholder="Answer in 2–3 plain sentences."
                                  class="block w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </template>
                <x-btn type="button" variant="secondary" size="sm" @click="addFaq()">+ Add FAQ</x-btn>
            </x-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <x-card title="Publishing">
                <div class="grid gap-4">
                    <x-field label="Category" name="post_category_id">
                        <x-select name="post_category_id">
                            <option value="">— None —</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('post_category_id', $post->post_category_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Publish date" name="published_at" hint="Leave blank to publish now.">
                        <x-input type="datetime-local" name="published_at"
                                 value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" />
                    </x-field>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="noindex" value="1" @checked(old('noindex', $post->noindex))
                               class="rounded border-slate-300 text-indigo-600">
                        Hide from search engines (noindex)
                    </label>
                </div>
            </x-card>

            <x-card title="Cover image">
                @if ($post->cover_image)
                    <img src="{{ Storage::url($post->cover_image) }}" alt="" class="mb-3 w-full rounded-lg object-cover">
                @endif
                <input type="file" name="cover" accept="image/*"
                       class="w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-slate-200">
                <div class="mt-3">
                    <x-field label="Alt text" name="cover_alt" hint="Describe the image for accessibility and image search.">
                        <x-input name="cover_alt" value="{{ old('cover_alt', $post->cover_alt) }}" />
                    </x-field>
                </div>
            </x-card>

            <x-card title="SEO">
                <div class="grid gap-4">
                    <x-field label="Focus keyword" name="focus_keyword" hint="The phrase you want to rank for.">
                        <x-input name="focus_keyword" value="{{ old('focus_keyword', $post->focus_keyword) }}" />
                    </x-field>

                    <x-field label="Meta title" name="meta_title">
                        <x-input name="meta_title" x-model="metaTitle" maxlength="70" />
                        <p class="mt-1 text-xs" :class="titleLen > 60 ? 'text-rose-600' : 'text-slate-500'">
                            <span x-text="titleLen"></span>/60 characters
                        </p>
                    </x-field>

                    <x-field label="Meta description" name="meta_description">
                        <textarea name="meta_description" x-model="metaDescription" rows="3" maxlength="300"
                                  class="block w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <p class="mt-1 text-xs" :class="descLen > 155 ? 'text-rose-600' : 'text-slate-500'">
                            <span x-text="descLen"></span>/155 characters
                        </p>
                    </x-field>

                    <x-field label="Canonical URL" name="canonical_url" hint="Only if this was published elsewhere first.">
                        <x-input type="url" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" />
                    </x-field>
                </div>

                {{-- Google-style preview --}}
                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="mb-1.5 text-xs font-medium text-slate-500">Search preview</p>
                    <p class="truncate text-xs text-emerald-700">{{ config('app.url') }}/blog/<span x-text="slug"></span></p>
                    <p class="truncate text-sm font-medium text-blue-800" x-text="metaTitle || title || 'Post title'"></p>
                    <p class="line-clamp-2 text-xs text-slate-600" x-text="metaDescription || 'Add a meta description to control how this appears in search results.'"></p>
                </div>
            </x-card>

            @if ($post->exists)
                <x-card title="Danger zone">
                    <p class="mb-3 text-sm text-slate-500">Deleting removes this post from the site and sitemap.</p>
                    <x-btn type="submit" variant="danger" size="sm" form="delete-post"
                           onclick="return confirm('Delete this post permanently?')">Delete post</x-btn>
                </x-card>
            @endif
        </div>
    </div>

</form>

@if ($post->exists)
    {{-- Separate form: HTML cannot nest forms, and this needs its own DELETE verb. --}}
    <form id="delete-post" method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
@endif
@endsection
