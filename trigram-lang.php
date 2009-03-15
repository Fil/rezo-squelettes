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

#	$r = array_filter($r, 'superieur_un');

	arsort($r);
	$r = array_slice($r,0,300);

	return $r;
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

// somme des distances de rangs
function trigram_distance($a, $b) {
	$max = max(count($a), count($b));

	$v = array();
	$i = 0;
	foreach ($b as $tri => $score)
		$v[$tri] = $i++;

	$j = 0;
	foreach($a as $tri => $score) {

		if (isset($v[$tri]))
			$distance += abs($v[$tri] - $j);
		else
			$distance += $max;
	}
	return $distance;
}

function trigram_score($a,$b) {
	$x = max(count($a), count($b));
	return 1-trigram_distance($a, $b) / $x /$x;
}

function trigram_answer($a, $b, $threshold) {
	return trigram_scalar($a, $b) > $threshold;
}

function debork($x) {
	$x = str_replace('&nbsp;', ' ', $x);
	$x = preg_replace(',[\s[:punct:]]+,S', ' ', $x);
	$x = strtolower($x);
	return $x;
}

// calculer le trigram de l'ensemble du site (fr)
$n = 4;

echo ".";
lire_fichier($f = _DIR_TMP.'grams-lang'.$n.'.txt', $grams);
if ($grams) {
	echo ".";
	$grams = unserialize($grams);
	echo ".";
} else {

	echo "calcule le $n-gram du site\n";
	$grams['titres']['tout'] = 'Tout le site';

	$s = spip_query("SELECT surtitre,titre,chapo,descriptif,texte FROM spip_articles ORDER BY date DESC LIMIT 500");
	while ($t = sql_fetch($s))
		$grams['g']['txt'] .= debork(join(' ', $t));
	$grams['g']['txt'] = trigrams($grams['g']['txt'], $n);
	
	
	// calculer le trigram des textes associŽs aux langues
	foreach (array('fr', 'en') as $lang) {

		echo "calcule le $n-gram de ".$lang."\n";
	
		$s = spip_query("SELECT a.surtitre,a.titre,a.chapo,a.descriptif,a.texte FROM spip_articles AS a WHERE a.lang='$lang' AND a.statut='publie' ORDER BY date DESC LIMIT 1000");
		while ($t = sql_fetch($s))
			$grams['g'][$lang] .= substr(debork(join(' ', $t)),0,500);
	
		$grams['g'][$lang] = trigrams($grams['g'][$lang], $n);
	}
	ecrire_fichier($f, serialize($grams));
}

// Maintenant on prend un texte 143648
unset($grams['g']['txt']);
$s = spip_query("SELECT a.id_article, a.surtitre,a.titre,a.chapo,a.descriptif,a.texte,a.lang FROM spip_articles AS a ORDER BY date DESC");
while ($t = sql_fetch($s)) {
	$article = trigrams(debork(join(' ', $t)), $n);

	$l = '';
	$l .= $t['id_article'];
	foreach ($grams['g'] as $lang => $gram) {
		$sc[$lang] = trigram_score($article, $gram);
	}
	$diff = 100*($sc['fr'] - $sc['en']);
	$l .= ' '.round($diff) . ' '.$t['titre'];

	if ($diff > 2
	AND $t['lang'] == 'en')
		echo "$l [fr]\n";
	else if ($diff < -2
	AND $t['lang'] == 'fr')
		echo "$l [en]\n";
}

/*
var_dump($score);
*/
/*
foreach ($grams['g'] as $lang => $gram) {
	echo "\n-- $lang --\n";
	foreach ($gram as $tri => $score) 
		echo "'$tri' $score\n";
}
*/