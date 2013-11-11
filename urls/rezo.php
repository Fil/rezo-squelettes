<?php

define('_url_minuscules', true);
define('_MARQUEUR_URL', serialize(array('rubrique1' => '', 'rubrique2' => '', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '@', 'auteur2' => '', 'mot1' => '', 'mot2' => '')));

function urls_rezo($i, $entite, $args='', $ancre='') {
	static $cache = array();

	## GENERER UNE URL
	if (is_numeric($i)) {
		// si #URL_ARTICLE est demande, pas la peine de chercher dans spip_urls.
		if ($entite === 'article') {
			$url = _DIR_RACINE.'a'.$i;
			if ($args)
				$url .= '?'.$args;
			if ($ancre)
				$url .= '#'.$ancre;
			return $url;
		}
		// si #URL_RUBRIQUE ou #URL_MOT est demandee, utiliser le static
		else if (in_array($entite, array('mot','rubrique'))) {
			# recalcul d'url
			if (_request('action')=='redirect') {
				$s = spip_query("SELECT descriptif FROM spip_".$entite."s WHERE id_".$entite."=".sql_quote($i));
				$t = sql_fetch($s);
				include_spip('inc/charsets');
				$url = str_replace(' ', '', translitteration($t['descriptif']));
				# regarder si l'url existe deja
				if ($url
				AND $s = spip_query("SELECT * FROM spip_urls WHERE url=".sql_quote($url))
				AND !$t = sql_fetch($s)) {
					sql_insertq('spip_urls',
						array(
						'id_objet' => $i,
						'type' => $entite,
						'url' => $url,
						'date' => date('Y-m-d H:i:s')
						)
					);
				}
			}
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

		if (isset($cache[$entite])
		AND isset($cache[$entite][$i])) {
			$url = $cache[$entite][$i];
			switch ($entite) {
				case 'mot':
					$url = _DIR_RACINE.'themes/'.$url;
					break;
				case 'rubrique';
					$url = _DIR_RACINE.'sources/'.$url;
					break;
			}
		}
		else {
			$f = charger_fonction('propres', 'urls');
			$url = $f($i, $entite, $args, $ancre);
		}

		return $url;
	}


	## DECODER UNE URL
	$i = preg_replace('/[?].*/', '', $i);
	$mot = urldecode(preg_replace(',^/(backend|themes|sources)/,','', $i));

	$f = charger_fonction('propres', 'urls');
	$url = $f($mot, $entite, $args, $ancre);

	if (preg_match(',^/microsummary,', $i))
		return array(null, 'microsummary');

	// Creer la 404 sur http://rezo.net/dsds(.html)
	if ($url[1] == ''
	AND preg_match(',^.*/[^\.]+(\.html)?$,', $i)
	) {
		$url[1] = '404';
	}

	if ($mot) {
		unset($url[0]['id_mot']);
		$url[0]['mot'] = $mot;
	}

	return $url;
}

