#!/bin/bash
set -e

# Script to manage GitHub secrets for deployment workflows
# This script helps to upload secrets from your secrets.yaml file to GitHub

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "GitHub CLI (gh) is not installed. Please install it first:"
    echo "  https://cli.github.com/manual/installation"
    exit 1
fi

# Check if yq is installed
if ! command -v yq &> /dev/null; then
    echo "yq is not installed. Please install it first:"
    echo "  brew install yq"
    exit 1
fi

# Check if user is authenticated with GitHub
if ! gh auth status &> /dev/null; then
    echo "You are not authenticated with GitHub CLI. Please run 'gh auth login' first."
    exit 1
fi

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE_DIR="$(dirname "$SCRIPT_DIR")"
SECRETS_FILE="$BASE_DIR/secrets.yaml"

# Check if secrets.yaml exists
if [ ! -f "$SECRETS_FILE" ]; then
    echo "Error: secrets.yaml file not found at $SECRETS_FILE"
    echo "Please copy the example file and fill in your values first:"
    echo "  cp .github/secrets.yaml.example .github/secrets.yaml"
    exit 1
fi

# Get repository from git config
REPO_URL=$(git -C "$BASE_DIR" config --get remote.origin.url)
REPO_PATH=$(echo "$REPO_URL" | sed -E 's/.*github.com[:/]([^/]+\/[^/]+)(\.git)?$/\1/')
# Ensure .git suffix is removed
REPO_PATH=$(echo "$REPO_PATH" | sed 's/\.git$//')

if [ -z "$REPO_PATH" ]; then
    echo "Could not determine GitHub repository. Please enter it manually (e.g., org/repo):"
    read -r REPO_PATH
fi

echo "Managing secrets for GitHub repository: $REPO_PATH"
echo "Using secrets file: $SECRETS_FILE"
echo

# Extract the SSH private key
SSH_PRIVATE_KEY=$(yq '.ssh.private_key' "$SECRETS_FILE")
if [ "$SSH_PRIVATE_KEY" != "null" ] && [ "$SSH_PRIVATE_KEY" != "" ]; then
    echo "Setting SSH_PRIVATE_KEY..."
    echo "$SSH_PRIVATE_KEY" | gh secret set SSH_PRIVATE_KEY --repo "$REPO_PATH"
else
    echo "SSH_PRIVATE_KEY not found in secrets.yaml"
fi

# Function to set GitHub secrets from YAML path
set_secrets_from_yaml_path() {
    local yaml_path="$1"
    local prefix="$2"

    # Get all keys at the specified path
    local keys
    keys=$(yq "$yaml_path | keys | .[]" "$SECRETS_FILE")

    if [ -z "$keys" ]; then
        echo "No secrets found at $yaml_path"
        return
    fi

    echo "Setting secrets from $yaml_path..."

    # For each key, get the value and set as GitHub secret
    while IFS= read -r key; do
        local value
        value=$(yq "$yaml_path.$key" "$SECRETS_FILE")

        if [ "$value" = "null" ] || [ -z "$value" ]; then
            echo "Warning: Empty value for $key, skipping"
            continue
        fi

        local secret_name="$prefix$key"
        echo "Setting $secret_name..."
        echo "$value" | gh secret set "$secret_name" --repo "$REPO_PATH"
    done <<< "$keys"
}

# Set production secrets
if yq -e '.deployment.production' "$SECRETS_FILE" &> /dev/null; then
    set_secrets_from_yaml_path '.deployment.production' ''
else
    echo "Production deployment configuration not found in secrets.yaml"
fi

# Set staging secrets
if yq -e '.deployment.staging' "$SECRETS_FILE" &> /dev/null; then
    set_secrets_from_yaml_path '.deployment.staging' ''
else
    echo "Staging deployment configuration not found in secrets.yaml"
    echo "⚠️  Note: Staging secrets are optional for production deployments"
fi

echo
echo "✅ Secrets have been set successfully!"
echo "You can verify them in your GitHub repository settings."
