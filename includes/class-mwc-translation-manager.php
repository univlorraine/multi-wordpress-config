<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MWC_Translation_Manager')) {
    class MWC_Translation_Manager
    {
        private static $isProcessingTrash = false;
        private static $isProcessingUntrash = false;

        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks()
        {
            // Ajout d'un champ de configuration pour la traduction dans Pods
            add_filter('pods_admin_setup_edit_field_options', [$this, 'add_translation_option'], 10, 2);

            // Hook sur la requête REST pour la mise à jour des champs Pods
            add_filter('rest_pre_dispatch', [$this, 'handle_field_save'], 10, 3);

            // Ajoute un flag de traduction aux champs Pods
            add_filter('pods_form_ui_label', [$this, 'add_translation_flag_to_label'], 10, 3);
            add_filter('pods_form_ui_field_label_text', [$this, 'add_translation_flag_to_label'], 10, 3);

            add_action('wp_trash_post', [$this, 'delete_linked_translations']);
            add_action('before_delete_post', [$this, 'delete_linked_translations']);
            add_action('untrash_post', [$this, 'restore_linked_translations']);

            add_action('admin_menu', function () {
                add_submenu_page(
                    'options-general.php',
                    'MWC Traductions',
                    'MWC Traductions',
                    'manage_options',
                    'mwc-translation-settings',
                    function () {
                        echo '<div class="wrap"><h1>MWC Traductions</h1>';
                        echo '<form method="post" action="">';
                        echo '<input type="hidden" name="mwc_clear_cache" value="1">';
                        submit_button('Rafraîchir la configuration');
                        echo '</form></div>';

                        if (isset($_POST['mwc_clear_cache'])) {
                            $this->rebuild_all_translation_config();
                            update_option('mwc_translations_initialized', 'yes');
                            echo '<div class="updated"><p>Configuration des traductions reconstruite et cache vidé.</p></div>';
                        }
                    }
                );
            });
        }

        /**
         * Initialise les traductions
         * Cette méthode est destinée à être appelée depuis la classe principale
         * après l'initialisation complète de Pods
         */
        public function init_translations() {
            $this->rebuild_all_translation_config();
        }

        /**
         * Reconstruit entièrement la configuration des traductions pour tous les pods
         */
        public function rebuild_all_translation_config() {
            // Initialiser la configuration
            $config = [
                'pods' => [],
                'fields' => [],
                'all_pod_fields' => []
            ];

            // Récupérer tous les pods existants
            $all_pods = pods_api()->load_pods();

            if (!empty($all_pods)) {
                foreach ($all_pods as $pod) {
                    $pod_name = $pod['name'];
                    $pod_has_translatable = false;

                    // Parcourir tous les champs de ce pod
                    if (isset($pod['fields']) && !empty($pod['fields'])) {
                        foreach ($pod['fields'] as $field) {
                            $field_name = $field['name'];
                            $is_translatable = !empty($field['options']['is_translatable']);

                            // Mettre à jour la configuration du champ
                            $config['fields'][$field_name] = [
                                'pod' => $pod_name,
                                'translatable' => $is_translatable
                            ];

                            // Ajouter ce champ à la liste complète
                            $config['all_pod_fields'][$field_name] = [
                                'pod' => $pod_name,
                                'translatable' => $is_translatable,
                                'action' => $is_translatable ? 'translate' : 'copy'
                            ];

                            // Vérifier si au moins un champ du pod est traduisible
                            if ($is_translatable) {
                                $pod_has_translatable = true;
                            }
                        }
                    }

                    // Ajouter le pod à la liste si au moins un champ est traduisible
                    if ($pod_has_translatable) {
                        $config['pods'][$pod_name] = true;
                    }
                }
            }

            // Sauvegarder la configuration
            update_option('_mwc_translation_config', $config);
            set_transient('_mwc_translation_config_cache', $config, 3600);

            // Générer le fichier wpml-config.xml
            $this->generate_wpml_config($config);

            return $config;
        }

        /**
         * Ajoute l'option de traduction dans l'interface de configuration des champs Pods
         *
         * @param array $options Les options actuelles du champ
         * @param array $field Les données du champ
         * @return array Les options modifiées
         */
        public function add_translation_option($options, $field)
        {
            // Vérifie si Polylang est actif
            if (!function_exists('pll_languages_list')) {
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
         * Gère la sauvegarde d'un champ via l'API REST
         * C'est cette méthode qui est appelée lors de la sauvegarde d'un champ
         * dans l'interface d'administration de Pods
         */
        public function handle_field_save($result, $server, $request) {
            $route = $request->get_route();

            // Vérifie si c'est une requête POST sur un champ Pods
            if (preg_match('#^/pods/v1/fields/(\d+)$#', $route, $matches) && $request->get_method() === 'POST') {
                $params = $request->get_json_params();

                // Si on a les données nécessaires
                if (!empty($params['pod_id']) && !empty($params['name'])) {
                    $pod = pods_api()->load_pod(['id' => $params['pod_id']]);

                    if ($pod) {
                        error_log('MWC Debug - Mise à jour de la configuration de traduction pour le pod: ' . $pod['name']);

                        // On met à jour la configuration de tout le pod
                        $this->update_pod_translation_config($pod['name']);
                    }
                }
            } else if (preg_match('#^/pods/v1/pods/(\d+)$#', $route, $matches) && $request->get_method() === 'POST') {
                // Gestion des modifications de pod
                $pod_id = $matches[1];
                $pod = pods_api()->load_pod(['id' => $pod_id]);

                if ($pod) {
                    error_log('MWC Debug - Mise à jour de la configuration de traduction pour le pod modifié: ' . $pod['name']);

                    // On met à jour la configuration de tout le pod
                    $this->update_pod_translation_config($pod['name']);
                }
            }

            return $result;
        }

        /**
         * Met à jour la configuration des traductions pour un pod spécifique
         *
         * @param string $pod_name Nom du pod à mettre à jour
         */
        private function update_pod_translation_config($pod_name) {
            // Récupère la configuration existante
            $config = get_transient('_mwc_translation_config_cache');

            if (!$config) {
                $config = get_option('_mwc_translation_config', [
                    'pods' => [],
                    'fields' => [],
                    'all_pod_fields' => []
                ]);
            }

            // Récupère les informations sur le pod
            $pod = pods_api()->load_pod(['name' => $pod_name]);

            if (!$pod) {
                return $config;
            }

            $pod_has_translatable = false;

            // Parcourt tous les champs du pod et met à jour la configuration
            if (isset($pod['fields']) && !empty($pod['fields'])) {
                foreach ($pod['fields'] as $field) {
                    $field_name = $field['name'];
                    $is_translatable = !empty($field['options']['is_translatable']);

                    // Met à jour la configuration du champ
                    $config['fields'][$field_name] = [
                        'pod' => $pod_name,
                        'translatable' => $is_translatable
                    ];

                    // Met à jour dans la liste complète
                    $config['all_pod_fields'][$field_name] = [
                        'pod' => $pod_name,
                        'translatable' => $is_translatable,
                        'action' => $is_translatable ? 'translate' : 'copy'
                    ];

                    // Vérifie si au moins un champ du pod est traduisible
                    if ($is_translatable) {
                        $pod_has_translatable = true;
                    }
                }
            }

            // Met à jour le statut du pod
            if ($pod_has_translatable) {
                $config['pods'][$pod_name] = true;
            } else {
                // Si aucun champ n'est traduisible, on retire le pod
                unset($config['pods'][$pod_name]);
            }

            // Sauvegarde la configuration
            update_option('_mwc_translation_config', $config);
            set_transient('_mwc_translation_config_cache', $config, 3600);

            // Régénère le fichier wpml-config.xml
            $this->generate_wpml_config($config);

            return $config;
        }

        /**
         * Ajoute un drapeau emoji à côté des champs traduisibles dans l'admin Pods.
         */
        public function add_translation_flag_to_label($label, $name, $options = null)
        {
            if (!function_exists('pll_current_language')) {
                return $label;
            }

            $translatable_fields = $this->get_translatable_fields();
            if (in_array($name, $translatable_fields)) {
                $flag_emoji = $this->get_flag_emoji(pll_current_language());
                return $label . ' ' . $flag_emoji;
            }

            return $label;
        }

        /**
         * Envoie toutes les traductions d'un post dans la corbeille.
         *
         * @param int $post_id ID du post supprimé
         */
        public function delete_linked_translations(int $post_id): void {
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            if (self::$isProcessingTrash) {
                return;
            }

            self::$isProcessingTrash = true;

            try {
                $translations = pll_get_post_translations($post_id);

                if (!empty($translations) && is_array($translations)) {
                    foreach ($translations as $translated_post_id) {
                        if ($translated_post_id != $post_id && get_post_status($translated_post_id) !== 'trash') {
                            wp_trash_post($translated_post_id);
                        }
                    }
                }
            } finally {
                self::$isProcessingTrash = false;
            }
        }

        /**
         * Restaure toutes les traductions associées lorsqu'un post est restauré.
         *
         * @param int $post_id ID du post restauré
         */
        public function restore_linked_translations($post_id) {
            // Vérifie si Polylang est actif
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            // Éviter la boucle infinie
            if (self::$isProcessingUntrash) {
                return;
            }

            self::$isProcessingUntrash = true;

            try {
                // Récupère toutes les traductions du post
                $translations = pll_get_post_translations($post_id);

                if (!empty($translations) && is_array($translations)) {
                    foreach ($translations as $translated_post_id) {
                        // Évite de restaurer le post d'origine
                        if ($translated_post_id == $post_id) {
                            continue;
                        }

                        // Vérifie si le post est bien dans la corbeille
                        $translated_post = get_post($translated_post_id);
                        if ($translated_post && $translated_post->post_status === 'trash') {
                            wp_untrash_post($translated_post_id);
                        }
                    }
                }
            } finally {
                // Réinitialise le flag une fois terminé
                self::$isProcessingUntrash = false;
            }
        }

        /**
         * Vide le cache de la configuration des traductions
         */
        public function clear_translation_config_cache() {
            // Supprime simplement le cache transient
            delete_transient('_mwc_translation_config_cache');
            return true;
        }

        /**
         * Génère le fichier wpml-config.xml basé sur la configuration des champs traduisibles
         * Version optimisée qui n'inclut pas les champs en mode 'copy' lorsque tous les champs
         * d'un pod sont en mode 'copy'
         *
         * @param array|null $config Configuration des traductions (optional)
         * @return bool Succès ou échec de la génération du fichier
         */
        private function generate_wpml_config($config = null) {
            // Si la configuration n'est pas fournie, on la récupère
            if ($config === null) {
                $config = get_transient('_mwc_translation_config_cache');

                if (!$config) {
                    $config = get_option('_mwc_translation_config', [
                        'pods' => [],
                        'fields' => [],
                        'all_pod_fields' => []
                    ]);
                }
            }

            // Commence à construire le XML
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;

            // Élément racine
            $root = $xml->createElement('wpml-config');
            $xml->appendChild($root);

            // Section custom-fields
            $customFields = $xml->createElement('custom-fields');
            $root->appendChild($customFields);

            // Organiser les champs par pod
            $fields_by_pod = [];
            $translatable_pods = [];

            if (isset($config['all_pod_fields']) && !empty($config['all_pod_fields'])) {
                foreach ($config['all_pod_fields'] as $field_name => $field_data) {
                    $pod_name = $field_data['pod'];

                    if (!isset($fields_by_pod[$pod_name])) {
                        $fields_by_pod[$pod_name] = [
                            'translate' => [],
                            'copy' => []
                        ];
                    }

                    if ($field_data['action'] === 'translate') {
                        $fields_by_pod[$pod_name]['translate'][$field_name] = $field_data;
                        $translatable_pods[$pod_name] = true;
                    } else {
                        $fields_by_pod[$pod_name]['copy'][$field_name] = $field_data;
                    }
                }
            }

            // Ajouter les champs au XML
            foreach ($fields_by_pod as $pod_name => $pod_fields) {
                // Si le pod a au moins un champ traduisible
                if (!empty($pod_fields['translate'])) {
                    // Ajouter tous les champs traduisibles
                    foreach ($pod_fields['translate'] as $field_name => $field_data) {
                        $field = $xml->createElement('custom-field');
                        $field->setAttribute('action', 'translate');
                        $field->appendChild($xml->createTextNode($field_name));
                        $customFields->appendChild($field);
                    }

                    // Ajouter tous les champs non-traduisibles (copy)
                    foreach ($pod_fields['copy'] as $field_name => $field_data) {
                        $field = $xml->createElement('custom-field');
                        $field->setAttribute('action', 'copy');
                        $field->appendChild($xml->createTextNode($field_name));
                        $customFields->appendChild($field);
                    }
                }
                // Si tous les champs sont en mode 'copy', on ne les inclut pas dans le XML
            }

            // Section custom-types
            $customTypes = $xml->createElement('custom-types');
            $root->appendChild($customTypes);

            // Ajoute chaque type de pod qui a au moins un champ traduisible
            foreach ($translatable_pods as $pod_name => $value) {
                $type = $xml->createElement('custom-type');
                $type->setAttribute('translate', '1');
                $type->appendChild($xml->createTextNode($pod_name));
                $customTypes->appendChild($type);
            }

            // Définit le chemin du fichier wpml-config.xml
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'wpml-config.xml';

            // Tente d'écrire le fichier
            $success = $xml->save($file_path);

            if ($success) {
                // Ajoute un message dans le journal
                error_log('Le fichier wpml-config.xml a été généré avec succès.');

                // Si le fichier doit être lisible par le serveur web, ajustez les permissions
                chmod($file_path, 0644);

                return true;
            } else {
                error_log('Échec de la génération du fichier wpml-config.xml.');
                return false;
            }
        }

        /**
         * Récupère la liste des champs Pods traduisibles depuis la configuration.
         */
        private function get_translatable_fields() {
            $config = get_option('_mwc_translation_config', []);
            $translatable_fields = [];

            if (isset($config['fields'])) {
                foreach ($config['fields'] as $field_name => $field_data) {
                    if ($field_data['translatable'] === true) {
                        $translatable_fields[] = $field_name;
                    }
                }
            }

            return $translatable_fields;
        }

        /**
         * Retourne l'emoji du drapeau en fonction du code de langue.
         */
        private function get_flag_emoji($lang) {
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
    }
}
