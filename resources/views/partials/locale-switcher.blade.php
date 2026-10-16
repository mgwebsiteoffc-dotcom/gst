{{-- Language switcher: English / Hindi / Hinglish. Standalone Alpine scope. --}}
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false" type="button"
            class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
            aria-label="Language">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5 0 4.5-4 4.5-9S14.5 3 12 3 7.5 7 7.5 12s2 9 4.5 9Zm-8.5-9h17"/></svg>
        {{ ['en' => 'EN', 'hi' => 'हिं', 'hinglish' => 'HG'][app()->getLocale()] ?? 'EN' }}
    </button>
    <div x-show="open" x-cloak x-transition.origin.top.right
         class="absolute right-0 z-30 mt-2 w-36 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
        @foreach (['en' => 'English', 'hi' => 'हिंदी', 'hinglish' => 'Hinglish'] as $code => $label)
            <a href="{{ route('locale', $code) }}"
               class="flex items-center justify-between px-4 py-2 text-sm {{ app()->getLocale() === $code ? 'font-semibold text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
                @if (app()->getLocale() === $code)<span></span>@endif
            </a>
        @endforeach
    </div>
</div>
