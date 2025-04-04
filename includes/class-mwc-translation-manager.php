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
        private static $isProcessingTrash = false;
        private static $isProcessingUntrash = false;

        public function __construct() {
            $this->init_hooks();
        }

        private function init_hooks(): void
        {
            // Ajout d'un champ de configuration pour la traduction dans Pods
            add_filter('pods_admin_setup_edit_field_options', [$this, 'add_translation_option'], 10, 2);

            // Hook sur la requête REST pour la mise à jour des champs Pods
            add_filter('rest_pre_dispatch', [$this, 'handle_field_save'], 10, 3);

            // Ajoute un flag de traduction aux champs Pods
            add_filter('pods_form_ui_label', [$this, 'add_translation_flag_to_label'], 10, 3);
            add_filter('pods_form_ui_field_label_text', [$this, 'add_translation_flag_to_label'], 10, 3);

            // Supprime toutes les traductions d'un post lorsqu'il est supprimé
            add_action('wp_trash_post', [$this, 'delete_linked_translations']);
            add_action('before_delete_post', [$this, 'delete_linked_translations']);
            // Restaure toutes les traductions d'un post lorsqu'il est restauré
            add_action('untrash_post', [$this, 'restore_linked_translations']);

            // Ajoute une entrée dans le menu des paramètres de WordPress pour gérer les traductions
            add_action('admin_menu', [$this, 'add_admin_menu']);
        }

        /**
         * Initialisation des traductions
         * Cette méthode est appelée lors de l'activation du plugin
         * @return void
         */
        public function init_translations(): void
        {
            $this->rebuild_all_translation_config();
        }

        /**
         * Reconstruit entièrement la configuration des traductions pour tous les pods
         * On crée ici une configuration pour éviter de parcourir tous les champs de Pods à chaque fois
         * @return array Configuration des traductions
         */
        public function rebuild_all_translation_config(): array
        {
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
         * Ajoute l'option de traduction dans l'interface de configuration des champs Pods dans l'admin
         *
         * @param array $options Les options actuelles du champ
         * @return array Les options modifiées
         */
        public function add_translation_option($options): array
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
         * @param mixed $result Résultat actuel de la requête
         * @param WP_REST_Server $server Serveur REST
         * @param WP_REST_Request $request Requête REST
         * @return mixed Résultat modifié
         */
        public function handle_field_save($result, $server, $request): mixed
        {
            $route = $request->get_route();

            // Vérifie si c'est une requête POST sur un champ Pods
            if (preg_match('#^/pods/v1/fields/(\d+)$#', $route, $matches) && $request->get_method() === 'POST') {
                $params = $request->get_json_params();

                // Si on a les données nécessaires
                if (!empty($params['pod_id']) && !empty($params['name'])) {
                    $pod = pods_api()->load_pod(['id' => $params['pod_id']]);

                    if ($pod) {
                        $field_name = $params['name'];
                        $is_translatable = !empty($params['args']['is_translatable']);

                        // On met à jour la configuration avec la nouvelle valeur du champ
                        $this->update_field_translation_config($pod['name'], $field_name, $is_translatable);
                    }
                }
            }

            return $result;
        }

        /**
         * Met à jour la configuration de traduction pour un champ spécifique
         *
         * @param string $pod_name Nom du pod
         * @param string $field_name Nom du champ
         * @param bool $is_translatable Indique si le champ est traduisible
         * @return array Configuration mise à jour
         */
        private function update_field_translation_config($pod_name, $field_name, $is_translatable): array
        {
            // Récupère la configuration existante
            $config = get_transient('_mwc_translation_config_cache');
            if (!$config) {
                $config = get_option('_mwc_translation_config', [
                    'pods' => [],
                    'fields' => [],
                    'all_pod_fields' => []
                ]);
            }

            // Met à jour la configuration pour ce champ spécifique
            $config['fields'][$field_name] = [
                'pod' => $pod_name,
                'translatable' => $is_translatable
            ];

            // Met à jour la configuration pour tous les champs
            $config['all_pod_fields'][$field_name] = [
                'pod' => $pod_name,
                'translatable' => $is_translatable,
                'action' => $is_translatable ? 'translate' : 'copy'
            ];

            // Vérifie si le pod a au moins un champ traduisible
            $pod_has_translatable = false;
            foreach ($config['fields'] as $f_name => $f_data) {
                if ($f_data['pod'] === $pod_name && $f_data['translatable']) {
                    $pod_has_translatable = true;
                    break;
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
         * @return void
         */
        public function delete_linked_translations(int $post_id): void
        {
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
         * @return void
         */
        public function restore_linked_translations($post_id): void
        {
            // Vérifie si Polylang est actif
            if (!function_exists('pll_get_post_translations')) {
                return;
            }

            // Éviter une boucle infinie sur la suppression
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
         * @return true
         */
        public function clear_translation_config_cache(): true
        {
            // Supprime simplement le cache transient
            delete_transient('_mwc_translation_config_cache');
            return true;
        }

        /**
         * Génère le fichier wpml-config.xml basé sur la configuration des champs traduisibles
         * Version optimisée qui n'inclut pas les champs en mode 'copy' lorsque tous les champs
         * d'un pod sont en mode 'copy'
         *
         * @param null $config Configuration des traductions (optional)
         * @return bool Succès ou échec de la génération du fichier
         * @throws DOMException
         */
        private function generate_wpml_config($config = null): bool
        {
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
         * @return array Liste des champs traduisibles
         */
        private function get_translatable_fields(): array
        {
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

        /**
         * Ajoute une entrée dans le menu des paramètres de WordPress pour gérer les traductions
         * @return void
         */
        public function add_admin_menu(): void
        {
            add_submenu_page(
                'options-general.php',
                'MWC Traductions',
                'MWC Traductions',
                'manage_options',
                'mwc-translation-settings',
                [$this, 'render_admin_page']
            );
        }

        /**
         * Affiche la page d'administration pour gérer les traductions
         * @return void
         */
        public function render_admin_page(): void
        {
            echo '<div class="wrap">';
            echo '<h1>MWC Traductions</h1>';

            $this->render_translation_status();

            echo '<div class="refresh-info notice notice-info inline" style="margin: 20px 0; padding: 10px 15px; border-left-color: #2271b1;">';
            echo '<h3 style="margin-top: 0;">"Rafraîchir la configuration" ?</h3>';
            echo '<p>Ce bouton permet de :</p>';
            echo '<ul style="list-style-type: disc; margin-left: 20px;">';
            echo '<li>Scanner tous les pods et leurs champs pour détecter ceux qui sont marqués comme traduisibles</li>';
            echo '<li>Mettre à jour le cache interne des traductions</li>';
            echo '<li>Régénérer le fichier wpml-config.xml utilisé par Polylang pour gérer les traductions</li>';
            echo '</ul>';
            echo '<p><strong>Quand l\'utiliser ?</strong> Si vous avez créé de nouveaux pods ou modifié manuellement des configurations de traduction, ou si vous constatez des problèmes avec les traductions.</p>';
            echo '</div>';

            // Formulaire pour rafraîchir la configuration
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="mwc_clear_cache" value="1">';
            submit_button('Rafraîchir la configuration');
            echo '</form>';

            echo '</div>'; // .wrap

            if (isset($_POST['mwc_clear_cache'])) {
                $this->rebuild_all_translation_config();
                update_option('mwc_translations_initialized', 'yes');
                echo '<div class="updated"><p>Configuration des traductions reconstruite et cache vidé.</p></div>';
                echo '<script>window.location.reload();</script>'; // Recharge la page pour afficher les modifications
            }
        }

        /**
         * Affiche la configuration actuelle des traductions
         * @return void
         */
        private function render_translation_status() {
            // Récupère la configuration actuelle
            $config = get_transient('_mwc_translation_config_cache');
            if (!$config) {
                $config = get_option('_mwc_translation_config', [
                    'pods' => [],
                    'fields' => [],
                    'all_pod_fields' => []
                ]);
            }

            // Organiser les champs par pod
            $fields_by_pod = $this->get_fields_organized_by_pod($config);

            // Afficher la configuration actuelle
            if (!empty($fields_by_pod)) {
                echo '<div class="translation-status" style="margin: 20px 0; max-width: 100%;">';
                echo '<h2>Configuration actuelle des traductions</h2>';
                echo '<p>Cette section affiche tous les pods et leur statut de traduction. Les pods qui n\'ont pas de champs traduisibles ne seront pas inclus dans le fichier WPML/Polylang.</p>';

                echo '<div class="pods-accordion" style="margin-top: 20px;">';

                // Pour chaque pod
                foreach ($fields_by_pod as $pod_name => $pod_fields) {
                    $this->render_pod_item($pod_name, $pod_fields);
                }

                echo '</div>'; // .pods-accordion
                echo '</div>'; // .translation-status

                // Ajouter le script JavaScript pour l'accordéon
                $this->render_accordion_script();
            } else {
                echo '<div class="notice notice-warning"><p>Aucune configuration de traduction trouvée. Cliquez sur "Rafraîchir la configuration" pour scanner tous les pods.</p></div>';
            }
        }

        /**
         * Organise les champs par pod et type d'action (translate/copy)
         * @param array $config Configuration des traductions
         * @return array Tableau organisé des champs par pod
         */
        private function get_fields_organized_by_pod($config) {
            $fields_by_pod = [];

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
                    } else {
                        $fields_by_pod[$pod_name]['copy'][$field_name] = $field_data;
                    }
                }
            }

            return $fields_by_pod;
        }

        /**
         * Affiche un élément pod avec ses champs
         * @param string $pod_name Nom du pod
         * @param array $pod_fields Tableau des champs du pod
         */
        private function render_pod_item($pod_name, $pod_fields): void
        {
            $has_translatable = !empty($pod_fields['translate']);
            $status_class = $has_translatable ? 'pod-translatable' : 'pod-not-translatable';
            $status_icon = $has_translatable ? '🌐' : '⛔';
            $status_text = $has_translatable ? 'Traduisible' : 'Non traduisible';

            echo '<div class="pod-item '.$status_class.'" style="margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; overflow: hidden;">';
            echo '<div class="pod-header" style="padding: 10px 15px; background-color: ' . ($has_translatable ? '#e7f7f4' : '#f7f7f7') . '; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">';
            echo '<h3 style="margin: 0; padding: 0;">' . esc_html($pod_name) . ' <span style="font-weight: normal; font-size: 14px;">(' . count($pod_fields['translate']) + count($pod_fields['copy']) . ' champs)</span></h3>';
            echo '<span class="pod-status" style="font-size: 14px; ' . ($has_translatable ? 'color: #0073aa;' : 'color: #999;') . '">' . $status_icon . ' ' . $status_text . ' <span class="dashicons dashicons-arrow-down-alt2"></span></span>';
            echo '</div>';

            echo '<div class="pod-fields" style="display: none; padding: 15px; background-color: #fff;">';

            // Section des champs traduisibles
            if (!empty($pod_fields['translate'])) {
                $this->render_field_list($pod_fields['translate'], 'Champs traduisibles', '🔤', '#0073aa');
            }

            // Section des champs non traduisibles
            if (!empty($pod_fields['copy'])) {
                $this->render_field_list($pod_fields['copy'], 'Champs non traduisibles (copiés)', '🔢', '#999', '#777');
            }

            echo '</div>'; // .pod-fields
            echo '</div>'; // .pod-item
        }

        /**
         * Affiche une liste de champs
         * @param array $fields Liste des champs à afficher
         * @param string $title Titre de la section
         * @param string $icon Icône à afficher
         * @param string $title_color Couleur du titre
         * @param string $text_color Couleur du texte (optionnel)
         */
        private function render_field_list($fields, $title, $icon, $title_color, $text_color = ''): void
        {
            echo '<div class="fields-list" style="margin-bottom: 15px;">';
            echo '<h4 style="margin-top: 0; color: ' . $title_color . ';">' . $icon . ' ' . $title . '</h4>';
            echo '<ul style="margin: 0; padding: 0 0 0 20px;' . ($text_color ? ' color: ' . $text_color . ';' : '') . '">';
            foreach ($fields as $field_name => $field_data) {
                echo '<li>' . esc_html($field_name) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        /**
         * Génère le script JavaScript pour l'accordéon
         * @return void
         */
        private function render_accordion_script(): void
        {
            echo '<script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(".pod-header").click(function() {
                            $(this).next(".pod-fields").slideToggle("fast");
                            $(this).find(".dashicons").toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
                        });
                    });
                </script>';
        }
    }
}
