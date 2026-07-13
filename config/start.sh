#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# PHP 8.4 con extensiones (iconv, mbstring requeridas por Symfony polyfill).
# El binario base php-8.4.x SIN extensiones falla con "undefined function iconv".
# Si el hash del store cambia, se busca automáticamente el primer candidato disponible.
# ---------------------------------------------------------------------------
PHP=""
for candidate in \
  /nix/store/1r2r2i8ssk0wi58d6vxhzlric5bn6p7y-php-with-extensions-8.4.10/bin/php \
  /nix/store/3knlminkvgxzh7wx7mb84s35rxd2331j-php-with-extensions-8.4.10/bin/php \
  /nix/store/4rsnfk2p9b431pxmskacvr70yqqm5ir6-php-with-extensions-8.4.10/bin/php \
  /nix/store/7xqcjznzaym8d662wz65mbdy137rxj92-php-with-extensions-8.4.10/bin/php; do
  if [ -x "$candidate" ]; then
    PHP="$candidate"
    break
  fi
done
if [ -z "$PHP" ]; then
  echo "[start.sh] ERROR: no se encontró php-with-extensions-8.4. Revisa el Nix store." >&2
  exit 1
fi
echo "[start.sh] PHP: $PHP"

COMPOSER=""
for composer_candidate in \
  /nix/store/7q9xlgf2z9xjydyiz6ppn9i44vb6znib-composer-2.7.7/bin/composer; do
  if [ -x "$composer_candidate" ]; then
    COMPOSER="$composer_candidate"
    break
  fi
done
if [ -z "$COMPOSER" ]; then
  # Fallback: search the Nix store dynamically
  COMPOSER=$(find /nix/store -maxdepth 2 -name "composer" -path "*/bin/composer" 2>/dev/null | head -1)
fi
if [ -z "$COMPOSER" ]; then
  echo "[start.sh] ERROR: no se encontró composer en el Nix store." >&2
  exit 1
fi
echo "[start.sh] Composer: $COMPOSER"

# ---------------------------------------------------------------------------
# Actualizar APP_URL con el dominio actual de Replit (tolerante a .env ausente)
# ---------------------------------------------------------------------------
if [ -n "$REPLIT_DEV_DOMAIN" ]; then
  APP_URL="https://${REPLIT_DEV_DOMAIN}"
  ENV_FILE="/home/runner/workspace/santarosa-laravel/.env"
  # Crear .env si no existe (workspace limpio / reset)
  if [ ! -f "$ENV_FILE" ] && [ -f "${ENV_FILE}.example" ]; then
    cp "${ENV_FILE}.example" "$ENV_FILE"
    echo "[start.sh] .env creado desde .env.example"
  fi
  if [ -f "$ENV_FILE" ]; then
    if grep -q "^APP_URL=" "$ENV_FILE"; then
      sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" "$ENV_FILE"
    else
      echo "APP_URL=${APP_URL}" >> "$ENV_FILE"
    fi
    echo "[start.sh] APP_URL set to ${APP_URL}"

    # En producción (deploy real): forzar modo seguro y sin debug
    if [ "${REPLIT_DEPLOYMENT:-0}" = "1" ]; then
      sed -i "s|^APP_ENV=.*|APP_ENV=production|" "$ENV_FILE"
      sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" "$ENV_FILE"
      sed -i "s|^LOG_LEVEL=.*|LOG_LEVEL=error|" "$ENV_FILE"
      echo "[start.sh] Producción detectada: APP_ENV=production, APP_DEBUG=false, LOG_LEVEL=error"
    fi
  fi

  # ── Inyectar credenciales PostgreSQL desde el entorno de Replit ──────────
  # Replit provee $PGHOST, $PGPORT, $PGDATABASE, $PGUSER, $PGPASSWORD
  # automáticamente. Los escribimos en .env para que Laravel los use.
  if [ -n "$PGHOST" ] && [ -f "$ENV_FILE" ]; then
    _pg_set() {
      local KEY="$1" VAL="$2"
      if grep -q "^${KEY}=" "$ENV_FILE"; then
        sed -i "s|^${KEY}=.*|${KEY}=${VAL}|" "$ENV_FILE"
      else
        echo "${KEY}=${VAL}" >> "$ENV_FILE"
      fi
    }
    _pg_set DB_CONNECTION pgsql
    _pg_set DB_HOST       "${PGHOST}"
    _pg_set DB_PORT       "${PGPORT:-5432}"
    _pg_set DB_DATABASE   "${PGDATABASE}"
    _pg_set DB_USERNAME   "${PGUSER}"
    _pg_set DB_PASSWORD   "${PGPASSWORD}"
    # Eliminar la línea de SQLite si quedara del .env anterior
    sed -i '/^DB_DATABASE=.*\.sqlite/d' "$ENV_FILE" 2>/dev/null || true
    echo "[start.sh] Credenciales PostgreSQL inyectadas (host: ${PGHOST}, db: ${PGDATABASE})"
  fi
fi

cd /home/runner/workspace/santarosa-laravel

# ---------------------------------------------------------------------------
# Instalar dependencias si vendor/ no existe
# ---------------------------------------------------------------------------
if [ ! -f "vendor/autoload.php" ]; then
  echo "[start.sh] Instalando dependencias composer..."
  $PHP $COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
  echo "[start.sh] Composer install done."
fi

# ---------------------------------------------------------------------------
# Directorios de storage y bootstrap/cache
# ---------------------------------------------------------------------------
mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ---------------------------------------------------------------------------
# Generar APP_KEY si está vacía (primer arranque o workspace reseteado)
# ---------------------------------------------------------------------------
if grep -q "^APP_KEY=$\|^APP_KEY=\"\"" .env 2>/dev/null; then
  echo "[start.sh] Generando APP_KEY..."
  $PHP artisan key:generate --force --quiet
fi

# ---------------------------------------------------------------------------
# Migraciones PostgreSQL
# ---------------------------------------------------------------------------
# Si la tabla de migraciones no existe aún, ejecutar migrate (+ seed en dev)
if ! $PHP artisan migrate:status --quiet 2>/dev/null; then
  if [ "${REPLIT_DEPLOYMENT:-0}" = "1" ]; then
    echo "[start.sh] Ejecutando migraciones (producción, sin seeders)..."
    if ! $PHP artisan migrate --force 2>&1; then
      echo "[start.sh] ERROR: las migraciones fallaron. Revisa los logs arriba." >&2
      exit 1
    fi
  else
    echo "[start.sh] Ejecutando migraciones y seeders (desarrollo)..."
    if ! $PHP artisan migrate --seed --force 2>&1; then
      echo "[start.sh] ERROR: las migraciones fallaron. Revisa los logs arriba." >&2
      exit 1
    fi
  fi
else
  # DB ya existe: aplicar migraciones pendientes (sin seeders siempre)
  $PHP artisan migrate --force --quiet 2>/dev/null || true
fi

# Enlace público de storage (fotos de perfil, CVs, materiales, etc.)
if [ ! -e public/storage ]; then
  $PHP artisan storage:link --quiet 2>/dev/null || true
fi

# Regenerar bootstrap/cache para que artisan y las rutas funcionen
$PHP artisan optimize --quiet 2>/dev/null || true

echo "[start.sh] Iniciando PHP built-in server en puerto 3000..."
exec $PHP -S 0.0.0.0:3000 -t public public/router.php
