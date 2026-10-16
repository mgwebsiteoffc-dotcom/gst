<form method="POST" action="{{ route('partners.apply') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc">@foreach ($errors->unique() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Honeypot --}}
    <div class="hidden" aria-hidden="true">
        <label>Website URL<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="grid gap-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Your name" name="name" required><x-input name="name" value="{{ old('name') }}" required /></x-field>
            <x-field label="Email" name="email" required><x-input type="email" name="email" value="{{ old('email') }}" required /></x-field>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Phone" name="phone" required><x-input name="phone" value="{{ old('phone') }}" required /></x-field>
            @if ($type === 'affiliate')
                <x-field label="Audience size" name="audience_size" hint="Total followers or subscribers.">
                    <x-input type="number" name="audience_size" value="{{ old('audience_size') }}" /></x-field>
            @else
                <x-field label="Company / firm" name="company"><x-input name="company" value="{{ old('company') }}" /></x-field>
            @endif
        </div>

        @if ($type === 'distributor')
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="City" name="city"><x-input name="city" value="{{ old('city') }}" /></x-field>
                <x-field label="Territory you can cover" name="territory">
                    <x-input name="territory" value="{{ old('territory') }}" placeholder="Lucknow + 3 districts" /></x-field>
                <x-field label="Years in business" name="years_experience">
                    <x-input type="number" name="years_experience" value="{{ old('years_experience') }}" /></x-field>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($type === 'channel')
                <x-field label="City" name="city"><x-input name="city" value="{{ old('city') }}" /></x-field>
            @endif
            <x-field label="Website or channel URL" name="website">
                <x-input type="url" name="website" value="{{ old('website') }}" placeholder="https://" /></x-field>
        </div>

        <x-field :label="match ($type) {
                'distributor' => 'What do you sell today, and which areas can you cover?',
                'channel' => 'Tell us about your business',
                default => 'Tell us about your audience',
            }" name="pitch" required>
            <textarea name="pitch" rows="4" required
                      class="block w-full rounded-lg border border-slate-300 p-2 text-sm"
                      placeholder="{{ match ($type) {
                          'distributor' => 'Which software/products do you sell today, how many shops are in your area, and how will you reach them?',
                          'channel' => 'What do you sell today, and how many SMB clients do you work with?',
                          default => 'Who follows you, and how would you introduce BikriBook to them?',
                      } }}">{{ old('pitch') }}</textarea>
        </x-field>

        @if ($type === 'distributor')
            <x-field label="Current business (name & type)" name="current_business"
                     hint="Helps us understand your network.">
                <x-input name="current_business" value="{{ old('current_business') }}"
                         placeholder="e.g. Sharma Computers — Tally dealer, 12 years" /></x-field>
        @endif

        <label class="flex items-start gap-2 text-sm text-slate-600">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-slate-300 text-indigo-600">
            <span>I agree to the partner terms and the privacy policy.</span>
        </label>

        <x-btn variant="primary" type="submit">{{ __('Apply to join') }}</x-btn>
    </div>
</form>
