# Projet RESOURCES-RELATIONNELLES

[![Laravel Test](https://github.com/INFCDAAL1/RESOURCES-RELATIONNELLES/actions/workflows/laravel.yml/badge.svg)](https://github.com/INFCDAAL1/RESOURCES-RELATIONNELLES/actions/workflows/laravel.yml)

## Développement Local

Cette section vous guide pour lancer le projet sur votre machine locale à des fins de développement.

### Prérequis

#### IDE

Avoir un IDE qui supporte le devcontainer :

- [Visual Studio Code](https://code.visualstudio.com) avec l'extension [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)
- [PhpStorm](https://www.jetbrains.com/phpstorm/) avec l'extension [Dev Containers](https://www.jetbrains.com/help/phpstorm/dev-containers-starting-page.html)

#### Docker

Avoir [Docker](https://docs.docker.com/get-started/get-docker/) d'installé sur votre machine.

#### Git

Avoir [Git](https://git-scm.com/downloads) d'installé sur votre machine.

### Lancer le projet

1.  **Cloner le projet**
    ```sh
    git clone git@github.com:INFCDAAL1/RESOURCES-RELATIONNELLES.git
    ```

2.  **Ouvrir le projet dans votre IDE**

    *   **Si vous utilisez Visual Studio Code**:
        1.  Ouvrez le projet dans Visual Studio Code.
        2.  Cliquez sur "Reopen in Container" pour ouvrir le projet dans un conteneur Docker.
        > Si vous ne voyez pas cette option, ouvrez la palette de commandes (`Ctrl` + `Shift` + `P`) et tapez `Dev Container: Rebuild Container`.

    *   **Si vous utilisez PhpStorm**:
        1.  Ouvrez PhpStorm.
        2.  Allez dans le menu "Dev Containers" et sélectionnez "New Dev Container".
        3.  Choisissez "From Local Project".
        4.  Sélectionnez le fichier `devcontainer.json` situé dans le répertoire du projet.

3.  **Initialisation de la base de donnée**
    Une fois dans le devcontainer, faites la première migration avec :
    ```sh
    php artisan migrate:fresh
    ```

4.  **Démarrer le serveur Vite**
    Pour démarrer le serveur Vite, exécutez la commande suivante :
    ```sh
    npm run dev
    ```

5.  **Accéder à l'application**
    Ouvrez votre navigateur et accédez à l'URL suivante : <http://localhost:8000>

Vous êtes prêt à développer !

---

## Déploiement (Production & Dev Distant)

Cette section explique comment déployer l'application sur un serveur distant pour les environnements de production et de développement.

### Prérequis

1.  **Serveur distant**: Vous avez besoin d'un serveur (VPS, machine dédiée, etc.) avec une adresse IP publique et un accès SSH.
2.  **Docker & Docker Compose**: Installez Docker sur votre serveur.
    ```sh
    # Commande d'installation de Docker (exemple pour Debian/Ubuntu)
    sudo apt-get update
    sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    ```
    Pour des instructions détaillées, suivez le [guide officiel de Docker](https://docs.docker.com/engine/install/).
3.  **Nom de domaine**: Vous devez avoir un nom de domaine configuré pour pointer vers l'adresse IP de votre serveur. Pour ce projet, la configuration Traefik utilise Cloudflare pour le challenge DNS afin de générer les certificats SSL.

### Configuration

1.  **Cloner le projet**
    ```sh
    git clone git@github.com:INFCDAAL1/RESOURCES-RELATIONNELLES.git
    cd RESOURCES-RELATIONNELLES
    ```

2.  **Fichiers d'environnement**
    Vous devez créer les fichiers `.env.prod` et/ou `.env.dev` à partir de `.env.example`.

    *   **Pour la production :**
        ```sh
        cp .env.example .env.prod
        ```
    *   **Pour le développement distant :**
        ```sh
        cp .env.example .env.dev
        ```

3.  **Remplir les variables d'environnement**
    Modifiez les fichiers `.env.prod` et/ou `.env.dev` avec les bonnes valeurs. Les variables les plus importantes sont :
    *   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Les identifiants pour la base de données. Choisissez des mots de passe forts.
    *   `CF_API_EMAIL`: Votre adresse e-mail de compte Cloudflare.
    *   `CF_DNS_API_TOKEN`: Un token d'API Cloudflare avec les permissions de modification DNS.

### Personnalisation des noms de domaine

Les noms de domaine pour les environnements de développement et de production sont définis dans les fichiers `docker-compose.dev.yml` et `docker-compose.prod.yml`. Si vous souhaitez utiliser vos propres noms de domaine, vous devez modifier les labels Traefik correspondants.

**Pour l'environnement de développement (`docker-compose.dev.yml`):**

Modifiez les `rule` de routage en remplaçant `rr-dev.qalpuch.cc` et `rr-api-dev.qalpuch.cc` par vos noms de domaine :

```yaml
services:
  rr-front-dev:
    labels:
      - "traefik.http.routers.rr-front-dev.rule=Host(`VOTRE_FRONTEND_DEV_DOMAINE`)"
      # ...
  rr-api-dev:
    labels:
      - "traefik.http.routers.rr-api-dev.rule=Host(`VOTRE_API_DEV_DOMAINE`)"
      # ...
```

**Pour l'environnement de production (`docker-compose.prod.yml`):**

Modifiez les `rule` de routage en remplaçant `rr.qalpuch.cc` et `rr-api.qalpuch.cc` par vos noms de domaine :

```yaml
services:
  rr-front-prod:
    labels:
      - "traefik.http.routers.rr-front-prod.rule=Host(`VOTRE_FRONTEND_PROD_DOMAINE`)"
      # ...
  rr-api-prod:
    labels:
      - "traefik.http.routers.rr-api-prod.rule=Host(`VOTRE_API_PROD_DOMAINE`)"
      # ...
```

N'oubliez pas de configurer les enregistrements DNS pour vos nouveaux noms de domaine afin qu'ils pointent vers l'adresse IP de votre serveur.

### Lancement des services

Le lancement se fait en deux étapes : d'abord les services globaux, puis les services de l'environnement choisi.

1.  **Lancer les services globaux (Traefik & Watchtower)**
    Ces services sont nécessaires pour le routage et les mises à jour automatiques.
    ```sh
    docker-compose -f docker-compose.global.yml up -d
    ```

2.  **Lancer l'environnement**
    *   **Pour la production :**
        ```sh
        docker-compose -f docker-compose.prod.yml up -d
        ```
        L'application sera accessible à `https://rr.qalpuch.cc` et l'API à `https://rr-api.qalpuch.cc`.

    *   **Pour le développement distant :**
        ```sh
        docker-compose -f docker-compose.dev.yml up -d
        ```
        L'application sera accessible à `https://rr-dev.qalpuch.cc` et l'API à `https://rr-api-dev.qalpuch.cc`.

### Créer le premier administrateur

Une fois l'application déployée, vous devez créer un compte utilisateur via l'interface web, puis élever son rôle à "admin".

1.  **Inscrivez-vous** sur le site web de l'environnement que vous avez déployé (par exemple, `https://rr.qalpuch.cc`).

2.  **Connectez-vous au conteneur de la base de données** via SSH sur votre serveur.
    *   Pour la production : `docker exec -it rr-db-prod mariadb -p`
    *   Pour le développement : `docker exec -it rr-db-dev mariadb -p`
    
    On vous demandera le mot de passe root de la base de données, que vous avez défini dans votre fichier `.env.prod` ou `.env.dev` (`DB_PASSWORD`).

3.  **Mettez à jour le rôle de l'utilisateur**. Dans le shell MariaDB, exécutez la requête SQL suivante, en remplaçant `<EMAIL>` par l'adresse e-mail de l'utilisateur que vous venez de créer.

    ```sql
    USE resources_relationnelles;
    UPDATE users SET role = 'admin' WHERE email LIKE '<EMAIL>';
    exit
    ```
Votre utilisateur a maintenant les privilèges d'administrateur.

## Info

Voici la convention pour les commits :
<https://www.conventionalcommits.org/fr/v1.0.0/#sp%C3%A9cification>
