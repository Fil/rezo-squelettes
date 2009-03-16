<?php

define('_url_minuscules', true);
define('_MARQUEUR_URL', serialize(array('rubrique1' => '', 'rubrique2' => '', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '@', 'auteur2' => '', 'mot1' => '', 'mot2' => '')));

function urls_rezo($i, $entite, $args='', $ancre='') {
	static $cache = array();

	if (is_numeric($i)) {
		// si #URL_ARTICLE est demande, pas la peine de chercher dans spip_urls.
		if ($entite === 'article') {
			return _DIR_RACINE.'a'.$i;
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
	}

	// pour un mot ou une rubrique supprimer themes/sources
	if (is_string($i))
		$mot = preg_replace(',^/(themes|sources)/,','', $i);
	if (isset($cache[$entite]) AND isset($cache[$entite][$mot]))
		$url = $cache[$entite][$mot];
	else {
		$f = charger_fonction('propres', 'urls');
		$url = $f($i, $entite, $args, $ancre);
	}

	// Generation d'une URL rubrique ou mot
	if (is_string($url)) {
		if ($entite == 'rubrique')
			$url = 'sources/'.$url;
		if ($entite == 'mot')
			$url = 'themes/'.$url;
	}

	// si on cherche un theme inexistant, ne pas renvoyer '404'
	// mais passer sur une recherche fulltext normale (cf. mot.html)
	// http://v3.rezo.net/themes/sorbonne
	if (is_array($url)) {
		if ($url[1] == '404'
		AND preg_match(',^/themes/(.*)$,', $i, $regs)) {
			$url[0] = array('uri' => $regs[1]);
			$url[1] = 'mot';
		}

		// Creer la 404 sur http://v3.rezo.net/dsds(.html)
		if ($url[1] == ''
		AND preg_match(',^.*/[^\.]+(\.html)?$,', $i))
			$url[1] = '404';

	}


	return $url;
}

