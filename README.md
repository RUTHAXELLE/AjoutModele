# Ajout Marque/Modèle

Application PHP/MySQL de gestion de demandes d'ajout de marque/modèle de véhicule (espace client + espace admin).

## Installation

1. Démarrer Apache et MySQL dans XAMPP.
2. Importer la base de données :
   - Via phpMyAdmin : créer/importer le fichier `sql/ajoutmodele.sql`
   - Ou en ligne de commande : `mysql -u root --default-character-set=utf8mb4 < sql/ajoutmodele.sql`
3. Vérifier les identifiants MySQL dans `config/config.php` (par défaut root sans mot de passe, standard XAMPP).
4. Ouvrir `http://localhost/AJOUT-MODELE/`

`config/config.php` détecte automatiquement l'environnement : sans la variable d'env `DATABASE_URL`, il se connecte au MySQL local (XAMPP) ; avec `DATABASE_URL` définie (cas de Render), il se connecte au PostgreSQL de production. Aucune modification de code n'est nécessaire entre les deux environnements.

## Compte admin par défaut

- Email : `it@emu-ci.com`
- Mot de passe : `Admin@123`

À changer/à remplacer une fois connecté (aucune interface de changement de mot de passe n'est fournie pour l'instant — modifiable directement en base si besoin).

## Fonctionnement

- **Client** : connexion → "Ajouter Marque/Modèle" (formulaire de demande) et "Consultation" (suivi de ses demandes + relance).
- **Admin** : "Demandes" (prise en compte / traiter / rejeter avec motif obligatoire — traiter et rejeter envoient un email au client) et "Comptes clients" (création de comptes clients, aucune auto-inscription).

## Emails

L'envoi utilise la fonction PHP `mail()`. Sur un environnement XAMPP local, cela nécessite de configurer un serveur SMTP (ex: dans `php.ini` / `sendmail.ini`, ou un outil comme Mailtrap/Mailpit) pour que les emails partent réellement. Sans configuration, les actions fonctionnent normalement mais l'envoi d'email échoue silencieusement. Sur Render, `mail()` ne fonctionnera pas non plus sans configuration SMTP externe — à traiter séparément.

## Déploiement sur Render

L'application se déploie sur Render via Docker (PHP n'est pas un runtime natif Render), avec une base **PostgreSQL** managée (Render ne propose pas de MySQL managé). Le code détecte l'environnement automatiquement (voir plus haut) — pas besoin de modifier `config/config.php`.

### 1. Créer la base PostgreSQL sur Render

1. Dashboard Render → **New** → **PostgreSQL**
2. Une fois créée, noter l'**Internal Database URL** (utilisée par le service web) et l'**External Database URL** (pour l'import initial depuis ta machine)

### 2. Importer le schéma

Depuis ta machine (nécessite `psql`, fourni avec PostgreSQL) :

```
psql "URL_EXTERNE_DE_LA_BASE" -f sql/postgres_schema.sql
```

Cela crée les tables, charge les 1117 marques et le compte admin par défaut (`it@emu-ci.com` / `Admin@123`).

### 3. Créer le Web Service

1. Dashboard Render → **New** → **Web Service** → connecter le repo Git du projet
2. **Environment** : `Docker` (le `Dockerfile` à la racine est détecté automatiquement)
3. Variables d'environnement à définir :
   - `DATABASE_URL` = Internal Database URL de l'étape 1 (relie automatiquement l'app à Postgres)
   - `APP_BASE_URL` = *(laisser vide)* — l'app est servie à la racine du domaine Render, pas dans un sous-dossier
   - `MAIL_FROM`, `MAIL_FROM_NAME` *(optionnel)*
4. Déployer. Render fournit un port via la variable `PORT`, déjà géré par le `Dockerfile`.

### 4. Vérifier

Ouvrir l'URL fournie par Render (`https://ton-app.onrender.com`) et se connecter avec le compte admin par défaut. **Changer le mot de passe admin en base dès que possible** (aucune interface de changement de mot de passe n'existe encore).

Le projet n'a pas encore été testé en conditions réelles sur Render (pas de Docker/PostgreSQL disponible en local pour valider) — à tester après le premier déploiement, notamment la connexion à la base et le rendu des accents (UTF-8).
