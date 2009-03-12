<?php

// A partir d'un titre renvoyer titre, auteur, source
function retitrage($titre, $quoi='titre') {
  static $r = array();
  if (!isset($r[$c = md5($titre)])) {
    $r[$c] = array();
    if (preg_match(',^(.*)\s+\(([^(]+)\)$,UimsS', trim($titre), $regs)
    AND !preg_match(',^\d+$,S', $regs[2])) {
      $titre = $regs[1];
      $r[$c]['source'] = $regs[2];
    }
    if (preg_match('/^(.*),\s+(?:by|par)\s+(.+)$/UimsS', $titre, $regs)) {
      $titre = $regs[1];
      $r[$c]['auteurs'] = $regs[2];
    }
    $r[$c]['titre'] = $titre;
  }

  return $r[$c][$quoi];
}

// Renvoie 14h12 si c'est le meme jour, et 19/02 si c'est un autre jour
function datehm($date) {
	$u = date('U', strtotime($date));
	$n = date('U');
	return ($n-$u < 24*3600)
		? date('H\hi', $u)
		: date('d/m', $u);
}

// Renvoie les n mots-cles les plus populaires
function mots_populaires($n=25) {
	/* $f = "select sum(articles.popularite) as pop, m.id_mot AS id_mot, mots.titre as titre from spip_articles as articles right join spip_mots_articles as m ON articles.id_article = m.id_article, spip_mots AS mots WHERE m.id_mot=mots.id_mot group by id_mot order by pop desc limit 0,".intval($n); */
	// Utilise le plugin "Popularite"
	$f = "SELECT popularite AS pop, id_mot, titre
	FROM spip_mots
	ORDER BY popularite DESC
	LIMIT 0,".intval($n);
	if ($s = spip_query($f)) {
		$a =array();
		while ($t = sql_fetch($s))
			$a[] = $t;
	}
	return $a;
}
function random_sort($a,$b) {
	return rand(0,1)
		? 1
		: -1;
}
function nuage_tags($ignore, $n=25, $random=1) {
	if (is_array($a = mots_populaires($n))) {
		$maxpop = $a[0]['pop'];
		$minpop = $a[count($a)-1]['pop'];
		$l = array();
		foreach($a as $mot) {
			$score = ($mot['pop']-$minpop)/($maxpop-$minpop+1); # entre 0 et 1
			$score = pow($score,0.5); # lissage
			$s = 10+ceil(10*$score);
			$t = str_replace(' ', '&nbsp;', typo($mot['titre']));
			$l[] = "<a href='".generer_url_entite($mot['id_mot'],'mot')."'
			style='font-size: ${s}px;'>$t</a>";
		}

		if ($random)
			usort($l, 'random_sort');

		return join("\n", $l);
	}
}

// Le titre des rubriques, version rapide : [(#ID_RUBRIQUE|titre_rub)]
function titre_rub($id_rubrique) {
	static $brut, $typo = array();

	if (isset($typo[$id_rubrique]))
		return $typo[$id_rubrique];

	if (!isset($brut)) {
		include_spip('base/abstract_sql');
		foreach(sql_allfetsel(array('titre','id_rubrique', 'lang'), array('spip_rubriques')) as $t)
			$brut[$t['id_rubrique']] = array($t['titre'], $t['lang']);
	}

	if (!isset($typo[$id_rubrique])) {
		lang_select($brut[$id_rubrique][1]);
		$typo[$id_rubrique] = typo($brut[$id_rubrique][0]);
		lang_select();
	}

	return $typo[$id_rubrique];
}

// Les mots des articles, version rapide : [(#ID_ARTICLE|mots_article)]
function mots_article($id_article, $wrap='%s', $sep=', ') {
	static $liens, $titres;

	if (!isset($liens)) {
		include_spip('base/abstract_sql');
		foreach(sql_allfetsel(array('id_mot','id_article'), array('spip_mots_articles')) as $t)
			$liens[intval($t['id_article'])][] = intval($t['id_mot']);
		foreach(sql_allfetsel(array('id_mot','titre'), array('spip_mots')) as $t)
			$titres[intval($t['id_mot'])] = $t['titre'];
	}

	if (!isset($liens[$id_article]))
		return '';

	$mots = array();
	foreach ($liens[$id_article] as $id_mot)
		$mots[] = sprintf($wrap, $titres[$id_mot]);  # on ne fait pas typo()

	return join($sep, $mots);
}
