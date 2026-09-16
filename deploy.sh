#!/bin/bash
#
# Script de déploiement staging — appelé par GitHub Actions
# Usage : ./deploy.sh
#
set -euo pipefail

cd /var/www/api.wiltek-software.online

echo "==> Pull des dernières modifications depuis Git..."
git fetch --prune
git reset --hard origin/develop
git clean -fd --exclude=docker-compose.staging.env --exclude=storage

echo "==> Vérification du fichier docker-compose.staging.env..."
if [ ! -f docker-compose.staging.env ]; then
    echo "ERREUR : docker-compose.staging.env manquant !"
    exit 1
fi

echo "==> Création de l'arborescence storage..."
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

sudo /bin/chown -R 33:33 storage
chmod -R 775 storage

echo "==> Construction de la nouvelle image Docker..."
docker compose -f docker-compose.staging.yml build --pull

echo "==> Redémarrage du conteneur..."
docker compose -f docker-compose.staging.yml \
    --env-file docker-compose.staging.env \
    up -d --remove-orphans

echo "==> Attente du démarrage..."
sleep 5

echo "==> Vérification de l'état du conteneur..."
if ! docker ps | grep -q wiltek-api-staging; then
    echo "ERREUR : le conteneur ne tourne pas !"
    docker logs --tail=50 wiltek-api-staging
    exit 1
fi

echo "==> Application des migrations..."
docker exec wiltek-api-staging php artisan migrate --force

echo "==> Nettoyage du cache Laravel..."
docker exec wiltek-api-staging php artisan config:clear || true
docker exec wiltek-api-staging php artisan route:clear || true
docker exec wiltek-api-staging php artisan view:clear || true

echo "==> Test de l'endpoint /api/health..."
if ! curl -sf http://127.0.0.1:8000/api/health > /dev/null; then
    echo "ERREUR : l'API ne répond pas sur /api/health !"
    docker logs --tail=50 wiltek-api-staging
    exit 1
fi

echo "==> Nettoyage des anciennes images Docker..."
docker image prune -f

echo "==> Déploiement réussi !"