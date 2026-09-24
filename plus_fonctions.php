<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if ($GLOBALS['auteur_session'] && ($id_auteur = $GLOBALS['auteur_session']['id_auteur'])) {

	// on recupere l'url passee en argument du bookmarklet
	$url = urldecode(sinon(_request('qs:url'), _request('url')));

	// URLs accentuees
	$url = preg_replace_callback(
		',[\x80-\xFF],',
		fn ($matches) => urlencode($matches[0]),
		$url
	);

	// virer les merdasses de tracking
	foreach (['[?&]__utma=.*', '[?&]utm_source=.*', '[?&]utm_medium=.*', '[?&]utm_content=.*', '[?&]utm_campaign=.*', '#xtor=.*', '[?&]fbclid=.*'] as $shit) {
		$url = preg_replace(",$shit,", '', $url);
	}

	$id_article = 0;
	// est-il dans la base ?
	if ($s = sql_query('SELECT id_article FROM spip_articles WHERE url_site=' . sql_quote($url))
	and $t = sql_fetch($s)) {
		$id_article = $t['id_article'];
	}
	// sinon on regarde si cet auteur a deja un article temporaire
	// de plus de 15minutes, et on le prend ; sinon on le cree
	else {
		if ($s = sql_query("SELECT a.id_article FROM spip_auteurs_liens AS l LEFT JOIN spip_articles AS a ON (l.id_auteur=$id_auteur AND l.id_objet=a.id_article AND l.objet = 'article')
	 	WHERE a.statut='prepa' AND a.date_modif<" . sql_quote(date('Y-m-d H:i:s', time() - 15 * 60))
		. ' ORDER BY a.date_modif DESC LIMIT 1')
		and $t = sql_fetch($s)) {
			$id_article = $t['id_article'];
			sql_updateq(
				'spip_articles',
				[
					'titre' => '',
					'descriptif' => '',
					'chapo' => '',
					'url_site' => $url,
				],
				'id_article=' . $id_article
			);
		} elseif (!$id_article) {
			$id_article = sql_insertq('spip_articles', ['url_site' => $url]);
			// Donner un auteur
			sql_insertq('spip_auteurs_liens', ['id_auteur' => $id_auteur, 'id_article' => $id_article, 'objet' => 'article']);
		}

		//
		// On va chercher le contenu
		//
		include_spip('inc/distant');
		include_spip('inc/charsets');
		$page = recuperer_url($url, ['transcoder' => true]);
		$page = $page['page'] ?? '';
		if (!$page) {
			echo 'Erreur, impossible de lire la page.';
		}

		$head = extraire_balise($page, 'head');
		if (!$body = extraire_balise($page, 'body')) {
			$body = str_replace($head, '', $page);
		}

		// supprimer les blocs style ou script qui trainent
		foreach (array_merge(extraire_balises($body, 'script'), extraire_balises($body, 'style')) as $script) {
			$body = str_replace($script, '', $body);
		}

		// le titre
		$titre = importer_charset(_request('title'), 'utf-8');
		if ($title = extraire_balise($head, 'title')
		or $title = extraire_balise($page, 'title')) {
			$titre = trim(preg_replace(',\s+,ms', ' ', supprimer_tags($title)));
			$titre = unicode2charset(html2unicode($titre));
		}

		// le texte & le descriptif...
		$page = preg_replace(',<(span)\b.*>,Uims', '', $page);
		$page = preg_replace('/(<br\b.*>(\s|&nbsp;)*){2,}/Uims', '<p>', $page);

		// sale pour remettre en spip
		include_spip('inc/sale');

		$texte = trim(sale($body));
		$descriptif = '';

		if ($metas = extraire_balises($head, 'meta')) {
			foreach ($metas as $meta) {
				if (strtolower(extraire_attribut($meta, 'name')) == 'description') {
					$descriptif = extraire_attribut($meta, 'content');
				}
			}
		}

		if (!$descriptif) {
			$descriptif = couper($texte, 600);
		}

		// Le logo
		if ($imgs = extraire_balises($texte, 'img')) {
			foreach ($imgs as $img) {
				if (preg_match(',logos?\b,i', extraire_attribut($img, 'class'))) {
					$logo = extraire_attribut($img, 'src');
					$base = sinon(extraire_attribut(extraire_balise('base', 'head'), 'href'), $url);
					$logo = suivre_lien($base, $logo);
					$logo = recuperer_url($logo);
					$logo = $logo['page'] ?? '';
					if ($logo && ecrire_fichier($tmp = _DIR_TMP . 'logo.tmp', $logo) && $f = @getimagesize($tmp)) {
						$formats = [1 => 'gif', 2 => 'jpg', 3 => 'png'];
						if ($fmt = $formats[$f[2]]) {
							rename($tmp, _DIR_IMG . 'arton' . $id_article . '.' . $fmt);
						}
					}
					break;
				}
			}
		}

		// les tags : microformat relTag
		$tags = [];
		foreach (extraire_balises($page, 'a') as $a) {
			if (extraire_attribut($a, 'rel') == 'tag') {
				$tags[] = str_replace('&nbsp;', ' ', $a);
			}
		}
		$surtitre = join(', ', $tags);

		// la langue
		include_spip('inc/lang_detect');
		include_spip('inc/charsets');
		$lang = '';
		[$lg, $certitude] = lang_detect(
			translitteration(supprimer_tags($page)),
			['fr', 'en', 'es']
		);
		spip_log(sprintf("lang_detect $lg (%02d", (100 * $certitude)) . '%)');
		if ($certitude > 0.02) {
			$lang = $lg;
		}
		// forcer fr si langue inconnue
		if (!in_array($lang, ['fr', 'en', 'es'])) {
			$lang = 'fr';
		}

		$rub = 1;
		// la rubrique
		if ($lang == 'fr') {
			$rub = 33;
		} # releve sur le net
		if ($lang == 'en') {
			$rub = 119;
		} # en anglais
		if ($lang == 'es') {
			$rub = 33;
		} # releve sur le net

		// si l'url matche un domaine connu, on pre-selectionne
		// cette rubrique
		$u = parse_url($url);
		$s = sql_query("SELECT id_rubrique, COUNT(*) as c FROM spip_articles
			WHERE url_site LIKE '%://" . $u['host'] . "/%' GROUP BY id_rubrique ORDER BY c DESC LIMIT 1");
		if ($t = sql_fetch($s)) {
			$rub = $t['id_rubrique'];
		}

		// Si le bookmarklet a selectionne un passage, l'utiliser comme resume
		if (strlen($v = _request('txt')) > 5) {
			include_spip('inc/charsets');
			$descriptif = utf_8_to_unicode($v);
		}

		// Mise a jour dans la base
		sql_updateq(
			'spip_articles',
			[
				'statut' => 'prepa',
				'id_rubrique' => $rub,
				'id_secteur' => $rub,
				'date' => date('Y-m-d H:i:s'),
				'date_modif' => date('Y-m-d H:i:s'),
				'titre' => sinon($titre, '(Sans titre)'),
				'descriptif' => $descriptif,
				'texte' => $texte,
				'surtitre' => $surtitre,
				'url_site' => $url,
				'lang' => $lang,
				'langue_choisie' => 'oui',
			],
			'id_article=' . $id_article
		);

		$GLOBALS['hack_new'] = 1;
	}

	$GLOBALS['hack_id_article'] = $id_article;
} else {
	include_spip('inc/headers');
	redirige_par_entete('/spip.php?page=login&url=' . urlencode(self('&')));
}
