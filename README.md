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

## Demo data for Time & Leave reports

To add report examples to a local or demo database:

```bash
php artisan db:seed --class=TimeLeaveReportDemoSeeder
```

This explicit seeder adds four employees named **Alex Demo**, **Bea Demo**,
**Carlo Demo**, and **Dina Demo**, plus labelled demo signatories. Their employee
IDs begin with `DEMO-RPT-`. It supplies records for the previous and current
months; for a seed run in October 2026, select **September 2026** for a complete
month, or **October 2026** for attendance through the seed date.

To populate both October 2026 DTR halves for Clyde Abendan and the report-demo
employees, run `php artisan db:seed --class=OctoberDtrDemoSeeder`. This includes
future weekdays for PDF testing, preserves existing punches, and fills only
missing weekday working hours.

| Screen | What to select and generate |
| --- | --- |
| DTR | Alex Demo (`DEMO-RPT-001`), previous month; try first half, second half, whole month, and overtime. |
| DTR logs | Alex Demo, the first through the last day of the previous month; try regular and overtime logs. |
| Leave | Alex Demo for decided and pending applications; open the leave-form PDF or use the month's dates for the leave report. Bea and Carlo also have sample applications. |
| Tardiness | Alex Demo or All Employees, previous month, then Generate. |
| Face Attendance | Open today's monitor or a weekday in either seeded month for face/QR sample punches, location flags, and missing locations. |
| Events | Choose a `DEMO - Orientation` or `DEMO - Safety Workshop` event and All employment statuses, then generate attendance. |

Demo quick access lists these accounts. Alternatively, Alex's username is
`report.alex@example.test`, and all newly created report-demo accounts use
`ReportDemo123!` as their password. The dedicated accounts include working hours
and a supervisor. Missing HR and Mayor settings are filled with labelled demo
signatories; existing assignments are kept. The sample attendance station is
inactive so it does not change the live kiosk's geofence. The seed contains audit
examples rather than face enrolments.

The seeder runs only in `local`/`testing` environments or with `APP_DEMO=true`.
It adds missing records on a repeat run, preserving edits to existing records.

Check the seed and the actual PDF endpoints with:

```bash
php artisan test --filter='TimeLeavePdfTest|TimeLeaveReportDemoSeederTest'
```

PDFs are rendered server-side with dompdf. The Time & Leave PDF templates embed
local images through `pdf_image()`, so their headers do not require another HTTP
request to the application server.

## Data protection

Employee photos, e-signatures and generated documents are excluded from version
control (see `.gitignore`). Never commit `.env` or database dumps — they contain
personal data.
