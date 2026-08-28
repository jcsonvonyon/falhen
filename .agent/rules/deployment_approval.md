# Rule: Mandatory Confirmation Before Deployment / Push to cPanel and GitHub

## Core Rule
ALWAYS ask the user for explicit confirmation before pushing any code, files, or updates to:
1. **cPanel Live Server** (`falhen.com` via SSH/SCP)
2. **GitHub Repository** (`https://github.com/henadinnovate/falhen.git` or git push)

## Workflow Requirements
- Complete local modifications, syntax checking, and linting first.
- Present a clear summary of changes to the user.
- Explicitly ask: *"Would you like me to push these updates to cPanel live server and GitHub now?"*
- Wait for explicit user confirmation (`Yes` / `Approved` / `Proceed`) BEFORE running `scp`, `ssh`, or `git push` deployment commands.
