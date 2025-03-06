<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MWC_Singleton_Manager')) {
    class MWC_Singleton_Manager
    {

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks()
        {
            add_action('admin_init', [$this, 'handle_singleton_instances'], 100);
            add_action('admin_menu', [$this, 'remove_singleton_add_new_menu'], 999);
            add_action('admin_head', [$this, 'remove_singleton_add_new_buttons']);
            add_filter('pods_admin_setup_edit_options', [$this, 'add_singleton_option'], 10, 2);
        }

        private function is_pod_singleton($pod): bool
        {
            if (!function_exists('pods_api')) {
                return false;
            }

            return !empty($pod['options']['singleton']);
        }

        /**
         * Ajoute l'option "singleton" dans les options de configuration des pods
         * @param array $options
         * @param array $pod
         * @return array
         */
        public function add_singleton_option($options, $pod)
        {
            if ($pod['type'] === 'post_type') {
                $options['admin-ui'][] = [
                    'name' => 'singleton',
                    'label' => 'Singleton',
                    'help' => 'Si activé, ce pod ne pourra avoir qu\'une seule instance',
                    'type' => 'boolean',
                    'default' => !empty($pod['options']['singleton']),
                    'boolean_yes_label' => 'Ce pod est un singleton'
                ];
            }
            return $options;
        }

        /**
         * Gère les pods singleton en redirigeant directement vers l'édition du premier élément
         * @return void
         * @throws Exception
         */
        public function handle_singleton_instances()
        {
            if (!function_exists('pods_api')) {
                return;
            }

            // Gestion de la traduction d'un singleton par Polylang
            if (!empty($_GET['from_post']) && !empty($_GET['new_lang'])) {
                return;
            }

            $api = pods_api();
            $all_pods = $api->load_pods();

            foreach ($all_pods as $pod) {
                if (!$this->is_pod_singleton($pod)) {
                    continue;
                }

                if (empty($pod['name']) || get_post_type_object($pod['name']) === null) {
                    continue;
                }

                $singleton_query = new \WP_Query([
                    'post_type' => $pod['name'],
                    'posts_per_page' => 1,
                ]);

                if ($singleton_query->have_posts()) {
                    $singleton_query->the_post();
                    $post_id = get_the_ID();
                    wp_reset_postdata();

                    if (isset($_GET['post_type']) && $_GET['post_type'] === $pod['name'] && !isset($_GET['post']) && !isset($_GET['action'])) {
                        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
                        exit;
                    }
                } else {
                    $new_post = [
                        'post_type' => $pod['name'],
                        'post_status' => 'publish',
                        'post_title' => 'Instance unique de ' . $pod['name']
                    ];
                    $post_id = wp_insert_post($new_post);

                    if (!is_wp_error($post_id)) {
                        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
                        exit;
                    }
                }
            }
        }

        /**
         * Retire les sous-menus "Ajouter un nouveau..." dans le menu d'admin pour les pods singleton
         * @return void
         * @throws Exception
         */
        public function remove_singleton_add_new_menu() {
            if (!function_exists('pods_api')) {
                return;
            }

            $api = pods_api();
            $all_pods = $api->load_pods();

            foreach ($all_pods as $pod) {
                if ($this->is_pod_singleton($pod)) {
                    remove_submenu_page(
                        'edit.php?post_type=' . $pod['name'],
                        'post-new.php?post_type=' . $pod['name']
                    );
                }
            }
        }

        /**
         * Retire le bouton "Ajouter un nouveau..." présent en haut dans le formulaire d'édition d'un élément
         * @return void
         * @throws Exception
         */
        public function remove_singleton_add_new_buttons() {
            if (!function_exists('pods_api')) {
                return;
            }

            global $pagenow, $post_type;

            $api = pods_api();
            $all_pods = $api->load_pods();

            foreach ($all_pods as $pod) {
                if ($this->is_pod_singleton($pod)) {
                    if (($pagenow === 'edit.php' || $pagenow === 'post.php') && $post_type === $pod['name']) {
                        echo '<style>
                .page-title-action,
                .wrap a.page-title-action,
                #favorite-actions,
                .add-new-h2,
                .page-title-action {
                    display: none !important;
                }
                </style>';
                    }
                }
            }
        }
    }
}
