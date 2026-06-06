#!/bin/bash
set -e

# Configuration
PROJECT_ROOT="/home/crekolda/creinit"
BACKEND_DIR="$PROJECT_ROOT/Backend"
FRONTEND_DIR="$PROJECT_ROOT/Frontend"

echo "🚀 Démarrage du déploiement E-CRE - $(date)"

# 1. Mise à jour du code
echo "📥 Récupération des derniers changements (Git)..."
git fetch origin main
git reset --hard origin/main
git clean -fd

# 2. Backend (Laravel)
echo "🐘 Mise à jour du Backend (Laravel)..."
cd "$BACKEND_DIR"

# Installation des dépendances Composer
echo "📦 Installation des dépendances Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs

# Nettoyage des caches existants
php artisan optimize:clear

# Migration de la base de données et lien de stockage
echo "🗄️  Exécution des migrations et création du lien de stockage..."
php artisan migrate --force
php artisan storage:link || true # Ne pas planter si le lien existe déjà

# Optimisation de Laravel
echo "✨ Optimisation du cache Laravel..."
php artisan optimize

# 3. Frontend (Vue.js / Vite)
echo "🎨 Mise à jour du Frontend (Vite)..."
cd "$FRONTEND_DIR"

# Suppression de l'ancien build et du fichier hot pour forcer le mode production
echo "🧹 Nettoyage de l'ancien build..."
rm -rf "$BACKEND_DIR/public/build"
rm -f "$BACKEND_DIR/public/hot"

# Installation des dépendances NPM
echo "📦 Installation des dépendances NPM..."
npm install

# Build du frontend
echo "🛠️  Compilation des assets frontend..."
npm run build

echo "✅ Déploiement terminé avec succès ! - $(date)"
