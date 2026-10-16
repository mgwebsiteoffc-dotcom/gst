{{-- Language options for the mobile drawer, as a full-width segmented control. --}}
<p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Language') }}</p>
<div class="flex gap-1 rounded-lg bg-slate-100 p-1">
    @foreach (['en' => 'English', 'hi' => 'हिंदी', 'hinglish' => 'Hinglish'] as $code => $label)
        <a href="{{ route('locale', $code) }}" @click="nav = false"
           class="flex-1 rounded-md px-2 py-1.5 text-center text-xs font-medium transition {{ app()->getLocale() === $code ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
