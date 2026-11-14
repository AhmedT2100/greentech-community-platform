# Contributing

Start with the README and docs/VALIDATION.md. Use a dedicated branch and describe the problem, scope, and verification in a pull request.

Install dependencies from the lockfiles. Keep credentials in an ignored `.env` or the process environment. Do not submit database rows, user uploads, reports, dependency directories, caches, or local editor settings.

Preserve existing URLs, module behavior, template attribution, and third-party notices. When moving a file, update PHP includes, browser resources, redirects, uploads, and SQL/setup references. Prefer focused changes over broad rewrites.

Run PHP lint, JavaScript syntax checks, Composer validation, asset restoration, and the structural checker. Record existing failures separately from new failures. Exercise affected workflows against a disposable database; never send test email to real users. Do not mark an integration tested unless it was actually exercised.

There is no new project-wide license grant in this repository. Preserve existing notices and clarify reuse rights with the repository owner when needed.
