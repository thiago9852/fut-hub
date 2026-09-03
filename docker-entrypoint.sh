#!/bin/sh
set -e

PORT_TO_USE="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen ${PORT_TO_USE}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT_TO_USE}>/" /etc/apache2/sites-available/000-default.conf

# Garante que APP_SECRET tenha um valor caso nao tenha sido configurado no painel
export APP_SECRET="${APP_SECRET:-8edd7c65d6af80a0df5c6b27fa132658}"

# Tratamento da DATABASE_URL para PostgreSQL
if [ -n "$DATABASE_URL" ]; then
    # Converte postgres:// para postgresql://
    export DATABASE_URL=$(echo "$DATABASE_URL" | sed 's|^postgres://|postgresql://|')

    # Adiciona serverVersion=16 se for PostgreSQL e nao possuir versao informada
    case "$DATABASE_URL" in
        *postgresql://*|*postgres://*)
            case "$DATABASE_URL" in
                *serverVersion*) ;;
                *\?*) export DATABASE_URL="${DATABASE_URL}&serverVersion=16" ;;
                *) export DATABASE_URL="${DATABASE_URL}?serverVersion=16" ;;
            esac
            ;;
    esac
fi

# Propaga variaveis de ambiente para o Apache e PHP
echo "PassEnv DATABASE_URL APP_ENV APP_DEBUG APP_SECRET PORT" > /etc/apache2/conf-available/environment.conf
a2enconf environment > /dev/null 2>&1 || true

for var in DATABASE_URL APP_ENV APP_DEBUG APP_SECRET PORT; do
    eval val=\$$var
    if [ -n "$val" ]; then
        echo "export $var=\"$val\"" >> /etc/apache2/envvars
    fi
done

# Permissoes das pastas do Symfony
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads
chmod -R 775 /var/www/html/var /var/www/html/public/uploads

# Sincroniza tabelas no banco 
if [ -n "$DATABASE_URL" ] && [ "$DATABASE_URL" != "mysql://build:build@127.0.0.1:3306/build" ]; then
    echo ">> [Render Entrypoint] Conectando e sincronizando banco de dados..."
    php bin/console doctrine:schema:update --force --no-interaction || {
        echo ">> [Render Entrypoint] AVISO: Falha ao sincronizar tabelas com o banco de dados."
    }
    if [ "${LOAD_FIXTURES:-false}" = "true" ]; then
        echo ">> [Render Entrypoint] Criando usuário admin inicial..."
        php bin/console app:create-user admin@futebollocal.com admin123 --name="Administrador" --role="ROLE_ADMIN" || true
    fi
fi

# cache de produção
if [ "${APP_ENV:-prod}" = "prod" ]; then
    echo ">> [Render Entrypoint] Aquecendo cache do Symfony..."
    php bin/console cache:warmup --no-interaction || echo ">> [Render Entrypoint] Aviso: Cache warmup ignorado."
fi

echo ">> [Render Entrypoint] Iniciando Apache na porta ${PORT_TO_USE}..."
exec apache2-foreground
