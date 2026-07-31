@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{
        returnType: 'gstr3b', dueDate: '', filedDate: '', turnover: 'below5',
        get daysLate(){ if(!this.dueDate || !this.filedDate) return 0; const d = new Date(this.filedDate) - new Date(this.dueDate); return Math.max(0, Math.ceil(d/86400000)); },
        get dailyFee(){ return this.turnover === 'below5' ? 25 : 50; },
        get lateFee(){ return this.daysLate * this.dailyFee * 2; },
        get interest(){ return 0; },
        money(v){ return '₹' + (v||0).toLocaleString('en-IN') }
    }">
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Return Type</label>
                <select x-model="returnType" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="gstr1">GSTR-1</option><option value="gstr3b">GSTR-3B</option>
                </select>
            </div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Annual Turnover</label>
                <select x-model="turnover" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="below5">Up to ₹5 Cr</option><option value="above5">Above ₹5 Cr</option>
                </select>
            </div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Due Date</label><input type="date" x-model="dueDate" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Filed Date</label><input type="date" x-model="filedDate" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
        </div>
        <div class="rounded-lg bg-slate-50 p-5 text-sm space-y-2">
            <div class="flex justify-between"><span class="text-slate-500">Days Late</span><span class="font-semibold" x-text="daysLate"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Daily Fee (CGST + SGST)</span><span class="tabular-nums" x-text="money(dailyFee) + ' + ' + money(dailyFee)"></span></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><span>Total Late Fee</span><span class="tabular-nums text-rose-600" x-text="money(lateFee)"></span></div>
        </div>
    </div>
    <div class="prose prose-slate mt-10 max-w-none">
        <h2>How GST late fees work</h2>
        <p><strong>GSTR-1:</strong> ₹50/day (₹20 for nil return). <strong>GSTR-3B:</strong> ₹50/day (₹20 for nil return). The fee is split into CGST + SGST. For taxpayers with turnover up to ₹5 Cr, the fee is reduced to ₹25/day per tax head.</p>
    </div>
</section>
@endsection