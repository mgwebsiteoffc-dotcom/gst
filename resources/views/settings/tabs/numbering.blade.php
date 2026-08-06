<form method="POST" action="{{ route('settings.numbering') }}">
    @csrf @method('PUT')

    <x-card title="Document numbering" subtitle="Set the prefix, next number and padding used for each document type this financial year ({{ $currentFy }}).">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500">
                    <tr>
                        <th class="py-2 pr-3">Document type</th>
                        <th class="py-2 pr-3">Prefix</th>
                        <th class="py-2 pr-3">Next number</th>
                        <th class="py-2 pr-3">Padding</th>
                        <th class="py-2 pr-3">Suffix</th>
                        <th class="py-2 pr-3">Preview</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($numberingRows as $i => $row)
                    @php
                        $prefix = old("series.$i.prefix", $row['prefix']);
                        $suffix = old("series.$i.suffix", $row['suffix']);
                        $next = old("series.$i.next_number", $row['next_number']);
                        $padding = old("series.$i.padding", $row['padding']);
                    @endphp
                    <tr class="border-t">
                        <td class="py-2 pr-3 font-medium text-slate-700 whitespace-nowrap">{{ $row['label'] }}</td>
                        <td class="py-2 pr-3">
                            <input type="hidden" name="series[{{ $i }}][doc_type]" value="{{ $row['doc_type'] }}">
                            <input type="hidden" name="series[{{ $i }}][tax_mode]" value="{{ $row['tax_mode'] }}">
                            <input name="series[{{ $i }}][prefix]" value="{{ $prefix }}"
                                   class="w-36 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </td>
                        <td class="py-2 pr-3">
                            <input type="number" min="1" name="series[{{ $i }}][next_number]" value="{{ $next }}"
                                   class="w-24 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </td>
                        <td class="py-2 pr-3">
                            <input type="number" min="1" max="10" name="series[{{ $i }}][padding]" value="{{ $padding }}"
                                   class="w-16 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </td>
                        <td class="py-2 pr-3">
                            <input name="series[{{ $i }}][suffix]" value="{{ $suffix }}"
                                   class="w-24 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </td>
                        <td class="py-2 pr-3 font-mono text-xs text-slate-500 whitespace-nowrap">
                            {{ $prefix }}{{ str_pad((string) $next, max(1, (int) $padding), '0', STR_PAD_LEFT) }}{{ $suffix }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            "Next number" is the number your <strong>next</strong> document will get — e.g. if your last invoice was #22,
            set this to 23 to continue that sequence. Numbering resets fresh at the start of each financial year; this
            only edits the {{ $currentFy }} series. Lowering it below what's already been issued risks duplicate numbers.
        </p>

        <div class="mt-4 flex justify-end">
            <x-btn variant="primary" type="submit">Save numbering</x-btn>
        </div>
    </x-card>
</form>
