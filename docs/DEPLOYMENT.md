# Deployment Setup

## GitHub Actions Secrets

Add these in **Settings → Secrets and variables → Actions** (exact names, case-sensitive):

| Secret | Value |
|--------|-------|
| `SSH_HOST` | Server IP (e.g. `107.175.36.2`) |
| `SSH_USER` | `root` |
| `SSH_PRIVATE_KEY` | Full private key including `-----BEGIN` and `-----END` lines |
| `DEPLOY_PATH` | `/var/www/wasetzon` |

## Server Prerequisites

1. PHP 8.4, Composer, Node.js, Nginx, MySQL (or SQLite for testing)
2. Deploy directory: created automatically by workflow
3. SSH key: Add the deploy **public** key to `~/.ssh/authorized_keys` for `SSH_USER`
4. `.env`: Created automatically on first deploy from `.env.example` (uses SQLite by default)

## Deploy

Push to `main` or run **Actions → Deploy Laravel → Run workflow** manually.
