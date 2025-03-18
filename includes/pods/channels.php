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
        'menu_position' => 16,
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
                $pod_singular_name . '_router_link' => [
                    'type' => 'text',
                    'label' => 'Router link',
                    'required' => false,
                    'description' => 'Chemin vers la route Ionic du service interne vers laquelle rediriger l\'utilisateur lorsqu\'il clique sur une notification associée à ce canal (exemple : /schedule).',
                ],
                $pod_singular_name . '_color' => [
                    'type' => 'color',
                    'label' => 'Couleur',
                    'required' => false,
                    'description' => 'Couleur de thème associée au canal de notification.',
                ],
                $pod_singular_name . '_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => false,
                    'description' => 'Nom \'ion-icon\' de l\'icône associée au canal de notification.',
                ],
                $pod_singular_name . '_filterable' => [
                    'type' => 'boolean',
                    'label' => 'Filtrable',
                    'required' => false,
                    'description' => 'Indique si les notifications liées à ce canal peuvent être filtrées à l\'affichage par l\'utilisateur ou non.',
                    'boolean_format_type' => 'radio',
                    'boolean_yes_label' => 'Oui',
                    'boolean_no_label' => 'Non',
                    'default_value' => '1',
                ],
            ],
        ]
    ]
];
