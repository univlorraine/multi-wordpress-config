<?php
/**
 * Plugin Name: Multi Wordpress Config
 * Plugin URI: https://github.com/univlorraine/multi-wordpress-config
 * Description: Plugin permettant de personnaliser l'administration WordPress pour une utilisation Headless
 * Version: 0.3.5
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

// Inclure Plugin Update Checker
require_once dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


// Initialiser le système de mise à jour
function initialiser_mise_a_jour_plugin() {
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/univlorraine/multi-wordpress-config/',
        __FILE__,
        'multi-wordpress-config'
    );

    // Définir la branche qui contient la version stable
    $myUpdateChecker->setBranch('main');
}
add_action('init', 'initialiser_mise_a_jour_plugin');

/**
 * Classe principale du plugin Multi Wordpress Config
 */
class Multi_Wordpress_Config {
    private const REQUIRED_PLUGINS = [
        'Pods' => 'pods/init.php',
        'Polylang' => 'polylang/polylang.php'
    ];
    private $disable_defaults;
    private $disable_frontend;
    private $disable_themes;
    private $medias_manager;
    private $pods_manager;
    private $translation_manager;

    public function __construct() {
        if ($this->check_dependencies()) {
            $this->define_hooks();
            $this->init_components();
        }
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

            return false;
        }

        return true;
    }

    private function define_hooks(): void {
        add_action('init', [$this, 'init_plugin']);
    }

    private function init_components(): void {
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-defaults.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-frontend.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-disable-themes.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-medias-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-pods-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-mwc-translation-manager.php';

        $this->disable_defaults = new MWC_Disable_Defaults();
        $this->disable_frontend = new MWC_Disable_Frontend();
        $this->disable_themes = new MWC_Disable_Themes();
        $this->medias_manager = new MWC_Medias_Manager();
        $this->pods_manager = new MWC_Pods_Manager();
        $this->translation_manager = new MWC_Translation_Manager();
    }

    public function init_plugin(): void {
        // Initialisations supplémentaires si nécessaire
    }

    /**
     * Fonctions initiées lors de l'activation du plugin
     */
    public static function activate(): void {
        $instance = new self();

        if ($instance->disable_defaults) {
            $instance->disable_defaults->disable_core_types();
        }

        if ($instance->pods_manager) {
            $instance->pods_manager->create_default_pods();
        }

        flush_rewrite_rules();
    }

    /**
     * Fonctions initiées lors de la désactivation du plugin
     */
    public static function deactivate(): void {
        $instance = new self();

        if ($instance->disable_defaults) {
            $instance->disable_defaults->restore_core_types();
        }

        flush_rewrite_rules();
    }
}

// Instanciation automatique au chargement du plugin
add_action('plugins_loaded', function() {
    new Multi_Wordpress_Config();
});

// Déclaration des hooks d’activation et de désactivation
register_activation_hook(__FILE__, ['Multi_Wordpress_Config', 'activate']);
register_deactivation_hook(__FILE__, ['Multi_Wordpress_Config', 'deactivate']);

