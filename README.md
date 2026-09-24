# Rakit

A Laravel ERP starter kit. Inventory, purchasing and sales sit on one stock ledger, so every unit in a warehouse can be traced back to the purchase order that brought it in or the sales order that took it out. Clone it, rename it, and build the rest of your system on a foundation that already gets stock right.

Rakit is the sister project of [Rewire](https://github.com/Recodex-ID/rewire): same stack and conventions, but aimed at internal systems instead of landing pages.

## Stack

| | |
|---|---|
| PHP | 8.4 |
| Laravel | 13 |
| Auth | Fortify (login, password reset, email verification; no self sign-up) |
| Frontend | Livewire 4 + Flux UI |
| Styling | Tailwind CSS v4 |
| Roles | Spatie Permission |
| Audit | Spatie Activitylog |
| Media | Spatie Media Library |
| Quality | Pest 4, Pint, Larastan |

## What's in it

- **Master data.** Items (SKU, unit, purchase and sale price, minimum stock, photo), warehouses, customers and suppliers. Anything already used on a document can only be deactivated, never deleted.
- **Inventory.** Stock is a ledger of signed movements, never an overwritable number. Stock levels per warehouse, a low-stock filter, a stock card per item with a running balance, and adjustments after a stock count (with a reason, and never below zero).
- **Purchasing.** Draft, submit, approve, receive. Approval is its own permission so ordering and approving can be split between people. Receiving books every line into the warehouse in one transaction.
- **Sales.** Draft, confirm, deliver. Delivery is all or nothing: if any line is short in the ship-from warehouse, nothing moves and the message names the item.
- **Documents.** `PO-2026-0001` and `SO-2026-0001` numbering from a locked counter, amounts stored as whole Rupiah integers, and a print view per order (browser "Save as PDF" covers the PDF case).
- **Roles & permissions.** Permissions are defined in code (`App\Enums\Permission`); roles are edited in the app by a super admin. `admin` gets every module, `staff` is read-only, and you can add roles like "warehouse clerk".
- **Dashboard.** Sales and purchases this month, orders waiting for approval or delivery, low-stock items, and a 14-day chart of delivered sales. Each card only shows if the user has access to that module.
- **Back office.** User management, company details for printed documents, a media library, and a full activity log of who changed what.
- **Private by default.** The only public page is `/landing`, which describes the kit; `/` goes to the dashboard, every page is `noindex`, and `robots.txt` disallows everything. Security headers and HTTPS enforcement are on in production.

Deliberately left out so each project can decide: accounting, invoices and payments, tax (PPN), multiple currencies, partial receipts and deliveries, and transfers between warehouses. The in-app docs explain how to add a module in the same shape.

## Getting started

Uses SQLite by default, so there is no database server to set up first.

```bash
laravel new my-erp --using=recodex-id/rakit
```

Or clone it:

```bash
git clone https://github.com/Recodex-ID/rakit.git
cd rakit
composer install
npm install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
composer run dev
```

Visit `http://localhost:8000`. `composer run dev` runs the app server, queue listener and Vite together.

### Demo accounts and data

`php artisan migrate --seed` creates a small demo company (two warehouses, eight items, suppliers, customers, and orders in every status) plus three accounts, all with the password `password`:

| Email | Role | Access |
|---|---|---|
| `super-admin@mail.test` | super-admin | Everything, plus roles and the activity log |
| `admin@mail.test` | admin | Every module, users, settings, media |
| `staff@mail.test` | staff | Read-only |

For a real deployment start from an empty database and seed only the accounts: `php artisan migrate --seed --seeder=UserSeeder`, then change the passwords.

## Quality checks

```bash
php artisan test --compact
vendor/bin/pint --dirty
vendor/bin/phpstan analyse
```

Or `composer test`, which runs all three, the same as CI on every push. The stock, purchasing and sales rules have their own tests in `tests/Feature/Inventory`, `Purchasing` and `Sales`.

## Making it yours

1. Set `APP_NAME` and the rest of `.env`.
2. Fill in the company name, address, phone, email and tax ID under **System > Settings**. They are printed on every order.
3. Swap the brand palette in `resources/css/app.css` (`--color-brand-*`), the logo in `public/images/logo.png` and the `public/favicon*` files.
4. Adjust roles under **Super Admin > Roles & permissions**, or add permissions for a new module in `App\Enums\Permission`.
5. Update `composer.json`'s `name` and `description` if you rename the repo.

### Going to production

| Setting | What it does |
|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | Turns on HTTPS enforcement and keeps stack traces out of responses. |
| `FORCE_HTTPS` | Defaults to `true` in production. Redirects http to https, sends HSTS and marks the session cookie secure. The `/up` health check is exempt. |
| `TRUSTED_PROXIES` | Your load balancer's IPs (comma separated), or `*` if a proxy you control terminates TLS. |
| Scheduler | Add `* * * * * php /path/to/artisan schedule:run` to cron. It prunes activity log entries older than 365 days. |
| Database | SQLite is fine for a handful of users. For more, switch to MySQL or PostgreSQL; the stock locks (`lockForUpdate`) do their real work there. |

More detail on how each rule is enforced, and where it lives in the code, is on the in-app **Documentation** page.

## Contributing

Commits to `main` follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `chore:`, ...). [release-please](https://github.com/googleapis/release-please) reads them to version and publish [GitHub Releases](https://github.com/Recodex-ID/rakit/releases). `feat:` bumps a minor version, `fix:` a patch, and `feat!:` or a `BREAKING CHANGE:` footer bumps major.

## License

MIT.
