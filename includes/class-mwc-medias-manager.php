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
