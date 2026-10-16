<form class="mb-5 grid gap-2 sm:flex sm:flex-wrap sm:items-end">
    <div>
        <label class="mb-1 block text-xs text-slate-500">From</label>
        <x-input type="date" name="from" value="{{ $from->toDateString() }}" class="w-full sm:!w-40" />
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">To</label>
        <x-input type="date" name="to" value="{{ $to->toDateString() }}" class="w-full sm:!w-40" />
    </div>
    <x-btn type="submit" class="w-full sm:w-auto">Apply</x-btn>
    @isset($export)
        <x-btn variant="secondary" class="w-full sm:w-auto" :href="route('reports.export', [$export, 'from' => $from->toDateString(), 'to' => $to->toDateString()])">
            Download CSV
        </x-btn>
    @endisset
</form>
