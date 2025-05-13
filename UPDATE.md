# Procédure de mise à jour

Ce plugin utilise la librairie [plugin-wordpress-config](https://github.com/YahnisElsts/plugin-update-checker) pour proposer la mise à jour automatique aux utilisateurs depuis l'interface de configuration des extensions de Wordpress.

Pour procéder à une montée de version de ce plugin, il est nécessaire d'effectuer les étapes suivantes afin de proposer la mise à jour automatique aux utilisateurs :

### 1. Modifier la version dans l'entête du fichier principal (`multi-wordpress-config.php`) :
```
/**
 * Plugin Name: Multi Wordpress Config
 * ...
 * Version: x.y.z
 * ...
 */
```

### 2. Mettre à jour le fichier `readme.txt` :
* Changer la ligne `Stable tag:` pour qu'il corresponde à la nouvelle version
* Ajouter les nouvelles fonctionnalités dans la section `== Changelog ==` :
```
== Changelog ==
= 0.3.0 =
* Liste des nouvelles fonctionnalités
* Corrections de bugs
* Autres changements importants

= 0.2.0 =
* Version précédente
...
```

### 3. Pousser les changements sur GitHub :
```
git add .
git commit -m "Version x.y.z"
git push origin main
```

### 4. Créer un tag GIT pour la nouvelle version :
```
git tag x.y.z
git push origin x.y.z
```

### 5. Créer une nouvelle release sur GitHub :
* Allez sur la page des releases de votre dépôt GitHub
* Cliquez sur "Draft a new release"
* Sélectionnez le tag que vous venez de créer
* Ajoutez un titre et une description pour la release
* Cliquez sur "Publish release"
