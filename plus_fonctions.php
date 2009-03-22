<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function get_content(&$node) {
	if (preg_match(',\bconten,i', $node->attribute['class'].' '.$node->attribute['id'])) {
		include_spip('inc/sale');
		return sale($node->value);
	}

	if ($node->hasChildren()) {
		foreach($node->child as $child) {
			if ($a = get_content($child))
				return $a;
		}
	}
}

if ($id_auteur = $GLOBALS['auteur_session']['id_auteur']) {

	// on recupere l'url passee en argument du bookmarklet
	$url = urldecode(sinon(_request('qs:url'), _request('url')));

	// est-il dans la base ?
	if ($s = spip_query("SELECT id_article FROM spip_articles WHERE url_site=".sql_quote($url))
	AND $t = sql_fetch($s)) {
		$id_article = $t['id_article'];
	}
	// sinon on regarde si cet auteur a deja un article temporaire
	// de plus de 15minutes, et on le prend ; sinon on le cree
	/* else */ {
		if ($s = sql_query("SELECT a.id_article FROM spip_auteurs_articles AS l LEFT JOIN spip_articles AS a ON (l.id_auteur=$id_auteur AND l.id_article=a.id_article)
	 	WHERE a.statut='prepa' AND a.date<".sql_quote(date('Y-m-d H:i:s', time()-15*60))
		." ORDER BY a.date DESC LIMIT 1")
		AND $t = sql_fetch($s)) {
			$id_article = $t['id_article'];
		}
		else if (!$id_article) {
			$id_article = sql_insertq('spip_articles', array());
		}

		// Donner un auteur
		sql_insertq('spip_auteurs_articles', array('id_auteur' => $id_auteur, 'id_article' => $id_article));

		//
		// On va chercher le contenu
		//
		include_spip('inc/distant');
		if (!$page = recuperer_page($url, $munge_charset = true))
			echo "Erreur, impossible de lire la page.";

		$head = extraire_balise($page, 'head');
		if (!$body = extraire_balise($page, 'body'))
			$body = $page;

		// le titre
		$titre = importer_charset(_request('title'), 'utf-8');
		if ($title = extraire_balise($head, 'title')
		OR $title = extraire_balise($page, 'title')) {
			$titre = trim(preg_replace(',\s+,ms', ' ', supprimer_tags($title)));
		}

		// le texte & le descriptif...
		$tidy = new tidy;
		$tidy->parseString($page);
		$tidy->cleanRepair();
		if (!$texte = get_content($tidy->root()))
			$texte = supprimer_tags($body);

		if ($metas = extraire_balises($head, 'meta'))
			foreach($metas as $meta)
				if (strtolower(extraire_attribut($meta, 'name')) == 'description')
					$descriptif = extraire_attribut($meta, 'content');

		if (!$descriptif)
			$descriptif = couper($texte, 600);

		// Le logo
		if ($imgs = extraire_balises($body, 'img'))
			foreach($imgs as $img)
				if (preg_match(',logos?\b,i', extraire_attribut($img, 'class'))) {
					$logo = extraire_attribut($img, 'src');
					$base = sinon(extraire_attribut(extraire_balise('base', 'head'), 'href'), $url);
					$logo = suivre_lien($base, $logo);
					if ($logo = recuperer_page($logo)
					AND ecrire_fichier($tmp = _DIR_TMP.'logo.tmp', $logo)
					AND $f = @getimagesize($tmp)) {
						$formats = array(1=>'gif', 2=>'jpg', 3=>'png');
						if ($fmt = $formats[$f[2]])
							rename($tmp, _DIR_IMG.'arton'.$id_article.'.'.$fmt);
					}
					break;
				}


		// les tags !
		$tags = array();
		foreach(extraire_balises($page, 'a') as $a) {
			if (extraire_attribut($a, 'rel') == 'tag')
				$tags[] = $a;
		}
		$surtitre = join(', ', $tags);

		// la langue
		include_spip('inc/lang_detect');
		include_spip('inc/charsets');
		list($lg, $certitude) = lang_detect(
			translitteration(supprimer_tags($page)),
			array('fr', 'en', 'es')
		);
		spip_log(sprintf("lang_detect $lang (%02d", (100*$certitude))."%)");
		if ($certitude > 0.02)
			$lang = $lg;
		// forcer fr si langue inconnue
		if (!in_array($lang, array('fr', 'en', 'es')))
			$lang = 'fr';


		// la rubrique
		if ($lang == 'fr')
			$rub = 33; # releve sur le net
		if ($lang == 'en')
			$rub = 119; # en anglais
		if ($lang == 'es')
			$rub = 33; # releve sur le net



		sql_updateq('spip_articles',
			array(
				'statut' => 'prepa',
				'id_rubrique' => $rub,
				'id_secteur' => $rub,
				'date' => date('Y-m-d H:i:s'),
				'titre' => sinon($titre, '(Sans titre)'),
				'descriptif' => $descriptif,
				'texte' => $texte,
				'surtitre' => $surtitre,
				'url_site' => $url,
				'lang' => $lang,
				'langue_choisie' => 'oui',
			),
			'id_article='.$id_article
		);
	}

	$GLOBALS['hack_id_article'] = $id_article;
}
else {
	include_spip('inc/headers');
	redirige_par_entete('/spip.php?page=login&url='.urlencode(self('&')));
}
