<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaqController extends Controller
{
    public const PAGES = ['home' => 'Home', 'pricing' => 'Pricing', 'features' => 'Features'];

    public function index(Request $request)
    {
        $page = $request->get('page_key', 'home');

        return view('admin.faqs.index', [
            'pageKey' => $page,
            'faqs' => Faq::where('page', $page)->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Faq::create($request->validate([
            'page' => ['required', Rule::in(array_keys(self::PAGES))],
            'question' => ['required', 'string', 'max:250'],
            'answer' => ['required', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]));

        return back()->with('success', 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($request->validate([
            'question' => ['required', 'string', 'max:250'],
            'answer' => ['required', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]) + ['active' => $request->boolean('active')]);

        return back()->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return back()->with('success', 'FAQ deleted.');
    }
}
