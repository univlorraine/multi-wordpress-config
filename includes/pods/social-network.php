<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'social_network';

return [
    'name' => $pod_name,
    'label' => 'Réseaux sociaux',
    'label_singular' => 'Réseau social',
    'description' => 'Réseaux sociaux de l\'application Multi',
    'menu-position' => 5,
    'menu-icon' => 'dashicons-share',
    'title_field' => $pod_name . '_name', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'groups' => [
        $pod_name . '_fields' => [
            'label' => 'Champs Réseau social',
            'fields' => [
                $pod_name . '_name' => [
                    'type' => 'text',
                    'label' => 'Nom',
                    'required' => true,
                    'description' => 'Nom du réseau social',
                ],
                $pod_name . '_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => true,
                    'description' => 'Nom \'Ionicons\' de l\'icône à afficher (ex: logo-facebook)'
                ],
                $pod_name . '_link_url' => [
                    'type' => 'website',
                    'label' => 'URL',
                    'required' => true,
                    'description' => 'URL du réseau social'
                ],
                $pod_name . '_position' => [
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
