<?php

define('_SYNDICATION_CORRECTION', false);
define('_SYNDICATION_URL_UNIQUE', true);
define('_SYNDICATION_DEREFERENCER_URL', true); # dereferencer feedburner
define('_PERIODE_SYNDICATION', 10); // 10 min
define('_PERIODE_SYNDICATION_SUSPENDUE', 60); // 1h

define('_ID_WEBMESTRES', '3');  // Fil
define('_FULLTEXT_MAX_RESULTS', 2000);
define('_POPULARITE_TABLES', 'spip_rubriques');

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

	spip_log("recherche article \"$recherche\" (" . count($r) . ' resultats) ' . spip_timer('recherche'), 'recherche');

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
	$sortexpr = '';
	$limit = 20;
	$ranker = SPH_RANK_PROXIMITY_BM25;
	$select = '';

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

	# var_dump($cl);

	$cl->SetRankingMode($ranker);
	$res = $cl->Query($q, $index);

	spip_log('recherche "' . htmlspecialchars($query) . '" ' . spip_timer('search'), 'recherche');

	if ($res === false) {
		spip_log('erreur sphinx : ' . $cl->GetLastError(), 'recherche');
		return [];
	}

	$matches = $res['matches'] ?? [];
	foreach ($matches as &$m) {
		$m['weight'] /= (100 + sqrt(time() + 3600 * 24 * 20 - intval($m['attrs']['date'])));
	}

	return $matches;

}
