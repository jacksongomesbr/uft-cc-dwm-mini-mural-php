# Imagem da aplicação: Apache com mod_php, a mesma combinação de
# servidor/apache.conf, com a extensão pdo_pgsql.
FROM php:8.4-apache

# A imagem oficial não traz o driver do PostgreSQL. O libpq-dev é a biblioteca
# do cliente PostgreSQL, que a extensão precisa para compilar.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Troca o site padrão do Apache pelo do Mini Mural. O ServerName global evita
# o aviso AH00558 a cada partida.
COPY docker/apache.conf /etc/apache2/sites-available/mini-mural.conf
RUN a2dissite 000-default && a2ensite mini-mural \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Só o que a aplicação usa em tempo de execução. O .dockerignore deixa de fora
# o config.php local, a documentação e o Git.
COPY public /var/www/mini-mural/public
COPY src /var/www/mini-mural/src
COPY docker/config.php /var/www/mini-mural/config.php
