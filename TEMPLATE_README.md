# Ledger — Microfinance Management System

Admin UI for a Philippine community lending institution (cooperative / MFI).

**Stack:** Laravel 12 · Livewire 3 · Tailwind CSS 4 · Alpine.js (bundled with Livewire) ·
Chart.js 4 · PHP 8.2+ · SQLite (dev) / MySQL (prod)

---

## Running it

```bash
cd hr
composer install
npm install

cp .env.example .env          # already done if you cloned this folder
php artisan key:generate
php artisan migrate --seed

npm run build                 # or: npm run dev
php artisan serve
```

Open <http://127.0.0.1:8000> and sign in.

### Demo accounts

| Email | Role | Sees |
| --- | --- | --- |
| `manager@ledger.coop.ph` | Branch Manager | All 5 modules, view/create/edit |
| `admin@ledger.coop.ph` | Super Admin | Everything including delete |
| `officer@ledger.coop.ph` | Loan Officer | Membership, Loans, Collections |
| `cashier@ledger.coop.ph` | Cashier | Collections, Savings & Treasury |
| `auditor@ledger.coop.ph` | Auditor | Read-only across all modules |

Password for every demo account: `password`

### Switching to MySQL

Development runs on SQLite (`database/database.sqlite`) so it works with no extra setup.
For MySQL, set this in `.env` and re-run `php artisan migrate --seed`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ledger
DB_USERNAME=root
DB_PASSWORD=
```

---

## What's in here

**27 screens**: Login, Dashboard, and all 25 sub-modules across 5 modules.

| Module | Sub-modules |
| --- | --- |
| Membership | Member Directory · Registration · KYC & Documents · Groups & Centers · Reports |
| Loan Management | Applications · Credit Assessment · Loan Accounts · Disbursements · Products |
| Collections | Daily Collection Sheet · Repayment Ledger · Past Due & PAR · Penalties & Restructuring · Performance |
| Savings & Treasury | Savings Accounts · Deposits & Withdrawals · Share Capital · Teller Blotter · Interest Posting |
| Administration | Reports Center · Analytics · Users & Roles · Branches & Settings · Audit Log |

Plus 6 drill-down screens: Member Profile, Group Detail, Loan Account Detail,
Savings Account Detail, Portfolio-at-Risk report, and Membership report view.

---

## Layout of the code

```
hr/
├── tailwind.config.js              Design tokens: primary / accent / neutral, shadows
├── app/
│   ├── Support/
│   │   ├── Nav.php                 THE sidebar tree — single source of truth
│   │   ├── MockData.php            All screen data (swap for Eloquent later)
│   │   ├── Format.php              ₱ formatting, badge mapping, delta tone
│   │   ├── ChartConfig.php         PHP chart config → JS (keeps callbacks)
│   │   ├── Icons.php               Inline Lucide paths
│   │   └── Rbac.php                Role → module permissions
│   └── Livewire/                   One thin class per screen
│       ├── Dashboard.php
│       ├── Membership/  Loans/  Collections/  Savings/  Admin/  Auth/
└── resources/
    ├── css/app.css                 Base layer + responsive table stacking
    ├── js/app.js                   Alpine behaviours + Chart.js defaults
    └── views/
        ├── components/             Blade UI kit (see below)
        │   ├── layouts/            app.blade.php · guest.blade.php
        │   └── charts/             One file per chart
        └── livewire/               One view per screen
```

### The Blade component kit

Every screen is assembled from these, so a change to one propagates everywhere:

`sidebar` · `sidebar-module` · `sidebar-sub-item` · `topbar` · `breadcrumb` ·
`page-header` · `filter-bar` · `data-table` · `th` · `stat-card` · `status-badge` ·
`pagination` · `empty-state` · `modal` · `drawer` · `form-field` · `tabs` · `meter` ·
`card` · `btn` · `icon` · `avatar` · `select` · `date-range` · `segmented` ·
`row-actions` · `row-action` · `kpi` · `stepper` · `chart` · `skeleton/*`

---

## Design system

Font is **Inter** (400/500/600/700). All money, account numbers, and dates use
`tabular-nums`; money is right-aligned. Currency renders as `₱1,234,567.89` —
always via `Format::peso()`, never hand-formatted in a view.

### Palette

Defined once in `tailwind.config.js` and loaded by Tailwind 4 through
`@config` in `resources/css/app.css`.

- `primary` 50–950 (teal, `#0F7A68` at 600)
- `accent` 100–700 (amber, `#E8940F` at 500) — **fill only, never text on white**; use `accent-700` for accent text
- `neutral` 50–950
- `success #14804A` · `warning #B54708` · `danger #B42318` · `info #1570EF`

### Rules the code enforces

- **One primary button per screen.** `bg-primary-600`, hover `bg-primary-700`.
  Everything else is `<x-btn>` default (white, `border-neutral-300`).
- **Status badges** come from a fixed map in `Format::badgeTone()`. Pages never
  pick a badge colour themselves.
- **Deltas are coloured by meaning, not direction.** `<x-stat-card good-direction="down">`
  makes a *rising* PAR render red while a rising portfolio renders green.
  There is a test for this.
- **Cards**: white, `border-neutral-200`, `rounded-[12px]`, `shadow-card`.
- **Inputs**: `rounded-[8px]`, `border-neutral-300`, focus `ring-2 ring-primary-600/30`.
- **Charts**: `#0F7A68 · #E8940F · #1570EF · #7FCFBE · #B42318 · #5A6B64`, in order.
- **PAR aging ramp**: `#DBF3ED · #FAC978 · #F5AC3D · #E8940F · #B42318`.
- **Icons**: Lucide, 18px, stroke 1.75.

---

## Navigation

`App\Support\Nav` defines the whole tree. The sidebar, the breadcrumb, and the
role permission matrix all read from it, so they cannot drift apart.

**Accordion behaviour** (`ledgerShell` in `resources/js/app.js`):

- Module rows are `<button aria-expanded aria-controls>`; Enter/Space toggle them.
- Opening one module collapses any other — exactly one open at a time.
- The module containing the current page is expanded on load.
- Sub-lists animate with a 200ms `max-height` + `opacity` transition.
- The chevron rotates 90° when open.
- Collapsed to the 68px rail, clicking a module icon opens a floating flyout to
  the right. Escape closes it.
- Below 1024px the sidebar becomes an off-canvas drawer; the accordion is unchanged.

Adding a sub-module means: add it to `Nav::modules()`, add a route with the
matching name, and create the Livewire component. The sidebar, breadcrumb, and
permission matrix pick it up automatically.

---

## Responsive & accessibility

- Sidebar → off-canvas drawer under 1024px.
- Tables → stacked cards under 768px, via the `.stack-table` rules in `app.css`.
  Each `<td>` carries `data-label="…"`, which becomes the row label on mobile.
  The markup stays a real `<table>` for screen readers.
- Daily Collection Sheet and Deposits & Withdrawals are built phone-first:
  large touch targets, `inputmode="decimal"`, and a sticky action bar.
- Semantic markup throughout: `<table>`, `<thead>`, `<form>`, `<nav>`, `<aside>`,
  `<fieldset>`. Every input has a real `<label>`.
- Visible focus rings (`ring-2 ring-primary-600`), `aria-label` on every
  icon-only button, `aria-current="page"` on the active nav item, skip link.

---

## Testing

```bash
php artisan test
```

50 tests / 603 assertions:

- `ScreensSmokeTest` — every one of the 33 routes renders.
- `SidebarNavigationTest` — the accordion contract: 5×5 tree, expansion on load,
  drill-down keeping the parent active, the accent bar, flyouts, breadcrumbs.
- `ScreenContractsTest` — money format, badge mapping, delta-by-meaning,
  the amortization schedule balancing to zero, collection sheet inputs,
  the 25×5 permission matrix, and Philippine mock-data ranges.

---

## Swapping the mock data for a database

Every screen is prop-driven. A Livewire class does one thing: pull arrays from
`MockData` and hand them to the view. To go live, replace the `MockData::…` calls
in `app/Livewire/**` with Eloquent queries that return the same shape — **no view
markup has to change**.

`MockData::amortization()` already implements both interest methods (Flat and
Diminishing) and is the piece worth keeping as real domain logic.

---

## Notes on the stack choice

The original brief specified Bootstrap 5, but the design system it defines is
expressed entirely in Tailwind tokens (`primary-600`, `rounded-[12px]`,
`ring-primary-600/30`, the badge colour map). Building on Bootstrap would have
meant re-deriving all of it. This is built on Tailwind, as confirmed.

Livewire is pinned to `^3.6`. Livewire 4 stores components as `⚡`-prefixed
single-file components inside `resources/views/components/`, which collides with
the Blade component directory this UI relies on, and emoji filenames are fragile
on Windows and in git.
