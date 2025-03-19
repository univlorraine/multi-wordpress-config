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
 * Classe permettant de gérer les pods singleton (= une instance unique) dans WordPress
 */
if (!class_exists('MWC_Singleton_Manager')) {
    class MWC_Singleton_Manager
    {

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            add_action('admin_init', [$this, 'handle_singleton_instances'], 100);
            add_action('admin_menu', [$this, 'remove_singleton_add_new_menu'], 999);
            add_action('admin_head', [$this, 'remove_singleton_add_new_buttons']);
            add_filter('pods_admin_setup_edit_options', [$this, 'add_singleton_option'], 10, 2);
        }

        /**
         * Vérifie si un pod est défini comme singleton
         * @param array $pod
         * @return bool
         */
        private function is_pod_singleton($pod): bool
        {
            if (!function_exists('pods_api')) {
                return false;
            }

            return !empty($pod['options']['singleton']);
        }

        /**
         * Ajoute l'option "singleton" dans les options de configuration des Pods dans l'admin
         * @param array $options
         * @param array $pod
         * @return array
         */
        public function add_singleton_option($options, $pod): array
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
        public function handle_singleton_instances(): void
        {
            if (!function_exists('pods_api')) {
                return;
            }

            // Vérification si une traduction est en cours (Polylang)
            if (!empty($_GET['from_post']) && !empty($_GET['new_lang'])) {
                return;
            }

            // Récupère le post_type actuel depuis la requête
            $current_post_type = $_GET['post_type'] ?? null;

            // Si aucun post_type ou si on est déjà sur une page d'édition ou action spécifique
            if (empty($current_post_type) || isset($_GET['post']) || (isset($_GET['action']) && $_GET['action'] !== 'edit')) {
                return;
            }

            // Récupération des pods seulement si nécessaire
            $api = pods_api();
            $all_pods = $api->load_pods();

            foreach ($all_pods as $pod) {
                // Vérifie si ce pod est celui demandé et s'il est défini comme singleton
                if ($pod['name'] !== $current_post_type || !$this->is_pod_singleton($pod)) {
                    continue;
                }

                // Vérifie si le post type est valide avant toute requête
                if (empty($pod['name']) || get_post_type_object($pod['name']) === null) {
                    continue;
                }

                // Recherche une instance existante
                $singleton_query = new \WP_Query([
                    'post_type'      => $pod['name'],
                    'posts_per_page' => 1,
                    'no_found_rows'  => true, // Optimisation de la requête
                    'fields'         => 'ids', // Récupère uniquement les IDs pour plus d'efficacité
                ]);

                if ($singleton_query->have_posts()) {
                    // Utilise le premier ID trouvé
                    $post_id = $singleton_query->posts[0];

                    // Redirection vers la page d'édition
                    wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
                    exit;
                } else {
                    // Crée une nouvelle instance si aucune n'existe
                    $new_post = [
                        'post_type'   => $pod['name'],
                        'post_status' => 'publish',
                        'post_title'  => 'Instance unique de ' . ($pod['label_singular'] ?? $pod['name'])
                    ];

                    $post_id = wp_insert_post($new_post);

                    if (!is_wp_error($post_id)) {
                        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
                        exit;
                    }
                }

                break;
            }
        }

        /**
         * Retire les sous-menus "Ajouter un nouveau..." dans le menu d'admin pour les pods singleton
         * @return void
         * @throws Exception
         */
        public function remove_singleton_add_new_menu(): void
        {
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
        public function remove_singleton_add_new_buttons(): void
        {
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
