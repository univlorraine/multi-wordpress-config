<?php

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
