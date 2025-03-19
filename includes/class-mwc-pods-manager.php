<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-mwc-singleton-manager.php';

/**
 * Classe permettant de gérer les pods de l'application nécessaires au fonctionnement de l'application MULTI
 */
if (!class_exists('MWC_Pods_Manager')) {
    class MWC_Pods_Manager {
        private $pods_config = [];
        private $pods_dir;
        private $singleton_manager;

        public function __construct()
        {
            $this->pods_dir = plugin_dir_path(__FILE__) . 'pods/';
            $this->load_pods_config();
            $this->singleton_manager = new MWC_Singleton_Manager();
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            add_action('pods_api_post_save_pod_item', [$this, 'sync_pod_field_with_title'], 10, 3);
            add_filter('pods_admin_setup_edit_options', [$this, 'add_title_sync_option'], 10, 2);
        }

        /**
         * Charge la configuration des pods depuis les fichiers de configuration dans le dossier pods/
         * @return void
         */
        private function load_pods_config(): void
        {
            if (!is_dir($this->pods_dir) || !glob($this->pods_dir . '*.php')) {
                error_log('Dossier pods/ introuvable ou vide.');
                return;
            }

            foreach (glob($this->pods_dir . '*.php') as $pod_file) {
                $pod_data = include_once $pod_file;

                if (is_array($pod_data) && isset($pod_data['pod_config']) && isset($pod_data['pod_fields']) && isset($pod_data['pod_config']['name'])) {
                    $this->pods_config[$pod_data['pod_config']['name']] = $pod_data;
                } else {
                    error_log('Configuration de pod invalide dans le fichier : ' . $pod_file);
                }
            }
        }

        /**
         * Vérifie si des pods configurés existent déjà (pour éviter d'écraser l'existant)
         * @return bool
         */
        public function check_existing_pods(): bool {
            if (!function_exists('pods_api')) {
                return false;
            }

            $api = pods_api();
            foreach (array_keys($this->pods_config) as $pod_name) {
                if (!$api->pod_exists($pod_name)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Crée les pods pré-configurés en base au moment de l'activation du plugin
         * @return void
         */
        public function create_default_pods(): void
        {
            if (!function_exists('pods_api')) {
                error_log('API Pods non disponible.');
                return;
            }

            $api = pods_api();

            // Paramètres par défaut pour tous les pods
            $default_pod_args = [
                'storage' => 'meta',                // Type de stockage des données (meta, table, etc.)
                'type' => 'post_type',              // Type de contenu (post_type, taxonomy, user, media, etc.)

                // Paramètres d'interface
                'public' => true,                   // true car on veut que le pod soit accessible via API
                'publicly_queryable' => false,      // false pour imposer une authentification pour accéder au pod
                'show_ui' => true,                  // true car on veut le gérer dans l'admin
                'show_in_menu' => true,             // true pour l'avoir dans le menu admin
                'show_in_nav_menus' => false,       // false car pas de frontend
                'show_in_admin_bar' => true,        // true pour un accès rapide dans la barre admin

                // Paramètres d'archive et URL
                'has_archive' => false,             // ne pas gérer les archives pour le pod (inutile en headless)
                'rewrite' => false,                 // supprime le permalien (inutile en headless)
                'query_var' => false,               // permet de personnaliser l'URL de requête (inutile en headless)

                // Paramètres REST API
                'show_in_rest' => true,             // true pour accès API
                'rest_api' => true,                 // true pour activer l'API
                'rest_enable' => true,              // true pour activer REST dans le plugin Pods

                // Paramètres GraphQL
                'show_in_graphql' => true,          // true pour activer l'API GraphQL
                'wpgraphql_enabled' => true,        // true pour activer GraphQL dans le plugin Pods

                // Options avancées de Pods
                'supports_title' => false,          // false pour désactiver le titre (car dissocié du reste du formulaire d'édition)
                'supports_quick_edit' => false,     // false pour désactiver l'édition rapide

                // Paramètres de capacités
                'capability_type' => 'post',        // Définit le pod comme un type de contenu de base (pour les permissions)
                'map_meta_cap' => true,             // true pour une gestion correcte des permissions

                // Paramètres d'export
                'can_export' => true,               // Indique que les données peuvent être exportées depuis l'admin

                // Paramètres de recherche
                'exclude_from_search' => true,      // true car pas de recherche front

                // Autres paramètres
                'delete_with_user' => false,        // false pour préserver les données
            ];

            // Pour chaque config de pod présente dans le dossier pods/
            foreach ($this->pods_config as $pod_name => $pod_data) {
                try {
                    // Vérifier que les sections requises sont présentes
                    if (!isset($pod_data['pod_config']) || !isset($pod_data['pod_fields'])) {
                        error_log('Configuration de pod invalide: structure incorrecte');
                        continue;
                    }

                    // On fusionne les paramètres par défaut avec la config du pod
                    $pod_args = array_merge($default_pod_args, $pod_data['pod_config']);

                    // On renseigne le nom du pod
                    $pod_args['name'] = $pod_name;
                    $pod_args['rest_base'] = $pod_name;

                    // On crée le pod
                    $pod_id = $api->save_pod($pod_args);

                    // Si le pod a été créé, on crée les groupes et champs
                    if ($pod_id && !empty($pod_data['pod_fields'])) {
                        pods_api()->cache_flush_pods();

                        foreach ($pod_data['pod_fields'] as $group_name => $group_config) {
                            // Paramètres du groupe
                            $group_args = [
                                'pod' => $pod_name,
                                'name' => $group_name,
                                'label' => $group_config['label']
                            ];

                            $api->save_group($group_args);

                            // On crée les champs
                            if (isset($group_config['fields']) && is_array($group_config['fields'])) {
                                foreach ($group_config['fields'] as $field_name => $field_config) {

                                    $default_field_args = [
                                        'pod' => $pod_name,
                                        'name' => $field_name,
                                        'group' => $group_name,
                                        'show_in_graphql' => true,
                                        'wpgraphql_enabled' => true
                                    ];

                                    // On fusionne les paramètres par défaut avec la config du champ
                                    $field_args = array_merge($default_field_args, $field_config);

                                    try {
                                        // On crée le champ
                                        $api->save_field($field_args);
                                    } catch (Exception $e) {
                                        error_log('MWC Debug - Erreur création champ : ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('MWC Debug - Erreur lors de la création du pod : ' . $e->getMessage());
                }
            }

            pods_api()->cache_flush_pods();
        }

        /**
         * Synchronise le champ de titre WordPress avec un champ Pod configuré (pour éviter que tous nos éléments s'appellent "Brouillon")
         * @param $pieces
         * @param $is_new_item
         * @param $post_id
         * @return void
         */
        public function sync_pod_field_with_title($pieces, $is_new_item, $post_id): void
        {
            if (!function_exists('pods_api')) {
                return;
            }

            $api = pods_api();
            $pod = $api->load_pod(['name' => $pieces['pod']['name']]);

            // On vérifie si un champ est configuré pour le titre
            if (empty($pod['options']['title_field']) || $pod['options']['title_field'] === 'none') {
                return;
            }

            // title_field peut être un tableau de champs ou un champ unique
            $title_fields = is_array($pod['options']['title_field'])
                ? $pod['options']['title_field']
                : [$pod['options']['title_field']];

            // On récupère l'objet Pod pour le post
            $pod_object = pods($pieces['pod']['name'], $post_id);
            $title_parts = [];

            // Pour chaque champ title_field, on récupère la valeur et on l'ajoute à un tableau
            foreach ($title_fields as $field) {
                $field_value = $pod_object->field($field);
                if (!empty($field_value)) {
                    if (is_array($field_value) && isset($field_value['post_title'])) {
                        $field_value = $field_value['post_title'];
                    }
                    $title_parts[] = $field_value;
                }
            }

            // Si on a des parties de titre, on les concatène et on met à jour le titre WordPress
            if (!empty($title_parts)) {
                $new_title = implode(' : ', $title_parts);
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $new_title
                ]);
            }
        }

        /**
         * Ajoute une option dans l'administration de Pods pour synchroniser un champ
         * vec le titre par défaut de WordPress
         * @param $options
         * @param $pod
         * @return mixed
         */
        public function add_title_sync_option($options, $pod): mixed
        {
            if ($pod['type'] === 'post_type') {
                // Récupére la liste des champs du pod
                $fields = pods_api()->load_fields([
                    'pod' => $pod['name']
                ]);

                // Prépare les options de champs pour le select
                $field_options = ['none' => 'Aucun'];
                foreach ($fields as $field) {
                    $field_options[$field['name']] = $field['label'];
                }

                $options['admin-ui'][] = [
                    'name' => 'title_field',
                    'label' => 'Champ pour le titre',
                    'help' => 'Choisir le champ à synchroniser avec le titre WordPress',
                    'type' => 'pick',
                    'data' => $field_options
                ];
            }
            return $options;
        }
    }
}
