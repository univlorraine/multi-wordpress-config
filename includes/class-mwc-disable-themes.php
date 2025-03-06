<?php
/**
 * Classe pour désactiver les thèmes et leur gestion dans l'administration WordPress
 *
 * @package Multi_Wordpress_Config
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MWC_Disable_Themes')) {
    class MWC_Disable_Themes {

        private const RESTRICTED_PAGES = [
            'themes.php',
            'theme-editor.php',
            'theme-install.php'
        ];

        /**
         * Constructeur
         */
        public function __construct() {
            $this->init_hooks();
        }

        /**
         * Initialise les hooks WordPress
         */
        private function init_hooks() {
            // Désactiver l'interface de personnalisation
            add_action('admin_init', [$this, 'disable_customizer_admin']);

            // Masquer les menus liés aux thèmes
            add_action('admin_menu', [$this, 'remove_theme_menus']);

            // Rediriger les pages de thèmes vers le dashboard
            add_action('admin_init', [$this, 'redirect_theme_pages']);

            // Désactiver les mises à jour de thèmes
            add_filter('pre_site_transient_update_themes', '__return_empty_array');

            // Masquer les sections de thèmes dans l'écran de mise à jour
            add_action('admin_head', [$this, 'hide_theme_sections'], 999);
        }

        public function disable_customizer_admin() {
            global $pagenow;

            if ($pagenow === 'customize.php') {
                wp_die(__('Le customizer a été désactivé.', 'multi-wordpress-config'));
            }

            remove_action('admin_bar_menu', 'wp_admin_bar_customize_menu', 40);
        }

        public function remove_theme_menus() {
            remove_menu_page('themes.php');
            remove_submenu_page('themes.php', 'themes.php');
            remove_submenu_page('themes.php', 'theme-editor.php');
            remove_submenu_page('themes.php', 'customize.php');
        }

        public function redirect_theme_pages() {
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

        public function hide_theme_sections() {
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
