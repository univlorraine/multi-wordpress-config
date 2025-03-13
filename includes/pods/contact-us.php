<?php

// Sécurité : empêche l'accès direct au fichier.
if (!defined('ABSPATH')) {
    exit;
}

//------------------------------------------------------------
// Singleton pod : contact_us
//------------------------------------------------------------

$pod_name = 'contact_us';

return [
    'name' => $pod_name,
    'label' => 'Formulaire de contact',
    'label_singular' => 'Formulaire de contact',
//    'label_add_new' => 'Nouveau formulaire de contact', // Inutile car le pod est un singleton
    'description' => 'Formulaire de contact de l\'application Multi',
    'menu-position' => 8,
    'menu-icon' => 'dashicons-email-alt',
    'singleton' => true,
    'title_field' => $pod_name . '_title', // Indique quel champ sera utilisé comme titre dans l'interface d'administration (autrement affiche 'brouillon')
    'graphql_singular_name' => 'contact_us_one', // Obligé de nommer le champ mais on ne l'utilise pas car le pod est un singleton
    'graphql_plural_name' => $pod_name,
    'groups' => [
        $pod_name . '_fields' => [
            'label' => 'Champs Formulaire de contact',
            'fields' => [
                $pod_name . '_title' => [
                    'type' => 'text',
                    'label' => 'Titre de formulaire',
                    'description' => 'Titre de la page de contact (affiché dans le menu).',
                    'required' => true,
                ],
                $pod_name . '_content' => [
                    'type' => 'wysiwyg',
                    'label' => 'Contenu',
                    'description' => 'Texte accompagnant le formulaire de contact.',
                    'required' => true,
                ],
                $pod_name . '_to' => [
                    'type' => 'email',
                    'label' => 'Adresse de contact',
                    'description' => 'Adresse email de contact derrière le formulaire',
                    'required' => true,
                ],
                $pod_name . '_icon' => [
                    'type' => 'text',
                    'label' => 'Icône',
                    'description' => 'Nom \'ion-icon\' de l\'icône à afficher dans le menu.',
                    'required' => false,
                ],
            ],
        ]
    ]
];
