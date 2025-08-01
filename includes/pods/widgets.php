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

$pod_name = 'widgets';
$pod_singular_name = 'widget';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Widgets',
        'label_singular' => 'Widget',
        'label_add_new_item' => 'Nouveau widget',
        'description' => 'Widgets de l\'application Multi',
        'menu_position' => 11,
        'menu_icon' => 'dashicons-excerpt-view',
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
            'label' => 'Champs Widget',
            'fields' => [
                $pod_singular_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre',
                    'required' => false,
                    'description' => 'Titre du widget.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => false,
                    'description' => 'Décrit ce que fait le widget dans l\'application (information à titre indicatif, la valeur saisie ne sera pas utilisée).',
                ],
                $pod_singular_name . '_content' => [
                    'type' => 'paragraph',
                    'label' => 'Contenu',
                    'required' => false,
                    'description' => 'Contenu du widget.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_code' => [
                    'type' => 'text',
                    'label' => 'Code système du Widget',
                    'required' => true,
                    'description' => 'Code système du widget à afficher. Se référer au Readme du projet pour connaître les codes des différents widgets.',
                ],
                $pod_singular_name . '_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'required' => false,
                    'description' => 'Nom \'ion-icon\' de l\'icône si aucune n\'est définie dans les blocs SVG ci-dessous.',
                ],
                $pod_singular_name . '_icon_svg_light' => [
                    'type' => 'code',
                    'label' => 'Code SVG de l\'icône du thème clair',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône du widget affichée avec le thème \'Light\'.',
                    'code_max_length' => '-1',
                    'code_trim' => true,
                    'code_trim_lines' => true,
                    'code_trim_p_brs' => false,
                    'code_trim_extra_lines' => false,
                    'code_sanitize_html' => false,
                    'code_allow_shortcode' => false
                ],
                $pod_singular_name . '_icon_svg_dark' => [
                    'type' => 'code',
                    'label' => 'Code SVG de l\'icône du thème sombre',
                    'required' => false,
                    'description' => 'Code SVG de l\'icône du widget affichée avec le thème \'Dark\'.',
                    'code_max_length' => '-1',
                    'code_trim' => true,
                    'code_trim_lines' => true,
                    'code_trim_p_brs' => false,
                    'code_trim_extra_lines' => false,
                    'code_sanitize_html' => false,
                    'code_allow_shortcode' => false
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
                    'description' => 'Rôles autorisés ou interdits d\'accès au widget.',
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
                ],
                $pod_singular_name . '_position' => [
                    'type' => 'number',
                    'label' => 'Ordre d\'affichage',
                    'required' => false,
                    'description' => 'Position lors de l\'affichage.',
                    'default_value' => 0,
                ],
                $pod_singular_name . '_positions_by_role' => [
                    'type' => 'pick',
                    'label' => 'Ordre d\'affichage par rôle',
                    'required' => false,
                    'description' => 'Position lors de l\'affichage.',
                    'pick_object' => 'post_type',
                    'pick_val' => 'positions_by_role',
                    'pick_format_type' => 'multi',
                    'pick_format_multi' => 'list',
                    'pick_display_format_multi' => 'custom',
                    'pick_display_format_separator' => ', ',
                ],
                $pod_singular_name . '_type' => [
                    'type' => 'pick',
                    'label' => 'Type',
                    'required' => true,
                    'description' => 'Type de service : Interne à l\'application ou Externe (redirection lien web).',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_object' => 'custom-simple',
                    'pick_format_multi' => 'list',
                    'pick_custom' => [
                        'internal' => 'Interne',
                        'external' => 'Externe',
                    ],
                ],
                $pod_singular_name . '_router_link' => [
                    'type' => 'text',
                    'label' => 'Router link',
                    'required' => false,
                    'description' => 'Chemin vers la route interne du module Ionic à appeler.',
                    'enable_conditional_logic' => '1',
                    'conditional_logic' => [
                        'action' => 'hide',
                        'logic' => 'all',
                        'rules' => [
                            [
                                'field' => $pod_singular_name . '_type',
                                'compare' => '!=',
                                'value' => 'internal',
                            ]
                        ],
                    ],
                ],
                $pod_singular_name . '_link_url' => [
                    'type' => 'website',
                    'label' => 'URL service externe',
                    'required' => false,
                    'description' => 'Lien http vers le service externe. Si authentification CAS requise, ajouter {st} pour le ticket.',
                    'enable_conditional_logic' => '1',
                    'conditional_logic' => [
                        'action' => 'hide',
                        'logic' => 'all',
                        'rules' => [
                            [
                                'field' => $pod_singular_name . '_type',
                                'compare' => '!=',
                                'value' => 'external',
                            ]
                        ],
                    ],
                ],
                $pod_singular_name . '_sso_service' => [
                    'type' => 'website',
                    'label' => 'URL service SSO',
                    'required' => false,
                    'description' => 'Lien vers la validation du ticket SSO du service.',
                    'enable_conditional_logic' => '1',
                    'conditional_logic' => [
                        'action' => 'hide',
                        'logic' => 'all',
                        'rules' => [
                            [
                                'field' => $pod_singular_name . '_type',
                                'compare' => '!=',
                                'value' => 'external',
                            ]
                        ],
                    ],
                ],
                $pod_singular_name . '_color' => [
                    'type' => 'color',
                    'label' => 'Couleur',
                    'required' => false,
                    'description' => 'Couleur de fond du widget.',
                ],
                $pod_singular_name . '_statistic_name' => [
                    'type' => 'text',
                    'label' => 'Identifiant de la statistique',
                    'required' => false,
                    'description' => 'Identifiant du service pour la génération des statistiques d\'accès.',
                ],
            ],
        ]
    ]
];
