#!/usr/bin/env bash
# deploy/server-setup.sh
# One-time server setup for Ubuntu 22.04 LTS
# Run as root: bash server-setup.sh
#
# Installs: PHP 8.3-FPM, Composer, Node 20 LTS, MySQL 8, Redis, nginx, Supervisor, certbot
# Creates: /var/www/wasetzon owned by www-data, deploy user

set -euo pipefail

DOMAIN="wasetzon.com"
DEPLOY_USER="deploy"
APP_DIR="/var/www/wasetzon"
DB_NAME="wasetzon"
DB_USER="wasetzon"

echo "========================================"
echo "  Wasetzon Server Setup — Ubuntu 22.04"
echo "========================================"

# ─── 1. System update ─────────────────────────────────────────────────────────
echo "→ Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget gnupg2 ca-certificates lsb-release \
    software-properties-common apt-transport-https \
    git unzip zip acl ufw fail2ban

# ─── 2. PHP 8.3 ───────────────────────────────────────────────────────────────
echo "→ Installing PHP 8.3..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-redis \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-gd \
    php8.3-opcache \
    php8.3-readline

# PHP-FPM tuning
cat > /etc/php/8.3/fpm/conf.d/99-wasetzon.ini <<'EOF'
; Performance
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.enable_cli=0

; Limits
upload_max_filesize=64M
post_max_size=64M
memory_limit=512M
max_execution_time=120
max_input_time=120

; Security
expose_php=Off
EOF

systemctl enable php8.3-fpm
systemctl restart php8.3-fpm
echo "  PHP $(php8.3 -r 'echo PHP_VERSION;') installed."

# ─── 3. Composer ──────────────────────────────────────────────────────────────
echo "→ Installing Composer..."
curl -sS https://getcomposer.org/installer | php8.3 -- --install-dir=/usr/local/bin --filename=composer
echo "  Composer $(composer --version --no-ansi | cut -d' ' -f3) installed."

# ─── 4. Node 20 LTS ───────────────────────────────────────────────────────────
echo "→ Installing Node 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y -qq nodejs
echo "  Node $(node --version) / npm $(npm --version) installed."

# ─── 5. MySQL 8 ───────────────────────────────────────────────────────────────
echo "→ Installing MySQL 8..."
apt-get install -y -qq mysql-server

# Generate a random password for the DB user
DB_PASS=$(openssl rand -base64 24)

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "  MySQL user: ${DB_USER} | database: ${DB_NAME}"
echo "  DB password saved to /root/.wasetzon-db-pass (store this securely!)"
echo "${DB_PASS}" > /root/.wasetzon-db-pass
chmod 600 /root/.wasetzon-db-pass

systemctl enable mysql

# ─── 6. Redis ─────────────────────────────────────────────────────────────────
echo "→ Installing Redis..."
apt-get install -y -qq redis-server

# Bind to localhost only, set memory limit
sed -i 's/^bind .*/bind 127.0.0.1 -::1/' /etc/redis/redis.conf
sed -i 's/^# maxmemory .*/maxmemory 256mb/' /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

systemctl enable redis-server
systemctl restart redis-server
echo "  Redis installed and bound to localhost."

# ─── 7. nginx ─────────────────────────────────────────────────────────────────
echo "→ Installing nginx..."
apt-get install -y -qq nginx

# nginx global tweaks
cat > /etc/nginx/conf.d/performance.conf <<'EOF'
# Worker / connection tuning
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    sendfile    on;
    tcp_nopush  on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;

    # Limit request rates
    limit_req_zone $binary_remote_addr zone=api:10m rate=30r/m;
}
EOF

systemctl enable nginx

# ─── 8. Supervisor ────────────────────────────────────────────────────────────
echo "→ Installing Supervisor..."
apt-get install -y -qq supervisor
systemctl enable supervisor
systemctl start supervisor

# ─── 9. certbot (Let's Encrypt) ───────────────────────────────────────────────
echo "→ Installing certbot..."
snap install --classic certbot 2>/dev/null || apt-get install -y -qq certbot python3-certbot-nginx
ln -sf /snap/bin/certbot /usr/bin/certbot 2>/dev/null || true

# ─── 10. UFW firewall ─────────────────────────────────────────────────────────
echo "→ Configuring UFW firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable
echo "  UFW enabled: SSH + HTTP(S) allowed."

# ─── 11. fail2ban ─────────────────────────────────────────────────────────────
echo "→ Configuring fail2ban..."
systemctl enable fail2ban
systemctl start fail2ban

# ─── 12. Deploy user + app directory ─────────────────────────────────────────
echo "→ Creating deploy user and app directory..."
id -u "${DEPLOY_USER}" &>/dev/null || useradd -m -s /bin/bash "${DEPLOY_USER}"
usermod -aG www-data "${DEPLOY_USER}"

mkdir -p "${APP_DIR}"
chown -R "${DEPLOY_USER}:www-data" "${APP_DIR}"
chmod -R 775 "${APP_DIR}"

echo "  App directory: ${APP_DIR}"
echo "  Owner: ${DEPLOY_USER}:www-data"

# ─── 13. Clone repo placeholder (manual step) ─────────────────────────────────
echo ""
echo "========================================"
echo "  Manual steps remaining:"
echo "========================================"
echo ""
echo "1. Add deploy SSH key to GitHub:"
echo "   sudo -u ${DEPLOY_USER} ssh-keygen -t ed25519 -C 'deploy@${DOMAIN}'"
echo "   cat /home/${DEPLOY_USER}/.ssh/id_ed25519.pub"
echo "   → Add this to GitHub repo → Settings → Deploy keys"
echo ""
echo "2. Clone the repo:"
echo "   sudo -u ${DEPLOY_USER} git clone git@github.com:YOUR_ORG/wasetzon.git ${APP_DIR}"
echo ""
echo "3. Create .env:"
echo "   cp ${APP_DIR}/deploy/.env.production.example ${APP_DIR}/.env"
echo "   nano ${APP_DIR}/.env"
echo "   → Set DB_PASSWORD=$(cat /root/.wasetzon-db-pass)"
echo "   → Set APP_KEY (will be generated in step 5)"
echo ""
echo "4. Copy nginx vhost:"
echo "   cp ${APP_DIR}/deploy/nginx.conf /etc/nginx/sites-available/${DOMAIN}"
echo "   ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/"
echo "   nginx -t && systemctl reload nginx"
echo ""
echo "5. Run first deploy:"
echo "   cd ${APP_DIR} && bash deploy/deploy.sh --fresh"
echo "   php8.3 artisan key:generate"
echo ""
echo "6. Issue SSL certificate:"
echo "   certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} --non-interactive --agree-tos -m admin@${DOMAIN}"
echo ""
echo "7. Copy Supervisor config:"
echo "   cp ${APP_DIR}/deploy/supervisor.conf /etc/supervisor/conf.d/wasetzon.conf"
echo "   supervisorctl reread && supervisorctl update"
echo ""
echo "8. Set up WordPress at old.${DOMAIN} (30-day fallback):"
echo "   → Point old.${DOMAIN} DNS A record to WordPress server IP"
echo "   → Or copy WordPress to this server under /var/www/old-wasetzon"
echo ""
echo "9. Switch DNS:"
echo "   → A record: ${DOMAIN} → this server's IP"
echo "   → Monitor error logs: tail -f /var/log/nginx/wasetzon.error.log"
echo ""
echo "DB password: $(cat /root/.wasetzon-db-pass)"
echo ""
echo "✓ Server setup complete!"
