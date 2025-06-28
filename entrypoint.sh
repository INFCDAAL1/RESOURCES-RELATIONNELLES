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
