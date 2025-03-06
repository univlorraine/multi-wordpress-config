<?php
/**
 * Plugin Name: Multi Custom Config
 * Plugin URI: https://github.com/univlorraine/multi-wordpress-config
 * Description: Plugin permettant de personnaliser l'administration WordPress pour une utilisation Headless
 * Version: 1.0.0
 * Author: Benjamin Lemoine
 * Author URI: https://github.com/benjhoo
 * License: CeCILL-2.1
 * License URI: https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html
 * Text Domain: multi-wordpress-config
 */

// TODO:
// - Ajouter tous les pods manquants pour compléter la collection de Directus
// - Ajouter les data de Directus dans Wordpress
// - Tester l'import export de pods
// - Revoir la gestion du bouton pour vider le cache des traductions dans les paramètres
// - Repasser sur tout le plugin pour commenter correctement, vérifier que tout est ok et optimiser le code

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin Multi Custom Config
 */
if (!class_exists('Multi_Custom_Config')) {
    class Multi_Custom_Config {
        private const VERSION = '1.0.0';
        private const REQUIRED_PLUGINS = [
            'Pods' => 'pods/init.php',
            'Polylang' => 'polylang/polylang.php'
        ];

        private static $instance = null;
        private $plugin_name = 'multi-wordpress-config';
        private $disable_defaults;
        private $disable_frontend;
        private $disable_themes;
        private $pods_manager;

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

        private function define_hooks() {
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        }

        private function init_components() {
            require_once plugin_dir_path(__FILE__) . 'includes/class-mcc-disable-defaults.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mcc-disable-frontend.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mcc-disable-themes.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mcc-pods-manager.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mcc-translation-manager.php';

            // Désactiver les types par défaut de WordPress (post, page, comment)
            $this->disable_defaults = new MCC_Disable_Defaults();
            // Désactiver toute la partie Frontend
            $this->disable_frontend = new MCC_Disable_Frontend();
            // Désactiver les thèmes et le gestionnaire dans l'admin
            $this->disable_themes = new MCC_Disable_Themes();
            // Création automatique des CPT (Custom Post Types) nécessaires au projet Multi
            $this->pods_manager = new MCC_Pods_Manager();
            // Gestion de la traduction des CPT Pods par Polylang
            $this->translation_manager = new MCC_Translation_Manager();
        }

        public function activate() {
            if ($this->disable_defaults) {
                $this->disable_defaults->disable_core_types();
            }

            if (get_option('mcc_pods_created') !== 'yes' && $this->pods_manager) {
                $this->pods_manager->create_default_pods();
                update_option('mcc_pods_created', 'yes');
            }

            flush_rewrite_rules();
        }

        public function deactivate() {
            if ($this->disable_defaults) {
                $this->disable_defaults->restore_core_types();
            }

            if ($this->pods_manager) {
                $this->pods_manager->delete_all_pods();
            }
            delete_option('mcc_pods_created');

            flush_rewrite_rules();
        }
    }

    Multi_Custom_Config::get_instance();
}
