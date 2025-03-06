# Multi Wordpress Config

Plugin Wordpress de configuration personnalisée pour l'application Esup Multi :  https://github.com/univlorraine/esup-multi

## Description
Multi Wordpress Config transforme WordPress en back-office d'administration optimisé 
pour une architecture Headless. 
Il désactive les fonctionnalités orientées front-end de WordPress et met en place une structure de données personnalisée 
à l'aide du plugin Pods (https://pods.io/).

### Principales fonctionnalités
* Désactivation complète du front-end WordPress
* Désactivation des types de contenu par défaut de WordPress (posts, pages, commentaires)
* Suppression de la gestion des thèmes
* Création automatique de Custom Post Types (CPT) et Custom Fields via Pods
* Support des pods "Singleton" (instances uniques)
* Gestion avancée des traductions avec Polylang

## Prérequis
Ce plugin nécessite l'installation et l'activation préalable des plugins suivants :

* [Pods](https://wordpress.org/plugins/pods/) - Pour la gestion des types de contenu personnalisés
* [Polylang](https://fr.wordpress.org/plugins/polylang/) - Pour la gestion multilingue

## Installation
1. Téléchargez et décompressez le plugin dans votre répertoire /wp-content/plugins/ (ou importez l'archive .zip depuis le menu 'Extensions' de Wordpress)
2. Activez le plugin via le menu 'Extensions' dans WordPress
3. Les CPT configurés seront automatiquement créés lors de l'activation

## Configuration des Pods
Les définitions des Pods se trouvent dans le répertoire `/includes/pods/`. Chaque fichier correspond à un Pod (=une collection) et utilise le format suivant :

```php 
<?php
// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'exemple';

return [
    'name' => $pod_name,
    'label' => 'Exemple',
    'label_singular' => 'Exemple',
    'description' => 'Description de l\'exemple',
    'menu-position' => 10,
    'menu-icon' => 'dashicons-admin-generic',
    'singleton' => false,
    'title_field' => 'nom_du_champ',
    'groups' => [
        $pod_name . '_fields' => [
            'label' => 'Champs Exemple',
            'fields' => [
                $pod_name . '_champ1' => [
                    'type' => 'text',
                    'label' => 'Champ 1',
                    'description' => 'Description du champ 1',
                ],
                // Autres champs...
            ],
        ]
    ]
];
```

* **name** : nom système du Pod
* **label** : label du Pod affiché au pluriel
* **label_singular** : label du Pod affiché au singulier
* **description** : description du Pod
* **menu-position** : entier désignant la position du Pod dans le menu latéral gauche
* **menu-icon** : icône du Pod dans le menu latéral gauche
* **singleton** : si définit à `true` le Pod sera considéré comme Singleton
* **title_field** : permet de définir quel champ de Pod surchargera le champ titre par défaut de Wordpress
* **groups** : permet de grouper les champs personnalisés du Pod
  * **label** : label du groupe de champs
  * **fields** : définit les champs du Pod
    * **type** : type du champ (text, number, email, date, website, ...)
    * **label** : label du champ affiché dans le formulaire
    * **description** : description du champ affiché dans le formulaire

Pour plus d'informations concernant les champs et la configuration Pods disponibles : https://docs.pods.io/fields/

## Pods Singleton
Le plugin permet de définir des Pods en tant que "singleton", ce qui signifie qu'ils ne peuvent avoir qu'une seule instance. 
Utile pour les pages de configuration ou les contenus uniques.

Pour définir un Pod en tant que singleton, définissez 'singleton' => true dans sa configuration.
Vous pouvez également définir un Pod comme Singleton depuis l'interface d'administration de Pods

## Gestion des traductions
Le plugin intègre la gestion des traductions via Polylang. 
Vous pouvez définir quels champs sont traduisibles dans l'interface d'administration de Pods.
Un menu "MCC Traductions" est disponible dans les paramètres pour rafraîchir la configuration des traductions.

## Licence

Ce plugin est distribué sous licence [CeCILL-2.1](https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html).
