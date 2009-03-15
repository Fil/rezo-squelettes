<?php

@require 'ecrire/inc_version.php';
ini_set('memory_limit', '2000M');

function superieur_un($s) {
	return ($s>4) ? $s : null;
}
// list all trigrams in a string with their frequencies
function trigrams($txt, $n=3) {
	$r = array();
	if (($l = strlen($txt)-($n-1)) >= 0)
		for ($i=0; $i<$l; $i++)
			$r[substr($txt, $i, $n)]++;
	return array_filter($r, 'superieur_un');
}


// les trigrams sont-ils discriminants pour une question
// give me true or false as an answer to my question,
// using $filter as knowledge and $t as data
// $filter is a trigram score function array('abc' => score)
// produit scalaire des deux vecteurs
function trigram_scalar($a, $b) {
	arsort($a); arsort($b);
	$score = 0;
	$na = 0;
	$nb = 0;
	$i=0;
	$v = array_values($b);
	foreach($a as $t => $freq) {
		#echo("freq $freq t $t filter ".$b[$t]."\n");
		$score += $freq*$b[$t];
		$na += $freq*$freq;
		$nb += $v[$i]*$v[$i];
		$i++;
	}

	if ($na*$nb)
		return $score/sqrt($na*$nb);
}

function trigram_answer($a, $b, $threshold) {
	return trigram_scalar($a, $b) > $threshold;
}

// calculer le trigram de l'ensemble du site (fr)
$n = 4;

echo ".";
lire_fichier($f = _DIR_TMP.'grams'.$n.'.txt', $grams);
if ($grams) {
	echo ".";
	$grams = unserialize($grams);
	echo ".";
} else {

	echo "calcule le $n-gram du site\n";
	$grams['titres']['tout'] = 'Tout le site';

	$s = spip_query("SELECT surtitre,titre,chapo,descriptif,texte FROM spip_articles ORDER BY date DESC LIMIT 500");
	while ($t = sql_fetch($s))
		$grams['g']['txt'] .= preg_replace(',\s+,S', ' ', join(' ', $t));
	$grams['g']['txt'] = trigrams($grams['g']['txt'], $n);
	
	
	// calculer le trigram des textes associŽs aux mots-clŽs
	foreach (sql_allfetsel(array('id_mot', 'titre'), 'spip_mots') as $k) {
		$id_mot = $k['id_mot'];
		$grams['titres'][$id_mot] = $k['titre'];
	
		echo "calcule le $n-gram de ".$k['titre']."\n";
	
		$s = spip_query("SELECT a.surtitre,a.titre,a.chapo,a.descriptif,a.texte FROM spip_articles AS a, spip_mots_articles AS l WHERE a.id_article=l.id_article AND l.id_mot=$id_mot ORDER BY date DESC LIMIT 100");
		while ($t = sql_fetch($s))
			$grams['g'][$id_mot] .= preg_replace(',\s+,S', ' ', join(' ', $t));
	
		$grams['g'][$id_mot] = trigrams($grams['g'][$id_mot], $n);
	}

	ecrire_fichier($f, serialize($grams));
}

// Maintenant on prend un texte 143648
echo $id_article = 143648;
$s = spip_query("SELECT a.surtitre,a.titre,a.chapo,a.descriptif,a.texte FROM spip_articles AS a WHERE a.id_article=$id_article");
if ($t = sql_fetch($s))
	$article = trigrams(preg_replace(',\s+,S', ' ', join(' ', $t)), $n);

foreach ($grams['g'] as $id_mot => $gram) {
	$sc = trigram_scalar($article, $gram);
	echo $grams['titres'][$id_mot].': '.$sc."\n";

	$score[$grams['titres'][$id_mot]] = $sc;

}

arsort($score);
var_dump($score);

