#!/usr/bin/env bash
set -Eeuo pipefail

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-${APP_URL:-http://127.0.0.1:8080}}"
MAINTENANCE_ACTIVE=false

restore_application() {
    status=$? # Preserve the deployment failure status while attempting recovery.
    if [ "$MAINTENANCE_ACTIVE" = true ]; then
        php artisan up || echo "Gagal mengaktifkan kembali aplikasi; jalankan php artisan up secara manual." >&2 # Avoid leaving the site in maintenance after a failed deploy.
    fi
    exit "$status" # Report the original error to the operator.
}
trap restore_application EXIT INT TERM

php artisan down # Keep requests away while code, assets, and schema are being updated.
MAINTENANCE_ACTIVE=true # Let the exit trap restore service if any later step fails.

git pull --ff-only origin "$DEPLOY_BRANCH" # Refuse a non-fast-forward update instead of silently replacing server changes.

composer install --no-dev --optimize-autoloader --no-interaction # Install only locked production dependencies for the new release.
npm ci # Recreate frontend dependencies exactly from the lockfile.
npm run build # Generate the production assets required by Laravel.

php artisan migrate --force # Apply compatible schema changes in a non-interactive deployment.
php artisan optimize:clear # Remove caches compiled from the previous release before rebuilding them.
php artisan config:cache # Compile configuration for efficient production reads.
php artisan route:cache # Compile route definitions for efficient production dispatch.
php artisan view:cache # Compile Blade templates before serving requests.
php artisan event:cache # Compile event/listener discovery for production.

php artisan queue:restart # Signal long-running workers to reload the newly deployed code.

php artisan up # Restore public traffic after the code, schema, caches, and worker signal are ready.
MAINTENANCE_ACTIVE=false # Prevent the recovery trap from repeating the successful maintenance exit.
curl --fail --silent --show-error --retry 5 --retry-connrefused --retry-delay 2 \
    "${HEALTHCHECK_URL%/}/up" >/dev/null # Fail visibly if the app does not recover after maintenance mode ends.

trap - EXIT INT TERM # Disable the recovery trap after all deployment checks pass.
echo "Deployment berhasil; health check lulus: ${HEALTHCHECK_URL%/}/up"
