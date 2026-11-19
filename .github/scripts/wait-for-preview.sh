#!/usr/bin/env bash
#
# Waits for Laravel Cloud to finish deploying a commit to a preview
# environment, then prints the preview's environment ID and URL.
#
# Usage: wait-for-preview.sh <application name or ID> <commit sha>
#
# Needs the `cloud` CLI (laravel/cloud-cli) authenticated through
# LARAVEL_CLOUD_TOKEN, and jq. Writes env_id and url to $GITHUB_OUTPUT when
# that is set. Fails if the deployment fails or nothing shows up in time.

set -euo pipefail

application="${1:?application name or ID required}"
commit="${2:?commit sha required}"
timeout_seconds="${PREVIEW_TIMEOUT_SECONDS:-1200}"
poll_seconds="${PREVIEW_POLL_SECONDS:-15}"

deadline=$(( $(date +%s) + timeout_seconds ))

echo "Waiting for a Laravel Cloud preview of ${commit:0:7} in '${application}'..."

while true; do
    previews=$(cloud environment:list "$application" --json -n | jq -c '[.[] | select(.createdFromAutomation == true) | {id, url}]')

    while read -r preview; do
        env_id=$(jq -r '.id' <<< "$preview")
        url=$(jq -r '.url' <<< "$preview")

        deployment=$(cloud deployment:list "$env_id" --commit="$commit" --json -n \
            | jq -c --arg commit "$commit" '[.[] | select(.commitHash == $commit)] | sort_by(.startedAt // "") | last // empty')

        if [ -z "$deployment" ]; then
            continue
        fi

        status=$(jq -r '.status' <<< "$deployment")
        echo "Preview $env_id ($url): $status"

        case "$status" in
            deployment.succeeded)
                if [ -n "${GITHUB_OUTPUT:-}" ]; then
                    echo "env_id=$env_id" >> "$GITHUB_OUTPUT"
                    echo "url=$url" >> "$GITHUB_OUTPUT"
                fi
                echo "Preview is deployed at $url"
                exit 0
                ;;
            failed | cancelled | build.failed | deployment.failed)
                echo "::error::Laravel Cloud preview deployment ended as $status: $(jq -r '.failureReason // "no reason given"' <<< "$deployment")"
                exit 1
                ;;
        esac
    done < <(jq -c '.[]' <<< "$previews")

    if [ "$(date +%s)" -ge "$deadline" ]; then
        echo "::error::No finished Laravel Cloud preview deployment of $commit after ${timeout_seconds}s"
        exit 1
    fi

    sleep "$poll_seconds"
done
