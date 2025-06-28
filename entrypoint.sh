#!/bin/sh

# Fonction pour afficher un message d'erreur et quitter
error_exit() {
    echo "[ERREUR] $1" 1>&2
    exit 1
}

if [ "$APP_DEBUG" = "true" ]; then
    echo "[INFO] Mode développement activé."
   echo "[INFO] Installation des dépendances avec Composer..."
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   mv composer.phar /usr/local/bin/composer
    composer install --no-interaction --optimize-autoloader || error_exit "Échec de l'installation des dépendances avec Composer."
fi


echo "[INFO] Vérification de la connexion à la base de données..."
until php -r "new PDO('mysql:host=$DB_HOST;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
    echo "[INFO] Attente de la base de données..."
    sleep 5
done

echo "[INFO] Exécution des migrations..."
php artisan migrate --force || error_exit "Échec de l'exécution des migrations. Vérifiez la base de données."

if [ "$APP_DEBUG" = "true" ]; then
    echo "[INFO] Exécution des seeders..."
    php artisan db:seed --force || error_exit "Échec de l'exécution des seeders. Vérifiez la base de données."
    echo "[INFO] Nettoyage du cache de configuration..."
    php artisan config:cache || error_exit "Échec du nettoyage du cache de configuration."
    echo "[INFO] Nettoyage du cache des routes..."
    php artisan route:cache || error_exit "Échec du nettoyage du cache des routes."
    # echo "[INFO] Nettoyage du cache des vues..."
    # php artisan view:cache || error_exit "Échec du nettoyage du cache des vues."
    echo "[INFO] Nettoyage du cache des événements..."
    php artisan event:cache || error_exit "Échec du nettoyage du cache des événements."
    echo "[INFO] Lancement de l'application en mode développement..."
    php artisan serve --host="0.0.0.0" --port="80" || error_exit "Échec du démarrage du serveur de développement."
    exit 0
else
    echo "[INFO] Lancement de l'application en mode production..."

    echo "[INFO] Démarrage de PHP-FPM..."
    php-fpm || error_exit "Échec du démarrage de PHP-FPM." &

    echo "[INFO] Démarrage de Nginx..."
    exec nginx -g "daemon off;" || error_exit "Échec du démarrage de Nginx."
fi
