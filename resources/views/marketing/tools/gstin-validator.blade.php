@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> ›
        <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}
    </nav>

    <div class="grid items-center gap-6 sm:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
            <p class="mt-3 text-slate-600">{{ $desc }}</p>
        </div>
        <x-illustration name="solution-e-invoicing" alt="Validating a GSTIN checksum" eager
                        class="w-full rounded-xl" />
    </div>

    @php $states = \App\Support\Gstin::STATES; @endphp

    <div class="mt-8 rounded-2xl border border-slate-200 p-6"
         x-data="{
            gstin: '',
            states: @js($states),
            get clean() { return this.gstin.toUpperCase().replace(/\s/g, ''); },
            get formatOk() { return /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(this.clean); },
            /* Mirrors App\Support\Gstin::checksum() — mod-36 weighted checksum. */
            get checksumChar() {
                const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                let sum = 0;
                for (let i = 0; i < 14; i++) {
                    const v = chars.indexOf(this.clean[i]) * (i % 2 === 0 ? 1 : 2);
                    sum += Math.floor(v / 36) + (v % 36);
                }
                return chars[(36 - (sum % 36)) % 36];
            },
            get stateCode() { return this.clean.slice(0, 2); },
            get stateName() { return this.states[this.stateCode] ?? null; },
            get pan() { return this.clean.slice(2, 12); },
            get valid() { return this.formatOk && this.stateName && this.clean[14] === this.checksumChar; }
         }">

        <label class="mb-1 block text-sm font-medium text-slate-700">Enter a GSTIN</label>
        <input x-model="gstin" maxlength="15" placeholder="09AAACD1234F1Z5"
               class="w-full rounded-lg border-slate-300 font-mono uppercase focus:border-indigo-500 focus:ring-indigo-500">

        <template x-if="clean.length === 15">
            <div class="mt-5 rounded-xl p-4" :class="valid ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200'">
                <p class="font-semibold" :class="valid ? 'text-emerald-800' : 'text-rose-800'"
                   x-text="valid ? ' Valid GSTIN' : ' Invalid GSTIN'"></p>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Format</dt>
                        <dd x-text="formatOk ? 'Correct' : 'Wrong pattern'"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">State</dt>
                        <dd x-text="stateName || 'Unknown state code'"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">PAN</dt>
                        <dd class="font-mono" x-text="pan"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Checksum</dt>
                        <dd x-text="clean[14] === checksumChar ? 'Matches' : 'Should be ' + checksumChar"></dd></div>
                </dl>
            </div>
        </template>
    </div>

    <div class="prose prose-slate mt-10 max-w-none">
        <h2>What the 15 characters mean</h2>
        <ul>
            <li><strong>1–2</strong> — state code (09 is Uttar Pradesh, 27 is Maharashtra)</li>
            <li><strong>3–12</strong> — the PAN of the business</li>
            <li><strong>13</strong> — entity number for that PAN within the state</li>
            <li><strong>14</strong> — always Z, reserved</li>
            <li><strong>15</strong> — a check digit computed from the first 14</li>
        </ul>
        <p>
            This tool checks the pattern, the state code and the check digit. It cannot tell you
            whether a registration is currently active — for that you need the GST portal.
        </p>
    </div>
</section>
@endsection
