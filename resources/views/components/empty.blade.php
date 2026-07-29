@props(['title' => 'Nothing here yet', 'message' => null])

<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/>
        </svg>
    </div>

    <p class="font-medium text-slate-800">{{ $title }}</p>

    @isset($message)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $message }}</p>
    @endisset

    <div class="mt-4 empty:hidden">{{ $slot }}</div>
</div>
