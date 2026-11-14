# Publication handoff

Intended new public repository: `AhmedT2100/greentech-community-platform`.

The GitHub account was verified as AhmedT2100 and its repositories inspected. Repository and release creation use GitHub CLI, since those operations are unavailable in the connector. The CLI was installed from a checksum-verified official release and authenticated through GitHub device login. Consult the repository PR, checks, and release history for remote publication status.

The protected empty root `.git` directory prevents a conventional Git checkout in the workspace root. A clean checkout is prepared in `.publication/repository`, with a bootstrap `main` commit and the reviewed project on `prepare/public-source`. No original history is included. Use that checkout for publication; `.publication` itself is ignored by the source project.

For a new publication environment, install GitHub CLI and authenticate as AhmedT2100 with repository and workflow access:

```sh
cd .publication/repository
gh auth login
bash scripts/publish-github.sh
```

The script refuses an existing target name, creates a new public repository, pushes the bootstrap main and working branch, opens a PR using `docs/PULL_REQUEST.md`, waits for registered GitHub checks to pass, and squash-merges the PR using the reviewed branch SHA. It then checks that local main equals remote main, creates an annotated `v0.1.0-alpha.1` tag on that exact commit, and publishes a prerelease using the reviewed release notes. It uploads no executable.

If creation succeeds but a later step fails, inspect the existing repository and complete only the remaining steps. The script deliberately refuses to repurpose an existing repository and is not a blind retry script. A failing CI run must be resolved or documented before merge. The release must remain a prerelease until important integrations are verified.
