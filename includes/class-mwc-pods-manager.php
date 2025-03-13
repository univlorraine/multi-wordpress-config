<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-mwc-singleton-manager.php';

if (!class_exists('MWC_Pods_Manager')) {
    class MWC_Pods_Manager {
        private $pods_config = [];
        private $pods_dir;
        private $singleton_manager;

        public function __construct() {
            $this->pods_dir = plugin_dir_path(__FILE__) . 'pods/';
            $this->load_pods_config();
            $this->singleton_manager = new MWC_Singleton_Manager();
            $this->init_hooks();
        }

        private function init_hooks() {
            add_action('pods_api_post_save_pod_item', [$this, 'sync_pod_field_with_title'], 10, 3);
            add_filter('pods_admin_setup_edit_options', [$this, 'add_title_sync_option'], 10, 2);
        }

        /**
         * Charge la configuration des pods depuis les fichiers de configuration dans le dossier pods/
         * @return void
         */
        private function load_pods_config() {
            if (!is_dir($this->pods_dir) || !glob($this->pods_dir . '*.php')) {
                error_log('Dossier pods/ introuvable ou vide.');
                return;
            }

            foreach (glob($this->pods_dir . '*.php') as $pod_file) {
                $pod_config = include_once $pod_file;

                if (is_array($pod_config) && isset($pod_config['name'])) {
                    $this->pods_config[$pod_config['name']] = $pod_config;
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
        public function create_default_pods() {
            if (!function_exists('pods_api')) {
                error_log('API Pods non disponible.');
                return;
            }

            $api = pods_api();

            foreach ($this->pods_config as $pod_name => $pod_config) {
                try {
                    $pod_args = [
                        // Paramètres de base
                        'storage' => 'meta',                                           // Type de stockage des données (meta, table, etc.)
                        'name' => $pod_name,                                           // Nom système du pod (Requis)
                        'label' => $pod_config['label'],                               // Nom affiché du pod dans l'admin
                        'label_singular' => $pod_config['label_singular'],             // Nom affiché au singulier
                        'label_add_new_item' => $pod_config['label_add_new'] ?? '',    // Nom du bouton d'ajout
                        'type' => 'post_type',                                         // Type de contenu (post_type, taxonomy, user, media, etc.)
                        'description' => $pod_config['description'],                   // Description du pod

                        // Paramètres d'interface
                        'public' => true,                                   // true car on veut que le pod soit accessible via API
                        'publicly_queryable' => false,                      // false pour imposer une authentification pour accéder au pod
                        'show_ui' => true,                                  // true car on veut le gérer dans l'admin
                        'show_in_menu' => true,                             // true pour l'avoir dans le menu admin
                        'show_in_nav_menus' => false,                       // false car pas de frontend
                        'show_in_admin_bar' => true,                        // true pour un accès rapide dans la barre admin
                        'menu_position' => $pod_config['menu-position'],    // position du pod dans le menu admin
                        'menu_icon' => $pod_config['menu-icon'],            // icône du pod dans le menu admin

                        // Paramètres d'archive et URL
                        'has_archive' => false,                         // ne pas gérer les archives pour le pod (inutile en headless)
                        'rewrite' => false,                             // supprime le permalien (inutile en headless)
                        'query_var' => false,                           // permet de personnaliser l'URL de requête (inutile en headless)

                        // Paramètres REST API
                        'show_in_rest' => true,                         // true pour accès API
                        'rest_base' => $pod_name,                       // URL de base pour l'API
                        'rest_api' => true,                             // true pour activer l'API
                        'rest_enable' => true,                          // true pour activer REST dans le plugin Pods

                        // Paramètres GraphQL
                        'show_in_graphql' => true,                                                  // true pour activer l'API GraphQL
                        'wpgraphql_enabled' => true,                                                // true pour activer GraphQL dans le plugin Pods
                        'wpgraphql_singular_name' => $pod_config['graphql_singular_name'] ?? '',    // Nom singulier du pod pour GraphQL
                        'wpgraphql_plural_name' => $pod_config['graphql_plural_name'] ?? '',        // Nom pluriel du pod pour GraphQL

                        // Options avancées de Pods
                        'supports_title' => false,                      // false pour désactiver le titre (car dissocié du reste du formulaire d'édition)
                        'supports_quick_edit' => false,                 // false pour désactiver l'édition rapide

                        // Paramètres de hiérarchie
                        'hierarchical' => $pod_config['hierarchical'],  // Indique si le pod doit gérer une hiérarchie (parent / enfant) comme pour les pages statiques par exemple

                        // Paramètres de capacités
                        'capability_type' => 'post',                    // Définit le pod comme un type de contenu de base (pour les permissions)
                        'map_meta_cap' => true,                         // true pour une gestion correcte des permissions

                        // Paramètres d'export
                        'can_export' => true,                           // Indique que les données peuvent être exportées depuis l'admin

                        // Paramètres de recherche
                        'exclude_from_search' => true,                  // true car pas de recherche front

                        // Autres paramètres
                        'delete_with_user' => false,                    // false pour préserver les données

                        // Options du pod
                        'options' => [
                            'singleton' => $pod_config['singleton'] ?? false,
                            'title_field' => $pod_config['title_field'] ?? 'none'
                        ]

                    ];

                    $pod_id = $api->save_pod($pod_args);

                    if ($pod_id && !empty($pod_config['groups'])) {
                        pods_api()->cache_flush_pods();

                        foreach ($pod_config['groups'] as $group_name => $group_config) {
                            // Créer le groupe
                            $group_args = [
                                'pod' => $pod_name,
                                'name' => $group_name,
                                'label' => $group_config['label']
                            ];

                            $api->save_group($group_args);

                            // Créer les champs
                            if (!empty($group_config['fields'])) {
                                foreach ($group_config['fields'] as $field_name => $field_config) {
                                    $field_args = [
                                        'pod' => $pod_name,
                                        'name' => $field_name,
                                        'label' => $field_config['label'],
                                        'description' => $field_config['description'],
                                        'type' => $field_config['type'],
                                        'required' => $field_config['required'] ?? false,
                                        'unique' => $field_config['unique'] ?? false,
                                        'default_value' => $field_config['default_value'] ?? null,
                                        'group' => $group_name,
                                        'show_in_graphql' => true,
                                        'wpgraphql_enabled' => true,
                                    ];

                                    try {
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
         * Supprime tous les pods configurés de la base au moment de la désactivation du plugin
         * @return void
         */
        public function delete_all_pods() {
            if (!function_exists('pods_api')) {
                return;
            }

            $api = pods_api();

            // On parcourt la configuration pour supprimer chaque pod
            foreach (array_keys($this->pods_config) as $pod_name) {
                try {
                    if ($api->pod_exists($pod_name)) {
                        $api->delete_pod(['name' => $pod_name]);
                    }
                } catch (Exception $e) {
                    // Log l'erreur si besoin
                    error_log("Erreur lors de la suppression du pod {$pod_name}: " . $e->getMessage());
                }
            }
        }

        /**
         * Synchronise le champ de titre WordPress avec un champ Pod configuré (pour éviter que tous nos éléments s'appellent "Brouillon")
         * @param $pieces
         * @param $is_new_item
         * @param $post_id
         * @return void
         */
        public function sync_pod_field_with_title($pieces, $is_new_item, $post_id) {
            if (!function_exists('pods_api')) {
                return;
            }

            $api = pods_api();
            $pod = $api->load_pod(['name' => $pieces['pod']['name']]);

            // Vérifier si un champ est configuré pour le titre
            if (empty($pod['options']['title_field']) || $pod['options']['title_field'] === 'none') {
                return;
            }

            // Récupérer la valeur du champ
            $pod_object = pods($pieces['pod']['name'], $post_id);
            $title_value = $pod_object->field($pod['options']['title_field']);

            if (!empty($title_value)) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $title_value
                ]);
            }
        }

        public function add_title_sync_option($options, $pod) {
            if ($pod['type'] === 'post_type') {
                // Récupérer la liste des champs du pod
                $fields = pods_api()->load_fields([
                    'pod' => $pod['name']
                ]);

                // Préparer les options de champs pour le select
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
