<form class="mb-5 flex flex-wrap items-end gap-2">
    <div>
        <label class="mb-1 block text-xs text-slate-500">From</label>
        <x-input type="date" name="from" value="{{ $from->toDateString() }}" class="!w-40" />
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">To</label>
        <x-input type="date" name="to" value="{{ $to->toDateString() }}" class="!w-40" />
    </div>
    <x-btn type="submit">Apply</x-btn>
    @isset($export)
        <x-btn variant="secondary" :href="route('reports.export', [$export, 'from' => $from->toDateString(), 'to' => $to->toDateString()])">
            Download CSV
        </x-btn>
    @endisset
</form>
