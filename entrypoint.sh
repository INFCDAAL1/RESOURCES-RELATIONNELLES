#!/bin/sh

# Fonction pour afficher un message d'erreur et quitter le script
error_exit() {
    echo "$1" 1>&2
    exit 1
}

# Vérifier la connexion à la base de données
echo "Vérification de la connexion à la base de données..."
until php -r "new PDO('mysql:host=$DB_HOST;dbname=$DB_DATABASE', '$DB_USER', '$DB_PASSWORD');" 2>/dev/null; do
    echo "Attente de la base de données..."
    sleep 5
done

# Exécuter les migrations
echo "Exécution des migrations..."
if ! php artisan migrate --force; then
    error_exit "Échec de l'exécution des migrations. Veuillez vérifier la base de données."
fi

# Démarrer Nginx et PHP-FPM
echo "Démarrage de Nginx et PHP-FPM..."
if ! sh -c nginx; then
    error_exit "Échec du démarrage de Nginx."
fi

if ! php-fpm; then
    error_exit "Échec du démarrage de PHP-FPM."
fi
