<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant la réécriture d'url pour le serveur des médias
 */
if (!class_exists('MWC_Medias_Manager')) {
    class MWC_Medias_Manager {

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            add_action('init', [$this, 'block_direct_uploads_access'], 1);
            add_filter('upload_dir', [$this, 'redirect_uploads_to_nginx_proxy']);
            add_filter('wp_get_attachment_url', [$this, 'redirect_uploads_to_nginx_proxy']);
        }

        /**
         * Redirige les uploads vers le proxy Nginx
         * @param $url_or_uploads
         * @return array|mixed|string|string[]
         */
        public function redirect_uploads_to_nginx_proxy($url_or_uploads): mixed
        {
            // Récupération de la variable d'environnement une seule fois
            static $nginx_proxy = null;
            if ($nginx_proxy === null) {
                $nginx_proxy = getenv('NGINX_UPLOADS_PROXY');
            }

            // Si pas de proxy configuré, retourner la valeur sans modification
            if (!$nginx_proxy) {
                return $url_or_uploads;
            }

            $nginx_proxy = rtrim($nginx_proxy, '/');

            // Cas 1: Filtre upload_dir (reçoit un tableau)
            if (is_array($url_or_uploads) && isset($url_or_uploads['baseurl'])) {
                $url_or_uploads['baseurl'] = $nginx_proxy . '/wp-content/uploads';
                return $url_or_uploads;
            }

            // Cas 2: Filtre wp_get_attachment_url (reçoit une chaîne)
            if (is_string($url_or_uploads)) {
                return str_replace(
                    site_url('/wp-content/uploads'),
                    $nginx_proxy . '/wp-content/uploads',
                    $url_or_uploads
                );
            }

            return $url_or_uploads;
        }

        /**
         * Bloque l'accès direct aux uploads
         * @return void
         */
        public function block_direct_uploads_access(): void
        {
            // Si nous sommes en train de demander un fichier dans uploads
            if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
                // Si le référent n'existe pas ou n'est pas de notre site (pour autoriser l'admin)
                $is_admin = is_admin() || (defined('DOING_AJAX') && DOING_AJAX);

                // Si nous ne sommes pas dans l'admin et que le proxy est configuré
                if (!$is_admin && getenv('NGINX_UPLOADS_PROXY')) {
                    // Rediriger vers le proxy
                    $nginx_proxy = rtrim(getenv('NGINX_UPLOADS_PROXY'), '/');
                    $redirect_url = $nginx_proxy . $_SERVER['REQUEST_URI'];

                    header("Location: $redirect_url", true, 301);
                    exit;
                }
            }
        }
    }
}
