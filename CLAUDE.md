# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> See `AGENTS.md` (Laravel Boost guidelines) for the canonical PHP/Laravel/Livewire/Pest conventions. This file covers the parts specific to *this* application that the Boost guidelines don't.

## Commands

```bash
composer run dev          # Run server + queue listener + vite concurrently (primary dev loop)
npm run dev               # Vite only (Boost: a frontend change not showing up usually means this isn't running)
npm run build             # Build assets (also fixes "Unable to locate file in Vite manifest")

php artisan test --compact                       # Run the suite (Pest)
php artisan test --compact --filter=KeluargaImport   # Run one test file/case by name
vendor/bin/pint --dirty --format agent           # Format changed PHP before finalizing (required)
```

Tests run on in-memory SQLite (`phpunit.xml`); production/dev uses the DB configured in `.env`.

## Domain

This is a church congregation-management app. The four core entities use Indonesian names and are the center of everything:

- **Umat** (`umat` table) — individual congregation members. Owns most fields (`nama_lengkap`, `hub_kk` = relation to head-of-household, `jenis_kelamin`, etc.) and `belongsTo` Area, Kemah, and Keluarga (all nullable — the dashboard tracks "unassigned" counts).
- **Keluarga** (`keluarga`) — family unit, keyed by `no_keluarga`; `hasMany` Umat.
- **Area** (`area`) and **Kemah** (`kemah`) — geographic/grouping lookups; each `hasMany` Umat.

Field and column names are Indonesian. Match that convention when extending models.

## Architecture notes

- **Pages are Livewire v4 single-file components**, not controllers or Folio. View files are named with a `⚡` prefix (e.g. `resources/views/pages/keluarga/⚡index.blade.php`) and combine the `Component` class and Blade markup in one file. They are wired in `routes/web.php` via `Route::livewire('keluarga', 'pages::keluarga.index')`. Each list page owns its own search/pagination/CRUD-modal state. The only plain controller is `KeluargaPdfController`.
- **Models declare mass-assignable fields with the `#[Fillable([...])]` attribute** (Laravel 13 style) and set an explicit `protected $table` (tables are singular Indonesian names, not Laravel's pluralized default).
- **UI is Flux UI (free) + Tailwind v4.** Auth/2FA is Laravel Fortify (see `app/Actions/Fortify`, `FortifyServiceProvider`).
- **Excel import is hand-rolled** in `app/Services/KeluargaExcelImporter.php`: it unzips the `.xlsx` with `ZipArchive` and parses the sheet XML with `SimpleXMLElement` directly — there is no PhpSpreadsheet dependency. Import runs in a DB transaction and upserts Keluarga + Umat.
- **PDF generation** uses `barryvdh/laravel-dompdf` (`app/Services/KeluargaCardPdf.php`), exposed at `keluarga/{keluarga}/pdf`.
- **Localization** supports `id` and `en`. `SetLocale` middleware (appended to the web group in `bootstrap/app.php`) reads the locale from the session; `POST preferences.locale` sets it. Strings live in `lang/{id,en}.json`.
- **App defaults** in `AppServiceProvider::configureDefaults()`: CarbonImmutable dates, destructive DB commands blocked in production, and strong password rules enforced only in production.
