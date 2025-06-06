#!/bin/bash
echo "🏃‍♂️ Installation de Sports Analytics..."

command -v symfony >/dev/null 2>&1 || { echo "❌ Symfony CLI n'est pas installé. Installer depuis: https://symfony.com/download"; exit 1; }
command -v docker >/dev/null 2>&1 || { echo "❌ Docker n'est pas installé."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "❌ Node.js/NPM n'est pas installé."; exit 1; }

make install

echo "✅ Installation terminée !"
echo "🔧 Configure ton .env avec tes identifiants Strava"
echo "🚀 Lance avec: make start"
