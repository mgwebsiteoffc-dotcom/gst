@if ($faqs->count())
<section class="border-t border-slate-200 bg-slate-50 py-16" aria-labelledby="faq-heading">
    <div class="mx-auto max-w-3xl px-4 lg:px-6">
        <h2 id="faq-heading" class="text-2xl font-semibold tracking-tight text-slate-900">Frequently asked questions</h2>
        <dl class="mt-8 divide-y divide-slate-200">
            @foreach ($faqs as $faq)
                <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="py-4">
                    <dt>
                        <button @click="open = !open" class="flex w-full items-start justify-between gap-4 text-left">
                            <span class="font-medium text-slate-900">{{ $faq->question }}</span>
                            <svg class="mt-1 h-4 w-4 shrink-0 text-slate-400 transition" :class="open && 'rotate-180'"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                    </dt>
                    <dd x-show="open" x-collapse class="mt-2 text-sm leading-relaxed text-slate-600">{!! nl2br(e($faq->answer)) !!}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
