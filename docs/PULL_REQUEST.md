## Changes

Prepare GreenTechHub as a public source repository: retain the integrated PHP application and standalone modules under a public document root, centralize environment configuration, replace bundled dependencies with locked package setup, sanitize database exports, and preserve attribution.

Add English setup/architecture documentation, contribution guidance, a changelog, exact structural limitations, a validation workflow, and initial source prerelease notes. Disable source-code credential rewriting and restrict administrator provisioning to CLI.

## Validation

Locally verified: 120 PHP source files lint successfully on PHP 8.5.12; all 18 browser JavaScript files pass `node --check`; npm installation and asset restoration succeed from an empty browser vendor tree; Composer installation resolves PHPMailer, and Composer validation passes with the expected missing-license warning. The schema imports into MariaDB 11.8.8 with 18 tables and no rows.

See `docs/VALIDATION.md` for HTTP/configuration checks and precise limitations. Twenty-one inherited missing resource references are documented. npm reports two moderate vulnerabilities in legacy Bootstrap/jQuery. PDO/MySQL and mbstring were unavailable in the temporary PHP runtime, so installation used explicit platform exceptions and PHP/database integration remains unverified. SMTP delivery, chatbot service, authentication and browser CRUD workflows have not been tested end to end.

The GitHub workflow must pass before merging. This PR is suitable for an initial source prerelease, not a claim of production readiness. No new project-wide license is assigned; existing notices and credit links are retained.
