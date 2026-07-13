# HRIS — LGU Mabinay

Human Resource Information System for the Municipality of Mabinay, Negros Oriental.
Laravel 9 + AdminLTE 3 (Bootstrap 4), MySQL.

## Modules

- **Employees / PDS** — 201 file and CSC Personal Data Sheet (family background, education, eligibility, work experience, voluntary work, learning & development, references, government IDs, e-signature).
- **DTR** — daily time records from biometric devices and log zones, with printable DTR and log reports.
- **Leave** — credits, filing, and the approval chain: Employee → Supervisor → HR → **Mayor or Vice Mayor**. The approved form is generated as a PDF; no scanned upload is required.
- **Recruitment** — job postings, applications, ETE evaluation, interview panels and ratings.
- **Events** — calendar, attendance logging, and reports.
- **Settings** — approving officials (Mayor, Vice Mayor, HR head), offices, users.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `gd`, `mbstring`
- MySQL 5.7+ / MariaDB
- Composer

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then point `.env` at it:

```
DB_DATABASE=hris_gov
DB_USERNAME=root
DB_PASSWORD=
```

Run the migrations and seed the starting data:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`db:seed` is idempotent — it is safe to re-run.

### Seeded accounts

Change these passwords immediately after the first sign-in.

| Sign in at  | Username / Email           | Password      | Role                        |
|-------------|----------------------------|---------------|-----------------------------|
| `/hr-admin` | `admin`                    | `admin123`    | Administrator               |
| `/hr-admin` | `hradmin`                  | `admin123`    | HR Administrator            |
| `/`         | `mayor@mabinay.gov.ph`     | `password123` | Mayor (approves leave)      |
| `/`         | `vicemayor@mabinay.gov.ph` | `password123` | Vice Mayor (approves leave) |
| `/`         | `hr@mabinay.gov.ph`        | `password123` | HR head                     |
| `/`         | `employee@mabinay.gov.ph`  | `password123` | Employee                    |

Sign-in accepts a **username or an email address**, plus Google sign-in once
`GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` are set (the account must already
exist in the system; Google only authenticates it).

## Important: `APP_URL` must match the address in the browser

`AppServiceProvider` calls `URL::forceRootUrl(config('app.url'))`, so **every**
generated link — including CSS, JS and images — is built from `APP_URL`. If it
does not match the host you are browsing, the page loads with no styling at all.

- XAMPP/Apache on port 80 → `APP_URL=http://localhost`
- `php artisan serve` → `APP_URL=http://localhost:8000`

## Note on PDF generation

Leave forms, DTRs and the PDS are rendered server-side with dompdf, which fetches
the page's images over HTTP. `php artisan serve` is single-threaded on Windows, so
it cannot serve those assets while it is busy rendering — the request deadlocks
until it times out. **Exercise PDF features under Apache (XAMPP), not the built-in
dev server.**

## Data protection

Employee photos, e-signatures and generated documents are excluded from version
control (see `.gitignore`). Never commit `.env` or database dumps — they contain
personal data.
