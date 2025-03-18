<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'important_news';
$pod_singular_name = 'important_new';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Informations importantes',
        'label_singular' => 'Information importante',
        'label_add_new_item' => 'Nouvelle information importante',
        'description' => 'Informations importantes de l\'application Multi',
        'menu_position' => 13,
        'menu_icon' => 'dashicons-clipboard',
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            'title_field' => $pod_singular_name. '_title', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
        ]
    ],
    'pod_fields' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Information importante',
            'fields' => [
                $pod_singular_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre',
                    'required' => true,
                    'description' => 'Titre de l\'information importante.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_content' => [
                    'type' => 'paragraph',
                    'label' => 'Contenu',
                    'required' => true,
                    'description' => 'Contenu de l\'information importante.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_button_label' => [
                    'type' => 'text',
                    'label' => 'Label du bouton',
                    'required' => false,
                    'description' => 'Label de l\'éventuel bouton permettant de rediriger l\'utilisateur vers un service lié à l\'information (exemple: En savoir plus).',
                ],
                $pod_singular_name . '_image' => [
                    'type' => 'file',
                    'label' => 'Image',
                    'required' => false,
                    'description' => 'Image illustrant l\'information importante.',
                ],
                $pod_singular_name . '_access_restriction' => [
                    'type' => 'pick',
                    'label' => 'Accès',
                    'required' => true,
                    'description' => 'Qui a accès à l\'information ?',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_object' => 'custom-simple',
                    'pick_format_multi' => 'list',
                    'pick_custom' => [
                        'NONE' => 'Tout le monde a accès',
                        'ALLOW' => 'Personne n\'a accès sauf les rôles listés',
                        'DISALLOW' => 'Tout le monde a accès sauf les rôles listés',
                    ],
                    'default_value' => 'NONE',
                ],
                $pod_singular_name . '_roles' => [
                    'type' => 'pick',
                    'label' => 'Rôles',
                    'required' => false,
                    'description' => 'Rôles autorisés ou interdits d\'accès à l\'information importante.',
                    'pick_object' => 'post_type',
                    'pick_format_type' => 'multi',
                    'pick_format_multi' => 'autocomplete',
                    'pick_post_type' => ['roles'],
                    'enable_conditional_logic' => '1',
                    'conditional_logic' => [
                        'action' => 'hide',
                        'logic' => 'all',
                        'rules' => [
                            [
                                'field' => $pod_singular_name . '_access_restriction',
                                'compare' => '=',
                                'value' => 'NONE',
                            ]
                        ],
                    ],
                    'pick_val' => 'roles',
                    'pick_taggable' => '1',
                ],
                $pod_singular_name . '_color' => [
                    'type' => 'color',
                    'label' => 'Couleur',
                    'required' => false,
                    'description' => 'Couleur de fond de l\'information en page d\'accueil.',
                ],
                $pod_singular_name . '_link_url' => [
                    'type' => 'website',
                    'label' => 'Lien',
                    'required' => false,
                    'description' => 'Lien du service vers lequel rediriger l\'utilisateur lorsqu\'il clique sur le bouton associé.',
                ],
                $pod_singular_name . 'statistic_name' => [
                    'type' => 'text',
                    'label' => 'Identifiant de la statistique',
                    'required' => false,
                    'description' => 'Identifiant de l\'information pour la génération des statistiques d\'accès.',
                ],
            ],
        ]
    ]
];
