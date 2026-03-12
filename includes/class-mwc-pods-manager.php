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

            // Fix pour map_point le champ de relation map_point_category
            add_filter('pods_field_pick_data', [$this, 'filter_map_point_category_field_data'], 10, 6);
            add_filter('pods_form_ui_field_pick_value', [$this, 'filter_map_point_category_ui_field_value'], 10, 5);
            add_action('pods_api_post_save_pod_item_map_points', [$this, 'action_map_point_category_sync_translation'], 10, 3);

            // Permet de reconstruire les relations Pods après un import
            // Hack pour palier au problème soulevé auprès de Pods : https://github.com/pods-framework/pods/issues/7415
            add_action('import_end', [$this, 'rebuild_pods_relations_after_import'], 20);
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
                // On n'utilise pas include_once ici, car on a besoin de l'instance pour la suppression des pods à la désactivation du plugin
                $pod_data = include $pod_file;

                if (is_array($pod_data) && isset($pod_data['pod_config']) && isset($pod_data['pod_fields']) && isset($pod_data['pod_config']['name'])) {
                    $this->pods_config[$pod_data['pod_config']['name']] = $pod_data;
                } else {
                    error_log('Configuration de pod invalide dans le fichier : ' . $pod_file);
                }
            }
        }

        /**
         * Vérifie si des pods configurés existent déjà (pour éviter d'écraser l'existant)
         * @return array
         */
        public function check_missing_pods(): array {
            if (!function_exists('pods_api')) {
                return array_keys($this->pods_config);
            }

            $api = pods_api();
            $missing_pods = [];

            foreach (array_keys($this->pods_config) as $pod_name) {
                if (!$api->pod_exists($pod_name)) {
                    $missing_pods[] = $pod_name;
                }
            }

            return $missing_pods;
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

            // On récupère la liste des pods manquants
            $missing_pods = $this->check_missing_pods();

            if (empty($missing_pods)) {
                return; // Tous les pods sont déjà installés
            }

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
                    // Si le pod est déjà créé, on passe au suivant
                    if (!in_array($pod_name, $missing_pods)) {
                        continue;
                    }

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
         * Ajoute une option dans l'administration de Pods pour synchroniser un champ avec le titre par défaut de WordPress
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

        /**
         * Pour le champ relation map_point_category de map_point.
         * Filtre les éléments liés pour n'inclure que ceux de la langue courante (Polylang).
         *
         * @param array $data
         * @param string $name
         * @param $value
         * @param $options
         * @param $pod
         * @param $id
         * @return array
         *
         * @see https://web.archive.org/web/20171124121811/http://hookr.io/plugins/pods-custom-content-types-and-fields/2.5.5/filters/pods_field_pick_data/
         */
        public function filter_map_point_category_field_data($data, $name, $value, $options, $pod, $id): array
        {
            if ($name !== 'map_point_category' || !function_exists('pll_current_language')) {
                return $data;
            }

            $lang = pll_current_language();
            if (empty($lang) || !is_array($data)) {
                return $data;
            }

            foreach($data as $key => $item) {
                $item_lang = pll_get_post_language($key);
                if ($item_lang && $item_lang !== $lang) {
                    unset($data[$key]);
                }
            }

            return $data;
        }

        /**
         * Pour le champ relation map_point_category de map_point.
         * Corrige la valeur présélectionnée par Pods pour qu'elle corresponde à ce qui est stocké en base. (bug Pods)
         * Ou lors de la création d'une nouvelle traduction, préremplit avec la catégorie traduite.
         * @param $value
         * @param string $name
         * @param $options
         * @param $field
         * @param $id
         * @return mixed
         *
         * @see https://web.archive.org/web/20171124122419/http://hookr.io/plugins/pods-custom-content-types-and-fields/2.5.5/filters/pods_form_ui_field_type_value/
         */
        public function filter_map_point_category_ui_field_value($value, $name, $options, $field, $id): mixed
        {
            if ($name !== 'map_point_category') {
                return $value;
            }

            // Cas 2 : Nouvelle traduction (détection via les paramètres GET de Polylang)
            if (isset($_GET['from_post']) && isset($_GET['new_lang'])) {
                $source_post_id = intval($_GET['from_post']);
                $target_lang = sanitize_text_field($_GET['new_lang']);

                // Récupérer la catégorie du post source
                $source_category_id = get_post_meta($source_post_id, 'map_point_category', true);

                if (!empty($source_category_id) && function_exists('pll_get_post')) {
                    // Trouver la traduction de la catégorie dans la langue cible
                    $translated_category = pll_get_post($source_category_id, $target_lang);

                    if ($translated_category) {
                        return [$translated_category => $translated_category];
                    }
                }

                return $value;
            }

            // Cas 1 : Édition d'un post existant
            if ($id) {
                $stored_value = get_post_meta($id, 'map_point_category', true);

                if (!empty($stored_value)) {
                    return [$stored_value => $stored_value];
                }
            }

            return $value;
        }

        /**
         * Pour le pod map_points, synchronise la catégorie liée lors de la sauvegarde d'une traduction (Polylang)
         * @param array $pieces
         * @param bool $is_new_item
         * @param int $id
         * @return void
         *
         * @see https://docs.pods.io/code/action-reference/pods_api_post_save_pod_item_podname/
         */
        public function action_map_point_category_sync_translation($pieces, $is_new_item, $id): void {
            $category_id = get_post_meta($id, 'map_point_category', true);

            if (empty($category_id) || !function_exists('pll_get_post_translations')) {
                return;
            }

            $translations = pll_get_post_translations($id);
            foreach ($translations as $lang => $translation_id) {
                if ($translation_id == $id) {
                    continue; // Skip le post actuel
                }

                // Trouver la traduction correspondante de la catégorie
                $translated_category = pll_get_post($category_id, $lang);
                if ($translated_category) {
                    update_post_meta($translation_id, 'map_point_category', $translated_category);
                }
            }
        }

        /**
         * Reconstruit les relations Pods après un import WordPress
         * S'exécute automatiquement à la fin d'un import via le hook 'import_end'
         * Hack pour palier au problème soulevé auprès de Pods : https://github.com/pods-framework/pods/issues/7415
         * @return void
         */
        public function rebuild_pods_relations_after_import(): void
        {
            global $wpdb;

            // Récupérer tous les posts qui ont des métadonnées _pods_* (relations)
            $meta_keys = $wpdb->get_col("
                SELECT DISTINCT meta_key 
                FROM {$wpdb->postmeta} 
                WHERE meta_key LIKE '\\_pods\\_%'
            ");

            if (empty($meta_keys)) {
                error_log("MWC_Pods_Manager - Aucune métadonnée de relation Pods trouvée");
                return;
            }

            $relations_count = 0;
            $skipped_count = 0;

            foreach ($meta_keys as $meta_key) {
                // Extraire le nom du champ sans le préfixe _pods_
                $field_name = substr($meta_key, 6);

                // Récupérer tous les posts avec cette métadonnée
                $posts_with_meta = $wpdb->get_results($wpdb->prepare("
                    SELECT post_id, meta_value 
                    FROM {$wpdb->postmeta} 
                    WHERE meta_key = %s
                ", $meta_key));

                foreach ($posts_with_meta as $post_meta) {
                    $post_id = $post_meta->post_id;
                    $post_type = get_post_type($post_id);

                    if (!$post_type) {
                        error_log("MWC_Pods_Manager - Post ID {$post_id} n'a pas de type de post valide");
                        continue;
                    }

                    // Récupérer les informations sur le pod et le champ
                    try {
                        $pod = pods_api()->load_pod(['name' => $post_type]);

                        if (empty($pod)) {
                            error_log("MWC_Pods_Manager - Pod non trouvé pour le type {$post_type}");
                            continue;
                        }

                        if (!isset($pod['fields'][$field_name])) {
                            error_log("MWC_Pods_Manager - Champ {$field_name} non trouvé dans le pod {$post_type}");
                            continue;
                        }

                        $field = $pod['fields'][$field_name];

                        // Vérifier si c'est un champ de relation
                        if (!in_array($field['type'], ['pick', 'file', 'avatar'])) {
                            error_log("MWC_Pods_Manager - Champ {$field_name} n'est pas un champ de relation, mais de type {$field['type']}");
                            continue;
                        }

                        // Désérialiser la valeur de la métadonnée
                        $related_ids = maybe_unserialize($post_meta->meta_value);

                        // Si c'est une chaîne ou un entier, le convertir en tableau
                        if (!is_array($related_ids)) {
                            $related_ids = [$related_ids];
                        }

                        // Trouver le pod et le champ de la relation
                        $related_pod_id = 0;
                        if (!empty($field['pick_val'])) {
                            $related_pod = pods_api()->load_pod(['name' => $field['pick_val']]);
                            if (!empty($related_pod)) {
                                $related_pod_id = $related_pod['id'];
                            }
                        }

                        // Ajouter les nouvelles relations
                        foreach ($related_ids as $related_id) {
                            if (empty($related_id)) {
                                continue;
                            }

                            // Vérifier si la relation existe déjà
                            $exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}podsrel 
                                WHERE pod_id = %d AND field_id = %d AND item_id = %d AND related_item_id = %d",
                                $pod['id'], $field['id'], $post_id, $related_id
                            ));

                            if ($exists) {
                                $skipped_count++;
                                continue;
                            }

                            // Effectuer l'insertion et vérifier le résultat
                            $result = $wpdb->insert(
                                "{$wpdb->prefix}podsrel",
                                [
                                    'pod_id' => $pod['id'],
                                    'field_id' => $field['id'],
                                    'item_id' => $post_id,
                                    'related_pod_id' => $related_pod_id,
                                    'related_field_id' => 0,
                                    'related_item_id' => $related_id,
                                    'weight' => 0
                                ]
                            );

                            if ($result) {
                                $relations_count++;
                                error_log("MWC_Pods_Manager - Relation reconstruite: {$post_id} -> {$related_id} (champ: {$field_name})");
                            } else {
                                error_log("MWC_Pods_Manager - Échec de l'insertion: {$post_id} -> {$related_id} (erreur: " . $wpdb->last_error . ")");
                            }
                        }

                    } catch (Exception $e) {
                        error_log("MWC_Pods_Manager - Erreur lors de la reconstruction: " . $e->getMessage());
                    }
                }
            }

            error_log("MWC_Pods_Manager - Reconstruction terminée: {$relations_count} relations reconstruites, {$skipped_count} relations ignorées (déjà existantes)");

            // Vider le cache des pods après modification
            if (function_exists('pods_api') && method_exists(pods_api(), 'cache_flush_pods')) {
                pods_api()->cache_flush_pods();
            }
        }

        /**
         * Supprime tous les pods configurés (sans supprimer les données utilisateur)
         * Les données restent en base et seront ré-associées lors de la réactivation du plugin
         * @return void
         */
        public function delete_all_pods(): void
        {
            if (!function_exists('pods_api')) {
                error_log('MWC_Pods_Manager - API Pods non disponible pour la suppression.');
                return;
            }

            $api = pods_api();
            $deleted_count = 0;

            // On parcourt la configuration pour supprimer chaque pod
            foreach (array_keys($this->pods_config) as $pod_name) {
                try {
                    if ($api->pod_exists($pod_name)) {
                        // Supprime uniquement la structure du pod, pas les données
                        $api->delete_pod(['name' => $pod_name]);
                        $deleted_count++;
                        error_log("MWC_Pods_Manager - Pod '{$pod_name}' supprimé");
                    }
                } catch (Exception $e) {
                    error_log("MWC_Pods_Manager - Erreur lors de la suppression du pod '{$pod_name}': " . $e->getMessage());
                }
            }

            if ($deleted_count > 0) {
                // Vider le cache des pods après suppression
                $api->cache_flush_pods();
                error_log("MWC_Pods_Manager - {$deleted_count} pods supprimés avec succès");
            }
        }
    }
}
