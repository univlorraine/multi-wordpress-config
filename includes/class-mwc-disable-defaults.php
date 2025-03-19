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
 * Classe gérant la désactivation des types de contenu par défaut de Wordpress
 */
if (!class_exists('MWC_Disable_Defaults')) {
    class MWC_Disable_Defaults {
        private const DISABLED_TYPES = ['post', 'page', 'comment'];
        private const DISABLED_TAXONOMIES = ['category', 'post_tag'];
        private const DISABLED_FEATURES = [
            'comments',
            'trackbacks',
            'custom-fields',
            'page-attributes',
            'post-formats'
        ];
        private const DASHBOARD_WIDGETS = [
            'dashboard_recent_comments',
            'dashboard_quick_press',
            'dashboard_activity'
        ];

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            // Administration
            add_action('admin_menu', [$this, 'remove_admin_menus']);
            add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);
            add_action('admin_bar_menu', [$this, 'remove_admin_bar_nodes'], 999);

            // Types de contenu et taxonomies
            add_action('init', [$this, 'unregister_default_taxonomies'], 5);

            // Métadonnées et autres filtres
            add_filter('get_post_metadata', [$this, 'filter_post_metadata'], 10, 4);
            add_filter('show_admin_bar', '__return_false');

            // Désactivation des commentaires et pings
            add_filter('comments_open', '__return_false', 20, 2);
            add_filter('pings_open', '__return_false', 20, 2);
            add_filter('comments_array', '__return_empty_array', 10, 2);

            // Sécurité supplémentaire
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }

            // Désactiver les types de contenu
            add_action('init', [$this, 'disable_core_types'], 10);
        }

        /**
         * Supprime les menus d'administration inutiles
         * @return void
         */
        public function remove_admin_menus(): void
        {
            foreach (self::DISABLED_TYPES as $type) {
                remove_menu_page("edit.php?post_type=$type");
            }
            remove_menu_page('edit-comments.php');
        }

        /**
         * Supprime les widgets du tableau de bord
         * @return void
         */
        public function remove_dashboard_widgets(): void
        {
            foreach (self::DASHBOARD_WIDGETS as $widget) {
                remove_meta_box($widget, 'dashboard', 'normal');
                remove_meta_box($widget, 'dashboard', 'side');
            }
        }

        /**
         * Désactive les types de contenu par défaut de Wordpress
         * @return void
         */
        public function disable_core_types(): void
        {
            global $wp_post_types;

            foreach (self::DISABLED_TYPES as $type) {
                if (isset($wp_post_types[$type])) {
                    $wp_post_types[$type]->public = false;
                    $wp_post_types[$type]->show_in_menu = false;
                    $wp_post_types[$type]->show_in_admin_bar = false;
                    $wp_post_types[$type]->show_in_nav_menus = false;
                    $wp_post_types[$type]->publicly_queryable = false;
                    $wp_post_types[$type]->exclude_from_search = true;
                    $wp_post_types[$type]->supports = [];

                    foreach (self::DISABLED_FEATURES as $feature) {
                        remove_post_type_support($type, $feature);
                    }
                }
            }
        }

        /**
         * Restaure les types de contenu par défaut de Wordpress lors de la désactivation du plugin
         * @return void
         */
        public function restore_core_types(): void
        {
            global $wp_post_types;

            foreach (self::DISABLED_TYPES as $type) {
                if (isset($wp_post_types[$type])) {
                    $wp_post_types[$type]->public = true;
                    $wp_post_types[$type]->show_in_menu = true;
                    $wp_post_types[$type]->show_in_admin_bar = true;
                    $wp_post_types[$type]->show_in_nav_menus = true;
                    $wp_post_types[$type]->publicly_queryable = true;
                    $wp_post_types[$type]->exclude_from_search = false;
                }
            }

            // Restaurer les taxonomies
            register_taxonomy('category', 'post', [
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => true
            ]);
            register_taxonomy('post_tag', 'post', [
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => true
            ]);

            delete_option('default_comment_status');
            delete_option('default_ping_status');
        }

        /**
         * Désactive la taxonomie dans Wordpress
         * @return void
         */
        public function unregister_default_taxonomies(): void
        {
            foreach (self::DISABLED_TAXONOMIES as $taxonomy) {
                if (taxonomy_exists($taxonomy)) {
                    unregister_taxonomy_for_object_type($taxonomy, 'post');
                    unregister_taxonomy($taxonomy);
                }
            }
        }

        /**
         * Filtre les métadonnées des types de contenu désactivés
         * @param mixed $value
         * @param int $object_id
         * @param string $meta_key
         * @param bool $single
         * @return mixed
         */
        public function filter_post_metadata($value, $object_id, $meta_key, $single): mixed
        {
            $post_type = get_post_type($object_id);
            if (in_array($post_type, self::DISABLED_TYPES, true)) {
                return '';
            }
            return $value;
        }

        /**
         * Supprime les entrées vers les types de contenu désactivés dans la barre d'administration
         * @param WP_Admin_Bar $wp_admin_bar
         * @return void
         */
        public function remove_admin_bar_nodes($wp_admin_bar): void
        {
            foreach (self::DISABLED_TYPES as $type) {
                $wp_admin_bar->remove_node("new-{$type}");
            }
            $wp_admin_bar->remove_node('comments');
        }
    }
}
