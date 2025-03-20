<?php
/**
 * Plugin Name: Multi Wordpress Config
 * Plugin URI: https://github.com/univlorraine/multi-wordpress-config
 * Description: Plugin permettant de personnaliser l'administration WordPress pour une utilisation Headless
 * Version: 1.0.0
 * Author: Benjamin Lemoine
 * Author URI: https://github.com/benjhoo
 * License: CeCILL-2.1
 * License URI: https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html
 * Text Domain: multi-wordpress-config
 */

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

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin Multi Wordpress Config
 */
if (!class_exists('Multi_Wordpress_Config')) {
    class Multi_Wordpress_Config {
        private const VERSION = '0.1.0';
        private const REQUIRED_PLUGINS = [
            'Pods' => 'pods/init.php',
            'Polylang' => 'polylang/polylang.php'
        ];

        private static $instance = null;
        private $plugin_name = 'multi-wordpress-config';
        private $disable_defaults;
        private $disable_frontend;
        private $disable_themes;
        private $medias_manager;
        private $pods_manager;
        private $translation_manager;

        private function __construct() {
            if ($this->check_dependencies()) {
                $this->define_hooks();
                $this->init_components();
            }
        }

        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Vérifie que les plugins requis sont bien activés
         * @return bool
         */
        private function check_dependencies(): bool {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

            $missing_plugins = [];
            foreach (self::REQUIRED_PLUGINS as $name => $path) {
                if (!is_plugin_active($path)) {
                    $missing_plugins[] = $name;
                }
            }

            if (!empty($missing_plugins)) {
                add_action('admin_notices', function() use ($missing_plugins) {
                    $message = sprintf(
                        esc_html__('Multi Wordpress Config nécessite les plugins suivants : %s', 'multi-wordpress-config'),
                        implode(', ', $missing_plugins)
                    );
                    echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
                });

                add_action('admin_init', function() {
                    deactivate_plugins(plugin_basename(__FILE__));
                });

                return false;
            }

            return true;
        }

        private function define_hooks(): void
        {
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        }

        private function init_components(): void
        {
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-defaults.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-frontend.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-themes.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-medias-manager.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-pods-manager.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-translation-manager.php';

            // Classe qui désactive les types par défaut de WordPress (post, page, comment)
            $this->disable_defaults = new MWC_Disable_Defaults();
            // Classe qui désactive toute la partie Frontend pour faire de Wordpress un CMS Headless
            $this->disable_frontend = new MWC_Disable_Frontend();
            // Classe qui désactive les thèmes et le gestionnaire de thèmes dans l'admin (inutiles en Headless)
            $this->disable_themes = new MWC_Disable_Themes();
            // Classe qui permet de rediriger l'utilisation des médias Wordpress vers un serveur Nginx
            $this->medias_manager = new MWC_Medias_Manager();
            // Classe de gestion des collections et champs personnalisés via l'utilisation du plugin Pods
            $this->pods_manager = new MWC_Pods_Manager();
            // Classe de gestion de la traduction personnalisée via l'utilisation du plugin Polylang
            $this->translation_manager = new MWC_Translation_Manager();
        }

        /**
         * Fonctions initiées lors de l'activation du plugin
         */
        public function activate(): void
        {
            if ($this->disable_defaults) {
                $this->disable_defaults->disable_core_types();
            }

            if ($this->pods_manager) {
                $this->pods_manager->create_default_pods();
            }

            if ((get_option('mwc_translations_initialized') !== 'yes') && $this->translation_manager) {
                $this->translation_manager->init_translations();
                update_option('mwc_translations_initialized', 'yes');
            }

            flush_rewrite_rules();
        }

        /**
         * Fonctions initiées lors de la désactivation du plugin
         */
        public function deactivate(): void
        {
            if ($this->disable_defaults) {
                $this->disable_defaults->restore_core_types();
            }

            delete_option('mwc_translations_initialized');

            flush_rewrite_rules();
        }
    }

    Multi_Wordpress_Config::get_instance();
}
