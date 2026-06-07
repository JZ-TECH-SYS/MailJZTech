# ============================================================
# MailJZTech — Dockerfile de PRODUCAO (monolito MVC)
# Base = Dockerfile padrao da JZ-TECH (PHP 8.2 FPM + Nginx, multi-stage)
# Extra: mariadb-client + gzip (feature de backup usa mysqldump via exec()).
# Envio de e-mail e via Gmail API (Workload Identity), nao por SMTP.
# ============================================================

# ---- Stage 1: Composer Install (build) ----
FROM php:8.2-cli-alpine AS composer-build

RUN apk add --no-cache \
        libxml2-dev libzip-dev icu-dev oniguruma-dev \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
        git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mysqli mbstring xml zip intl gd bcmath soap

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-progress --no-scripts

# ---- Stage 2: Runtime ----
FROM php:8.2-fpm-alpine AS runtime

# nginx + supervisor + extensoes PHP + mariadb-client/gzip (mysqldump do backup)
RUN apk add --no-cache \
        nginx supervisor \
        mariadb-client gzip \
        libxml2-dev libzip-dev icu-dev oniguruma-dev \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mysqli mbstring xml zip intl gd bcmath soap opcache \
    && rm -rf /var/cache/apk/*

# ---- Configs (PHP, FPM, supervisor, nginx) em 1 layer ----
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        '; JZ-TECH PHP Config' \
        'memory_limit = 256M' \
        'upload_max_filesize = 20M' \
        'post_max_size = 25M' \
        'max_execution_time = 120' \
        'max_input_time = 30' \
        'display_errors = Off' \
        'log_errors = On' \
        'error_log = /dev/stderr' \
        'date.timezone = America/Sao_Paulo' \
        'opcache.enable = 1' \
        'opcache.memory_consumption = 128' \
        'opcache.interned_strings_buffer = 16' \
        'opcache.max_accelerated_files = 10000' \
        'opcache.validate_timestamps = 0' \
        'opcache.revalidate_freq = 0' \
        'opcache.save_comments = 1' \
        'opcache.jit_buffer_size = 64M' \
        > /usr/local/etc/php/conf.d/99-jztech.ini \
    && printf '%s\n' \
        '[www]' \
        'access.log = /dev/stdout' \
        'catch_workers_output = yes' \
        'decorate_workers_output = no' \
        'pm = dynamic' \
        'pm.max_children = 15' \
        'pm.start_servers = 3' \
        'pm.min_spare_servers = 2' \
        'pm.max_spare_servers = 5' \
        'pm.max_requests = 500' \
        'ping.path = /fpm-ping' \
        'ping.response = pong' \
        'pm.status_path = /fpm-status' \
        > /usr/local/etc/php-fpm.d/zz-jztech.conf \
    && mkdir -p /etc/supervisor/conf.d \
    && printf '%s\n' \
        '[supervisord]' \
        'nodaemon=true' \
        'logfile=/dev/stdout' \
        'logfile_maxbytes=0' \
        'pidfile=/var/run/supervisord.pid' \
        'user=root' \
        '' \
        '[program:php-fpm]' \
        'command=php-fpm --nodaemonize' \
        'autostart=true' \
        'autorestart=true' \
        'stdout_logfile=/dev/stdout' \
        'stdout_logfile_maxbytes=0' \
        'stderr_logfile=/dev/stderr' \
        'stderr_logfile_maxbytes=0' \
        '' \
        '[program:nginx]' \
        'command=nginx -g "daemon off;"' \
        'autostart=true' \
        'autorestart=true' \
        'stdout_logfile=/dev/stdout' \
        'stdout_logfile_maxbytes=0' \
        'stderr_logfile=/dev/stderr' \
        'stderr_logfile_maxbytes=0' \
        > /etc/supervisor/conf.d/supervisord.conf \
    && printf '%s\n' \
        'server {' \
        '    listen 80 default_server;' \
        '    server_name _;' \
        '    root /var/www/html/public;' \
        '    index index.php;' \
        '    client_max_body_size 25M;' \
        '    add_header X-Frame-Options "SAMEORIGIN" always;' \
        '    add_header X-Content-Type-Options "nosniff" always;' \
        '    add_header Referrer-Policy "strict-origin-when-cross-origin" always;' \
        '    access_log /dev/stdout;' \
        '    error_log /dev/stderr warn;' \
        '    location = /nginx-health {' \
        '        access_log off;' \
        '        return 200 "ok\\n";' \
        '    }' \
        '    # Compatibilidade HostGator: /public/login -> request=login' \
        '    location /public/ {' \
        '        rewrite ^/public/(.*)$ /index.php?request=$1&$args last;' \
        '    }' \
        '    location / {' \
        '        try_files $uri $uri/ /index.php?request=$uri&$query_string;' \
        '    }' \
        '    location ~ \\.php$ {' \
        '        fastcgi_pass 127.0.0.1:9000;' \
        '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' \
        '        include fastcgi_params;' \
        '        fastcgi_read_timeout 120s;' \
        '        fastcgi_send_timeout 60s;' \
        '    }' \
        '    location ~ /\\.(env|git|htaccess) {' \
        '        deny all;' \
        '        return 404;' \
        '    }' \
        '    location ~ /vendor/ {' \
        '        deny all;' \
        '        return 404;' \
        '    }' \
        '}' \
        > /etc/nginx/http.d/default.conf \
    && mkdir -p /run/nginx /var/log/nginx /var/tmp/nginx

WORKDIR /var/www/html

# vendor vem do stage de build (composer)
COPY --from=composer-build /build/vendor ./vendor

# codigo da aplicacao (inclui .env via .dockerignore que NAO exclui .env)
COPY . .

RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/nginx-health || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
