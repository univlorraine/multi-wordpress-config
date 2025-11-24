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

$pod_name = 'map_points';
$pod_singular_name = 'map_point';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Points sur la carte',
        'label_singular' => 'Point sur la carte',
        'label_add_new_item' => 'Ajouter un point sur la carte',
        'label_all_items' => 'Tous les points sur la carte',
        'description' => 'Points d\'intérêt sur la carte de l\'application Multi',
        'menu_position' => 16,
        'menu_icon' => 'dashicons-location-alt',
        'menu_name' => 'Carte',
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
            'label' => 'Champs Point sur la carte',
            'fields' => [
                $pod_singular_name . '_name' => [
                    'type' => 'text',
                    'label' => 'Nom',
                    'description' => 'Nom du point sur la carte.',
                    'required' => true,
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_description' => [
                    'type' => 'wysiwyg',
                    'label' => 'Contenu',
                    'required' => true,
                    'description' => 'Description du point sur la carte.',
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_latitude' => [
                    'type' => 'number',
                    'label' => 'Latitude',
                    'description' => 'Latitude du point sur la carte.',
                    'required' => true,
                    'number_decimals' => 10,
                    'number_max_length' => 15,
                ],
                $pod_singular_name . '_longitude' => [
                    'type' => 'number',
                    'label' => 'Longitude',
                    'description' => 'Longitude du point sur la carte.',
                    'required' => true,
                    'number_decimals' => 10,
                    'number_max_length' => 15,
                ],
                $pod_singular_name . '_campus' => [
                    'type' => 'pick',
                    'label' => 'Campus',
                    'description' => 'Campus auquel ce point est rattaché.',
                    'required' => true,
                    'pick_object' => 'post_type',
                    'pick_val' => 'campuses',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_allow_add_new' => true,
                ],
                $pod_singular_name . '_category' => [
                    'type' => 'pick',
                    'label' => 'Catégorie',
                    'description' => 'Catégorie du point sur la carte.',
                    'required' => false,
                    'pick_object' => 'post_type',
                    'pick_val' => 'map_categories',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_allow_add_new' => false,
                    'is_translatable' => true,
                ],
                $pod_singular_name . '_icon' => [
                    'type' => 'pick',
                    'label' => 'Icône',
                    'description' => 'Icône à utiliser pour ce point sur la carte.',
                    'required' => true,
                    'pick_object' => 'post_type',
                    'pick_val' => 'map_icons',
                    'pick_format_type' => 'single',
                    'pick_format_single' => 'dropdown',
                    'pick_allow_add_new' => true,
                ],
            ]
        ]
    ]
];
