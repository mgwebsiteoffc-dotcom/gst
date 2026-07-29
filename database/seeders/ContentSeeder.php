<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('is_super_admin', true)->first();

        $cats = collect([
            ['name' => 'GST Basics', 'slug' => 'gst-basics', 'description' => 'Understand GST rules in plain English.'],
            ['name' => 'e-Invoicing', 'slug' => 'e-invoicing', 'description' => 'IRN, QR codes and the NIC portal.'],
            ['name' => 'Business Tips', 'slug' => 'business-tips', 'description' => 'Running a small business in India.'],
        ])->mapWithKeys(fn ($c) => [$c['slug'] => PostCategory::updateOrCreate(['slug' => $c['slug']], $c)]);

        // Home page FAQs — written as direct answers so AI assistants can quote them.
        $faqs = [
            'home' => [
                ['Is BikriBook free?', 'BikriBook is free for 14 days with every feature unlocked and no credit card required. After the trial, paid plans start at ₹499 per month.'],
                ['Do I need a GSTIN to use BikriBook?', 'No. BikriBook works for both GST-registered and unregistered businesses. Without a GSTIN you can raise plain non-GST invoices; add a GSTIN later and GST invoicing switches on.'],
                ['Can I generate e-invoices and e-way bills?', 'Yes. BikriBook connects to the NIC e-invoice portal to generate the IRN and signed QR code, and can create an e-way bill directly from that IRN.'],
                ['Does it work on a mobile phone?', 'Yes. BikriBook runs in any modern browser on a phone, tablet or computer. There is nothing to install.'],
                ['Can my accountant access my data?', 'Yes. Invite your CA as a read-only Viewer or as an Accountant who can prepare returns. You control exactly what each person can see.'],
                ['Is my business data safe?', 'Your data is stored on servers in India, credentials are encrypted at rest, and you can export everything at any time.'],
            ],
            'pricing' => [
                ['What happens after the free trial?', 'Your account stays accessible but new invoices are paused until you choose a plan. Nothing is charged automatically and no data is deleted.'],
                ['Can I change plans later?', 'Yes, you can upgrade or downgrade at any time. Changes apply from your next billing cycle.'],
                ['Do you offer annual billing?', 'Yes. Paying yearly costs the equivalent of ten months, saving you two months on every plan.'],
                ['How do I pay?', 'UPI, debit or credit card, and net banking are all supported. You receive a GST invoice for every payment.'],
                ['Is there a refund policy?', 'Yes. Email us within 7 days of your first payment for a full refund. See our refund policy for details.'],
            ],
            'features' => [
                ['Does BikriBook file my GST returns?', 'BikriBook prepares GSTR-1 and GSTR-3B as portal-ready data from your invoices. The final submission is made by you or your CA on the GST portal.'],
                ['Can I use it for both GST and non-GST bills?', 'Yes. You can switch between a GST tax invoice and a plain non-GST invoice on each document, and the two keep separate numbering series.'],
                ['Does it work with Shopify?', 'Yes. Connect your Shopify store and every order becomes a GST invoice automatically, with the correct place of supply. Refunds become credit notes.'],
                ['Can I add my logo and signature?', 'Yes. Upload your logo and signature in Settings and they appear on every invoice PDF, along with your bank and UPI details.'],
            ],
        ];

        foreach ($faqs as $page => $rows) {
            foreach ($rows as $i => [$q, $a]) {
                Faq::updateOrCreate(
                    ['page' => $page, 'question' => $q],
                    ['answer' => $a, 'position' => $i, 'active' => true]
                );
            }
        }

        $posts = [
            [
                'title' => 'What Is a Pakka Bill? GST Tax Invoice Rules Explained Simply',
                'slug' => 'what-is-pakka-bill-gst-tax-invoice',
                'category' => 'gst-basics',
                'focus_keyword' => 'pakka bill',
                'excerpt' => 'A pakka bill is simply a GST tax invoice. Here is exactly what it must contain, when you must issue one, and how it differs from a kaccha bill.',
                'meta_description' => 'A pakka bill is a GST tax invoice. Learn the 8 mandatory fields under Rule 46, when to issue one, and how it differs from a kaccha bill.',
                'body' => <<<'HTML'
<p>If you run a shop in India, you have heard both terms. A customer asks for a <strong>pakka bill</strong>, or says a <strong>kaccha bill</strong> will do. The difference is not slang — it is the difference between a legal tax document and a scrap of paper.</p>
<h2>What a pakka bill actually is</h2>
<p>A pakka bill is the everyday name for a <strong>GST tax invoice</strong>. It is the document a GST-registered business must issue for every taxable sale. It carries your GSTIN, the tax breakup, and an HSN code for each item. Crucially, it is what lets your buyer claim input tax credit.</p>
<p>A kaccha bill is an informal receipt. It has no legal standing under GST, and a business buyer cannot claim credit against it.</p>
<h2>What must appear on a GST invoice</h2>
<p>Rule 46 of the CGST Rules lists the mandatory fields. Miss one and your buyer's credit can be blocked:</p>
<ul>
<li>A unique, consecutive invoice number for the financial year</li>
<li>Date of issue</li>
<li>Your name, address and GSTIN</li>
<li>The buyer's name, address and GSTIN, if they are registered</li>
<li>HSN code for goods, or SAC code for services</li>
<li>Description, quantity, unit and taxable value of each item</li>
<li>The tax rate and amount, split into CGST and SGST, or shown as IGST</li>
<li>Place of supply, and your signature or digital signature</li>
</ul>
<h2>CGST and SGST, or IGST?</h2>
<p>This trips up more people than anything else, but the rule is simple. Compare your state with the <strong>place of supply</strong>:</p>
<ul>
<li><strong>Same state</strong> — split the tax equally into CGST and SGST. An 18% rate becomes 9% + 9%.</li>
<li><strong>Different state</strong> — charge the full 18% as IGST.</li>
</ul>
<p>The place of supply is usually the buyer's location for goods. Get this wrong and your GSTR-1 will not match your buyer's records.</p>
<h2>When you must issue one</h2>
<p>For goods, issue the invoice before or at the time of delivery. For services, within 30 days of providing them. If you are under the composition scheme you cannot issue a tax invoice at all — you issue a <strong>bill of supply</strong> instead, with no tax charged.</p>
<h2>Doing it by hand is where errors creep in</h2>
<p>Handwritten bills go wrong in predictable ways: a repeated invoice number, a missing HSN code, or CGST charged on an inter-state sale. Each of those can cost your buyer their input credit and cost you the relationship.</p>
<p>Billing software removes the judgement calls. BikriBook picks the tax split from the place of supply, keeps your numbering unbroken, and stores HSN codes against each item so they are never forgotten.</p>
HTML,
                'faqs' => [
                    ['question' => 'Is a pakka bill the same as a GST invoice?', 'answer' => 'Yes. Pakka bill is the common Indian term for a GST tax invoice — a legal document with your GSTIN, HSN codes and the tax breakup.'],
                    ['question' => 'Can I issue a pakka bill without a GSTIN?', 'answer' => 'No. Only a GST-registered business can issue a tax invoice. Without registration you may issue a bill of supply or an ordinary invoice with no GST charged.'],
                    ['question' => 'What happens if my GST invoice has a mistake?', 'answer' => 'Issue a credit note or debit note against the original invoice. Do not delete or overwrite it — GST law requires an unbroken invoice trail.'],
                ],
            ],
            [
                'title' => 'e-Invoicing in India 2026: Who Must Do It and How IRN Works',
                'slug' => 'e-invoicing-india-irn-guide',
                'category' => 'e-invoicing',
                'focus_keyword' => 'e-invoicing India',
                'excerpt' => 'e-Invoicing applies to businesses above ₹5 crore turnover. Here is how the IRN process works, what the QR code means, and the deadlines that matter.',
                'meta_description' => 'Complete guide to e-invoicing in India: turnover limits, how to generate an IRN on the NIC portal, the 30-day reporting rule, and cancellation.',
                'body' => <<<'HTML'
<p>e-Invoicing does not mean emailing a PDF. It means reporting your invoice to a government portal <em>before</em> you give it to the customer, and receiving a unique number back.</p>
<h2>Who has to do it</h2>
<p>The threshold has fallen steadily since 2020. Businesses with annual aggregate turnover above <strong>₹5 crore</strong> must generate e-invoices for B2B supplies. Turnover is calculated PAN-wide, not per GSTIN, and once you cross the limit in any year you stay in the system permanently.</p>
<p>e-Invoicing applies to B2B, SEZ, deemed export and export invoices. Plain B2C sales are outside it.</p>
<h2>How the IRN process works</h2>
<ol>
<li>You create the invoice in your billing software as usual.</li>
<li>The software sends it in the government's schema to the Invoice Registration Portal.</li>
<li>The IRP validates it, checks for duplicates, and returns an <strong>IRN</strong> — a 64-character hash — plus a digitally signed QR code.</li>
<li>You print the IRN and QR code on the invoice before handing it over.</li>
</ol>
<p>Without a valid IRN, a B2B invoice from a covered business is not legally valid, and your buyer cannot claim input credit.</p>
<h2>The 30-day reporting rule</h2>
<p>Invoices must be reported to the IRP within <strong>30 days</strong> of the invoice date. Miss the window and the portal simply rejects the invoice — there is no appeal, and you are left with a document your customer cannot claim credit against.</p>
<h2>Cancelling an IRN</h2>
<p>You can cancel an IRN within <strong>24 hours</strong> of generating it, and only in full — partial cancellation does not exist. After 24 hours, your only route is a credit note against the original invoice.</p>
<h2>What e-invoicing gives you back</h2>
<p>The compliance burden comes with a real benefit: reported invoices flow automatically into your GSTR-1 and into your buyer's GSTR-2B. Done properly, it removes most reconciliation work at month end.</p>
HTML,
                'faqs' => [
                    ['question' => 'What is the turnover limit for e-invoicing in India?', 'answer' => 'Businesses with aggregate annual turnover above ₹5 crore must generate e-invoices for B2B supplies. Turnover is measured PAN-wide across all your GSTINs.'],
                    ['question' => 'Can I cancel an e-invoice after generating the IRN?', 'answer' => 'Only within 24 hours, and only the entire invoice. After that you must issue a credit note against the original.'],
                    ['question' => 'Is e-invoicing required for B2C sales?', 'answer' => 'No. e-Invoicing covers B2B, SEZ, deemed export and export invoices. B2C sales are exempt, though large businesses must show a dynamic QR code on B2C invoices.'],
                ],
            ],
            [
                'title' => 'GSTR-1 and GSTR-3B: What They Are and When to File',
                'slug' => 'gstr-1-gstr-3b-filing-guide',
                'category' => 'gst-basics',
                'focus_keyword' => 'GSTR-1 GSTR-3B',
                'excerpt' => 'Two returns confuse most small business owners. Here is what each one reports, when it is due, and what happens if you file late.',
                'meta_description' => 'GSTR-1 reports your sales; GSTR-3B is your monthly tax summary and payment. Learn the due dates, differences and late fees for Indian businesses.',
                'body' => <<<'HTML'
<p>Almost every GST-registered business files two returns. They sound similar and are often confused, but they do quite different jobs.</p>
<h2>GSTR-1: your sales, invoice by invoice</h2>
<p>GSTR-1 is a detailed statement of outward supplies. Every B2B invoice is listed individually, while B2C sales are summarised by state and tax rate. It also captures credit notes, debit notes, exports and an HSN-wise summary.</p>
<p>This return is what feeds your buyer's GSTR-2B. If you file late or wrongly, your customer cannot claim their input credit — which is why buyers chase suppliers about it.</p>
<h2>GSTR-3B: your tax summary and payment</h2>
<p>GSTR-3B is a self-declared summary. It reports total outward supplies, the input tax credit you are claiming, and the net tax you owe. This is the return where you actually pay.</p>
<h2>Due dates</h2>
<ul>
<li><strong>GSTR-1 (monthly)</strong> — 11th of the following month</li>
<li><strong>GSTR-1 (QRMP, quarterly)</strong> — 13th of the month after the quarter</li>
<li><strong>GSTR-3B (monthly)</strong> — 20th of the following month</li>
</ul>
<p>Businesses with turnover up to ₹5 crore can opt for the QRMP scheme: file quarterly, but pay monthly.</p>
<h2>Late filing costs more than the fee</h2>
<p>Late fees run at ₹50 per day (₹20 for nil returns), plus 18% annual interest on unpaid tax. The bigger risk is structural — persistent late filing can get your e-way bill generation blocked, which stops you moving goods at all.</p>
<h2>Making filing a non-event</h2>
<p>Both returns are built entirely from invoices you have already raised. If your billing data is correct through the month, filing is a review-and-submit exercise. BikriBook generates portal-ready GSTR-1 JSON and a GSTR-3B summary from your invoices, so nothing has to be re-keyed.</p>
HTML,
                'faqs' => [
                    ['question' => 'What is the difference between GSTR-1 and GSTR-3B?', 'answer' => 'GSTR-1 is an invoice-level statement of your sales. GSTR-3B is a summary return where you declare your tax liability, claim input credit and make the payment.'],
                    ['question' => 'What is the late fee for GSTR-3B?', 'answer' => 'Late fees are ₹50 per day (₹20 per day for nil returns), plus interest at 18% per annum on any unpaid tax.'],
                    ['question' => 'Can I revise GSTR-1 after filing?', 'answer' => 'No. GST returns cannot be revised. Corrections are made by amending the entry in a later period return.'],
                ],
            ],
        ];

        foreach ($posts as $i => $p) {
            Post::updateOrCreate(['slug' => $p['slug']], [
                'title' => $p['title'],
                'post_category_id' => $cats[$p['category']]->id,
                'author_id' => $author?->id,
                'excerpt' => $p['excerpt'],
                'body' => $p['body'],
                'meta_description' => $p['meta_description'],
                'focus_keyword' => $p['focus_keyword'],
                'faqs' => $p['faqs'],
                'status' => 'published',
                'published_at' => now()->subDays(($i + 1) * 4),
                'reading_minutes' => Post::estimateReadingMinutes($p['body']),
            ]);
        }

        $this->command?->info('Seeded '.count($posts).' posts, '.PostCategory::count().' categories and '.Faq::count().' FAQs.');
    }
}
