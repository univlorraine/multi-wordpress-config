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

$pod_name = 'map_icons';
$pod_singular_name = 'map_icon';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Icônes carte',
        'label_singular' => 'Icône carte',
        'label_all_items' => 'Icônes carte',
        'description' => 'Icônes pour les points sur la carte de l\'application Multi',
        'menu_position' => 102,
        'menu_icon' => 'dashicons-image-filter',
        'menu_location_custom' => 'edit.php?post_type=map_points',
        'show_in_menu' => true,
        'show_in_admin_bar' => false,
        'show_in_nav_menus' => false,
        'wpgraphql_singular_name' => $pod_singular_name,
        'wpgraphql_plural_name' => $pod_name,
        'options' => [
            'singleton' => false,
            // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
            'title_field' => $pod_singular_name . '_name',
        ]
    ],
    'pod_fields' => [
        $pod_singular_name . '_fields' => [
            'label' => 'Champs Icône carte',
            'fields' => [
                $pod_singular_name . '_name' => [
                    'type' => 'text',
                    'label' => 'Nom',
                    'description' => 'Nom de l\'icône (pour usage interne)',
                    'required' => true,
                ],
                $pod_singular_name . '_svg' => [
                    'type' => 'code',
                    'label' => 'Code SVG de l\'icône',
                    'description' => 'Code SVG de l\'icône à utiliser pour les points sur la carte',
                    'required' => true,
                    'code_max_length' => '-1',
                    'code_trim' => true,
                    'code_trim_lines' => true,
                    'code_trim_p_brs' => false,
                    'code_trim_extra_lines' => false,
                    'code_sanitize_html' => false,
                    'code_allow_shortcode' => false
                ],
                $pod_singular_name . '_width' => [
                    'type' => 'number',
                    'label' => 'Largeur',
                    'description' => 'Largeur de l\'icône en pixels',
                    'required' => true,
                ],
                $pod_singular_name . '_height' => [
                    'type' => 'number',
                    'label' => 'Hauteur',
                    'description' => 'Hauteur de l\'icône en pixels',
                    'required' => true,
                ],
                $pod_singular_name . '_pos_x' => [
                    'type' => 'number',
                    'label' => 'Position X',
                    'description' => 'Position X du point d\'ancrage de l\'icône',
                    'required' => true,
                ],
                $pod_singular_name . '_pos_y' => [
                    'type' => 'number',
                    'label' => 'Position Y',
                    'description' => 'Position Y du point d\'ancrage de l\'icône',
                    'required' => true,
                ],
            ]
        ]
    ]
];
