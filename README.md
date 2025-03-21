# SportGest

Application de gestion de coaching sportif personnalisé.

## Prérequis

- Docker
- Docker Compose

## Installation

1. Cloner le repository :

```bash
git clone https://github.com/enzofrnt/SportGest.git
cd SportGest
```

2. Lancer l'environnement de développement :

```bash
cd deploiement-dev
docker compose up -d --build
```

3. Exécuter les migrations de la base de données :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

4. Charger les données de test (optionnel) :

```bash
docker compose exec php php bin/console doctrine:fixtures:load
```

## Structure du projet

- `symfony/` : Backend Symfony

  - `src/Controller/` : Contrôleurs de l'application
  - `public/app/` : Application Angular (frontend)
  - `config/` : Configuration Symfony
  - `templates/` : Templates Twig
  - `migrations/` : Migrations Doctrine

- `deploiement-dev/` : Configuration Docker pour le développement

## Accès à l'application

- Frontend : https://localhost:8000/app
- Backend API : https://localhost:8000/api
- Interface d'administration : https://localhost:8000/admin
- Adminer (gestion de la base de données) : https://localhost:8000/adminer

## Commandes utiles

```bash
# Redémarrer les conteneurs
docker compose restart

# Voir les logs
docker compose logs -f

# Accéder au shell PHP
docker compose exec php bash

# Vider le cache Symfony
docker compose exec php php bin/console cache:clear
```

## Note

Le build de angualr doit normalement se trouver dans le dossier `symfony/SportGest/public/app` s'il n'y est pas ajouter le à la mains.
Commande à utiliser pour le build de angular :

```bash
cd angular/SportGest
pnpm install
ng build --base-href /app/ --deploy-url /app/
```
