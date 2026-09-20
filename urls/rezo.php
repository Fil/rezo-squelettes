<?php

define('_url_minuscules', true);
define('_MARQUEUR_URL', serialize([
	'rubrique1' => '',
	'rubrique2' => '',
	'breve1' => '+',
	'breve2' => '+',
	'site1' => '@',
	'site2' => '@',
	'auteur1' => '@',
	'auteur2' => '',
	'mot1' => '',
	'mot2' => '',
]));

function urls_rezo($i, &$entite, $args = '', $ancre = '') {
	static $cache = [];

	# # GENERER UNE URL
	if (is_numeric($i)) {
		// si #URL_ARTICLE est demande, pas la peine de chercher dans spip_urls.
		if ($entite === 'article') {
			$url = _DIR_RACINE . 'a' . $i;
			if ($args) {
				$url .= '?' . $args;
			}
			if ($ancre) {
				$url .= '#' . $ancre;
			}
			return $url;
		}
		// si #URL_RUBRIQUE ou #URL_MOT est demandee, utiliser le static
		if (in_array($entite, ['mot', 'rubrique'])) {
			# recalcul d'url
			if (_request('action') == 'redirect') {
				$s = sql_query('SELECT descriptif FROM spip_' . $entite . 's WHERE id_' . $entite . '=' . sql_quote($i));
				$t = sql_fetch($s);
				include_spip('inc/charsets');
				$url = str_replace(' ', '', translitteration($t['descriptif']));
				# regarder si l'url existe deja
				if ($url && ($s = sql_query('SELECT * FROM spip_urls WHERE url=' . sql_quote($url))) && !($t = sql_fetch($s))) {
					sql_insertq(
						'spip_urls',
						[
							'id_objet' => $i,
							'type' => $entite,
							'url' => $url,
							'date' => date('Y-m-d H:i:s'),
						]
					);
				}
			}
			if (!isset($cache[$entite])) {
				$tmp = [];
				include_spip('base/abstract_sql');
				foreach (sql_allfetsel(
					$select = ['id_objet', 'url'],
					$from = ['spip_urls'],
					$where = ["type='$entite'"],
					$groupby = [],
					$orderby = ['date']
				) as $t
				) {
					$tmp[$t['id_objet']] = $t['url'];
				}
				$cache[$entite] = $tmp;
			}

		}

		if (isset($cache[$entite]) && isset($cache[$entite][$i])) {
			$url = $cache[$entite][$i];
			switch ($entite) {
				case 'mot':
					$url = _DIR_RACINE . 'themes/' . $url;
					break;
				case 'rubrique':
					$url = _DIR_RACINE . 'sources/' . $url;
					break;
			}
		} else {
			$f = charger_fonction('propres', 'urls');
			$url = $f($i, $entite, $args, $ancre);
		}

		return $url;
	}

	# # DECODER UNE URL
	$i = preg_replace('/[?].*/', '', $i);
	$f = charger_fonction('propres', 'urls');
	$url = $f($i, $entite, $args, $ancre);

	if (preg_match(',^sources/(.*)$,', $i, $a)) {
		$r = sql_fetsel('*', 'spip_urls', ['url=' . sql_quote($a[1]), "type='rubrique'"]);
		$url[1] = 'rubrique';
		$url[0] = ['id_rubrique' => $r['id_objet']];
	}

	if (preg_match(',^/microsummary,', $i)) {
		return [null, 'microsummary'];
	}

	// Creer la 404 sur https://rezo.net/dsds(.html)
	if ($url[1] === '' && preg_match(',^.*/[^\.]+(\.html)?$,', $i)
	) {
		$url[1] = '404';
	}

	return $url;
}
