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

$pod_name = 'channels';
$pod_singular_name = 'channel';

return [
    'pod_config' => [
        'name' => $pod_name,
        'label' => 'Canaux de notification',
        'label_singular' => 'Canal de notification',
        'label_add_new_item' => 'Nouveau canal de notification',
        'description' => 'Canaux de notifications de l\'application Multi',
        'menu_position' => 15,
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
                ],
            ],
        ]
    ]
];
