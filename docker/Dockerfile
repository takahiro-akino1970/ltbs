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

FROM php:8.4-fpm-bookworm AS app

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
    && mkdir -p /app/vendor \
    && chown -R app:app /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --chmod=755 entrypoint.sh /usr/local/bin/ltbs-entrypoint
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-ltbs.conf

WORKDIR /app
USER app

EXPOSE 9000

ENTRYPOINT ["ltbs-entrypoint"]
CMD ["php-fpm", "-F"]
