#!/bin/sh
# Cloud-init скрипт для развёртывания Sportida на Timeweb Cloud

set -e

DOMAIN="sportida.ru"
DB_NAME="sportida"
DB_USER="sportida"
DB_PASS="$(openssl rand -base64 32)"
APP_KEY="$(openssl rand -base64 32)"

exec > >(tee /var/log/cloud-init-sportida.log)
exec 2>&1

echo "=== Начало установки Sportida === $(date)"

# Обновление системы
apt-get update
apt-get upgrade -y

# Установка зависимостей
apt-get install -y \
    git unzip nginx postgresql-15 redis-server certbot python3-certbot-nginx

# PHP 8.2
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y \
    php8.2-fpm php8.2-pgsql php8.2-redis php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd

# PostgreSQL
systemctl start postgresql
systemctl enable postgresql

sudo -u postgres psql << EOF
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASS';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
EOF

# Копировуем Laravel проект
mkdir -p /var/www/sportida
chown -R www-data:www-data /var/www/sportida

# Загружаем с GitHub
cd /var/www/sportida
sudo -u www-data git clone https://github.com/abbyasovmr/sportida-backend.git .

# ENV
cat > /var/www/sportida/.env << EOF
APP_NAME=Sportida
APP_ENV=production
APP_KEY=base64:$APP_KEY
APP_DEBUG=false
APP_URL=https://$DOMAIN

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
EOF

chown www-data:www-data /var/www/sportida/.env

# Composer
sudo -u www-data composer install --no-dev --no-interaction
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache

# Nginx
cat > /etc/nginx/sites-available/sportida << 'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/sportida/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/sportida /etc/nginx/sites-enabled/
pm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx

# Права
chown -R www-data:www-data /var/www/sportida
chmod -R 775 /var/www/sportida/storage

# Firewall
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Credentials
echo "DB_PASS=$DB_PASS" > /root/.credentials
chmod 600 /root/.credentials

echo "=== Готово! IP: $(curl -s ifconfig.me) ==="
