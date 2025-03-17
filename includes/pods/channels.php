<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'channels';
$pod_singular_name = 'channel';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Canaux de notification',
        'label_singular' => 'Canal de notification',
        'label_add_new_item' => 'Nouveau canal de notification',
        'description' => 'Canaux de notifications de l\'application Multi',
        'menu_position' => 6,
        'menu_icon' => 'dashicons-megaphone',
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
            'label' => 'Champs Canal',
            'fields' => [
                $pod_singular_name . '_code' => [
                    'type' => 'text',
                    'label' => 'Code',
                    'required' => true,
                    'description' => 'Code système du canal de notification.',
                ],
                $pod_singular_name . '_label' => [
                    'type' => 'text',
                    'label' => 'Label',
                    'required' => true,
                    'description' => 'Label du canal de notification.',
                    'is_translatable' => true,
                ],
            ],
        ]
    ]
];
