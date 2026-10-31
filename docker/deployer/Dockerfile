FROM php:8.4-cli-bookworm

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -g ${LOCAL_GID} deployer \
    && useradd -m -u ${LOCAL_UID} -g deployer deployer

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /workspace/deploy
USER deployer

ENTRYPOINT ["php", "vendor/bin/dep"]
CMD ["list"]
