# Placeholder pour thèmes personnalisés

Ce répertoire existe uniquement comme placeholder pour que l'arborescence soit présente dans Git et pour éviter des erreurs de type "directory does not exist" (ex. dans des outils de linting).

## Usage

- Si aucun thème n'est encore implémenté ici, ce dossier sert de conteneur neutre.
- Pour créer un thème Drupal 11 personnalisé :
  1. Créez un sous-dossier, par exemple `mon_theme`.
  2. Ajoutez un fichier `mon_theme.info.yml` avec les métadonnées du thème.
  3. Organisez les assets (`templates/`, `css/`, `js/`, etc.) selon les bonnes pratiques de theming Drupal.
  4. Activez le thème via l'interface ou Drush.

## Versionnement Git

Git n'inclut pas les dossiers vides par défaut. Ce README permet de conserver ce dossier dans le dépôt. On peut aussi utiliser un fichier `.gitkeep` (ou un `.gitignore` avec une exception) si besoin pour signaler explicitement la présence du répertoire.

Exemple de `.gitignore` minimal pour garder le dossier tout en n'ayant rien d'autre dedans :
