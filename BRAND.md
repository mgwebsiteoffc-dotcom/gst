# BikriBook Brand Guide

Everything visual now comes from one place: the logo mark in
`public/images/brand/`.

## The mark

A white **B** on a blue gradient tile with a red accent dot.

| Asset | Use |
|---|---|
| `images/brand/mark-256.png` / `mark-512.png` | App headers, auth pages, anywhere on light backgrounds |
| `images/brand/mark-on-color-256.png` / `-512.png` | **On coloured or photo backgrounds** — white tile so the mark stays visible |
| `images/brand/logo-light.png` | Navy wordmark lockup (light backgrounds) |
| `images/brand/logo-on-color.png` | White wordmark lockup (dark / brand backgrounds) |
| `images/brand/logo-white.png` | Single-colour white lockup (print, watermarks) |
| `favicon.ico`, `images/brand/favicon-16.png` / `-32.png` | Browser tabs |
| `images/brand/apple-touch-icon.png` | iOS home screen |
| `icons/icon-192.png`, `icon-512.png`, `maskable-*.png` | PWA / Android home screen |
| `splash/splash-*.png` | iOS launch screens (brand gradient + white tile) |

**Blade component** for headers:

```blade
<x-brand />                        {{-- mark + wordmark --}}
<x-brand :mark-only="true" />      {{-- tight spaces --}}
<x-brand variant="white" />        {{-- on dark backgrounds --}}
```

> Rule learned the hard way: never place the navy-on-blue mark directly on a
> blue surface — it vanishes. Use `mark-on-color-*` (white tile) or a lockup.

## Colour

Sampled directly from the logo, defined once in `resources/css/app.css`
under `@theme`:

| Token | Hex | Use |
|---|---|---|
| `brand-900` | `#00227B` | Deep navy — dark sections, headings on light |
| `brand-700` | `#0B4EA8` | Hover states on dark blue |
| `brand-600` | `#006FD1` | **Primary blue** — buttons, links, active nav, focus rings |
| `brand-500` | `#1995EE` | Lighter blue accents, charts |
| `brand-300` | `#8ACBFB` | Subtle borders on blue surfaces |
| `brand-50/100` | `#EFF8FF` / `#DBEFFE` | Tinted backgrounds, badges |
| `accent-500` | `#FF2D47` | **Red accent** — promo strip, "Most popular" badge, attention |
| `accent-600` | `#E6203A` | Red hover |

Usage ratio: **white dominates, blue carries the brand, red is a spice** — use
red only for a few emphasis points per page (announcement bar, popular plan
badge, destructive actions, notification dot). Overusing it loses the effect.

### How the whole app got branded

The templates were written with Tailwind `indigo-*` (318 usages across 75
views). Rather than edit every file, the palette maps `indigo-*` → `brand-*`
in `app.css`:

```css
@theme {
    --color-indigo-600: var(--color-brand-600);
    /* …all shades… */
}
```

So every existing `bg-indigo-600`, `text-indigo-600`, `border-indigo-300` and
focus ring now paints the logo's own blue. New work should use `brand-*` and
`accent-*` directly.

## Type

Inter, with `ui-sans-serif, system-ui` fallbacks (loaded via `--font-sans`).

## Applying this to a customer's own invoices

Each business keeps its own accent colour (Settings → Branding). The default
for new businesses is the brand blue; tenants can change it to match their
shop, which keeps their invoices distinct from ours.

## Regenerating assets

The mark came from `logo-mark-choice.png` (2048²). If the logo changes:

```bash
# trim, make white transparent, pad square, then rebuild derivatives
convert logo-mark-choice.png -fuzz 6% -trim +repage /tmp/mark_trim.png
convert /tmp/mark_trim.png -alpha on -fuzz 8% -transparent white /tmp/mark_alpha.png
convert /tmp/mark_alpha.png -background none -gravity center -extent 1700x1700 /tmp/mark_square.png
# then: marks, white-tile variant, lockups, favicons, PWA icons, splash screens
```
