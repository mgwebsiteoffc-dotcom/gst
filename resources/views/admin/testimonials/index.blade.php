@extends('layouts.app')
@section('title', __('Testimonials'))

@section('content')
<x-page :title="__('Testimonials')" :subtitle="__('Customer reviews shown on the marketing site.')" />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @forelse ($testimonials as $t)
            <x-card>
                <form method="POST" action="{{ route('admin.testimonials.update', $t) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field :label="__('Name')" name="name" required>
                            <x-input name="name" value="{{ $t->name }}" required />
                        </x-field>
                        <x-field :label="__('Business')" name="business">
                            <x-input name="business" value="{{ $t->business }}" placeholder="Sharma Kirana Store" />
                        </x-field>
                        <x-field :label="__('City')" name="city">
                            <x-input name="city" value="{{ $t->city }}" />
                        </x-field>
                        <x-field :label="__('Type')" name="business_type">
                            <x-select name="business_type">
                                @foreach ([
                                    '' => __('— not set —'),
                                    'retailer' => __('Retailer'),
                                    'wholesaler' => __('Wholesaler'),
                                    'distributor' => __('Distributor'),
                                    'manufacturer' => __('Manufacturer'),
                                    'ca' => __('Chartered Accountant'),
                                    'service' => __('Service business'),
                                    'shopify' => __('Online seller'),
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($t->business_type === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                    </div>

                    <x-field :label="__('Review')" name="quote" required class="mt-3">
                        <textarea name="quote" rows="3" required maxlength="600"
                                  class="block w-full rounded-lg border border-slate-300 p-2 text-sm">{{ $t->quote }}</textarea>
                    </x-field>

                    <div class="mt-3 grid gap-3 sm:grid-cols-4">
                        <x-field :label="__('Rating')" name="rating" required>
                            <x-select name="rating">
                                @foreach ([5, 4, 3, 2, 1] as $r)
                                    <option value="{{ $r }}" @selected((int) $t->rating === $r)>{{ $r }} ★</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field :label="__('Order')" name="position" hint="{{ __('Lower shows first') }}">
                            <x-input type="number" name="position" value="{{ $t->position }}" />
                        </x-field>
                        <x-field :label="__('Photo')" name="photo" hint="{{ __('Optional') }}">
                            <input type="file" name="photo" accept="image/*" class="text-xs">
                        </x-field>
                        <div class="flex flex-col justify-end gap-2 pb-1">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="active" value="1" @checked($t->active) class="rounded border-slate-300 text-indigo-600"> {{ __('Visible') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="featured" value="1" @checked($t->featured) class="rounded border-slate-300 text-indigo-600"> {{ __('Featured') }}
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-btn variant="primary" size="sm" type="submit">{{ __('Save') }}</x-btn>
                        <span class="text-xs text-slate-400">
                            {{ $t->active ? __('Live on the site') : __('Hidden') }}
                            @if ($t->featured) · {{ __('Featured in the top strip') }}@endif
                        </span>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.testimonials.destroy', $t) }}" class="mt-3 border-t border-slate-100 pt-3"
                      onsubmit="return confirm('{{ __('Delete this testimonial?') }}')">
                    @csrf @method('DELETE')
                    <button class="text-xs font-medium text-rose-600 hover:underline">{{ __('Delete') }}</button>
                </form>
            </x-card>
        @empty
            <x-card>
                <x-empty :title="__('No testimonials yet')"
                         :message="__('Add your first customer review — it appears on the homepage straight away.')" />
            </x-card>
        @endforelse
    </div>

    <x-card :title="__('Add a testimonial')" class="h-fit">
        <form method="POST" action="{{ route('admin.testimonials.store') }}" enctype="multipart/form-data" class="grid gap-3">
            @csrf
            <x-field :label="__('Name')" name="name" required><x-input name="name" required /></x-field>
            <x-field :label="__('Business')" name="business"><x-input name="business" placeholder="Sharma Kirana Store" /></x-field>
            <x-field :label="__('City')" name="city"><x-input name="city" /></x-field>
            <x-field :label="__('Type')" name="business_type">
                <x-select name="business_type">
                    @foreach (['retailer' => 'Retailer', 'wholesaler' => 'Wholesaler', 'distributor' => 'Distributor', 'manufacturer' => 'Manufacturer', 'ca' => 'Chartered Accountant', 'service' => 'Service business', 'shopify' => 'Online seller'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field :label="__('Review')" name="quote" required>
                <textarea name="quote" rows="4" required maxlength="600"
                          class="block w-full rounded-lg border border-slate-300 p-2 text-sm"
                          placeholder="{{ __('What changed for them after switching?') }}"></textarea>
            </x-field>
            <x-field :label="__('Rating')" name="rating" required>
                <x-select name="rating">
                    @foreach ([5, 4, 3, 2, 1] as $r)<option value="{{ $r }}">{{ $r }} ★</option>@endforeach
                </x-select>
            </x-field>
            <x-field :label="__('Photo')" name="photo" hint="{{ __('Optional — a square photo looks best') }}">
                <input type="file" name="photo" accept="image/*" class="text-xs">
            </x-field>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="active" value="1" checked class="rounded border-slate-300 text-indigo-600"> {{ __('Show on the site') }}
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="featured" value="1" class="rounded border-slate-300 text-indigo-600"> {{ __('Feature in the top strip') }}
            </label>
            <x-btn variant="primary" type="submit">{{ __('Add testimonial') }}</x-btn>
        </form>
    </x-card>
</div>
@endsection
