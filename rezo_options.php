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
	static $sites = array();
	$url = $data[0];
	$id_syndic = $data[1];

	if (!isset($sites[$id_syndic]))
		$sites[$id_syndic] = sql_fetsel('*', 'spip_syndic',
		'id_syndic='.sql_quote($id_syndic));

	$update = array(
		'id_rubrique' => $sites[$id_syndic]['id_rubrique'],
		'id_secteur' => $sites[$id_syndic]['id_secteur']
	);

	// valeur par defaut de la source
	// si publiee : 'article' (sinon '' aka depeche)
	// un article qui arrive sans descriptif est automatiquement
	// recale en depeche
	if ($sites[$id_syndic]['statut'] == 'publie'
	AND strlen($data[2]['descriptif']) > 10)
		$update['type'] = 'article';

	// detection de la langue
	// attention sur certaines sources (http://blogs.lesoir.be/colette-braeckman/feed/)
	// la langue indiquee est anglais, alors que les textes sont fr.
	$update['lang'] = $data[2]['lang'];

	// attention aux fr-FR et autres joyeusetes
	$update['lang'] = preg_replace(',^.*(fr|en|es).*$,i', '\1', $update['lang']);

	// passer au detecteur de langue
	include_spip('inc/lang_detect');
	include_spip('inc/charsets');
	list($lang, $certitude) = lang_detect(
		translitteration($data[2]['titre'] . ' ' . $data[2]['descriptif']),
		array('fr', 'en', 'es')
	);
	spip_log(sprintf("lang_detect $lang (%02d", (100*$certitude))."%)");
	if ($certitude > 0.02)
		$update['lang'] = $lang;


	// forcer fr si langue inconnue
	if (!in_array($update['lang'], array('fr', 'en', 'es')))
		$update['lang'] = 'fr';


	lang_select($update['lang']);
	$update['langue_choisie'] = 'oui';


	// Indiquer le "bon" titre
	$update['titre'] = trim(preg_replace(',\s+,ims', ' ', $data[2]['titre']));

	// Supprimer les trucs entre crochets, genre [by fil.rezo.net] de delicious
	$update['titre'] = trim(preg_replace(',[[].*[]],', '', $update['titre']));

	// "titre, par auteur"
	// "titre, par auteur (source)"
	// ne pas prendre les auteurs contenant un @ (emails affiches dans le RSS)
	if (strlen($aut = trim($data[2]['lesauteurs']))
	AND !strpos($aut, '@')
	AND $aut !== $sites[$id_syndic]['titre']
	AND !preg_match('/, (par|by|por) /i', $update['titre'])
	AND !preg_match('/ [(].*[)]$/', $update['titre'])
	) {
		$aut = couper($aut, 60);
		$update['titre'] .= ', '._T('forum_par_auteur', array('auteur' => $aut));
	}

	// Ajouter sous forme de tags les mots-cles associes au site source
	$tags = array();
	foreach(sql_allfetsel(array('m.descriptif AS descriptif, m.titre AS titre'),
		array('spip_mots AS m', 'spip_mots_syndic AS l'),
		array('l.id_mot=m.id_mot', 'l.id_syndic='.$id_syndic)
	) as $t)
		$tags[$t['descriptif']] = '<a rel="tag">'.$t['titre'].'</a>';

	// S'il y a un enclosure mp3, tag audio
	if ($data[2]['enclosures']
	AND preg_match(',\.mp3,', $data[2]['enclosures']))
		$tags['audio'] = '<a rel="tag">Audio</a>';

	include_spip('inc/charsets');
	if ($data[2]['tags'])
	foreach ($data[2]['tags'] as $b) {
		$key = strtolower(translitteration(trim(supprimer_tags($b))));
		$tags[$key] = $b;
	}
	if ($tags)
		$update['tags'] = supprimer_tags(join(', ', $tags));

	// la date c'est maintenant !
	$update['date'] = date('Y-m-d H:i:s');

	// Mettre a jour
	spip_log($url . ': '.join(' | ',$update), 'syndic');
	sql_updateq('spip_syndic_articles',
		$update,
		'url='.sql_quote($url).' AND id_syndic='.sql_quote($id_syndic));

	lang_select();

	// S'il y a un element publie : invalider
	if ($data[2]['statut'] == 'publie')
		ecrire_meta('derniere_modif', time());

	return $data;
}

// pour le crayon de logo dans le controleur rezo
function rezo_revision($id, $file, $type, $ref) {
	logo_revision($id, $file, $type, $ref);
	crayons_update_article($id, $file, $type, $ref);
}

// delegation a la rache de mon openid vers gmail
if ($login = @$_SERVER['PHP_AUTH_USER']
AND $login == 'fil') {
	header('X-XRDS-Location: https://www.google.com/accounts/o8/id');
	echo "<html><head>\n";
	echo "<meta http-equiv='X-XRDS-Location' content='https://www.google.com/accounts/o8/id' />\n";
	echo "<link rel='openid.server' href='http://openid-provider.appspot.com/' />\n";
	echo "<link rel='openid.delegate' href='https://www.google.com/accounts/o8/id' />\n";
}

