@extends('layouts.marketing')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-14 lg:px-6">
    <nav aria-label="Breadcrumb" class="mb-4 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a> › <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a> › {{ $name }}</nav>
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $name }}</h1>
    <p class="mt-3 text-slate-600">{{ $desc }}</p>
    <div class="mt-8 rounded-2xl border border-slate-200 p-6" x-data="{
        seller: '{{ config("app.name") }}', buyer: '', gstRate: 18, items: [{name:'Service',qty:1,rate:1000}],
        addItem(){ this.items.push({name:'',qty:1,rate:0}) },
        get subtotal(){ return this.items.reduce((s,i) => s + (i.qty * i.rate), 0) },
        get tax(){ return this.subtotal * this.gstRate / 100 },
        get total(){ return this.subtotal + this.tax },
        money(v){ return '₹' + (v||0).toLocaleString('en-IN',{minimumFractionDigits:2}) }
    }">
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Your Business Name</label><input type="text" x-model="seller" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Customer Name</label><input type="text" x-model="buyer" placeholder="Customer name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
        </div>
        <div class="mb-4"><label class="mb-1 block text-sm font-medium text-slate-700">GST Rate</label>
            <select x-model.number="gstRate" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @foreach ([0,5,12,18,28] as $r)<option value="{{ $r }}">{{ $r }}%</option>@endforeach
            </select>
        </div>
        <div class="mb-4">
            <div class="text-sm font-medium text-slate-700 mb-2">Line Items</div>
            <template x-for="(item, idx) in items" :key="idx">
                <div class="grid grid-cols-12 gap-2 mb-2">
                    <input type="text" x-model="item.name" placeholder="Item name" class="col-span-5 rounded border border-slate-300 px-2 py-1.5 text-sm">
                    <input type="number" x-model.number="item.qty" min="1" placeholder="Qty" class="col-span-2 rounded border border-slate-300 px-2 py-1.5 text-sm">
                    <input type="number" x-model.number="item.rate" min="0" placeholder="Rate" class="col-span-3 rounded border border-slate-300 px-2 py-1.5 text-sm">
                    <button @click="items.splice(idx,1)" class="col-span-2 text-rose-500 text-sm hover:bg-rose-50 rounded">Remove</button>
                </div>
            </template>
            <button @click="addItem()" class="text-sm text-indigo-600 font-medium hover:underline">+ Add item</button>
        </div>
        <div class="border-t border-slate-200 pt-4 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="tabular-nums" x-text="money(subtotal)"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">GST (<span x-text="gstRate"></span>%)</span><span class="tabular-nums" x-text="money(tax)"></span></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><span>Total</span><span class="tabular-nums" x-text="money(total)"></span></div>
        </div>
    </div>
    <div class="mt-8 rounded-xl bg-indigo-600 p-6 text-center">
        <p class="text-lg font-semibold text-white">Want PDF invoices with e-invoice support?</p>
        <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">Create free account</a>
    </div>
</section>
@endsection