# HRIS — LGU Mabinay

Human Resource Information System for the Municipality of Mabinay, Negros Oriental.
Laravel 11 with Tailwind and AdminLTE 3 screens, MySQL.

## Modules

- **Employees / PDS** — 201 file and CSC Personal Data Sheet (family background, education, eligibility, work experience, voluntary work, learning & development, references, government IDs, e-signature).
- **DTR** — daily time records from biometric devices and log zones, with printable DTR and log reports.
- **Leave** — credits, filing, and the approval chain: Employee → Supervisor → HR → **Mayor or Vice Mayor**. The approved form is generated as a PDF; no scanned upload is required.
- **Recruitment** — job postings, applications, ETE evaluation, interview panels and ratings.
- **Events** — calendar, attendance logging, and reports.
- **Settings** — approving officials (Mayor, Vice Mayor, HR head), offices, users.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `gd`, `mbstring`
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

## Face recognition on Hostinger Web or Cloud hosting

Use the bundled **ONNX Runtime Web** library. SCRFD detection, ArcFace 512-float
descriptors and the anti-spoof model run in JavaScript/WebAssembly on the camera
device. Laravel stores and compares descriptors, issues single-use challenges,
and verifies flash-image responses with PHP GD. Python, Node.js, a VPS and a
background inference process are not required for this mode.

Set these values in the hosted `.env`:

```dotenv
APP_URL=https://your-domain.example
FACE_RUNTIME=browser
FACE_REQUIRE_QR=true
FACE_FLASH_IMAGES_REQUIRED=true
```

`browser` overrides old `FACE_SCORING_ENABLED` and `FACE_PUNCH_SCORING_ENABLED`
flags, so an old sidecar setting cannot accidentally block registration or
attendance. Existing ArcFace enrolments stay compatible. Older 128-float
face-api.js enrolments still need registration again.

Deploy the Laravel application with PHP 8.2+ and GD enabled. Point the web root
at `public/`, and upload the **complete** `public/js/onnx/`,
`public/js/face-engine/`, and `public/models/arcface/` directories, including
their hidden `.htaccess` files. The runtime and model files total about 29 MB.
Keep the matching vendored JavaScript, loader and WASM files together. The
included MIME rules serve the loader as JavaScript and the binary as
`application/wasm`; the single-threaded runtime needs no cross-origin isolation
headers. Face security initialization refuses a missing anti-spoof model.

After updating the hosted environment, run:

```bash
php artisan config:clear
php artisan face:check
php artisan config:cache
```

Open Face Recognition and the attendance kiosk on the HTTPS domain, allow the
camera, register a face, then test the QR badge and face check. Camera access
requires HTTPS outside localhost. The check command verifies files and PHP
configuration; it does not replace testing the actual hosting domain and camera.

In browser mode, identity descriptors and anti-spoof probabilities are supplied
by the client. PHP independently checks the flash images, but it cannot recompute
face identity from the pixels. Keep the QR requirement and use controlled kiosk
devices. A VPS can opt into independent inference with `FACE_RUNTIME=server`
and the [optional scoring service](face-service/README.md); this profile enables
server scoring for both registration and attendance and retains fail-closed
behavior when that service is unavailable.

References: [ONNX Runtime Web deployment](https://onnxruntime.ai/docs/tutorials/web/deploy.html)
and [Hostinger supported frameworks](https://www.hostinger.com/support/which-programming-languages-and-frameworks-are-supported-at-hostinger/).

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
