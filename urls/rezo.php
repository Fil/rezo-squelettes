<?php

define('_url_minuscules', true);
define('_MARQUEUR_URL', serialize(array('rubrique1' => '', 'rubrique2' => '', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '@', 'auteur2' => '', 'mot1' => '', 'mot2' => '')));

function urls_rezo($i, $entite, $args='', $ancre='') {
	static $cache = array();

	// supprimer le themes/ ou sources/
	if (!is_numeric($i)) {
		$i = preg_replace(',^.*/,', '/', $i);
	}
	// si #URL_ARTICLE est demande, pas la peine de chercher dans spip_urls.
	else if ($entite === 'article') {
		return 'a'.$i;
	}
	// si #URL_RUBRIQUE ou #URL_MOT est demandee, utiliser le static
	else if (in_array($entite, array('mot','rubrique'))) {
		if (!isset($cache[$entite])) {
			$tmp = array();
			include_spip('base/abstract_sql');
			foreach(sql_allfetsel(
				$select = array('id_objet', 'url'),
				$from = array('spip_urls'),
				$where = array("type='$entite'"),
				$groupby = array(),
				$orderby = array('date')) as $t
			) {
				$tmp[$t['id_objet']] = $t['url'];
			}
			$cache[$entite] = $tmp;
		}
	}

	if (isset($cache[$entite]) AND isset($cache[$entite][$i]))
		$url = $cache[$entite][$i];
	else {
		$f = charger_fonction('propres', 'urls');
		$url = $f($i, $entite, $args, $ancre);
	}

	if ($entite == 'rubrique')
		$url = 'sources/'.$url;
	if ($entite == 'mot')
		$url = 'themes/'.$url;

	// si on cherche un theme inexistant, ne pas renvoyer '404'
	// mais passer sur une recherche fulltext normale (cf. mot.html)
	// http://v3.rezo.net/themes/sorbonne
	if (is_array($url)
	AND $url[1] == '404'
	AND true) {
		$url[0] = array('uri' => preg_replace(',^.*/,', '', $i));
		$url[1] = 'mot';
	}

	return $url;
}

