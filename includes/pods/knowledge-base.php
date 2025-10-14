<?php
/*
 * Copyright ou © ou Copr. Université de Lorraine, (2025)
 *
 * Direction du Numérique de l'Université de Lorraine - SIED
 * (dn-mobile-dev@univ-lorraine.fr)
 *
 * Ce plugin Wordpress est spécifique à un usage en mode Headless et a été conçu
 * pour l'administration de l'application ESUP-MULTI : https://github.com/univlorraine/esup-multi
 *
 * Ce plugin est régi par la licence CeCILL 2.1, soumise au droit français
 * et respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et INRIA
 * sur le site "http://cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée. Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme, le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * À cet égard, l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement, à l'utilisation, à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant des connaissances informatiques approfondies. Les
 * utilisateurs sont donc invités à charger et à tester l'adéquation du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et/ou de leurs données et, plus généralement,
 * à l'utiliser et à l'exploiter dans les mêmes conditions de sécurité.
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
 * pris connaissance de la licence CeCILL 2.1, et que vous en avez accepté les
 * termes.
 */

if (!defined('ABSPATH')) {
    exit;
}

$pod_name = 'knowledge_bases';
$pod_singular_name = 'knowledge_base';
$pod_field_name = 'information';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Informations',
        'label_singular' => 'Information',
        'label_add_new_item' => 'Nouvelle information',
        'description' => 'Base de connaissance de l\'application Multi',
        'menu_position' => 13,
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            'title_field' => $pod_field_name. '_title', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
        ]
    ],
    'pod_fields' => [
        $pod_field_name . '_fields' => [
            'label' => 'Champs Information',
            'fields' => [
                $pod_field_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre',
                    'required' => true,
                    'description' => 'Titre de l\'information',
                    'is_translatable' => true,
                ],
                $pod_field_name . '_content' => [
                    'type' => 'wysiwyg',
                    'label' => 'Contenu',
                    'required' => true,
                    'description' => 'Contenu de l\'information',
                    'wysiwyg_editor' => 'tinymce',
                    'wysiwyg_media_buttons' => '1',
                    'wysiwyg_delay_init' => '0',
                    'wysiwyg_trim' => '1',
                    'wysiwyg_trim_lines' => '0',
                    'wysiwyg_trim_p_brs' => '0',
                    'wysiwyg_trim_extra_lines' => '0',
                    'wysiwyg_sanitize_html' => '1',
                    'wysiwyg_oembed' => '0',
                    'wysiwyg_wptexturize' => '1',
                    'wysiwyg_convert_chars' => '1',
                    'wysiwyg_wpautop' => '1',
                    'wysiwyg_allow_shortcode' => '0',
                    'is_translatable' => true,
                ],
                $pod_field_name . '_type' => [
                    'type' => 'pick',
                    'label' => 'Type',
                    'required' => true,
                    'description' => 'Quel est le type de l\'information ?',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_object' => 'custom-simple',
                    'pick_format_multi' => 'list',
                    'pick_custom' => [
                        'content' => 'Contenu',
                        'external_link' => 'Lien externe',
                        'internal_link' => 'Lien interne'
                    ],
                    'default_value' => 'content',
                ],
                $pod_field_name . '_child_display' => [
                    'type' => 'pick',
                    'label' => 'Affichage des enfants',
                    'required' => false,
                    'description' => 'Affichage des éléments enfants',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_object' => 'custom-simple',
                    'pick_format_multi' => 'list',
                    'pick_custom' => [
                        'card' => 'Carte',
                        'list' => 'Lien Liste'
                    ],
                    'default_value' => 'content',
                    'enable_conditional_logic' => '1',
                    'conditional_logic' => [
                        'action' => 'hide',
                        'logic' => 'any',
                        'rules' => [
                            [
                                'field' => $pod_field_name . '_type',
                                'compare' => '=',
                                'value' => 'internal_link'
                            ],
                            [
                                'field' => $pod_field_name . '_type',
                                'compare' => '=',
                                'value' => 'external_link'
                            ]
                        ],
                    ],
                ],
                $pod_field_name . '_access_restriction' => [
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
                $pod_field_name . '_roles' => [
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
                                'field' => $pod_field_name . '_access_restriction',
                                'compare' => '=',
                                'value' => 'NONE',
                            ]
                        ],
                    ],
                    'pick_val' => 'roles',
                ],
                $pod_field_name . '_parent' => [
                    'type' => 'pick',
                    'label' => 'Identifiant information parent',
                    'required' => false,
                    'description' => 'Identifiant de la page d\'information parent',
                    'pick_object' => 'post_type',
                    'pick_format_type' => 'single',
                    'pick_format_multi' => 'list',
                    'pick_post_type' => ['knowledge_base'],
                    'pick_val' => 'knowledge_base',
                ],
                $pod_field_name . '_position' => [
                    'type' => 'number',
                    'label' => 'Ordre d\'affichage',
                    'required' => false,
                    'description' => 'Position lors de l\'affichage.',
                    'default_value' => 0,
                ],
                $pod_field_name . '_cover_image' => [
                    'type' => 'file',
                    'label' => 'Image de couverture',
                    'required' => false,
                    'description' => 'Image de couverture illustrant une information',
                ],
                $pod_field_name . '_link' => [
                    'type' => 'text',
                    'label' => 'Lien',
                    'required' => false,
                    'description' => 'Lien vers une page ou redirection si la page est de type lien externe',
                ],
                $pod_field_name . '_phone' => [
                    'type' => 'phone',
                    'label' => 'Téléphone',
                    'required' => false,
                    'description' => 'Numéro de téléphone associé à l\'information',
                    'phone_format' => 'international',
                    'phone_max_length' => '25',
                    'phone_enable_phone_extension' => '1'
                ],
                $pod_field_name . '_address' => [
                    'type' => 'text',
                    'label' => 'Adresse',
                    'required' => false,
                    'description' => 'L\'adresse associée à l\'information'
                ],
                $pod_field_name . '_email' => [
                    'type' => 'email',
                    'label' => 'Email',
                    'required' => false,
                    'description' => 'L\'adresse email associée à l\'information'
                ],
                $pod_field_name . '_search_keywords' => [
                    'type' => 'email',
                    'label' => 'Email',
                    'required' => false,
                    'description' => 'L\'adresse email associée à l\'information'
                ],
                $pod_field_name . '_search_keywords' => [
                    'type' => 'text',
                    'label' => 'Mots clés de recherche',
                    'required' => false,
                    'description' => 'Tags qui serviront pour la recherche dans les services.',
                    'repeatable' => true,
                    'is_translatable' => true,
                    'repeatable_format' => 'custom',
                    'repeatable_format_separator' => ','
                ],
            ],
        ]
    ]
];
