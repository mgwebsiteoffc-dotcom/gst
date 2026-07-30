BikriBook — brand & site images
===============================

WHERE TO PUT THESE
------------------
Copy the contents of this zip into your project like so:

    favicon.ico            ->  public/favicon.ico
    everything else (*)    ->  public/images/

So you end up with:

    public/favicon.ico
    public/images/hero-invoice.png
    public/images/hero-invoice.webp
    public/images/logo.png
    ... etc

On macOS / Linux, from your project root:

    unzip bikribook-images.zip -d /tmp/bb
    mkdir -p public/images
    cp /tmp/bb/favicon.ico public/
    cp /tmp/bb/*.png /tmp/bb/*.webp public/images/


WHAT EACH FILE IS FOR
---------------------
hero-invoice.*      Homepage hero illustration (laptop + phone, e-invoice flow)
og-default.*        Social share card, exactly 1200x630. Shown when someone
                    pastes your link into WhatsApp, X, LinkedIn or Slack.
logo.*              Square brand mark. Used in the Organization JSON-LD schema.
favicon.ico         Browser tab icon (16/32/48px multi-resolution)
icon-180.png        Apple touch icon (iPhone/iPad home screen)
icon-192.png        Android / PWA home screen icon
icon-512.png        PWA splash screen icon

feature-reports.*     Features page, reports group
feature-whatsapp.*    Features page, sharing group
feature-inventory.*   Features page, operations group
partner-channel.*     /partners/channel
partner-affiliate.*   /partners/affiliate

solution-gst-billing.*    /solutions/gst-billing
solution-e-invoicing.*    /solutions/e-invoicing
solution-e-way-bill.*     /solutions/e-way-bill
solution-inventory.svg    /solutions/inventory
solution-shopify.*        /solutions/shopify
solution-accountants.*    /solutions/accountants
solution-startups.*       /solutions/startups
solution-api.*            /solutions/api

page-tools.*              /tools hub
page-templates.*          /invoice-formats and /pricing
page-download.svg         /download
page-integrations.svg     /integrations

NOTE ON SVG
-----------
Three files ship as SVG rather than PNG (solution-inventory, page-download,
page-integrations). They are 2-3 KB each, stay sharp at any zoom level, and
need no WebP twin. The x-illustration component detects them automatically.


PNG vs WEBP
-----------
Every image ships in both formats. WebP is ~93% smaller (175 KB for the whole
set, versus 2.4 MB of PNG) and is supported by every browser in use today.

Serve WebP first with a PNG fallback:

    <picture>
      <source srcset="/images/hero-invoice.webp" type="image/webp">
      <img src="/images/hero-invoice.png" alt="..." width="1200" height="655">
    </picture>

The homepage hero already does this. The five feature/partner images are not
wired into any page yet — they are ready when you want them.


IMPORTANT: ALWAYS SET width AND height
--------------------------------------
Without them the page reflows as images load, which hurts your Core Web Vitals
(Cumulative Layout Shift) and therefore your search ranking.

    hero-invoice     1200 x 655
    og-default       1200 x 630
    logo             512 x 512
    feature-*        800 x 436
    partner-*        800 x 436


LICENCE
-------
Generated for BikriBook. Yours to use, edit and redistribute.
