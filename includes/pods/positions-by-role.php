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

$pod_name = 'positions_by_role';
$pod_singular_name = 'position_by_role';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Positions par rôle',
        'label_singular' => 'Position par rôle',
        'label_add_new_item' => 'Nouvelle position par rôle',
        'description' => 'Positions par rôle de l\'application Multi.',
        'menu_position' => 21,
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
                    'simple_relationship' => '1',
                    'pick_display_format_single' => 'name'
                ],
            ],
        ]
    ]
];
