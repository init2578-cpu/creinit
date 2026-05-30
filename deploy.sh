#!/bin/bash

# Configuration
PROJECT_ROOT="/home/crekolda/creinit"
BACKEND_DIR="$PROJECT_ROOT/Backend"
FRONTEND_DIR="$PROJECT_ROOT/Frontend"

echo "🚀 Démarrage du déploiement E-CRE - $(date)"

# 1. Mise à jour du code
echo "📥 Récupération des derniers changements (Git)..."
git pull origin main

# 2. Backend (Laravel)
echo "🐘 Mise à jour du Backend (Laravel)..."
cd "$BACKEND_DIR"

# Installation des dépendances Composer (si nécessaire)
# composer install --no-interaction --prefer-dist --optimize-autoloader

# Migration de la base de données
echo "🗄️  Exécution des migrations..."
php artisan migrate --force

# Optimisation de Laravel
echo "✨ Optimisation du cache Laravel..."
php artisan optimize

# 3. Frontend (Vue.js / Vite)
echo "🎨 Mise à jour du Frontend (Vite)..."
cd "$FRONTEND_DIR"

# Installation des dépendances NPM (si nécessaire)
# npm install

# Build du frontend
echo "🛠️  Compilation des assets frontend..."
npm run build

echo "✅ Déploiement terminé avec succès ! - $(date)"
