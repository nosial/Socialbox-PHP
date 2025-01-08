# -----------------------------------------------------------------------------
# Dockerfile for PHP 8.3 + FPM with Cron support and Supervisor
# -----------------------------------------------------------------------------

# Base image: Official PHP 8.3 with FPM
FROM php:8.3-fpm AS base

# ----------------------------- Metadata labels ------------------------------
LABEL maintainer="Netkas <netkas@n64.cc>" \
      version="1.0" \
      description="Socialbox Docker image based off PHP 8.3 FPM and NCC" \
      application="SocialBox" \
      base_image="php:8.3-fpm"

# Environment variable for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive

# ----------------------------- System Dependencies --------------------------
# Update system packages and install required dependencies in one step
RUN apt-get update -yqq && apt-get install -yqq --no-install-recommends \
    git \
    libpq-dev \
    libzip-dev \
    zip \
    make \
    wget \
    gnupg \
    cron \
    supervisor \
    mariadb-client \
    libcurl4-openssl-dev \
    libmemcached-dev \
    redis \
    libgd-dev \
    nginx \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------- PHP Extensions -------------------------------
# Install PHP extensions and enable additional ones
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    curl \
    opcache \
    zip \
    pcntl && \
    pecl install redis memcached && \
    docker-php-ext-enable redis memcached

# ----------------------------- Additional Tools -----------------------------
# Install Phive (Package Manager for PHAR libraries) and global tools in one step
RUN wget -O /usr/local/bin/phive https://phar.io/releases/phive.phar && \
    wget -O /usr/local/bin/phive.asc https://phar.io/releases/phive.phar.asc && \
    gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79 && \
    gpg --verify /usr/local/bin/phive.asc /usr/local/bin/phive && \
    chmod +x /usr/local/bin/phive && \
    phive install phpab --global --trust-gpg-keys 0x2A8299CE842DD38C

# ----------------------------- Clone and Build NCC --------------------------
# Clone the NCC repository, build the project, and install it
RUN git clone https://git.n64.cc/nosial/ncc.git && \
    cd ncc && \
    make redist && \
    NCC_DIR=$(find build/ -type d -name "ncc_*" | head -n 1) && \
    if [ -z "$NCC_DIR" ]; then \
      echo "NCC build directory not found"; \
      exit 1; \
    fi && \
    php "$NCC_DIR/INSTALL" --auto && \
    cd .. && rm -rf ncc

# ----------------------------- Project Build ---------------------------------
# Set build directory and copy pre-needed project files
WORKDIR /tmp/build
COPY . .

RUN ncc build --config release --build-source --log-level debug && \
    ncc package install --package=build/release/net.nosial.socialbox.ncc --build-source -y --log-level=debug

# Clean up
RUN rm -rf /tmp/build && rm -rf /var/www/html/*

# Copy over the required files
COPY nginx.conf /etc/nginx/nginx.conf
COPY public/index.php /var/www/html/index.php
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# ----------------------------- Cron Configuration ---------------------------
RUN echo "*/1 * * * * root for i in {1..12}; do /usr/bin/socialbox process-outgoing; sleep 5; done" > /etc/cron.d/socialbox-process-outgoing && \
    echo "*/1 * * * * root /usr/bin/socialbox session-cleanup" > /etc/cron.d/socialbox-session-cleanup && \
    echo "*/5 * * * * root /usr/bin/socialbox peer-cleanup" > /etc/cron.d/socialbox-peer-cleanup && \
    \
    chmod 0644 /etc/cron.d/socialbox-process-outgoing && \
    chmod 0644 /etc/cron.d/socialbox-session-cleanup && \
    chmod 0644 /etc/cron.d/socialbox-peer-cleanup && \
    \
    crontab /etc/cron.d/socialbox-process-outgoing && \
    crontab /etc/cron.d/socialbox-session-cleanup && \
    crontab /etc/cron.d/socialbox-peer-cleanup

# ----------------------------- Supervisor Configuration ---------------------
# Copy Supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ----------------------------- Cleanup ---------------------
WORKDIR /

# ----------------------------- Port Exposing ---------------------------------
EXPOSE 8085

# ----------------------------- Container Startup ----------------------------
# Copy over entrypoint script and set it as executable
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["/usr/bin/bash", "/usr/local/bin/entrypoint.sh"]
