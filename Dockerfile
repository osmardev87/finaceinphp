# Imagem oficial do PHP com suporte a PDO e SQLite
FROM php:8.2-apache

# Instalar extensões necessárias
RUN docker-php-ext-install pdo pdo_sqlite

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar todos os arquivos do projeto para dentro do container
COPY . /var/www/html/

# Definir permissões (importante para o SQLite criar o arquivo)
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Porta que vai ser usada
EXPOSE 80