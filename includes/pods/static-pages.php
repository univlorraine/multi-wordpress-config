<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'static_pages';

return [
    'name' => $pod_name,
    'label' => 'Pages statiques',
    'label_singular' => 'Page statique',
    'description' => 'Pages statiques de l\'application Multi',
    'menu-position' => 4,
    'menu-icon' => 'dashicons-admin-page',
    'title_field' => $pod_name . '_title', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'groups' => [
        $pod_name . '_fields' => [
            'label' => 'Champs Page statique',
            'fields' => [
                $pod_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre',
                    'required' => true,
                    'description' => 'Titre de la page',
                ],
                $pod_name . '_content' => [
                    'type' => 'wysiwyg',
                    'label' => 'Contenu',
                    'required' => true,
                    'description' => 'Contenu de la page'
                ],
                $pod_name . '_link_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => false,
                    'description' => 'Nom \'ion-icon\' de l\'icône à afficher.'
                ],
                $pod_name . '_icon_svg_light' => [
                    'type' => 'paragraph',
                    'label' => 'Code SVG de l\'icône du thème clair',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône à afficher pour le thème clair.',
                ],
                $pod_name . '_icon_svg_dark' => [
                    'type' => 'paragraph',
                    'label' => 'Code SVG de l\'icône du thème sombre',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône à afficher pour le thème sombre.',
                ],
                $pod_name . '_statistic_page' => [
                    'type' => 'text',
                    'label' => 'Identifiant de la statistique',
                    'required' => false,
                    'description' => 'Identifiant de la page statique pour la génération des statistiques d\'accès.',
                ],
                $pod_name . '_position' => [
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
