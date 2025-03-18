<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'roles';
$pod_singular_name = 'role';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Rôles',
        'label_singular' => 'Rôle',
        'label_add_new_item' => 'Nouveau rôle',
        'description' => 'Rôles des utilisateur dans l\'application Multi',
        'menu_position' => 57,
        'menu_icon' => 'dashicons-universal-access',
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
            'title_field' => $pod_singular_name . '_code',
        ]
    ],
    'pod_fields' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Rôle',
            'fields' => [
                $pod_singular_name . '_code' => [
                    'type' => 'text',
                    'label' => 'Code',
                    'required' => true,
                    'description' => 'Code permettant d\'identifier le rôle dans l\'application.',
                ],
                $pod_singular_name . '_description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => false,
                    'description' => 'Indications sur le rôle (information à titre indicatif, la valeur saisie ne sera pas utilisée).',
                ],
            ],
        ]
    ]
];
