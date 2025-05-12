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
                    'is_translatable' => true,
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
                ],
                $pod_singular_name . '_color' => [
                    'type' => 'color',
                    'label' => 'Couleur',
                    'required' => false,
                    'description' => 'Couleur de fond de l\'information en page d\'accueil.',
                ],
                $pod_singular_name . '_link_url' => [
                    'type' => 'text',
                    'label' => 'Lien',
                    'required' => false,
                    'description' => 'Lien du service vers lequel rediriger l\'utilisateur lorsqu\'il clique sur le bouton associé. Peut être un service interne (exemple : /rss) ou bien un service externe (exemple : https://mon-service.edu).',
                ],
                $pod_singular_name . '_statistic_name' => [
                    'type' => 'text',
                    'label' => 'Identifiant de la statistique',
                    'required' => false,
                    'description' => 'Identifiant de l\'information pour la génération des statistiques d\'accès.',
                ],
            ],
        ]
    ]
];
