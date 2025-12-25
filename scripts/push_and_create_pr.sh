#!/usr/bin/env bash
set -eo pipefail

# Usage: ./scripts/push_and_create_pr.sh [branch] [base]
# Example: ./scripts/push_and_create_pr.sh chore/stabilize-dashboard-agenda-tests main

BRANCH="${1:-chore/stabilize-dashboard-agenda-tests}"
BASE="${2:-main}"
PR_TITLE="chore: Stabilize dashboard, add Agenda & tests"
PR_BODY_FILE="PR_DESCRIPTION.md"

# Ensure we're in a git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Not a git repository. Run this from the project's root." >&2
  exit 2
fi

# Get remote URL
REMOTE_URL=$(git remote get-url origin 2>/dev/null || true)
if [ -z "$REMOTE_URL" ]; then
  echo "No remote 'origin' configured. Please set a remote and try again." >&2
  exit 2
fi

# Normalize owner/repo from remote URL
if [[ "$REMOTE_URL" =~ github.com(:|/)(.+)(\.git)?$ ]]; then
  OWNER_REPO="${BASH_REMATCH[2]}"
else
  echo "Couldn't parse owner/repo from remote URL: $REMOTE_URL" >&2
  echo "You can still push the branch and create the PR manually: https://github.com/<owner>/<repo>/pull/new/$BRANCH" 
  git push -u origin "$BRANCH"
  exit 0
fi

# Push branch
echo "Pushing branch '$BRANCH' to origin..."
git push -u origin "$BRANCH"

# If gh CLI exists, use it
if command -v gh >/dev/null 2>&1; then
  echo "gh CLI detected — creating PR..."
  if [ -f "$PR_BODY_FILE" ]; then
    gh pr create --base "$BASE" --head "$BRANCH" --title "$PR_TITLE" --body-file "$PR_BODY_FILE" || true
  else
    gh pr create --base "$BASE" --head "$BRANCH" --title "$PR_TITLE" --body "Auto PR: $PR_TITLE" || true
  fi
  echo "Done. If gh created the PR, it should be open in your default browser." 
  exit 0
fi

# If GITHUB_TOKEN is present, use the REST API as fallback
if [ -n "${GITHUB_TOKEN:-}" ]; then
  echo "GITHUB_TOKEN detected — creating PR via GitHub API..."
  API_URL="https://api.github.com/repos/$OWNER_REPO/pulls"
  BODY="{\"title\": \"$PR_TITLE\", \"head\": \"$BRANCH\", \"base\": \"$BASE\", \"body\": \"$(cat $PR_BODY_FILE 2>/dev/null || echo "Auto PR: $PR_TITLE")\"}"
  curl -s -X POST -H "Authorization: token $GITHUB_TOKEN" -H "Accept: application/vnd.github+json" "$API_URL" -d "$BODY" | jq -r '.html_url // .message' || true
  echo "Done. PR should be created if the token has correct scopes (repo)."
  exit 0
fi

# Fallback: print URL to create a PR manually
PR_URL="https://github.com/$OWNER_REPO/pull/new/$BRANCH"
echo "Neither gh CLI nor GITHUB_TOKEN available. Open the URL below to create the PR manually:"
echo "$PR_URL"

exit 0
