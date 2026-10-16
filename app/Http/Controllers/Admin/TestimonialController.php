<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TestimonialController extends Controller
{
    public function index()
    {
        return view('admin.testimonials.index', [
            'testimonials' => Testimonial::orderBy('position')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Testimonial::create($this->validated($request));

        return back()->with('success', 'Testimonial added — it appears on the homepage immediately.');
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $testimonial->update($this->validated($request, $testimonial));

        return back()->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->photo) {
            Storage::disk('public')->delete($testimonial->photo);
        }

        $testimonial->delete();

        return back()->with('success', 'Testimonial removed.');
    }

    protected function validated(Request $request, ?Testimonial $testimonial = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'business' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:80'],
            'business_type' => ['nullable', Rule::in(['retailer', 'wholesaler', 'distributor', 'manufacturer', 'ca', 'service', 'shopify'])],
            'quote' => ['required', 'string', 'max:600'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ]);

        $data['active'] = $request->boolean('active');
        $data['featured'] = $request->boolean('featured');
        $data['position'] = $data['position'] ?? 0;

        if ($request->hasFile('photo')) {
            if ($testimonial?->photo) {
                Storage::disk('public')->delete($testimonial->photo);
            }
            $data['photo'] = $request->file('photo')->store('testimonials', 'public');
        }

        return $data;
    }
}
