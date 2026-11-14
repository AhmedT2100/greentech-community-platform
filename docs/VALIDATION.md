# Validation report

Date: 2026-11-14 (Africa/Tunis). This records commands actually executed during repository preparation. The release status is **source prerelease**, with application integration outstanding.

## Results

| Check | Result |
| --- | --- |
| PHP syntax | All 120 first-party `.php` files pass `php -n -l` on PHP 8.5.12 |
| JavaScript syntax | All 18 first-party `.js` files pass `node --check` |
| Composer resolution/install | PHPMailer 6.12.0 installed and `composer.lock` generated; temporary runtime required explicit exceptions for missing PDO/MySQL and mbstring |
| Composer manifest/lock validation | `composer validate` passes; warns that no project license is specified. `--strict` exits 1 for that warning; no license was invented to silence it |
| Composer advisory check during update | No security vulnerability advisories reported |
| Browser dependency installation | npm install succeeds; package-lock generated |
| Fresh checkout | Local Git clone, offline `npm ci`, asset restoration and structure regression check pass with no original vendor/upload directories |
| Browser assets | `npm run assets` succeeds after removing three original browser vendor trees; the clean-checkout test also verifies restoration of the event MVC vendor layout, proving files are restored rather than reused from bundled copies |
| npm audit | Two moderate findings, affecting Bootstrap and jQuery; not resolved by breaking upgrades |
| SQL import | `database/schema.sql` imports successfully into an isolated MariaDB 11.8.8 database; 18 tables, zero rows |
| Configuration | Process environment/default handling and SMTP values in both MailManager and email_helper match synthetic test configuration; no email sent |
| HTTP smoke checks | `/index.html` returns 200; `/articles/create_admin.php` returns 404 over HTTP; `/save_gmail_config.php` returns 410 |
| Static structure | 1,369 literal resource/include references checked; 21 inherited missing references recorded in `structural-check.json`; no missing literal PHP includes reported |
| Git whitespace review | The inherited source contains trailing whitespace; raw `git diff --check` reports it. No broad formatting rewrite was applied |
| Publication script | `bash -n scripts/publish-github.sh` passes |
| Public source review | Text reviewed for credential patterns, original credential literals checked without printing their values, SQL row statements removed, unique source image assets visually inspected; uploads and reports excluded |
| GitHub identity/repositories | Connector authenticates as AhmedT2100; accessible repositories inspected; descriptive new target does not repurpose any of them |
| GitHub publication | Publishing uses authenticated GitHub CLI after device login; see remote PR/check/release history for final publication results |

## Missing resources and inherited integration gaps

The 21 recorded references point to absent touch icons, `auth.js`, article language scripts, and terms/privacy pages. They are retained as explicit source limitations rather than replaced with fabricated functionality. Run `python3 scripts/check-structure.py --strict` to treat all of them as errors, or `--check` to reject newly missing references against this documented baseline.

The checker examines literal HTML/CSS references and common PHP include syntax. It cannot prove that dynamic paths, CSS class usage, JavaScript requests, remote CDN dependencies, or database-driven resource names work.

The primary schema uses `utilisateur`; some existing migrations/endpoints expect `users`. Article administrators have a separate `admins` table. Login/session conventions differ. The 2FA/reset and metrics scripts are not a verified migration chain. Some dashboard values/vote actions are placeholders in the supplied source. Existing code is retained without claiming those features are complete.

Complete login/signup, authorization, article/event/innovation/complaint CRUD, email delivery, reservation concurrency, metrics, file uploads, chatbot requests, and browser visual behavior remain untested. Historical alternative schemas were sanitized, but only `database/schema.sql` was imported for validation. No production deployment, executable build, or automated end-to-end test was performed. There is no compiled application build target.

## Tooling constraints

The host had no PHP or Composer commands. PHP CLI/common packages were downloaded and extracted into `/tmp`, and the official Composer installer was checked against its published SHA384 before execution. Downloading additional PHP extensions failed across the available package mirrors. Consequently, the PHP runtime lacked PDO/MySQL and mbstring; Composer installation explicitly used `--ignore-platform-req=ext-pdo --ignore-platform-req=ext-pdo_mysql --ignore-platform-req=ext-mbstring`. The README still requires those real runtime extensions. SQL was tested independently using the installed MariaDB server/client.

Docker access remained denied even after an approved attempt; no Docker validation is claimed. The MariaDB instance used a temporary data directory and Unix socket with networking disabled. Runtime setup and HTTP checks used isolated temporary processes and synthetic settings, not the original account data.

The GitHub Actions workflow is included. Remote run results are recorded on the pull request. It performs normal Composer platform checks, syntax checks, dependency restoration, and missing-reference regression checks on PHP 8.2. It does not establish end-to-end integration readiness.

## Credential handling

The supplied mail helpers contained a Gmail SMTP app password. Revoke/rotate that app password with its provider before reuse. An administrator bootstrap script also contained a fixed password; rotate any account that used it. Database exports contained account hashes and recovery-related data; if those original exports were previously shared, reset affected passwords and invalidate outstanding recovery/unlock tokens. Original credentials are not repeated in this report.

No usable Git history was supplied: the root `.git` directory was empty and protected. The new prepared repository therefore starts from reviewed clean commits, without publishing prior history. A private original backup is kept outside the publishable source at `/tmp/flow-tech-original-private.tar.gz`; do not upload it. Original runtime uploads remain ignored locally. Temporary backups are not guaranteed to persist across sessions.
