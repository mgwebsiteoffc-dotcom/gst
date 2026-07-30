<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\FaqController as AdminFaq;
use App\Http\Controllers\Admin\LeadController as AdminLead;
use App\Http\Controllers\Admin\PostController as AdminPost;
use App\Http\Controllers\Admin\CommissionPlanController as AdminCommissionPlan;
use App\Http\Controllers\Admin\PartnerController as AdminPartner;
use App\Http\Controllers\Admin\PayoutController as AdminPayout;
use App\Http\Controllers\Admin\PlanController as AdminPlan;
use App\Http\Controllers\Admin\TenantController as AdminTenant;
use App\Http\Controllers\Marketing\BlogController;
use App\Http\Controllers\Marketing\PartnerApplicationController;
use App\Http\Controllers\Partner\PortalController;
use App\Http\Controllers\Marketing\PageController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EinvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopifyController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public marketing site
// ---------------------------------------------------------------
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('features', [PageController::class, 'features'])->name('features');
Route::get('pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('about', [PageController::class, 'about'])->name('about');
Route::get('contact', [PageController::class, 'contact'])->name('contact');
Route::post('contact', [PageController::class, 'submitContact'])->name('contact.submit')->middleware('throttle:5,1');

// Solution / integration pages
Route::get('solutions/{slug}', [PageController::class, 'solution'])->name('solution');

// Free tools
Route::get('tools', [PageController::class, 'tools'])->name('tools');
Route::get('tools/{slug}', [PageController::class, 'tool'])->name('tool');

Route::get('invoice-formats', [PageController::class, 'templates'])->name('templates');
Route::get('download', [PageController::class, 'download'])->name('download');
Route::get('integrations', [PageController::class, 'integrations'])->name('integrations');
Route::get('page/{page}', [PageController::class, 'simple'])->name('simple');

// Partner programs
Route::get('partners/channel', [PartnerApplicationController::class, 'channel'])->name('partners.channel');
Route::get('partners/affiliate', [PartnerApplicationController::class, 'affiliate'])->name('partners.affiliate');
Route::post('partners/apply', [PartnerApplicationController::class, 'store'])
    ->middleware('throttle:5,1')->name('partners.apply');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('sitemap.xml', [BlogController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [BlogController::class, 'robots'])->name('robots');

Route::get('legal/{page}', [PageController::class, 'legal'])->name('legal');

// Signed public invoice link shared over WhatsApp/email.
Route::get('i/{document}', [\App\Http\Controllers\DocumentController::class, 'publicView'])
    ->middleware('signed')->name('documents.public');

// Shopify webhooks: HMAC-verified, no session/CSRF.
Route::post('webhooks/shopify/{topic}', [ShopifyController::class, 'webhook'])
    ->middleware('shopify.webhook')
    ->name('shopify.webhook');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('tenant.required')->name('dashboard');

    Route::middleware(['tenant.required', 'subscribed'])->group(function () {
        // Clients & vendors
        Route::get('contacts', [PartyController::class, 'index'])->name('parties.index');
        Route::get('contacts/create', [PartyController::class, 'create'])->name('parties.create');
        Route::post('contacts', [PartyController::class, 'store'])->name('parties.store');
        Route::get('contacts/{party}', [PartyController::class, 'show'])->name('parties.show');
        Route::get('contacts/{party}/edit', [PartyController::class, 'edit'])->name('parties.edit');
        Route::put('contacts/{party}', [PartyController::class, 'update'])->name('parties.update');
        Route::delete('contacts/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

        // Products & services
        Route::resource('items', ItemController::class)->except('show');

        // Business profile / branding
        Route::get('branding', [BrandingController::class, 'edit'])->name('branding.edit');
        Route::post('branding', [BrandingController::class, 'update'])->name('branding.update');
        Route::delete('branding/{type}', [BrandingController::class, 'removeImage'])->name('branding.remove');

        // Documents (invoice, quotation, proforma, challan, CN/DN, PO, purchase bill)
        Route::get('documents/{type}', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{type}/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('document/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('document/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('document/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::get('document/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
        Route::post('document/{document}/convert/{target}', [DocumentController::class, 'convert'])->name('documents.convert');
        Route::post('document/{document}/payment', [DocumentController::class, 'payment'])->name('documents.payment');
        Route::post('document/{document}/cancel', [DocumentController::class, 'cancel'])->name('documents.cancel');

        // e-Invoice / e-Way bill
        Route::middleware('subscribed:einvoice')->group(function () {
            Route::post('document/{document}/irn', [EinvoiceController::class, 'generate'])->name('einvoice.generate');
            Route::post('document/{document}/irn/cancel', [EinvoiceController::class, 'cancel'])->name('einvoice.cancel');
            Route::post('document/{document}/eway-bill', [EinvoiceController::class, 'ewayBill'])->name('ewaybill.generate');
        });

        // Reports
        Route::get('reports/gstr1', [ReportController::class, 'gstr1'])->name('reports.gstr1');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.pl');
        Route::get('reports/item-wise', [ReportController::class, 'itemWise'])->name('reports.item');
        Route::get('reports/party-wise', [ReportController::class, 'partyWise'])->name('reports.party');
        Route::get('reports/ledger/{party}', [ReportController::class, 'ledger'])->name('reports.ledger');
        Route::get('reports/export/{report}', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/gstr3b', [ReportController::class, 'gstr3b'])->name('reports.gstr3b');
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');

        // Shopify
        Route::middleware('subscribed:shopify')->prefix('shopify')->name('shopify.')->group(function () {
            Route::get('/', [ShopifyController::class, 'index'])->name('index');
            Route::post('install', [ShopifyController::class, 'install'])->name('install');
            Route::get('callback', [ShopifyController::class, 'callback'])->name('callback');
            Route::post('{store}/sync-products', [ShopifyController::class, 'syncProducts'])->name('sync');
            Route::put('{store}/settings', [ShopifyController::class, 'updateSettings'])->name('settings');
        });
    });

    // Settings: invoicing mode (GST / non-GST) and NIC credentials
    Route::middleware('tenant.required')->group(function () {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/tax-mode', [SettingsController::class, 'taxMode'])->name('settings.tax-mode');
    Route::put('settings/nic', [SettingsController::class, 'nic'])->name('settings.nic');
    Route::post('settings/nic/test', [SettingsController::class, 'testNic'])->name('settings.nic.test');

    Route::view('billing/plans', 'billing.plans')->name('billing.plans');

    // Migration / Import
    Route::get('migration', [MigrationController::class, 'index'])->name('migration.index');
    Route::post('migration/upload', [MigrationController::class, 'upload'])->name('migration.upload');
    Route::get('migration/{job}', [MigrationController::class, 'show'])->name('migration.show');
    Route::get('migration/{job}/review', [MigrationController::class, 'review'])->name('migration.review');
    Route::put('migration/{job}/mapping', [MigrationController::class, 'updateMapping'])->name('migration.mapping');
    Route::post('migration/{job}/execute', [MigrationController::class, 'execute'])->name('migration.execute');
    Route::get('migration/{job}/progress', [MigrationController::class, 'progress'])->name('migration.progress');
    Route::delete('migration/{job}', [MigrationController::class, 'destroy'])->name('migration.destroy');
    });
});

// Partner portal — for approved partners, not tenant users.
Route::middleware('auth')->prefix('partner')->name('partner.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::put('profile', [PortalController::class, 'updateProfile'])->name('profile');
});

// Platform staff area.
Route::middleware(['auth', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');

    // Partner program
    Route::get('partners', [AdminPartner::class, 'index'])->name('partners.index');
    Route::get('partners/{partner}', [AdminPartner::class, 'show'])->name('partners.show');
    Route::put('partners/{partner}', [AdminPartner::class, 'update'])->name('partners.update');
    Route::post('partners/{partner}/payout', [AdminPartner::class, 'payout'])->name('partners.payout');

    Route::get('payouts', [AdminPayout::class, 'index'])->name('payouts.index');
    Route::put('payouts/{payout}', [AdminPayout::class, 'update'])->name('payouts.update');
    Route::post('commissions/approve-matured', [AdminPayout::class, 'approveMatured'])->name('commissions.approve');

    Route::get('commission-plans', [AdminCommissionPlan::class, 'index'])->name('commission-plans.index');
    Route::post('commission-plans', [AdminCommissionPlan::class, 'store'])->name('commission-plans.store');
    Route::put('commission-plans/{commissionPlan}', [AdminCommissionPlan::class, 'update'])->name('commission-plans.update');
    Route::delete('commission-plans/{commissionPlan}', [AdminCommissionPlan::class, 'destroy'])->name('commission-plans.destroy');

    // Subscription plans
    Route::get('plans', [AdminPlan::class, 'index'])->name('plans.index');
    Route::post('plans', [AdminPlan::class, 'store'])->name('plans.store');
    Route::put('plans/{plan}', [AdminPlan::class, 'update'])->name('plans.update');
    Route::delete('plans/{plan}', [AdminPlan::class, 'destroy'])->name('plans.destroy');

    // Businesses and their users
    Route::get('tenants', [AdminTenant::class, 'index'])->name('tenants.index');
    Route::get('tenants/{tenant}', [AdminTenant::class, 'show'])->name('tenants.show');
    Route::put('tenants/{tenant}', [AdminTenant::class, 'update'])->name('tenants.update');
    Route::put('tenants/{tenant}/subscription', [AdminTenant::class, 'subscription'])->name('tenants.subscription');
    Route::put('users/{user}/toggle', [AdminTenant::class, 'toggleUser'])->name('users.toggle');

    // Blog CMS
    Route::get('posts', [AdminPost::class, 'index'])->name('posts.index');
    Route::get('posts/create', [AdminPost::class, 'create'])->name('posts.create');
    Route::post('posts', [AdminPost::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [AdminPost::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [AdminPost::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [AdminPost::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/upload', [AdminPost::class, 'upload'])->name('posts.upload');

    // Marketing FAQs
    Route::get('faqs', [AdminFaq::class, 'index'])->name('faqs.index');
    Route::post('faqs', [AdminFaq::class, 'store'])->name('faqs.store');
    Route::put('faqs/{faq}', [AdminFaq::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [AdminFaq::class, 'destroy'])->name('faqs.destroy');

    // Contact form submissions
    Route::get('leads', [AdminLead::class, 'index'])->name('leads.index');
    Route::put('leads/{lead}', [AdminLead::class, 'update'])->name('leads.update');
});

require __DIR__.'/auth.php';
