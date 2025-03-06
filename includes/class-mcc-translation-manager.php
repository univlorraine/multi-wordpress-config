<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MCC_Translation_Manager')) {
    class MCC_Translation_Manager
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

            // Hook sur la requête REST pour les champs Pods
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
                    'MCC Traductions',
                    'MCC Traductions',
                    'manage_options',
                    'mcc-translation-settings',
                    function () {
                        echo '<div class="wrap"><h1>MCC Traductions</h1>';
                        echo '<form method="post" action="">';
                        echo '<input type="hidden" name="mcc_clear_cache" value="1">';
                        submit_button('Rafraîchir la configuration');
                        echo '</form></div>';

                        if (isset($_POST['mcc_clear_cache'])) {
                            (new MCC_Translation_Manager())->clear_translation_config_cache();
                            echo '<div class="updated"><p>Cache des traductions vidé.</p></div>';
                        }
                    }
                );
            });
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
                        $field_data = [
                            'pod_name' => $pod['name'],
                            'field_name' => $params['name'],
                            'is_translatable' => !empty($params['args']['is_translatable'])
                        ];

                        // Récupère l'ancienne configuration pour vérifier si elle a changé
                        $config = get_transient('_mcc_translation_config_cache');
                        if (!$config) {
                            $config = get_option('_mcc_translation_config', [
                                'pods' => [],
                                'fields' => []
                            ]);
                        }

                        $old_value = false;
                        if (isset($config['fields'][$params['name']]['translatable'])) {
                            $old_value = $config['fields'][$params['name']]['translatable'];
                        }

                        // Met à jour la configuration
                        $this->update_translation_config($field_data);

                        // Si la valeur de is_translatable a changé, on régénère le fichier wpml-config.xml
                        if ($old_value !== $field_data['is_translatable']) {
                            $this->generate_wpml_config();
                        }
                    }
                }
            }

            return $result;
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
         * Envoie toutes les traductions d’un post dans la corbeille.
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
         * Restaure toutes les traductions associées lorsqu’un post est restauré.
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
         * Met à jour la configuration des traductions et calcule tous les champs
         * des pods traduisibles pour le cache.
         *
         * @param array $field_data Données du champ à mettre à jour
         * @return array Configuration mise à jour
         */
        private function update_translation_config($field_data) {
            // Récupère la configuration existante
            $config = get_transient('_mcc_translation_config_cache');

            if (!$config) {
                $config = get_option('_mcc_translation_config', [
                    'pods' => [],
                    'fields' => [],
                    'all_pod_fields' => [] // Nouvelle section pour stocker tous les champs
                ]);
            }

            // Met à jour la configuration du champ
            $config['fields'][$field_data['field_name']] = [
                'pod' => $field_data['pod_name'],
                'translatable' => $field_data['is_translatable']
            ];

            // Met à jour la liste des pods avec des champs traduisibles
            if ($field_data['is_translatable']) {
                $config['pods'][$field_data['pod_name']] = true;
            } else {
                // Vérifie si d'autres champs du pod sont traduisibles
                $pod_has_translatable = false;
                foreach ($config['fields'] as $field) {
                    if ($field['pod'] === $field_data['pod_name'] && $field['translatable']) {
                        $pod_has_translatable = true;
                        break;
                    }
                }
                if (!$pod_has_translatable) {
                    unset($config['pods'][$field_data['pod_name']]);
                }
            }

            // Recalcule tous les champs pour tous les pods traduisibles
            $all_pod_fields = [];

            if (!empty($config['pods'])) {
                foreach ($config['pods'] as $pod_name => $has_translatable) {
                    if ($has_translatable) {
                        // Récupère tous les champs pour ce pod
                        $pod_obj = pods_api()->load_pod(['name' => $pod_name]);
                        if ($pod_obj && isset($pod_obj['fields'])) {
                            foreach ($pod_obj['fields'] as $field) {
                                // Détermine si le champ est traduisible en se basant sur la configuration
                                $is_translatable = false;
                                if (isset($config['fields'][$field['name']]['translatable'])) {
                                    $is_translatable = $config['fields'][$field['name']]['translatable'];
                                }

                                $all_pod_fields[$field['name']] = [
                                    'pod' => $pod_name,
                                    'translatable' => $is_translatable,
                                    'action' => $is_translatable ? 'translate' : 'copy'
                                ];
                            }
                        }
                    }
                }
            }

            // Stocke la liste complète des champs dans la configuration
            $config['all_pod_fields'] = $all_pod_fields;

            // Sauvegarde la configuration
            update_option('_mcc_translation_config', $config);
            set_transient('_mcc_translation_config_cache', $config, 3600); // Met à jour le cache

            return $config;
        }

        /**
         * Vide le cache de la configuration des traductions
         */
        public function clear_translation_config_cache() {
            // Supprime simplement le cache transient
            delete_transient('_mcc_translation_config_cache');

            // Récupère la configuration existante depuis les options
            $config = get_option('_mcc_translation_config', [
                'pods' => [],
                'fields' => [],
                'all_pod_fields' => []
            ]);

            // Régénère le fichier wpml-config.xml avec la configuration existante
            $this->generate_wpml_config($config);

            return true;
        }


        /**
         * Génère le fichier wpml-config.xml basé sur la configuration des champs traduisibles
         *
         * @param array|null $config Configuration des traductions (optional)
         * @return bool Succès ou échec de la génération du fichier
         */
        private function generate_wpml_config($config = null) {
            // Si la configuration n'est pas fournie, on la récupère
            if ($config === null) {
                $config = get_transient('_mcc_translation_config_cache');

                if (!$config) {
                    $config = get_option('_mcc_translation_config', [
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

            // Ajoute chaque champ au XML avec l'action pré-calculée
            if (isset($config['all_pod_fields']) && !empty($config['all_pod_fields'])) {
                foreach ($config['all_pod_fields'] as $field_name => $field_data) {
                    $field = $xml->createElement('custom-field');
                    $field->setAttribute('action', $field_data['action']);
                    $field->appendChild($xml->createTextNode($field_name));
                    $customFields->appendChild($field);
                }
            }

            // Section custom-types
            $customTypes = $xml->createElement('custom-types');
            $root->appendChild($customTypes);

            // Ajoute chaque type de pod qui a au moins un champ traduisible
            if (isset($config['pods']) && !empty($config['pods'])) {
                foreach ($config['pods'] as $pod_name => $has_translatable) {
                    if ($has_translatable) {
                        $type = $xml->createElement('custom-type');
                        $type->setAttribute('translate', '1');
                        $type->appendChild($xml->createTextNode($pod_name));
                        $customTypes->appendChild($type);
                    }
                }
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
            $config = get_option('_mcc_translation_config', []);
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
