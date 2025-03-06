<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant la désactivation des types de contenu par défaut
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

        public function __construct() {
            $this->init_hooks();
        }

        private function init_hooks() {
            // Administration
            add_action('admin_menu', [$this, 'remove_admin_menus']);
            add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);
            add_action('admin_bar_menu', [$this, 'remove_admin_bar_nodes'], 999);

            // Types de contenu et taxonomies
            add_action('init', [$this, 'unregister_default_taxonomies'], 5);

            // Métadonnées et autres filtres
            add_filter('get_post_metadata', [$this, 'filter_post_metadata'], 10, 4);
            add_filter('show_admin_bar', '__return_false');

            // Désactiver commentaires et pings
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

        public function remove_admin_menus() {
            foreach (self::DISABLED_TYPES as $type) {
                remove_menu_page("edit.php?post_type=$type");
            }
            remove_menu_page('edit-comments.php');
        }

        public function remove_dashboard_widgets() {
            foreach (self::DASHBOARD_WIDGETS as $widget) {
                remove_meta_box($widget, 'dashboard', 'normal');
                remove_meta_box($widget, 'dashboard', 'side');
            }
        }

        public function disable_core_types() {
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

        public function restore_core_types() {
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

        public function unregister_default_taxonomies() {
            foreach (self::DISABLED_TAXONOMIES as $taxonomy) {
                if (taxonomy_exists($taxonomy)) {
                    unregister_taxonomy_for_object_type($taxonomy, 'post');
                    unregister_taxonomy($taxonomy);
                }
            }
        }

        public function filter_post_metadata($value, $object_id, $meta_key, $single) {
            $post_type = get_post_type($object_id);
            if (in_array($post_type, self::DISABLED_TYPES, true)) {
                return '';
            }
            return $value;
        }

        public function remove_admin_bar_nodes($wp_admin_bar) {
            foreach (self::DISABLED_TYPES as $type) {
                $wp_admin_bar->remove_node("new-{$type}");
            }
            $wp_admin_bar->remove_node('comments');
        }
    }
}
