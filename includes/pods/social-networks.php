<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'social_networks';
$pod_singular_name = 'social_network';

return [
    'name' => $pod_name,
    'label' => 'Réseaux sociaux',
    'label_singular' => 'Réseau social',
    'label_add_new' => 'Nouveau réseau social',
    'description' => 'Réseaux sociaux de l\'application Multi',
    'menu-position' => 5,
    'menu-icon' => 'dashicons-share',
    'title_field' => $pod_singular_name. '_name', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'graphql_singular_name' => $pod_singular_name,
    'graphql_plural_name' => $pod_name,
    'groups' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Réseau social',
            'fields' => [
                $pod_singular_name . '_name' => [
                    'type' => 'text',
                    'label' => 'Nom',
                    'required' => true,
                    'description' => 'Nom du réseau social',
                ],
                $pod_singular_name . '_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => true,
                    'description' => 'Nom \'Ionicons\' de l\'icône à afficher (ex: logo-facebook)'
                ],
                $pod_singular_name . '_link_url' => [
                    'type' => 'website',
                    'label' => 'URL',
                    'required' => true,
                    'description' => 'URL du réseau social'
                ],
                $pod_singular_name . '_position' => [
                    'type' => 'number',
                    'label' => 'Ordre d\'affichage',
                    'required' => false,
                    'description' => 'Position lors de l\'affichage',
                    'default_value' => 0,
                ]
            ],
        ]
    ]
];
