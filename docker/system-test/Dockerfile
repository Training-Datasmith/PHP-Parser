FROM php:7.4.33-cli-bullseye@sha256:691f9ae2a3639de11d95f507bc29c723a7f27b79bdf91317aef8ded35f9864ce

RUN sed -i 's|deb.debian.org/debian|archive.debian.org/debian|g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' /etc/apt/sources.list \
    && sed -i '/bullseye-security/d' /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
