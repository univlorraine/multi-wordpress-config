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
 * Classe permettant de gérer les traductions des champs Pods via le plugin Polylang
 */
if (!class_exists('MWC_Translation_Manager')) {
    class MWC_Translation_Manager
    {
        private const CACHE_KEY = 'mwc_non_translatable_fields_cache';
        private const OPTION_POLYLANG_CHANGED = 'mwc_polylang_options_changed';
        private const TRANSIENT_CLEANUP = 'mwc_pods_relations_cleanup_last_run';

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            // Ajout d'un champ de configuration pour la traduction dans Pods
            add_filter('pods_admin_setup_edit_field_options', [$this, 'add_translation_option'], 10, 2);

            // Intercepter les requêtes API REST pour les pods et les champs
            add_filter('rest_pre_dispatch', [$this, 'handle_rest_requests'], 10, 3);

            // Lors de la modification des réglages Polylang
            add_action('wp_ajax_pll_save_options', [$this, 'intercept_polylang_options_save'], 1);
            add_action('admin_init', [$this, 'check_polylang_options_changed']);

            // Ajoute un flag de traduction aux champs Pods
            add_filter('pods_form_ui_label', [$this, 'add_translation_flag_to_label'], 10, 3);
            add_filter('pods_form_ui_field_label_text', [$this, 'add_translation_flag_to_label'], 10, 3);

            // Ajoute les champs non traduisibles à la liste des méta-données à copier par Polylang
            add_filter('pll_copy_post_metas', [$this, 'copy_post_metas'], 10, 3);

            // Hack pour gérer la copie des relations Pods
            // cf: https://github.com/pods-framework/pods/issues/7415
            add_action('add_meta_boxes', [$this, 'setup_translation_relations'], 10, 2);
            add_action('save_post', [$this, 'sync_translation_relations'], 10, 3);
            add_action('admin_init', [$this, 'clean_orphaned_relations'], 10);

            // Supprime / restaure toutes les traductions associées à un post lors de sa suppression / restauration
            add_action('wp_trash_post', [$this, 'trash_post_translations'], 10, 1);
            add_action('untrash_post', [$this, 'untrash_post_translations'], 10, 1);
        }

        /**
         * Détermine si un type de post est traduisible dans Polylang
         *
         * @param string $post_type Le type de post à vérifier
         * @return bool True si le type de post est traduisible, false sinon
         */
        private function is_post_type_translatable(string $post_type): bool
        {
            if (function_exists('pll_is_translated_post_type')) {
                return pll_is_translated_post_type($post_type);
            }

            $polylang_options = get_option('polylang');
            $post_types = $polylang_options['post_types'] ?? [];
            return in_array($post_type, $post_types);
        }

        /**
         * Récupère les champs non traduisibles depuis un pod
         *
         * @param array|\Pods\Whatsit\Pod $pod Le pod chargé
         * @return array Liste des noms de champs non traduisibles
         */
        private function get_non_translatable_fields_from_pod($pod): array
        {
            $non_translatable_fields = [];

            // Si le pod n'a pas de champs, retourner un tableau vide
            if (empty($pod['fields'])) {
                return $non_translatable_fields;
            }

            // Parcourir tous les champs du pod
            foreach ($pod['fields'] as $field) {
                // Vérifier si le champ n'est pas traduisible (is_translatable = 0 ou absent)
                $is_translatable = isset($field['options']['is_translatable']) ?
                    filter_var($field['options']['is_translatable'], FILTER_VALIDATE_BOOLEAN) :
                    false;

                if (!$is_translatable) {
                    $non_translatable_fields[] = $field['name'];

                    // Ajouter également le nom interne du champ Pods pour la compatibilité
                    $non_translatable_fields[] = '_pods_' . $field['name'];
                }
            }

            return $non_translatable_fields;
        }

        /**
         * Retourne l'emoji du drapeau en fonction du code de langue.
         */
        private function get_flag_emoji(string $lang) {
            $flags = [
                'fr' => '🇫🇷',
                'en' => '🇬🇧',
                'es' => '🇪🇸',
                'de' => '🇩🇪',
                'it' => '🇮🇹',
                'nl' => '🇳🇱',
                'pt' => '🇵🇹',
                'ru' => '🇷🇺',
                'ja' => '🇯🇵',
                'zh' => '🇨🇳'
            ];

            return $flags[$lang] ?? '';
        }

        /**
         * Ajoute l'option de traduction dans l'interface de configuration des champs Pods dans l'admin
         * Uniquement pour les types de posts synchronisés avec Polylang
         *
         * @param array $options Les options actuelles du champ
         * @return array Les options modifiées
         */
        public function add_translation_option($options, $pod = null): array
        {
            // Vérifie si Polylang est actif
            if (!function_exists('pll_languages_list')) {
                return $options;
            }

            // Vérifie si le pod actuel est un type de publication valide
            $pod_name = $pod['name'] ?? '';
            if (empty($pod_name)) {
                return $options;
            }

            // Vérifie si le type de publication est traduisible
            if (!$this->is_post_type_translatable($pod_name)) {
                return $options;
            }

            $options['basic'][] = [
                'name' => 'is_translatable',
                'label' => 'Champ traductible',
                'type' => 'boolean',
                'default' => false,
                'boolean_yes_label' => 'Ce champ peut être traduit',
                'help' => 'Si activé, ce champ pourra être traduit dans les différentes langues configurées',
                'weight' => 25
            ];

            return $options;
        }

        /**
         * Gère les requêtes REST pour les champs Pods
         *
         * @param mixed $result Résultat de la requête
         * @param WP_REST_Server $server Instance du serveur REST
         * @param WP_REST_Request $request Requête REST
         * @return mixed Le résultat non modifié
         */
        public function handle_rest_requests($result, $server, $request): mixed
        {
            $route = $request->get_route();
            $method = $request->get_method();

            // Uniquement traiter les modifications de champs
            if (preg_match('#^/pods/v1/fields/(\d+)($|\?)#', $route, $matches) && $method === 'POST') {
                $params = $request->get_json_params();

                if (!empty($params['pod_id']) && isset($params['args']['is_translatable'])) {
                    try {
                        $pod_id = $params['pod_id'];
                        $field_name = $params['name'] ?? '';
                        $is_translatable = !empty($params['args']['is_translatable']);

                        // On met à jour le cache pour ce champ spécifique
                        $this->update_field_in_cache($pod_id, $field_name, $is_translatable);
                    } catch (Exception $e) {
                        error_log("MWC_Translation_Manager - Erreur lors de la mise à jour du cache: " . $e->getMessage());
                    }
                } else {
                    error_log("MWC_Translation_Manager - Données insuffisantes pour la mise à jour du cache");
                }
            }
            return $result;
        }

        /**
         * Mise à jour directe d'un champ spécifique dans le cache
         *
         * @param int $pod_id ID du pod
         * @param string $field_name Nom du champ
         * @param bool $is_translatable Si le champ est traduisible
         */
        public function update_field_in_cache($pod_id, $field_name, $is_translatable): void
        {
            if (empty($field_name)) {
                error_log("MWC_Translation_Manager - Nom de champ vide, impossible de mettre à jour le cache");
                return;
            }

            try {
                // Récupérer le pod pour obtenir son nom
                $pod = pods_api()->load_pod(['id' => $pod_id]);

                if (empty($pod) || empty($pod['name'])) {
                    error_log("MWC_Translation_Manager - Pod ID {$pod_id} introuvable, impossible de mettre à jour le cache");
                    return;
                }

                $pod_name = $pod['name'];

                // Vérifier si le pod est traduisible
                if (!$this->is_post_type_translatable($pod_name)) {
                    error_log("MWC_Translation_Manager - Pod {$pod_name} non traduisible, aucune mise à jour du cache");
                    return;
                }

                // Récupérer le cache actuel
                $cache = get_option(self::CACHE_KEY, []);

                // Initialiser l'entrée pour ce pod si elle n'existe pas
                if (!isset($cache[$pod_name])) {
                    $cache[$pod_name] = [];
                }

                // Noms des champs dans le cache (avec et sans préfixe _pods_)
                $field_keys = [$field_name, '_pods_' . $field_name];

                if ($is_translatable) {
                    // Si le champ est traduisible, le retirer du cache
                    foreach ($field_keys as $key) {
                        $index = array_search($key, $cache[$pod_name]);
                        if ($index !== false) {
                            unset($cache[$pod_name][$index]);
                        }
                    }
                    $cache[$pod_name] = array_values($cache[$pod_name]); // Réindexer le tableau
                } else {
                    // Si le champ n'est pas traduisible, l'ajouter au cache s'il n'y est pas déjà
                    foreach ($field_keys as $key) {
                        if (!in_array($key, $cache[$pod_name])) {
                            $cache[$pod_name][] = $key;
                        }
                    }
                }

                // Sauvegarder le cache mis à jour
                update_option(self::CACHE_KEY, $cache, false);
            } catch (Exception $e) {
                error_log("MWC_Translation_Manager - Erreur lors de la mise à jour du champ dans le cache: " . $e->getMessage());
            }
        }


        /**
         * Intercepte la sauvegarde des options Polylang via AJAX
         * On indique juste qu'une modification a eu lieu sur les paramètres Polylang mais on n'interfère pas avec la requête Ajax
         * Le traitement aura lieu via le hook init au prochain chargement d'un page de l'admin
         */
        public function intercept_polylang_options_save(): void
        {
            error_log('MWC_Translation_Manager - Interception de la sauvegarde des options Polylang');

            // Marquer que les options ont changé
            update_option(self::OPTION_POLYLANG_CHANGED, time(), false);
        }

        /**
         * Vérifie si les options Polylang ont changé en comparant les timestamps
         */
        public function check_polylang_options_changed(): void
        {
            // Vérifier si nous avons une notification qu'un changement a eu lieu
            $polylang_changed = get_option(self::OPTION_POLYLANG_CHANGED, false);

            if ($polylang_changed) {
                error_log('MWC_Translation_Manager - Changement des options Polylang détecté');

                // Réinitialiser le drapeau
                delete_option(self::OPTION_POLYLANG_CHANGED);

                // Reconstruire le cache
                $this->rebuild_cache_for_selected_pods();
            }
        }

        /**
         * Reconstruit le cache pour tous les pods sélectionnés dans les paramètres de Polylang
         */
        public function rebuild_cache_for_selected_pods(): void
        {
            if (!function_exists('pods_api') || !class_exists('PodsAPI')) {
                return;
            }

            try {
                // Récupérer les pods traduisibles selon Polylang
                $polylang_options = get_option('polylang');
                $translatable_post_types = $polylang_options['post_types'] ?? [];

                if (empty($translatable_post_types)) {
                    // Si aucun type n'est traduisible, on vide le cache
                    delete_option(self::CACHE_KEY);
                    return;
                }

                // Récupérer le cache actuel ou créer un nouveau
                $cache = get_option(self::CACHE_KEY, []);

                // Récupérer tous les podss
                $api = pods_api();
                $all_pods = $api->load_pods();

                // Pour chaque pod traduisible, mettre à jour le cache
                foreach ($all_pods as $pod) {
                    $pod_name = $pod['name'];

                    if (in_array($pod_name, $translatable_post_types)) {
                        $cache[$pod_name] = $this->get_non_translatable_fields_from_pod($pod);
                    } else {
                        // Supprimer du cache les pods qui ne sont plus traduisibles
                        if (isset($cache[$pod_name])) {
                            unset($cache[$pod_name]);
                        }
                    }
                }

                // Sauvegarder le cache mis à jour
                update_option(self::CACHE_KEY, $cache, false);
            } catch (Exception $e) {
                error_log('MWC_Translation_Manager - Erreur lors de la reconstruction du cache: ' . $e->getMessage());
            }
        }

        /**
         * Ajoute un drapeau emoji à côté des champs traduisibles dans l'admin Pods.
         * @param string $label Label du champ
         * @param string $name Nom du champ
         * @param array|null $options Options du champ
         * @return string Label modifié
         */
        public function add_translation_flag_to_label($label, $name, $options = null): string
        {
            if (!function_exists('pll_current_language')) {
                return $label;
            }

            // Récupérer le post type actuel
            $post_type = get_post_type();
            if (!$post_type) {
                // Essayer de détecter le pod depuis l'URL
                global $pagenow;
                if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'pods-edit-pod' && isset($_GET['id'])) {
                    $pod_id = intval($_GET['id']);
                    $pod = pods_api()->load_pod(['id' => $pod_id]);
                    $post_type = $pod['name'] ?? '';
                }

                if (!$post_type) {
                    return $label;
                }
            }

            // Vérifier si le type de post est traduisible
            if (!$this->is_post_type_translatable($post_type)) {
                return $label;
            }

            // Récupérer le cache
            $cache = get_option(self::CACHE_KEY, []);

            // Si le champ n'est pas dans la liste des champs non traduisibles, on affiche le drapeau
            if (isset($cache[$post_type]) && !in_array($name, $cache[$post_type])) {
                $current_lang = pll_current_language();
                $flag_emoji = $this->get_flag_emoji($current_lang);
                return $label . ' ' . $flag_emoji;
            }

            return $label;
        }

        /**
         * Ajoute à la liste des méta-données à synchroniser tous les champs Pods non traduisibles
         *
         * @param array $metas Les métadonnées à synchroniser
         * @param bool $sync Si true, on est en mode synchronisation, sinon en mode copie
         * @param int $from ID du post source
         * @param int $to ID du post de destination
         * @param string $lang Code de la langue de destination
         * @return array Liste mise à jour des métadonnées à synchroniser
         */
        public function copy_post_metas($metas, $sync = false, $from = null, $to = null, $lang = null): array
        {
            // Vérifier si les paramètres from et to sont disponibles
            if (empty($from)) {
                return $metas;
            }

            // Récupérer le type de post
            $post_type = get_post_type($from);

            // Vérifier si le type de post est traduisible
            if (!$this->is_post_type_translatable($post_type)) {
                return $metas;
            }

            // Récupérer les champs non traduisibles depuis le cache
            $cache = get_option(self::CACHE_KEY, []);
            $non_translatable_fields = $cache[$post_type] ?? [];

            // Si le cache est vide pour ce type de post, essayer de le reconstruire
            if (empty($non_translatable_fields)) {
                error_log("MWC_Translation_Manager - Cache vide pour {$post_type}, tentative de reconstruction");

                // Charger le pod et reconstruire le cache pour ce type
                try {
                    $pod = pods_api()->load_pod(['name' => $post_type]);
                    if ($pod) {
                        $this->update_pod_cache($pod);
                        // Recharger le cache après la mise à jour
                        $cache = get_option(self::CACHE_KEY, []);
                        $non_translatable_fields = $cache[$post_type] ?? [];
                    }
                } catch (Exception $e) {
                    error_log("MWC_Translation_Manager - Erreur lors de la reconstruction du cache: " . $e->getMessage());
                }
            }

            // Fusionner avec la liste existante
            return array_merge($metas, $non_translatable_fields);
        }

        /**
         * Permet de copier les fields de type relation lors de la création d'une traduction
         * Hack mis en place pour corriger le problème de Pods et Polylang : https://github.com/pods-framework/pods/issues/7415
         *
         * @param string $post_type Type de publication
         * @param WP_Post $post Objet post
         */
        public function setup_translation_relations($post_type, $post): void
        {
            global $wpdb;

            // Vérifier si nous sommes dans le cas d'une nouvelle traduction
            if (!isset($_GET['from_post']) || !isset($_GET['new_lang'])) {
                return;
            }

            $source_post_id = intval($_GET['from_post']);
            $target_post_id = $post->ID;

            // Des vérifications plus strictes pour éviter les valeurs invalides
            if ($source_post_id <= 0 || $target_post_id <= 0 || $source_post_id === $target_post_id) {
                error_log("MWC_Translation_Manager - IDs invalides: source={$source_post_id}, target={$target_post_id}");
                return;
            }

            error_log("MWC_Translation_Manager - Nouvelle traduction en cours de création : {$source_post_id} -> {$target_post_id}");

            // Vérifier si des relations existent déjà pour le post cible
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}podsrel WHERE item_id = %d",
                $target_post_id
            ));

            // S'il n'y a pas de relations existantes, copier celles du post source
            if ($existing == 0) {
                // Récupérer les relations du post source
                $relations = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}podsrel WHERE item_id = %d",
                    $source_post_id
                ));

                // Copier les relations, mais uniquement si elles sont valides
                foreach ($relations as $relation) {
                    // Vérifier que toutes les valeurs nécessaires sont valides
                    if ($relation->pod_id <= 0 || $relation->field_id <= 0 || $relation->related_item_id <= 0) {
                        error_log("MWC_Translation_Manager - Relation invalide ignorée: " . print_r($relation, true));
                        continue;
                    }

                    $wpdb->insert(
                        "{$wpdb->prefix}podsrel",
                        [
                            'pod_id' => $relation->pod_id,
                            'field_id' => $relation->field_id,
                            'item_id' => $target_post_id,
                            'related_pod_id' => $relation->related_pod_id,
                            'related_field_id' => $relation->related_field_id,
                            'related_item_id' => $relation->related_item_id,
                            'weight' => $relation->weight
                        ]
                    );

                    error_log("MWC_Translation_Manager - Relation copiée: item_id={$target_post_id}, related_item_id={$relation->related_item_id}");
                }
            }

            // Nous gérons aussi les relations inverses, mais avec des vérifications strictes
            $inverse_relations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}podsrel WHERE related_item_id = %d",
                $source_post_id
            ));

            foreach ($inverse_relations as $relation) {
                // Vérifier que toutes les valeurs nécessaires sont valides
                if ($relation->pod_id <= 0 || $relation->field_id <= 0 || $relation->item_id <= 0) {
                    error_log("MWC_Translation_Manager - Relation inverse invalide ignorée: " . print_r($relation, true));
                    continue;
                }

                // Vérifier si cette relation inverse existe déjà
                $existing_inverse = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}podsrel WHERE pod_id = %d AND field_id = %d AND item_id = %d AND related_item_id = %d",
                    $relation->pod_id, $relation->field_id, $relation->item_id, $target_post_id
                ));

                if ($existing_inverse == 0) {
                    $wpdb->insert(
                        "{$wpdb->prefix}podsrel",
                        [
                            'pod_id' => $relation->pod_id,
                            'field_id' => $relation->field_id,
                            'item_id' => $relation->item_id,
                            'related_pod_id' => $relation->related_pod_id,
                            'related_field_id' => $relation->related_field_id,
                            'related_item_id' => $target_post_id,
                            'weight' => $relation->weight
                        ]
                    );

                    error_log("MWC_Translation_Manager - Relation inverse copiée: item_id={$relation->item_id}, related_item_id={$target_post_id}");
                }
            }
        }

        /**
         * Synchronise les relations entre toutes les traductions d'un post
         *
         * @param int $post_id ID du post
         * @param WP_Post $post Objet post
         * @param bool $update Si c'est une mise à jour
         */
        public function sync_translation_relations($post_id, $post, $update): void
        {
            global $wpdb;

            // Ne pas exécuter pour les auto-save ou les révisions
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
                return;
            }

            // Ne pas exécuter si le post n'est pas publié
            if ($post->post_status !== 'publish') {
                return;
            }

            // Vérification supplémentaire pour s'assurer que l'ID est valide
            if ($post_id <= 0) {
                error_log("MWC_Translation_Manager - ID de post invalide: {$post_id}");
                return;
            }

            // Vérifier si Polylang est actif
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            // Récupérer toutes les traductions du post
            $translations = pll_get_post_translations($post_id);

            // S'il n'y a qu'une seule traduction (le post lui-même), ne rien faire
            if (count($translations) <= 1) {
                return;
            }

            // Récupérer les relations du post actuel
            $relations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}podsrel WHERE item_id = %d",
                $post_id
            ));

            // Récupérer les relations inverses
            $inverse_relations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}podsrel WHERE related_item_id = %d",
                $post_id
            ));

            // Pour chaque traduction, mettre à jour les relations
            foreach ($translations as $lang => $translation_id) {
                // Ne pas traiter le post actuel
                if ($translation_id == $post_id || $translation_id <= 0) {
                    continue;
                }

                // Supprimer les relations existantes avec vérification
                $wpdb->delete("{$wpdb->prefix}podsrel", ['item_id' => $translation_id]);
                $wpdb->delete("{$wpdb->prefix}podsrel", ['related_item_id' => $translation_id]);

                // Copier les relations directes avec vérifications
                foreach ($relations as $relation) {
                    // Vérifier que toutes les valeurs nécessaires sont valides
                    if ($relation->pod_id <= 0 || $relation->field_id <= 0 || $relation->related_item_id <= 0) {
                        error_log("MWC_Translation_Manager - Relation invalide ignorée pendant la synchronisation: " . print_r($relation, true));
                        continue;
                    }

                    $wpdb->insert(
                        "{$wpdb->prefix}podsrel",
                        [
                            'pod_id' => $relation->pod_id,
                            'field_id' => $relation->field_id,
                            'item_id' => $translation_id,
                            'related_pod_id' => $relation->related_pod_id,
                            'related_field_id' => $relation->related_field_id,
                            'related_item_id' => $relation->related_item_id,
                            'weight' => $relation->weight
                        ]
                    );
                }

                // Copier les relations inverses avec vérifications
                foreach ($inverse_relations as $relation) {
                    // Vérifier que toutes les valeurs nécessaires sont valides
                    if ($relation->pod_id <= 0 || $relation->field_id <= 0 || $relation->item_id <= 0) {
                        error_log("MWC_Translation_Manager - Relation inverse invalide ignorée pendant la synchronisation: " . print_r($relation, true));
                        continue;
                    }

                    $wpdb->insert(
                        "{$wpdb->prefix}podsrel",
                        [
                            'pod_id' => $relation->pod_id,
                            'field_id' => $relation->field_id,
                            'item_id' => $relation->item_id,
                            'related_pod_id' => $relation->related_pod_id,
                            'related_field_id' => $relation->related_field_id,
                            'related_item_id' => $translation_id,
                            'weight' => $relation->weight
                        ]
                    );
                }
            }
        }

        /**
         * Nettoie périodiquement les relations orphelines dans la table wp_podsrel
         *
         * @since 1.0.0
         * @access public
         * @return void
         */
        public function clean_orphaned_relations(): void
        {
            // Ne pas exécuter à chaque chargement d'admin, mais une fois par jour
            $last_run = get_transient(self::TRANSIENT_CLEANUP);

            if ($last_run !== false) {
                return;
            }

            global $wpdb;

            error_log("MWC_Translation_Manager - Nettoyage périodique des relations orphelines");

            // Supprimer les relations où item_id pointe vers un post qui n'existe plus
            $deleted_items = $wpdb->query("
                DELETE FROM {$wpdb->prefix}podsrel
                WHERE item_id > 0
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->posts} 
                    WHERE ID = item_id
                )
            ");

            // Supprimer les relations où related_item_id pointe vers un post qui n'existe plus
            $deleted_related = $wpdb->query("
                DELETE FROM {$wpdb->prefix}podsrel
                WHERE related_item_id > 0
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->posts} 
                    WHERE ID = related_item_id
                )
            ");

            if ($deleted_items > 0 || $deleted_related > 0) {
                error_log("MWC_Translation_Manager - Nettoyage terminé : {$deleted_items} relations directes et {$deleted_related} relations inverses orphelines supprimées");
            }

            // Marquer comme exécuté pour les prochaines 24 heures
            set_transient(self::TRANSIENT_CLEANUP, time(), DAY_IN_SECONDS);
        }

        /**
         * Déplace à la corbeille toutes les traductions d'un post lorsque celui-ci est mis à la corbeille
         *
         * @param int $post_id ID du post mis à la corbeille
         */
        public function trash_post_translations($post_id): void
        {
            // Vérifier si Polylang est actif
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            // Éviter les appels récursifs en vérifiant si on est déjà en train de traiter une suppression en cascade
            static $processing_translations = false;
            if ($processing_translations) {
                return;
            }

            // Récupérer toutes les traductions du post
            $translations = pll_get_post_translations($post_id);

            // S'il n'y a qu'une seule traduction (le post lui-même), ne rien faire
            if (count($translations) <= 1) {
                return;
            }

            // Marquer qu'on est en train de traiter des traductions pour éviter la récursion
            $processing_translations = true;

            // Récupérer les posts qui sont en cours de suppression dans une action en masse
            $bulk_posts = [];
            if (isset($_REQUEST['post']) && is_array($_REQUEST['post'])) {
                $bulk_posts = array_map('intval', $_REQUEST['post']);
            }

            error_log("MWC_Translation_Manager - Traitement de la suppression en cascade pour le post {$post_id}");

            // Pour chaque traduction, la mettre à la corbeille si elle n'est pas déjà incluse dans une action en masse
            foreach ($translations as $lang => $translation_id) {
                // Ne pas traiter le post actuel
                if ($translation_id == $post_id) {
                    continue;
                }

                // Vérifier si cette traduction est déjà dans la liste des posts à supprimer en masse
                if (in_array($translation_id, $bulk_posts)) {
                    error_log("MWC_Translation_Manager - Traduction {$translation_id} ignorée (déjà dans une suppression en masse)");
                    continue;
                }

                // Mettre la traduction à la corbeille
                wp_trash_post($translation_id);
                error_log("MWC_Translation_Manager - Traduction {$translation_id} mise à la corbeille");
            }

            // Réinitialiser l'indicateur
            $processing_translations = false;
        }

        /**
         * Restaure toutes les traductions d'un post lorsque celui-ci est restauré de la corbeille
         *
         * @param int $post_id ID du post restauré
         */
        public function untrash_post_translations($post_id): void
        {
            // Vérifier si Polylang est actif
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            // Éviter les appels récursifs
            static $processing_translations = false;
            if ($processing_translations) {
                return;
            }

            // Récupérer toutes les traductions du post
            $translations = pll_get_post_translations($post_id);

            // S'il n'y a qu'une seule traduction (le post lui-même), ne rien faire
            if (count($translations) <= 1) {
                return;
            }

            // Marquer qu'on est en train de traiter des traductions pour éviter la récursion
            $processing_translations = true;

            // Récupérer les posts qui sont en cours de restauration dans une action en masse
            $bulk_posts = [];
            if (isset($_REQUEST['post']) && is_array($_REQUEST['post'])) {
                $bulk_posts = array_map('intval', $_REQUEST['post']);
            }

            error_log("MWC_Translation_Manager - Traitement de la restauration en cascade pour le post {$post_id}");

            // Pour chaque traduction, la restaurer si elle n'est pas déjà incluse dans une action en masse
            foreach ($translations as $lang => $translation_id) {
                // Ne pas traiter le post actuel
                if ($translation_id == $post_id) {
                    continue;
                }

                // Vérifier si cette traduction est déjà dans la liste des posts à restaurer en masse
                if (in_array($translation_id, $bulk_posts)) {
                    error_log("MWC_Translation_Manager - Traduction {$translation_id} ignorée (déjà dans une restauration en masse)");
                    continue;
                }

                // Vérifier si la traduction est dans la corbeille
                $translation_status = get_post_status($translation_id);
                if ($translation_status === 'trash') {
                    // Restaurer la traduction
                    wp_untrash_post($translation_id);
                    error_log("MWC_Translation_Manager - Traduction {$translation_id} restaurée de la corbeille");
                } else {
                    error_log("MWC_Translation_Manager - Traduction {$translation_id} ignorée (pas dans la corbeille)");
                }
            }

            // Réinitialiser l'indicateur
            $processing_translations = false;
        }
    }
}
