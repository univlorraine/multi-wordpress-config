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

/**
 * Classe pour désactiver les thèmes et leur gestion dans l'administration WordPress
 */
if (!class_exists('MWC_Disable_Themes')) {
    class MWC_Disable_Themes {

        private const RESTRICTED_PAGES = [
            'themes.php',
            'theme-editor.php',
            'theme-install.php'
        ];

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            // Désactive l'interface de personnalisation
            add_action('admin_init', [$this, 'disable_customizer_admin']);

            // Masque les menus liés aux thèmes dans l'administration
            add_action('admin_menu', [$this, 'remove_theme_menus']);

            // Redirige les pages de thèmes vers le dashboard
            add_action('admin_init', [$this, 'redirect_theme_pages']);

            // Masque les sections de thèmes dans l'écran de mise à jour
            add_action('admin_head', [$this, 'hide_theme_sections'], 999);
        }

        /**
         * Désactive l'interface de personnalisation
         * @return void
         */
        public function disable_customizer_admin(): void
        {
            global $pagenow;

            if ($pagenow === 'customize.php') {
                wp_die(__('Le customizer a été désactivé.', 'multi-wordpress-config'));
            }

            remove_action('admin_bar_menu', 'wp_admin_bar_customize_menu', 40);
        }

        /**
         * Supprime les menus liés aux thèmes dans l'administration
         * @return void
         */
        public function remove_theme_menus(): void
        {
            remove_menu_page('themes.php');
            remove_submenu_page('themes.php', 'themes.php');
            remove_submenu_page('themes.php', 'theme-editor.php');
            remove_submenu_page('themes.php', 'customize.php');
        }

        /**
         * Redirige les pages de thèmes vers le dashboard
         * @return void
         */
        public function redirect_theme_pages(): void
        {
            global $pagenow;

            if (in_array($pagenow, self::RESTRICTED_PAGES, true)) {
                wp_die(
                    esc_html__('Les thèmes ont été désactivés.', 'multi-wordpress-config'),
                    '',
                    [
                        'response' => 410,
                        'back_link' => true,
                    ]
                );
            }
        }

        /**
         * Masque les sections de thèmes dans l'écran de mise à jour
         * @return void
         */
        public function hide_theme_sections(): void
        {
            echo '<style>
                .theme-browser,
                .theme-count,
                .theme.add-new-theme,
                #menu-appearance,
                #wp-admin-bar-customize {
                    display: none !important;
                }
            </style>';
        }
    }
}
