#!/usr/bin/env bash
# Run from the prepared Git checkout after authenticating gh as AhmedT2100.
set -euo pipefail
script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
cd "$script_dir/.."
[[ "$(git rev-parse --show-toplevel)" == "$PWD" ]] || { echo 'Run from the prepared Git checkout.' >&2; exit 1; }
command -v gh >/dev/null || { echo 'Install GitHub CLI and run gh auth login.' >&2; exit 1; }
[[ "$(gh api user --jq .login)" == AhmedT2100 ]] || { echo 'Authenticate as AhmedT2100.' >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo 'Commit or resolve local changes first.' >&2; exit 1; }
[[ "$(git branch --show-current)" == prepare/public-source ]] || { echo 'Use the prepared prepare/public-source branch.' >&2; exit 1; }
repo=AhmedT2100/greentech-community-platform
if gh repo view "$repo" >/dev/null 2>&1; then
  echo 'Target already exists; refusing to overwrite or repurpose it.' >&2
  exit 1
fi
# Creation is atomic: if the name is unavailable or permission is denied, stop.
gh repo create "$repo" --public --description 'GreenTechHub PHP/MySQL community platform for sustainable innovation, articles, events and reservations' --source . --remote origin
git push origin main
git push -u origin prepare/public-source
pr_url=$(gh pr create --repo "$repo" --base main --head prepare/public-source --title 'Prepare GreenTechHub for public source development' --body-file docs/PULL_REQUEST.md)
echo "$pr_url"
head_sha=$(git rev-parse HEAD)
# Wait for GitHub to register the workflow before waiting on its checks.
found=false
for attempt in {1..30}; do
  count=$(gh run list --repo "$repo" --commit "$head_sha" --json databaseId --jq length)
  if [[ "$count" -gt 0 ]]; then found=true; break; fi
  sleep 5
done
[[ "$found" == true ]] || { echo 'Workflow not registered; inspect the PR before merging.' >&2; exit 1; }
gh pr checks "$pr_url" --watch --interval 10
gh pr merge "$pr_url" --squash --match-head-commit "$head_sha"
git fetch origin main
git switch main
git merge --ff-only origin/main
final_sha=$(git rev-parse HEAD)
remote_sha=$(gh api "repos/$repo/git/ref/heads/main" --jq .object.sha)
[[ "$final_sha" == "$remote_sha" ]] || { echo 'Main SHA mismatch; stopping before release.' >&2; exit 1; }
tag=v0.1.0-alpha.1
git tag -a "$tag" "$final_sha" -m 'Initial source prerelease'
git push origin "refs/tags/$tag"
gh release create "$tag" --repo "$repo" --verify-tag --target "$final_sha" --prerelease --title 'v0.1.0-alpha.1 — initial source prerelease' --notes-file docs/releases/v0.1.0-alpha.1.md
echo "Repository: https://github.com/$repo"
echo "Pull request: $pr_url"
echo "Release: https://github.com/$repo/releases/tag/$tag"
echo "Released main commit: $final_sha"
