#!/bin/sh

# Arrêt forcé du serveur existant
symfony server:stop || true

# Nettoyage des fichiers temporaires
rm -rf /root/.symfony5/log/*
rm -f /var/www/html/.symfony/server-*
rm -f /root/.symfony/server-*

# Démarrage du serveur
exec symfony server:start --port=8000 --allow-all-ip 