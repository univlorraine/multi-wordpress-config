=== Multi Wordpress Config ===
Contributors: benjhoo
Tags: headless, wordpress, multi, administration, esup
Requires at least: 6.0
Tested up to: 6.8.1
Stable tag: 0.3.8
License: CeCILL-2.1
License URI: https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html

Plugin permettant de personnaliser l'administration WordPress pour une utilisation Headless.

== Description ==
Plugin permettant de personnaliser l'administration WordPress pour une utilisation Headless. Développé par l'Université de Lorraine pour l'application ESUP-MULTI.

== Changelog ==
= 0.3.8 =
* Ajout du nouveau pod pour la gestion de la base de connaissance depuis Wordpress
* Augmentation du nombre de réponses pour les requêtes GraphQL de 100 à 500 par défaut
* Correction bug perte de relations entre les pods des POIs et icônes lors de la désactivation / réactivation du plugin
* Ajout d'une fonctionnalité qui permet d'activer automatiquement les nouveaux pods lors de la mise à jour du plugin (plus besoin de désactiver / réactiver le plugin)

= 0.3.7 =
* Modification des champs Campus et Icône pour le POD map-points, passés à optionnels

= 0.3.6 =
* Ajout de 4 nouveaux pods pour la gestion de la map interactive depuis Wordpress (map-points, campuses, map-categories, map-icons)
* Désactiver le plugin retire désormais les pods personnalisés dans l'interface de Wordpress
* Nouvelle fonctionnalité permettant de réparer les liens cassés des traductions à l'import des données Wordpress

= 0.3.5 =
* Suppression valeur par défaut 'filtrable' pour les channels

= 0.3.4 =
* Modification du type pods pour les icônes SVG

= 0.3.3 =
* Suppression de la majuscule sur le code libellé du menu des services

= 0.3.2 =
* Sanitization du HTML pour l'attribut contenu du pod Important-News

= 0.3.1 =
* Ajout d'un attribut 'position' dans le pod Important-News

= 0.3.0 =
* Ajout plugin-update-checker
* Correction problème traduction Singleton Login

= 0.2.0 =
* silence is golden

= 0.1.0 =
* Version initiale
