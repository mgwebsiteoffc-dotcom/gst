# Plans, Billing & Team

## What was broken

`/billing/plans` was a scaffold ("wire up with Livewire components"), and it sat
**inside** the `subscribed` middleware group. So the moment a trial expired:

1. Middleware redirected you to `/billing/plans` with *"Your subscription has expired."*
2. That page was a placeholder — no plans, no way to pay.
3. You were stuck in a loop with no way out (and no way to add users either).

## What it does now

### Plans page (`Setup → Subscription Plans`, also linked from the expiry banner)
- Current plan, status and renewal date, plus a monthly ⇄ yearly switch
  (yearly shows the saving).
- Every active plan with its real limits: invoice limit, user limit,
  e-Invoice, Shopify and **multiple branches**.
- Buttons behave by context: **Choose plan** (new), **Upgrade** (higher plan),
  **Switch to this plan** (downgrade), **Active** (current).
- Reached **without** the `subscribed` middleware, so a lapsed business can pay
  and recover. Plans page is never blocked.

### Checkout
- Order summary with **18% GST** added, period dates and your GSTIN.
- With Razorpay keys configured → Razorpay Checkout opens (UPI/cards/netbanking);
  the payment signature is verified server-side before activation.
- Without keys → *Activate plan* works immediately so you are never locked out;
  add keys later in **Platform Admin → Businesses → (business) → Subscription
  payments**.
- **Renewals are not automatic** — you get a reminder and renew with one tap.

### Cancel / downgrade
- Cancel keeps access until the end of the paid period.
- Cancel is blocked while branches exist (they all run on the main plan).
- Downgrade to a plan without multi-branch is blocked until branches are removed.

### Team: create users and assign branches (`Settings → Team & roles`)
- **Add team member**: name, email, phone, password, **role** and **branch**.
- Enforces the plan's **user limit** across the main business + branches
  ("Your Starter plan includes 1 user. Upgrade to add more.").
- Assigning someone to a branch requires a **multi-branch plan**; without it the
  branch dropdown is disabled with an upgrade link.
- Change a member's role inline, remove members (owners self-protected),
  and see each member's branch with a `Branch` badge.

### Multi-branch is now a paid capability
- New `plans.multi_branch` column, editable in **Platform Admin → Plans**.
- Seeded defaults: Free/Starter/Growth = no, **Enterprise = yes**.
- Branch creation is blocked with an upgrade message when the plan lacks it, and
  a **branch allowance** is enforced (Enterprise = main + 1 branch; adjustable).

### Super admin
- **Businesses → (business)** now has:
  - *Paid capabilities*: enable/disable `multi_branch`, `einvoice`, `shopify`
    for one business only, plus an **extra-branches** allowance — ideal for demos
    or promotional deals without touching global plans.
  - *Subscription payments*: per-business Razorpay keys (else the platform
    `.env` account is used) with a sandbox toggle.
  - Subscription card extended with **interval** and **period (days)** so staff
    can grant a year with one field.
  - Branches card with per-branch **Login as** (audited).

## Go-live steps

```bash
php artisan migrate        # plans.multi_branch
php artisan db:seed --class=DatabaseSeeder   # adds the Free plan + multi_branch flags (safe, idempotent)
php artisan optimize:clear
```

Then, in **Platform Admin → Plans**, tick *Multi-branch* on the plan that should
allow stores (Enterprise is pre-ticked by the seeder).

## Test checklist

- [ ] Expired trial → dashboard redirects to plans → a plan can be chosen and
      activated → dashboard unlocks.
- [ ] Plans page shows monthly/yearly toggle and GST at checkout.
- [ ] Settings → Team: create a user; the user can log in.
- [ ] On a non-multi-branch plan: branch dropdown disabled + upgrade link;
      adding a branch shows the upgrade message.
- [ ] Switch the business to Enterprise (Platform Admin) → add branch B →
      assign a new user to B → that user sees only B.
- [ ] Super admin → Business → *Paid capabilities*: enable multi-branch for a
      Starter business → branch creation works even though the plan lacks it.
