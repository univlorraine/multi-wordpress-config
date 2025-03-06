<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant la désactivation du frontend de WordPress
 */
if (!class_exists('MCC_Disable_Frontend')) {
    class MCC_Disable_Frontend {
        /**
         * Liste des chemins protégés qui ne seront pas redirigés
         */
        private const PROTECTED_PATHS = [
            'wp-admin',
            'wp-login.php',
            'admin-ajax.php',
            'wp-cron.php',
            'wp-json',
            'graphql'
        ];

        /**
         * Liste des types de flux à désactiver
         */
        private const FEED_TYPES = [
            'do_feed',
            'do_feed_rdf',
            'do_feed_rss',
            'do_feed_rss2',
            'do_feed_atom'
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
            // Redirection du frontend vers l'admin
            add_action('template_redirect', [$this, 'redirect_frontend']);

            // Désactive les liens canoniques et les flux RSS
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'feed_links', 2);
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'rel_canonical');

            // Désactive les emoji
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');

            // Désactive l'API REST pour les utilisateurs non connectés
            add_filter('rest_authentication_errors', [$this, 'restrict_rest_api']);

            // Désactive les requêtes XML-RPC
            add_filter('xmlrpc_enabled', '__return_false');

            // Désactive l'API oEmbed
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');

            // Nettoyage des scripts et styles du frontend
            add_action('wp_enqueue_scripts', [$this, 'dequeue_frontend_assets'], 9999);

            // Désactive les flux RSS
            foreach (self::FEED_TYPES as $feed) {
                add_action($feed, [$this, 'disable_feeds'], 1);
            }

            // Désactive le sitemap XML
            add_filter('wp_sitemaps_enabled', '__return_false');
        }

        /**
         * Redirige toutes les requêtes frontend vers l'admin
         */
        public function redirect_frontend() {
            if ($this->is_protected_path()) {
                return;
            }

            // Redirection vers wp-admin
            $admin_url = admin_url();

            // Préserve les paramètres de requête s'il y en a
            $query_string = $_SERVER['QUERY_STRING'] ?? '';
            if (!empty($query_string)) {
                $admin_url = add_query_arg($_GET, $admin_url);
            }

            wp_redirect($admin_url, 302);
            exit;
        }

        /**
         * Vérifie si le chemin actuel est protégé
         */
        private function is_protected_path(): bool {
            $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';

            foreach (self::PROTECTED_PATHS as $path) {
                if (strpos($current_path, '/' . trim($path, '/')) === 0) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Restreint l'accès à l'API REST aux utilisateurs connectés
         */
        public function restrict_rest_api($access) {
            if (!is_user_logged_in()) {
                return new WP_Error(
                    'rest_not_logged_in',
                    __('Vous devez être connecté pour accéder à l\'API REST.', 'multi-wordpress-config'),
                    ['status' => rest_authorization_required_code()]
                );
            }

            return $access;
        }

        /**
         * Désactive les flux RSS
         */
        public function disable_feeds() {
            wp_die(
                esc_html__('Les flux RSS ont été désactivés.', 'multi-wordpress-config'),
                '',
                ['response' => 410]
            );
        }

        /**
         * Désenregistre tous les scripts et styles du frontend
         */
        public function dequeue_frontend_assets() {
            global $wp_scripts, $wp_styles;

            // Désenregistre tous les scripts sauf ceux nécessaires
            if (!empty($wp_scripts->registered)) {
                foreach ($wp_scripts->registered as $handle => $script) {
                    wp_deregister_script($handle);
                }
            }

            // Désenregistre tous les styles sauf ceux nécessaires
            if (!empty($wp_styles->registered)) {
                foreach ($wp_styles->registered as $handle => $style) {
                    wp_deregister_style($handle);
                }
            }
        }
    }
}
