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

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant la désactivation du frontend de WordPress
 */
if (!class_exists('MWC_Disable_Frontend')) {
    class MWC_Disable_Frontend {
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

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
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
         * Redirige le frontend vers l'admin
         * @return void
         */
        public function redirect_frontend(): void
        {
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
         * @return bool
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
         * @param $access
         * @return mixed|WP_Error
         */
        public function restrict_rest_api($access): mixed
        {
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
         * @return void
         */
        public function disable_feeds(): void
        {
            wp_die(
                esc_html__('Les flux RSS ont été désactivés.', 'multi-wordpress-config'),
                '',
                ['response' => 410]
            );
        }

        /**
         * Désactive les scripts et styles du frontend
         * @return void
         */
        public function dequeue_frontend_assets(): void
        {
            global $wp_scripts, $wp_styles;

            // Dés-enregistre tous les scripts sauf ceux nécessaires
            if (!empty($wp_scripts->registered)) {
                foreach ($wp_scripts->registered as $handle => $script) {
                    wp_deregister_script($handle);
                }
            }

            // Dés-enregistre tous les styles sauf ceux nécessaires
            if (!empty($wp_styles->registered)) {
                foreach ($wp_styles->registered as $handle => $style) {
                    wp_deregister_style($handle);
                }
            }
        }
    }
}
