#!/bin/sh

# Fonction pour afficher un message d'erreur et quitter
error_exit() {
    echo "[ERREUR] $1" 1>&2
    exit 1
}

echo "[INFO] Vérification de la connexion à la base de données..."
until php -r "new PDO('mysql:host=$DB_HOST;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
    echo "[INFO] Attente de la base de données..."
    sleep 5
done

echo "[INFO] Exécution des migrations..."
php artisan migrate --force || error_exit "Échec de l'exécution des migrations. Vérifie la base de données."

echo "[INFO] Démarrage de PHP-FPM..."
php-fpm || error_exit "Échec du démarrage de PHP-FPM." &

echo "[INFO] Démarrage de Nginx..."
exec nginx -g "daemon off;" || error_exit "Échec du démarrage de Nginx."
