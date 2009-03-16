<?php

/*
 var_dump(lang_detect("Mon Dieu ceci est un texte en français"));
 var_dump(lang_detect("Hear hear, this is in ENglish"));
 var_dump(lang_detect("Olà esto es español"));
*/

class Trigram {
	var $n = 3; // default is 3-gram
	var $threshold = 300;

	// list all trigrams in a string with their frequencies
	function trigrams($txt) {
		$r = array();
		if (($l = strlen($txt)-($this->n-1)) >= 0)
			for ($i=0; $i<$l; $i++)
				$r[substr($txt, $i, $this->n)]++;

		arsort($r);
		$r = array_slice($r,0,$this->threshold);
		return $r;
	}

	function trigram_scalar($a, $b) {
		arsort($a); arsort($b);
		$score = $na = $nb = $i = 0;
		$v = array_values($b);
		foreach($a as $t => $freq) {
			$score += $freq*$b[$t];
			$na += $freq*$freq;
			$nb += $v[$i]*$v[$i];
			$i++;
		}

		if ($na*$nb)
			return $score/sqrt($na*$nb);
	}

	function trigram_distance($a, $b) {
		$max = max(count($a), count($b));
		$v = array();
		$i = 0;
		foreach ($b as $tri => $score)
			$v[$tri] = $i++;

		$j = 0;
		foreach($a as $tri => $score)
			$distance += isset($v[$tri])
				? abs($v[$tri] - $j)
				: $max;
		return $distance;
	}

	function trigram_score($a,$b) {
		$x = max(count($a), count($b));
		return 1-$this->trigram_distance($a, $b) / $x /$x;
	}
}

function lang_detect_debork($x) {
	$x = str_replace('&nbsp;', ' ', $x);
	$x = preg_replace(',[\s[:punct:]]+,S', ' ', $x);
	$x = strtolower($x);
	return $x;
}

function lang_detect($txt, $langs = null) {
	static $grams = array();

	if (is_null($langs))
		$langs = array('fr','en','es');

	$sc = array();
	$t = new Trigram();

	// Our n-gram database, copied from http://guess-language.googlecode.com/
	// is based on 3-grams
	$t->n = 3;
	$dir = dirname(__FILE__).'/trigrams/';

	$txt = lang_detect_debork($txt);

	foreach($langs as $lang) {
		if (!isset($grams[$lang])) {
			$gram = array();
			if ($g = file($dir.$lang)) {
				foreach ($g as $l) {
					list($tri,$rank) = preg_split(",\t+,S", trim($l));
					$gram[$tri] = $rank;
				}
			}
			$grams[$lang] = $gram;
		}
		if ($grams[$lang])
			$sc[$lang] = $t->trigram_score($t->trigrams($txt), $grams[$lang]);
	}

	arsort($sc);
	list($lang, $score) = each($sc);
	list(, $score2) = each($sc);
	return array($lang, $score-$score2);
}

