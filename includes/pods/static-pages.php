<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'static_pages';
$pod_singular_name = 'static_page';

return [
    'name' => $pod_name,
    'label' => 'Pages statiques',
    'label_singular' => 'Page statique',
    'label_add_new' => 'Nouvelle page statique',
    'description' => 'Pages statiques de l\'application Multi',
    'menu-position' => 4,
    'menu-icon' => 'dashicons-admin-page',
    'title_field' => $pod_singular_name . '_title', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'graphql_singular_name' => $pod_singular_name,
    'graphql_plural_name' => $pod_name,
    'groups' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Page statique',
            'fields' => [
                $pod_singular_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre',
                    'required' => true,
                    'description' => 'Titre de la page',
                ],
                $pod_singular_name . '_content' => [
                    'type' => 'wysiwyg',
                    'label' => 'Contenu',
                    'required' => true,
                    'description' => 'Contenu de la page'
                ],
                $pod_singular_name . '_link_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => false,
                    'description' => 'Nom \'ion-icon\' de l\'icône à afficher.'
                ],
                $pod_singular_name . '_icon_svg_light' => [
                    'type' => 'paragraph',
                    'label' => 'Code SVG de l\'icône du thème clair',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône à afficher pour le thème clair.',
                ],
                $pod_singular_name . '_icon_svg_dark' => [
                    'type' => 'paragraph',
                    'label' => 'Code SVG de l\'icône du thème sombre',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône à afficher pour le thème sombre.',
                ],
                $pod_singular_name . '_statistic_page' => [
                    'type' => 'text',
                    'label' => 'Identifiant de la statistique',
                    'required' => false,
                    'description' => 'Identifiant de la page statique pour la génération des statistiques d\'accès.',
                ],
                $pod_singular_name . '_position' => [
                    'type' => 'number',
                    'label' => 'Ordre d\'affichage',
                    'required' => false,
                    'description' => 'Position lors de l\'affichage.',
                    'default_value' => 0,
                ]
            ],
        ]
    ]
];
