#######################################
# STAGE 1 — DEPENDÊNCIAS (Composer)
#######################################
FROM composer:2 AS vendor

WORKDIR /app

# Copiar apenas ficheiros necessários para o composer
COPY composer.json composer.lock ./

# Instalar dependências (sem dev, sem scripts, sem cache)
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --no-scripts

#######################################
# STAGE 2 — IMAGEM FINAL (FrankenPHP)
#######################################
FROM dunglas/frankenphp:php8.3-alpine

# Instalar extensões PHP (SEM mudar PHP)
RUN install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    intl \
    zip \
    opcache

# Diretório da app
WORKDIR /app

# Copiar código da aplicação
COPY . .

# Copiar vendor pronto do stage anterior
COPY --from=vendor /app/vendor ./vendor

# Caddyfile (atenção ao path!)
COPY deployment/Caddyfile /etc/frankenphp/Caddyfile

# Permissões Laravel
RUN chown -R www-data:www-data /app \
 && chmod -R 755 /app/storage /app/bootstrap/cache

EXPOSE 80
