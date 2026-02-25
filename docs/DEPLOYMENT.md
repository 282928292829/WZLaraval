# Deployment Setup

## GitHub Actions Secrets

Add these in **Settings → Secrets and variables → Actions**:

| Secret | Description |
|--------|-------------|
| `SSH_HOST` | Server IP or hostname |
| `SSH_USER` | SSH user (e.g. `root` or `deploy`) |
| `SSH_PRIVATE_KEY` | Full private key content (PEM format) |
| `DEPLOY_PATH` | Path on server (e.g. `/var/www/wasetzon`) |

## Server Prerequisites

1. PHP 8.4, Composer, Node.js, MySQL, Nginx
2. Create deploy directory: `mkdir -p /var/www/wasetzon && chown -R www-data:www-data /var/www/wasetzon`
3. Create `.env` on server (copy from `.env.example`, fill in DB credentials, `APP_KEY`, etc.)
4. SSH key: Add the **public** key to `~/.ssh/authorized_keys` for `SSH_USER`

## First Deploy

Push to `main` or run **Actions → Deploy Laravel → Run workflow** manually.
