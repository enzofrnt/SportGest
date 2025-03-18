# Résumé des Endpoints API

## Information générale

- **Base URL**: `https://localhost:8000` (ou votre domaine de déploiement) - IMPORTANT: notez le HTTPS
- **Authentication**: La plupart des endpoints nécessitent un token JWT dans l'en-tête `Authorization: Bearer {votre_token}`
- **Content-Type**: `application/ld+json` pour toutes les requêtes avec body

## Endpoints pour les Utilisateurs/Authentification

- [x] POST /api/auth/login - Authentification des utilisateurs et retour d'un token JWT

  - **Accès**: Public (tous)
  - **Body**: `{ "email": "string", "password": "string" }`
  - **Response**: `{ "token": "string", "user": { "id": "number", "email": "string", "nom": "string", "prenom": "string", "roles": ["string"] } }`

- [x] POST /api/auth/register - Inscription d'un nouvel utilisateur (sportif)

  - **Accès**: Public (tous)
  - **Body**: `{ "email": "string", "password": "string", "nom": "string", "prenom": "string" }`
  - **Response**: `{ "message": "string", "user": { "id": "number", "email": "string", "nom": "string", "prenom": "string", "role": "string" } }`

- [x] GET /api/auth/user - Récupération des informations de l'utilisateur connecté

  - **Accès**: Utilisateur authentifié (tous)
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `{ "user": { "id": "number", "email": "string", "nom": "string", "prenom": "string", "roles": ["string"], "role": "string" } }`

- [x] PUT /api/auth/user - Mise à jour des informations personnelles
  - **Accès**: Utilisateur authentifié (tous)
  - **Headers**: `Authorization: Bearer {token}`
  - **Body**: `{ "nom": "string", "prenom": "string", "email": "string", "password": "string", "niveauSportif": "string" }`
  - **Response**: `{ "message": "string", "user": { "id": "number", "email": "string", "nom": "string", "prenom": "string", "roles": ["string"], "role": "string" } }`
  - **Note**: Tous les champs sont optionnels dans le body. Modification uniquement d'un sportif car accessible dans Angular uniquement par un sportif.

## Endpoints pour les Coachs

- [x] GET /api/coachs - Liste des coachs disponibles (information publique)

  - **Accès**: Public (tous)
  - **Response**: `[ { "id": "number", "nom": "string", "prenom": "string", "email": "string" }, ... ]`

- [x] GET /api/coachs/{id} - Détails d'un coach spécifique

  - **Accès**: Public (tous)
  - **Params**: `id` - ID du coach
  - **Response**: `{ "id": "number", "nom": "string", "prenom": "string", "email": "string" }`

- [x] GET /api/coachs/{id}/specialites - Liste des spécialités d'un coach

  - **Accès**: Public (tous)
  - **Params**: `id` - ID du coach
  - **Response**: `[ "specialite1", "specialite2", ... ]`

- [x] GET /api/coachs/{id}/seances - Liste des séances proposées par un coach
  - **Accès**: Public (tous)
  - **Params**: `id` - ID du coach
  - **Response**: `[ { "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "statut": "string", "niveauSeance": "string", "nbSportifs": "number" }, ... ]`

## Endpoints pour les Sportifs

- [x] GET /api/sportifs/{id} - Récupération des informations d'un sportif

  - **Accès**: Sportif (lui-même), Coach, Admin
  - **Params**: `id` - ID du sportif
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `{ "id": "number", "nom": "string", "prenom": "string", "email": "string", "niveauSportif": "string", "dateInscription": "string" }`
  - **Note**: Accessible uniquement par le sportif lui-même ou un coach/admin

- [x] GET /api/sportifs/{id}/seances - Liste des séances réservées par un sportif

  - **Accès**: Sportif (lui-même), Coach, Admin
  - **Params**: `id` - ID du sportif
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `[ { "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "statut": "string", "coach": { "id": "number", "nom": "string", "prenom": "string" } }, ... ]`

- [x] GET /api/sportifs/{id}/historique - Historique des entraînements validés
  - **Accès**: Sportif (lui-même), Coach, Admin
  - **Params**: `id` - ID du sportif
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `[ { "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "coach": { "id": "number", "nom": "string", "prenom": "string" } }, ... ]`

## Endpoints pour les Séances

- [x] GET /api/seances - Liste des séances disponibles

  - **Accès**: Public (tous)
  - **Query**: `type_seance` (optionnel), `niveau_seance` (optionnel), `date_min` (optionnel), `date_max` (optionnel), `statut` (optionnel)
  - **Response**: `[ { "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "statut": "string", "niveauSeance": "string", "coach": { "id": "number", "nom": "string", "prenom": "string" } }, ... ]`

- [x] GET /api/seances/{id} - Détails d'une séance spécifique

  - **Accès**: Public (tous)
  - **Params**: `id` - ID de la séance
  - **Response**: `{ "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "statut": "string", "niveauSeance": "string", "coach": { "id": "number", "nom": "string", "prenom": "string" }, "exercices": [...], "sportifs": [...] }`

- [ ] POST /api/seances - Création d'une nouvelle séance (pour les coachs)

  - **Accès**: Coach, Admin
  - **Headers**: `Authorization: Bearer {token}`
  - **Content-Type**: `application/ld+json`
  - **Body**: `{ "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "niveauSeance": "string", "dureeMinutes": "number", "capaciteMax": "number", "statut": "string", "coach": "string", "exercices": ["string"] }`
  - **Response**: `{ "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "statut": "string", "niveauSeance": "string" }`
  - **Note**: `exercices` est un tableau d'IRIs vers les exercices (ex: "/api/exercices/59"), `coach` doit être un IRI (ex: "/api/coaches/98"), et `statut` est généralement "prévue" pour une nouvelle séance
  - **Sécurité**: Accès restreint aux coachs et admins via le SeanceVoter.

- [ ] PUT /api/seances/{id} - Modification d'une séance existante

  - **Accès**: Coach (propriétaire de la séance), Admin
  - **Params**: `id` - ID de la séance
  - **Headers**: `Authorization: Bearer {token}`
  - **Body**: `{ "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "niveauSeance": "string", "dureeMinutes": "number", "capaciteMax": "number" }`
  - **Response**: `{ "id": "number", "themeSeance": "string", ... }`
  - **Note**: Tous les champs sont optionnels dans le body

- [ ] PATCH /api/seances/{id}/statut - Changement de statut d'une séance

  - **Accès**: Coach (propriétaire de la séance), Admin
  - **Params**: `id` - ID de la séance
  - **Headers**: `Authorization: Bearer {token}`
  - **Body**: `{ "statut": "string" }`
  - **Response**: `{ "id": "number", "themeSeance": "string", "statut": "string", ... }`
  - **Note**: Les valeurs acceptées pour `statut` sont: "PREVUE", "VALIDEE", "ANNULEE"

- [ ] GET /api/seances/creneaux-disponibles - Liste des créneaux horaires disponibles

  - **Accès**: Utilisateur authentifié (tous)
  - **Headers**: `Authorization: Bearer {token}`
  - **Query**: `date_debut` (optionnel, YYYY-MM-DD), `date_fin` (optionnel, YYYY-MM-DD), `coach_id` (optionnel)
  - **Response**: `[ { "date": "string", "creneaux": ["string"] }, ... ]`
  - **Note**: Sans paramètres, retourne les créneaux disponibles pour tous les coachs pour les 7 prochains jours

- [ ] GET /api/seances/disponibles - Liste des séances disponibles pour réservation

  - **Accès**: Utilisateur authentifié (tous)
  - **Headers**: `Authorization: Bearer {token}`
  - **Query**: `date_debut` (optionnel, YYYY-MM-DD), `date_fin` (optionnel, YYYY-MM-DD), `coach_id` (optionnel)
  - **Response**: `[ { "id": "number", "themeSeance": "string", "dateHeure": "string", "typeSeance": "string", "niveauSeance": "string", "coach": { ... }, "placesDisponibles": "number", ... }, ... ]`
  - **Note**: Retourne uniquement les séances ayant des places disponibles

- [ ] GET /api/seances/planning-disponibilites - Vue combinée (créneaux + séances disponibles)
  - **Accès**: Utilisateur authentifié (tous)
  - **Headers**: `Authorization: Bearer {token}`
  - **Query**: `date_debut` (optionnel, YYYY-MM-DD), `date_fin` (optionnel, YYYY-MM-DD), `coach_id` (optionnel)
  - **Response**: `{ "periode": { "debut": "string", "fin": "string" }, "creneaux_disponibles": [ ... ], "seances_disponibles": [ ... ], "filtre_coach": boolean }`
  - **Note**: Cet endpoint combine les deux précédents pour une vue complète des disponibilités

## Endpoints pour les Réservations

- [x] POST /api/reservations - Réservation d'une séance par un sportif

  - **Accès**: Sportif uniquement
  - **Headers**: `Authorization: Bearer {token}`
  - **Content-Type**: `application/json`
  - **Body**: `{ "seance_id": "number" }`
  - **Response**: `{ "message": "string", "reservation": { "id": "number", "seance": { "id": "number", "themeSeance": "string", ... } } }`
  - **Note**: Le paramètre `seance_id` doit être un nombre entier (ID de la séance), et non un IRI.
  - **Sécurité**: Accès restreint aux sportifs via le ReservationVoter. Les admins peuvent créer des réservations pour d'autres sportifs en ajoutant `sportif_id` au body.

- [x] DELETE /api/reservations/{id} - Annulation d'une réservation

  - **Accès**: Sportif (lui-même), Responsable, Admin
  - **Params**: `id` - ID de la séance dont on veut annuler la réservation
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `{ "message": "string" }`
  - **Note**: Un sportif ne peut annuler que ses propres réservations. Dans l'implémentation simplifiée actuelle, les responsables et admins peuvent uniquement annuler leurs propres réservations également. Une version plus avancée pourrait permettre aux admins d'annuler n'importe quelle réservation avec un paramètre ?sportif_id=X.

- [x] GET /api/reservations/sportif/{id} - Liste des réservations d'un sportif
  - **Accès**: Sportif (lui-même), Coach, Responsable, Admin
  - **Params**: `id` - ID du sportif
  - **Headers**: `Authorization: Bearer {token}`
  - **Response**: `[ { "id": "number", "seance": { "id": "number", "themeSeance": "string", ... } }, ... ]`
  - **Note**: Un sportif ne peut consulter que ses propres réservations. Les coachs, responsables et admins peuvent consulter les réservations de n'importe quel sportif.

## Endpoints pour les Exercices

- [x] GET /api/exercices - Liste de tous les exercices

  - **Accès**: Public (tous)
  - **Query**: `difficulte` (optionnel), `dureeEstimee[lte]` (optionnel, remplace duree_max)
  - **Response**: `[ { "id": "number", "nom": "string", "description": "string", "difficulte": "string", "dureeMinutes": "number" }, ... ]`
  - **Note**: Pour filtrer par durée maximale, utilisez `dureeEstimee[lte]=30` au lieu de `duree_max=30`
  - **Exemple utilisation** :
    - dureeEstimee[lte]=30 : exercices durant 30 minutes ou moins
    - dureeEstimee[gte]=10 : exercices durant 10 minutes ou plus
    - dureeEstimee[lt]=20 : exercices durant moins de 20 minutes
    - dureeEstimee[between]=10..30 : exercices durant entre 10 et 30 minutes

- [x] GET /api/exercices/{id} - Détails d'un exercice spécifique

  - **Params**: `id` - ID de l'exercice
  - **Response**: `{ "id": "number", "nom": "string", "description": "string", "difficulte": "string", "dureeMinutes": "number", "consignes": "string" }`

- [x] GET /api/seances/{id}/exercices - Liste des exercices pour une séance spécifique
  - **Params**: `id` - ID de la séance
  - **Response**: `[ { "id": "number", "nom": "string", "description": "string", "difficulte": "string", "dureeMinutes": "number" }, ... ]`
