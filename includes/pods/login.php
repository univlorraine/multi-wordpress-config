<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

//------------------------------------------------------------
// Singleton pod : login
//------------------------------------------------------------

$pod_name = 'login';

return [
    'name' => $pod_name,
    'label' => 'Page de login',
    'label_singular' => 'Page de login',
    //    'label_add_new' => 'Nouvelle page de login', // Inutile car le pod est un singleton
    'description' => 'Page de login de l\'application Multi',
    'menu-position' => 7,
    'menu-icon' => 'dashicons-id',
    'singleton' => true,
    'title_field' => $pod_name . '_not_authenticated_text', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'graphql_singular_name' => 'login_one', // Obligé de nommer le champ mais on ne l'utilise pas car le pod est un singleton
    'graphql_plural_name' => $pod_name,
    'groups' => [
        $pod_name . '_fields' => [
            'label' => 'Champs Page de login',
            'fields' => [
                $pod_name . '_not_authenticated_text' => [
                    'type' => 'text',
                    'label' => 'Texte non authentifié',
                    'description' => 'Phrase en page d\'accueil invitant l\'utilisateur à s\'authentifier',
                ],
                $pod_name . '_connection_text' => [
                    'type' => 'wysiwyg',
                    'label' => 'Texte de connexion',
                    'description' => 'Texte accompagnant le formulaire d\'authentification'
                ],
            ],
        ]
    ]
];
