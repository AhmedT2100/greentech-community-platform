# GreenTech Community Platform

GreenTechHub is a PHP and MySQL community web application focused on sustainable technology. This repository preserves the integrated application and its standalone module variants, with environment-based configuration and reproducible third-party dependency setup.

**Status:** source prerelease preparation. Syntax and schema checks pass; complete application workflows are not yet verified. See [validation](docs/VALIDATION.md) before running a deployment.

## Features present in the source

- Account registration and login, account locking, administration, and email-related account recovery/2FA endpoints.
- Event management, reservations, reservation status, capacity, and profit calculations.
- Articles with comments, replies, likes, saved articles, reporting, and a separate article administration interface.
- Innovation and category CRUD, voting/rating, history, and exports.
- Complaints with administration responses and email notification templates.
- User metrics dashboards, session/event metrics, and report exports.
- A PHP chatbot proxy for an OpenAI-compatible local LM Studio server.

These are implemented modules, not a guarantee that every integration works together. In particular, account modules use inconsistent table names and session conventions.

## Technologies and architecture

The server uses plain PHP, PDO/MySQL, sessions, cURL, and PHPMailer through Composer. HTML, CSS, and browser JavaScript provide the interface. Legacy Bootstrap 4, jQuery, Ionicons, Isotope, Lightbox, Owl Carousel, Superfish, TouchSwipe, Waypoints, and CounterUp assets are restored from pinned npm dependencies.

There is no application framework, frontend compiler, or production binary build. Most top-level PHP files are HTTP endpoints. Articles, categories, innovations, and the standalone event module also contain controllers, models, and views. Compatibility config files load the shared connection factory in `config/Database.php`.

```text
config/                  Private environment bootstrap and shared PDO factory
public/                  Web document root: landing pages, APIs, CSS, JS, images
  articles/              Article MVC backend, pages, and BackOffice
  categorie/             Category models, controller, and views
  innovation/            Innovation models, controller, and views
  accounts/                  Original account CRUD screens
  event-reservations/    Preserved standalone event/reservation application
  FrontOffice/           Preserved alternative article frontend
  html-business-template/ Original template source and attribution
  legacy/                Alternative root authentication and browser script
  vendor/                Generated browser dependencies; ignored
  uploads/               Local runtime media; ignored
scripts/                 Asset restoration, structure check, setup and publication
database/               Schema-only SQL and historical schema alternatives
docs/                   Validation, original guides, attribution, release notes
vendor/                  Composer dependencies; ignored
```


## Prerequisites

- PHP 8.2 or newer with `pdo`, `pdo_mysql`, `curl`, and `mbstring`, plus the usual Composer runtime extensions.
- MySQL 8 or MariaDB. The main schema was imported successfully on MariaDB 11.8.8.
- Composer 2, Node.js 20+ with npm, and Python 3 for the structural checker.
- An SMTP account for email workflows; a local LM Studio service and loaded model for the chatbot.

## Configuration and setup

From the repository root:

```sh
cp .env.example .env
composer install
npm ci
npm run assets
```

Edit `.env` privately. The application reads it outside the web root; exported environment variables take precedence. Values containing spaces or special characters should be quoted. Do not commit `.env`.

| Settings | Purpose |
| --- | --- |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Shared MySQL connection; create a dedicated local database user |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD` | SMTP transport using STARTTLS |
| `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` | Sender identity; use an address authorized by the provider |
| `LM_STUDIO_API`, `LM_STUDIO_MODEL` | Local chat-completions endpoint and loaded model |
| `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_FULL_NAME` | CLI administrator provisioning; leave empty unless provisioning |

Create an empty `greentech` database and a user with access to it, then import:

```sh
mysql -u your_database_user -p greentech < database/schema.sql
```

`database/schema.sql` is the broadest supplied export (18 tables), with all row data removed. Do not import every SQL file: `database/legacy/` contains alternative historical schemas, and `database/innovation-tables.sql` is an optional module-only setup. Existing migration scripts are retained for reference; review their target tables before running them. For example, `public/migrate_2fa_reset.php` references `users`, while the primary schema defines `utilisateur`. It is not a verified upgrade path.

To provision the article administrator, set the `ADMIN_*` variables privately, then run:

```sh
php public/articles/create_admin.php
```

This creates an article `admins` account. It does not reconcile the separate `utilisateur` login system. The helper runs only from CLI, requires a password of at least 12 characters, and never prints credentials. `php public/update_user_admin.php` can promote an existing `utilisateur` account selected by `ADMIN_EMAIL`.

## Run and validation

```sh
php -S 127.0.0.1:8080 -t public
```

Open `http://127.0.0.1:8080/index.html`. Other entry points include `events.html`, `reclamations.html`, `articles/index.php`, `innovation/index.php`, and `admin.html` under that origin. Serve only `public/`, so `.env`, database exports, and Composer dependencies are outside the document root. The built-in server is for local development.

There is no compilation step. `npm run assets` copies downloaded dependencies into the legacy resource paths and creates upload directories. Browser assets and Composer dependencies must be installed on a fresh checkout.

```sh
composer validate
find public config scripts -name '*.php' -not -path '*/vendor/*' -exec php -l {} \;
find public -name '*.js' -not -path '*/vendor/*' -exec node --check {} \;
python3 scripts/check-structure.py
npm audit
```

The structure checker reports literal references only. `--strict` fails on all missing references; `--check` fails when a missing reference is absent from the documented baseline. There is no automated integration test suite. The supplied `test_*.php` files are manual diagnostics, not unit tests.

## Known limitations

- Account schema/session conventions differ between modules; 2FA/reset, metrics migrations, and complete cross-module flows need integration work and testing.
- Twenty-one static references are missing in the original source: touch icons, language scripts, `auth.js`, and legal pages. See [the exact list](docs/structural-check.json).
- SMTP delivery, LM Studio, browser interactions, and authenticated CRUD flows have not been validated end to end. Several email links still assume localhost and require review before deployment.
- The asset restore uses available pinned package releases; Superfish, Owl Carousel, and hoverIntent differ slightly from the bundled versions and need browser regression testing.
- The inherited UI dependencies are old; npm reports two moderate findings affecting Bootstrap and jQuery. No breaking library upgrade was attempted.
- Database dumps contain no example users or articles. Runtime uploads and reports are excluded; a new checkout starts without historical user media.
- Legacy setup, reset, diagnostic, and migration endpoints remain in the source. This is not a hardened production deployment. Review endpoint authorization and restrict maintenance tools before hosting publicly.
- Web-based SMTP credential editing is intentionally retired. Configure the environment instead.

## Screenshots and attribution

No verified application screenshot was supplied. The image below is the existing **upstream template feature preview**, not a screenshot of GreenTechHub.

![Original HTML Codex template feature preview](public/html-business-template.jpg)

The original HTML Codex template license and readme are preserved in [docs/template-license.txt](docs/template-license.txt) and [docs/template-readme.txt](docs/template-readme.txt), with their existing credit links in source. Other preserved template copies retain their notices. Third-party packages retain their own licenses when installed. No new project-wide license has been assigned, and no contributor identities have been inferred from filenames or account records.

Original French implementation guides are in [docs/legacy](docs/legacy); they describe historical configurations and may be obsolete. Current setup instructions are those above.
