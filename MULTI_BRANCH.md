# Multi-Branch / Multi-Store (one login, many shops)

Scenario: **X** is the main business with branches **A** and **B**. The owner
logs in once and manages X, A and B — no separate accounts, no shared password.

## How it works

Every branch is a **full business of its own** (`tenants` row with
`parent_tenant_id = X`). That means each branch automatically gets, with zero
extra code:

- its own **GSTIN, state and tax mode** (or run as a non-GST store),
- its own **invoice/PO/quotation numbering series** (GST audit friendly),
- its own **customers, products, stock, payments and reports**,
- its own **logo/signature branding**.

The main business keeps the **subscription** — branches ride on X's plan and
trial, so adding a store does not add a bill.

## Switching

- **Header switcher** (top bar): tap the business name → pick X, A or B.
  The choice is stored in the session as `active_tenant_id`.
- The **Branches page** (`Setup → Business & Branches`) lists every business
  with sales this month, outstanding, document count, and a **Switch** button.
- Safety: the session value is re-validated on **every request** against the
  businesses that login may access, so a tampered session cannot open someone
  else's shop.

## Staff rules

| Who | What they see |
|---|---|
| Owner / admin of X | X + every branch, can switch any time |
| X staff (accountant, operator) | X + every branch (switchable) |
| Staff attached to branch A | **Only A** — other branches stay invisible |
| Platform admin | Sees every business in `/admin`, plus **Login as** per branch (audited) |

Owners/admins of X carry their role into a branch automatically the first time
they switch (roles are copied into that branch's permission team).
New branches get their own role rows at creation, exactly like a new signup.

## Managing branches

`Setup → Business & Branches` (owners/admins only):

1. **Group overview** — one row per shop with Sales (MTD), Outstanding, Documents.
2. **Add branch** — name, optional GSTIN (validated with checksum), state, city,
   phone. No GSTIN → the branch runs as a non-GST store.
3. **Edit / remove** — a branch can only be removed while it has no bills or
   contacts (removal moves its staff back to the main business).
4. **Team** — move any staff member to another branch from the dropdown.
   Their roles travel with them.

## Go-live step

```bash
php artisan migrate     # adds tenants.parent_tenant_id
```

## Deliberately not included (ask if you want it)

- **Shared catalog** across branches (right now products/stock are per store —
  correct for separate inventory, but a chain with a common price list would
  want a sync).
- **Consolidated org reports** (one GSTR-1 / P&L across X + A + B). Each
  branch files separately today, which is what the law expects when the GSTINs
  differ.
- **Branch-level plan limits** (`user_limit` is not enforced anywhere yet).
