# Imagem base
FROM php:8.2-apache

# Instalar dependências do sistema PRIMEIRO
RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões — agora com a biblioteca do SQLite disponível
RUN docker-php-ext-install pdo pdo_sqlite

# Habilitar rewrite
RUN a2enmod rewrite

# Copiar arquivos do projeto
COPY . /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

EXPOSE 80