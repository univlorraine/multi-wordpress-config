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
* Redirection de la gestion des médias vers un serveur Nginx si configuré
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

if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'movies';
$pod_singular_name = 'movie';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Films à venir',
        'label_singular' => 'Film à venir',
        'label_add_new_item' => 'Nouveau film à venir',
        'description' => 'Listing des films qui sortiront prochainement',
        'menu_position' => 65,
        'menu_icon' => 'dashicons-megaphone',
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
            'title_field' => $pod_singular_name . '_title',
        ]
    ],
    'pod_fields' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Film à venir',
            'fields' => [
                $pod_singular_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre du film',
                    'required' => true,
                    'description' => 'Titre du film à venir.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_synopsis' => [
                    'type' => 'wysiwyg',
                    'label' => 'Résumé du film',
                    'required' => true,
                    'description' => 'Résumé du film à venir.',
                    'is_translatable' => true,
                ],
                // Autres champs...
            ],
        ],
    ],
];
```

* **$pod_name** : nom du Pod au pluriel
* **$pod_singular_name** : nom du Pod au singulier

Ces deux variables servent principalement à bien distinguer les noms du Pod et les champs au niveau des queries GraphQL.


### pod_config

Concerne la configuration générale du Pod

* **name** : nom système du Pod
* **label** : label du Pod affiché au pluriel
* **label_singular** : label du Pod affiché au singulier
* **label_add_new_item** : label du bouton "Ajouter" dans l'interface d'administration
* **description** : description du Pod
* **menu_position** : entier désignant la position du Pod dans le menu latéral gauche (sur une plage de 11 à 59, les autres étant réservés par WordPress)
* **menu_icon** : nom dashicons de l'icône du Pod dans le menu latéral gauche
* **wpgraphql_singular_name** : nom du Pod au singulier pour les requêtes GraphQL
* **wpgraphql_plural_name** : nom du Pod au pluriel pour les requêtes GraphQL
* **options** : options custom du Pod
  * **singleton** : si définit à `true` le Pod sera considéré comme Singleton
  * **title_field** : permet de définir quel champ de Pod surchargera le champ titre par défaut de Wordpress. La valeur de cet attribut peut être un string (=champ concerné) ou un tableau de strings (=plusieurs champs concernés qui seront formatés de la manière suivante : "champ1 : champ2")

### pod_fields

Concerne la configuration des champs du Pod

* **label** : label du groupe de champs
* **fields** : définit les champs du Pod
  * **type** : type du champ (text, number, email, date, website, ...)
  * **label** : label du champ affiché dans le formulaire
  * **required** : si le champ est obligatoire
  * **description** : description du champ affiché dans le formulaire
  * **is_translatable** : si le champ est traduisible

Pour plus d'informations concernant les champs et la configuration Pods disponibles : https://docs.pods.io/fields/

Attention également à bien nommer la clé de chaque champ (ex `$pod_singular_name . '_title'`) car ce sont ces noms qui seront utilisés dans les queries GraphQL.

## Pods Singleton
Le plugin permet de définir des Pods en tant que "singleton", ce qui signifie qu'ils ne peuvent avoir qu'une seule instance. 
Utile pour les pages de configuration ou les contenus uniques.

Pour définir un Pod en tant que singleton, définissez 'singleton' => true dans sa configuration.
Vous pouvez également définir un Pod comme Singleton depuis l'interface d'administration de Pods

## Rediriger la gestion des médias vers un serveur Nginx
Si vous utilisez un serveur Nginx pour délivrer les médias de manière statique, vous pouvez activer la redirection de la gestion des médias vers le serveur Nginx.
Pour cela, il suffit de définir une variable d'environnement `NGINX_UPLOADS_PROXY=`

## Gestion des traductions
Le plugin intègre la gestion des traductions via Polylang. 
Vous pouvez définir quels champs sont traduisibles dans l'interface d'administration de Pods.
Il faut au préalable avoir configuré les langues à utiliser dans Polylang, et sélectionner les types de contenu à traduire dans l'interface d'administration de Polylang : `Langues > Réglages > Types de publication personnalisés et taxonomies`

## Licence

Ce plugin est distribué sous licence [CeCILL-2.1](https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html).
