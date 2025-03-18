<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'positions_by_role';
$pod_singular_name = 'position_by_role';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Positions par rôle',
        'label_singular' => 'Position par rôle',
        'label_add_new_item' => 'Nouvelle position par rôle',
        'description' => 'Positions par rôle de l\'application Multi.',
        'menu_position' => 58,
        'menu_icon' => 'dashicons-sort',
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
            'title_field' => [$pod_singular_name . '_position', $pod_singular_name . '_role'],
        ]
    ],
    'pod_fields' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Position par rôles',
            'fields' => [
                $pod_singular_name . '_position' => [
                    'type' => 'number',
                    'label' => 'Position d\'affichage',
                    'required' => true,
                    'description' => 'Ordre pour l\'affichage.',
                    'number_max_length' => '-1',
                ],
                $pod_singular_name . '_role' => [
                    'type' => 'pick',
                    'label' => 'Rôle',
                    'required' => true,
                    'description' => 'Rôle de l\'utilisateur associé à la position.',
                    'pick_object' => 'post_type',
                    'pick_val' => 'roles',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'autocomplete',
                    'pick_display_format_separator' => ', ',
			        'pick_taggable' => '1',
                    'simple_relationship' => '1',
                    'pick_display_format_single' => 'name'
                ],
            ],
        ]
    ]
];
