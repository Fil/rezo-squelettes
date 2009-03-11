<?php

define('_SYNDICATION_CORRECTION', false);
define('_SYNDICATION_URL_UNIQUE', true);
define('_ID_WEBMESTRES', '3');

function rezo_post_syndication($data) {
	static $sites = array();
	$url = $data[0];
	$id_syndic = $data[1];

	if (!isset($sites[$id_syndic]))
		$sites[$id_syndic] = sql_fetsel('*', 'spip_syndic',
		'id_syndic='.sql_quote($id_syndic));

		/* statut idiot : on pourrait dire 'bof' = 'prop' */
		/* attention la syndication demande le statut 'dispo' et non 'prop'
		  (a changer dans le core) */

	$update = array(
		'id_rubrique' => $sites[$id_syndic]['id_rubrique'],
		'id_secteur' => $sites[$id_syndic]['id_secteur']
	);

	// valeur par defaut de la source
	// si publiee : 'article' (sinon '' aka depeche)
	if ($sites[$id_syndic]['statut'] == 'publie')
		$update['type'] = 'article';

	// bug sur certaines sources (http://blogs.lesoir.be/colette-braeckman/feed/) :
	// la langue indiquee est anglais, alors que les textes sont fr.
	if ($sites[$id_syndic]['descriptif'])
		$data[2]['lang'] = $update['lang'] = 'fr';

	lang_select($data[2]['lang']);


	// COMMENT GERER LES RETITRAGES MAISON ? ==> champ titre2 ?

	// Indiquer le "bon" titre
	$update['titre'] = trim(preg_replace(',\s+,ims', ' ', $data[2]['titre']));
	// Supprimer les trucs entre crochets, genre [by fil.rezo.net] de delicious
	$update['titre'] = trim(preg_replace(',[[].*[]],', '', $update['titre']));
	// "titre, par auteur"
	if (strlen($data[2]['lesauteurs'])
	AND !preg_match('/,\s(par|by)\s/i', $update['titre']))
		$update['titre'] = $update['titre']
		.', '._T('forum_par_auteur', array('auteur' => $data[2]['lesauteurs']));

	#var_dump($data);var_dump($update);exit;


	sql_updateq('spip_syndic_articles',
		$update,
		'url='.sql_quote($url).' AND id_syndic='.sql_quote($id_syndic));

	lang_select();


	// Ajouter les mots-cles associes au site source



	return $data;
}


/*

selon statut du site : prop ou publie
et selon options 'moderation' du site 'oui' => breve, 'non' => 'article'

<SELECT NAME="ps" SIZE="1">

NON PUBLIES :======> statut 'prop' du site
<option value=0 SELECTED>Dépêche Bof</option>
<option value=1 >Article Bof</option>

PUBLIES :========> statut 'publie' du site
<option value=2 >Dépêche Bien</option>
<option value=3 >Article Bien</option>

Comment distinguer articles et depeches ? par defaut depeches sauf si le site a une particularité (titre commençant par ...) ?

MANUEL :
<option value=4 >(Très bien)</option>
<option value=5 >A la Une</option>
</SELECT>

*/


/*

mysql> select id_article,titre from spip_articles where url_site='';
+------------+-----------------------------------------------------------------------------+
| id_article | titre                                                                       |
+------------+-----------------------------------------------------------------------------+
| 2          | La liste de diffusion                                                       |
| 3          | Votre page de démarrage                                                     |
| 6          | Découvrez notre « Toolbar » et recommandez en un clic vos es préférés |
| 22619      | Brevetage et pneumopathie atypique, par Hervé Le Crosnier                   |
| 53405      | Livre d'Or                                                                  |
| 61805      | Les thèmes de rezo.net                                                      |
+------------+-----------------------------------------------------------------------------+

=> On pourrait ajouter un UNIQUE sur url_site, pour interdire les syndications en provenance de sites differents (ou manuel / automatique)

*/