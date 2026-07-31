@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{ lang: 'eng', file: null, text: '', processing: false }">
        <div class="mb-4">
            <label class="mb-1 block text-sm font-medium text-slate-700">Language</label>
            <select x-model="lang" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                <option value="eng">English</option><option value="hin">Hindi</option><option value="tam">Tamil</option><option value="tel">Telugu</option><option value="ben">Bengali</option><option value="mar">Marathi</option><option value="guj">Gujarati</option>
            </select>
        </div>
        <label class="block cursor-pointer rounded-lg border-2 border-dashed border-slate-300 p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition">
            <svg class="mx-auto h-10 w-10 text-slate-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
            <p class="text-sm font-medium text-slate-700" x-text="file ? file.name : 'Drop image or PDF here, or click to browse'"></p>
            <p class="mt-1 text-xs text-slate-400">JPG, PNG, PDF · Max 10 MB</p>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="file = $event.target.files[0]">
        </label>
        <button x-show="file" @click="processing = true; setTimeout(() => { processing = false; text = 'Sample extracted text from the uploaded document.\n\nThis is a demo — the actual OCR tool will extract text using Tesseract or a cloud OCR API.' }, 2000)"
                class="mt-4 w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
            <span x-show="!processing">Extract Text</span><span x-show="processing">Processing...</span>
        </button>
        <div x-show="text" x-cloak class="mt-4">
            <label class="mb-1 block text-sm font-medium text-slate-700">Extracted Text</label>
            <textarea x-model="text" rows="8" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"></textarea>
            <button @click="navigator.clipboard.writeText(text)" class="mt-2 text-sm text-indigo-600 font-medium hover:underline">Copy to clipboard</button>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>How OCR works</h2>
        <p>Optical Character Recognition (OCR) converts images of text into machine-readable text. It works with scanned documents, photos of documents, and PDF files. Supports multiple Indian languages including Hindi, Tamil, Telugu, Bengali, Marathi, and Gujarati.</p>
    </div>
</section>
@endsection