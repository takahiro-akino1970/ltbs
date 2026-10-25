FROM node:22-bookworm-slim AS frontend

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

RUN groupmod -g ${LOCAL_GID} node \
    && usermod -u ${LOCAL_UID} -g ${LOCAL_GID} node \
    && mkdir -p /app/node_modules \
    && chown -R node:node /app

WORKDIR /app
USER node

CMD ["sh", "-c", "npm ci && npm run dev"]

FROM php:8.3-fpm-bookworm AS app

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libfcgi-bin libicu-dev libonig-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) bcmath intl mbstring pcntl pdo_mysql pdo_sqlite zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -g ${LOCAL_GID} app \
    && useradd -m -u ${LOCAL_UID} -g app app \
    && mkdir -p /app/vendor /app/storage/framework/cache/data /app/storage/framework/cache/locks \
        /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs \
        /app/bootstrap/cache /shared/business-files \
    && chown -R app:app /app \
    && chown app:app /shared/business-files

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --chmod=755 entrypoint.sh /usr/local/bin/ltbs-entrypoint
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-ltbs.conf

WORKDIR /app
USER app

EXPOSE 9000

ENTRYPOINT ["ltbs-entrypoint"]
CMD ["php-fpm", "-F"]

FROM mariadb:11.4 AS mariadb

RUN ln -s /usr/bin/mariadb /usr/local/bin/mysql

FROM node:22-bookworm-slim AS frontend-production

WORKDIR /app

COPY src/package.json src/package-lock.json ./
RUN npm ci

COPY src/ ./
RUN npm run build

FROM php:8.3-fpm-bookworm AS app-production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfcgi-bin libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) bcmath intl mbstring pcntl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --system --gid 10001 app \
    && useradd --system --uid 10001 --gid app --home-dir /app --no-create-home app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-ltbs.conf

WORKDIR /app
COPY --chown=app:app src/ ./
COPY --from=frontend-production --chown=app:app /app/public/build /app/public/build

RUN mkdir -p /app/storage/framework/cache/data /app/storage/framework/cache/locks \
        /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs \
        /app/bootstrap/cache /app/vendor /shared/business-files \
    && chown -R app:app /app/storage /app/bootstrap/cache /app/vendor \
    && chown app:app /app /shared/business-files

USER app
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM nginxinc/nginx-unprivileged:stable-alpine AS web-production

COPY docker/nginx/production.conf.template /etc/nginx/templates/default.conf.template
COPY --from=frontend-production /app/public /app/public

EXPOSE 8080
