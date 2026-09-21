<?php

define('_SYNDICATION_CORRECTION', false);
define('_SYNDICATION_URL_UNIQUE', true);
define('_SYNDICATION_DEREFERENCER_URL', true); # dereferencer feedburner
define('_PERIODE_SYNDICATION', 10); // 10 min
define('_PERIODE_SYNDICATION_SUSPENDUE', 60); // 1h

define('_ID_WEBMESTRES', '3');  // Fil
define('_FULLTEXT_MAX_RESULTS', 2000);
define('_POPULARITE_TABLES', 'spip_rubriques');

function rezo_post_syndication($data) {
	static $sites = [];
	$url = $data[0];
	$id_syndic = $data[1];

	$sites[$id_syndic] ??= sql_fetsel(
		'*',
		'spip_syndic',
		'id_syndic=' . sql_quote($id_syndic)
	);

	$update = [
		'id_rubrique' => $sites[$id_syndic]['id_rubrique'],
		'id_secteur' => $sites[$id_syndic]['id_secteur'],
	];

	// valeur par defaut de la source
	// si publiee : 'article' (sinon '' aka depeche)
	// un article qui arrive sans descriptif est automatiquement
	// recale en depeche
	if ($sites[$id_syndic]['statut'] == 'publie' && strlen($data[2]['descriptif']) > 10) {
		$update['type'] = 'article';
	}

	// detection de la langue
	// attention sur certaines sources (http://blogs.lesoir.be/colette-braeckman/feed/)
	// la langue indiquee est anglais, alors que les textes sont fr.
	$update['lang'] = $data[2]['lang'];

	// attention aux fr-FR et autres joyeusetes
	$update['lang'] = preg_replace(',^.*(fr|en|es).*$,i', '\1', $update['lang']);

	// passer au detecteur de langue
	include_spip('inc/lang_detect');
	include_spip('inc/charsets');
	[$lang, $certitude] = lang_detect(
		translitteration($data[2]['titre'] . ' ' . $data[2]['descriptif']),
		['fr', 'en', 'es']
	);
	spip_log(sprintf("lang_detect $lang (%02d", (100 * $certitude)) . '%)');
	if ($certitude > 0.02) {
		$update['lang'] = $lang;
	}

	// forcer fr si langue inconnue
	if (!in_array($update['lang'], ['fr', 'en', 'es'])) {
		$update['lang'] = 'fr';
	}

	lang_select($update['lang']);
	$update['langue_choisie'] = 'oui';

	// Indiquer le "bon" titre
	$update['titre'] = trim(preg_replace(',\s+,ims', ' ', $data[2]['titre']));

	// Supprimer les trucs entre crochets, genre [by fil.rezo.net] de delicious
	$update['titre'] = trim(preg_replace(',[[].*[]],', '', $update['titre']));

	// "titre, par auteur"
	// "titre, par auteur (source)"
	// ne pas prendre les auteurs contenant un @ (emails affiches dans le RSS)
	if (strlen($aut = trim($data[2]['lesauteurs'])) && !strpos($aut, '@') && $aut !== $sites[$id_syndic]['titre'] && !preg_match('/, (par|by|por) /i', $update['titre']) && !preg_match('/ [(].*[)]$/', $update['titre'])
	) {
		$aut = couper($aut, 60);
		$update['titre'] .= ', ' . _T('forum_par_auteur', ['auteur' => $aut]);
	}

	// Ajouter sous forme de tags les mots-cles associes au site source
	$tags = [];
	foreach (sql_allfetsel(
		['m.descriptif AS descriptif, m.titre AS titre'],
		['spip_mots AS m', 'spip_mots_syndic AS l'],
		['l.id_mot=m.id_mot', 'l.id_syndic=' . $id_syndic]
	) as $t) {
		$tags[$t['descriptif']] = '<a rel="tag">' . $t['titre'] . '</a>';
	}

	// S'il y a un enclosure mp3, tag audio
	if ($data[2]['enclosures'] && preg_match(',\.mp3,', $data[2]['enclosures'])) {
		$tags['audio'] = '<a rel="tag">Audio</a>';
	}

	include_spip('inc/charsets');
	if ($data[2]['tags']) {
		foreach ($data[2]['tags'] as $b) {
			$key = strtolower(translitteration(trim(supprimer_tags($b))));
			$tags[$key] = $b;
		}
	}
	if ($tags) {
		$update['tags'] = supprimer_tags(join(', ', $tags));
	}

	// la date c'est maintenant !
	$update['date'] = date('Y-m-d H:i:s');

	// Mettre a jour
	spip_log($url . ': ' . join(' | ', $update), 'syndic');
	sql_updateq(
		'spip_syndic_articles',
		$update,
		'url=' . sql_quote($url) . ' AND id_syndic=' . sql_quote($id_syndic)
	);

	lang_select();

	// S'il y a un element publie : invalider
	if ($data[2]['statut'] == 'publie') {
		ecrire_meta('derniere_modif', time());
	}

	return $data;
}

// pour le crayon de logo dans le controleur rezo
function rezo_revision($id, $file, $type, $ref) {
	logo_revision($id, $file, $type, $ref);
	crayons_update_article($id, $file, $type, $ref);
}

/*
// delegation a la rache de mon openid vers gmail
if ($login = @$_SERVER['PHP_AUTH_USER']
AND $login == 'fil') {
	header('X-XRDS-Location: https://www.google.com/accounts/o8/id');
	echo "<html><head>\n";
	echo "<meta http-equiv='X-XRDS-Location' content='https://www.google.com/accounts/o8/id' />\n";
	echo "<link rel='openid.server' href='http://openid-provider.appspot.com/' />\n";
	echo "<link rel='openid.delegate' href='https://www.google.com/accounts/o8/id' />\n";
}
*/

function inc_recherche_to_array($recherche, $options) {

	if ($options['table'] !== 'article') {
		include_spip('inc/recherche_to_array');
		return inc_recherche_to_array_dist($recherche, $options);
	}

	spip_timer('recherche');

	$r = [];

	$conf = [
		'host' => '127.0.0.1',
		'index' => 'rezo',
		'limit' => 1000,
	];

	if ($v = sphinx_search($recherche, $conf)) {
		$r = [];
		foreach ($v as $w) {
			$r[$w['id']] = [
				'score' => $w['weight'],
				'attrs' => $w['attrs'],
			];
		}
	}

	spip_log("recherche $table \"$recherche\" (" . count($r) . ' resultats) ' . spip_timer('recherche'), 'recherche');

	return $r;
}

function sphinx_search($query, $conf = []) {

	spip_timer('search');

	include_spip('inc/charsets');
	if (!is_utf8($query)) {
		$query = unicode_to_utf_8(charset2unicode($query));
	}

	include_spip('lib/sphinxapi');

	$cl = new SphinxClient();

	# var_dump($cl);exit;

	$q = '';
	$sql = '';
	$mode = SPH_MATCH_ALL;
	$host = 'rezo.net';
	$port = 9312;
	$index = '*';
	$groupby = '';
	$groupsort = '@group desc';
	$filter = 'group_id';
	$filtervals = [];
	$distinct = '';
	$sortby = '';
	$limit = 20;
	$ranker = SPH_RANK_EXPR;
	$select = '';

	for ($i = 0; $i < count($args); $i++) {
		$arg = $args[$i];

		if ($arg == '-h' || $arg == '--host') {
			$host = $args[++$i];
		} elseif ($arg == '-p' || $arg == '--port') {
			$port = (int) $args[++$i];
		} elseif ($arg == '-i' || $arg == '--index') {
			$index = $args[++$i];
		} elseif ($arg == '-s' || $arg == '--sortby') {
			$sortby = $args[++$i];
			$sortexpr = '';
		} elseif ($arg == '-S' || $arg == '--sortexpr') {
			$sortexpr = $args[++$i];
			$sortby = '';
		} elseif ($arg == '-a' || $arg == '--any') {
			$mode = SPH_MATCH_ANY;
		} elseif ($arg == '-b' || $arg == '--boolean') {
			$mode = SPH_MATCH_BOOLEAN;
		} elseif ($arg == '-e' || $arg == '--extended') {
			$mode = SPH_MATCH_EXTENDED;
		} elseif ($arg == '-e2') {
			$mode = SPH_MATCH_EXTENDED2;
		} elseif ($arg == '-ph' || $arg == '--phrase') {
			$mode = SPH_MATCH_PHRASE;
		} elseif ($arg == '-f' || $arg == '--filter') {
			$filter = $args[++$i];
		} elseif ($arg == '-v' || $arg == '--value') {
			$filtervals[] = $args[++$i];
		} elseif ($arg == '-g' || $arg == '--groupby') {
			$groupby = $args[++$i];
		} elseif ($arg == '-gs' || $arg == '--groupsort') {
			$groupsort = $args[++$i];
		} elseif ($arg == '-d' || $arg == '--distinct') {
			$distinct = $args[++$i];
		} elseif ($arg == '-l' || $arg == '--limit') {
			$limit = (int) $args[++$i];
		} elseif ($arg == '--select') {
			$select = $args[++$i];
		} elseif ($arg == '-fr' || $arg == '--filterrange') {
			$cl->SetFilterRange($args[++$i], $args[++$i], $args[++$i]);
		} elseif ($arg == '-r') {
			$arg = strtolower($args[++$i]);
			if ($arg == 'bm25') {
				$ranker = SPH_RANK_BM25;
			}
			if ($arg == 'none') {
				$ranker = SPH_RANK_NONE;
			}
			if ($arg == 'wordcount') {
				$ranker = SPH_RANK_WORDCOUNT;
			}
			if ($arg == 'fieldmask') {
				$ranker = SPH_RANK_FIELDMASK;
			}
			if ($arg == 'sph04') {
				$ranker = SPH_RANK_SPH04;
			}
		} else {
			$q .= $args[$i] . ' ';
		}
	}

	foreach ($conf as $k => $v) {
		${$k} = is_numeric($v) ? 0 + $v : $v;
	}

	$q = $query;

	// //////////
	// do query
	// //////////

	$cl->SetServer($host, $port);
	$cl->SetConnectTimeout(1);
	$cl->SetArrayResult(true);
	$cl->SetWeights([100, 1]);
	$cl->SetMatchMode($mode);
	if (count($filtervals)) {
		$cl->SetFilter($filter, $filtervals);
	}
	if ($groupby) {
		$cl->SetGroupBy($groupby, SPH_GROUPBY_ATTR, $groupsort);
	}
	if ($sortby) {
		$cl->SetSortMode(SPH_SORT_EXTENDED, $sortby);
	}
	if ($sortexpr) {
		$cl->SetSortMode(SPH_SORT_EXPR, $sortexpr);
	}
	if ($distinct) {
		$cl->SetGroupDistinct($distinct);
	}
	if ($select) {
		$cl->SetSelect($select);
	}
	if ($limit) {
		$cl->SetLimits(0, $limit, ($limit > 1000) ? $limit : 1000);
	}

	$rankexpr = '(sum(lcs*user_weight)*1000+bm25) / (100+(SQRT(' . (time() + 3600 * 24 * 365) . '-date)))';

	# var_dump($cl);

	$cl->SetRankingMode($ranker);
	$res = $cl->Query($q, $index);

	spip_log('recherche "' . htmlspecialchars($query) . '" ' . spip_timer('search'), 'recherche');

	foreach ($res['matches'] as &$m) {
		$m['weight'] /= (100 + sqrt(time() + 3600 * 24 * 20 - intval($m['attrs']['date'])));
	}

	return $res['matches'];

}
