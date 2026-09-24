<?php

/*
 var_dump(lang_detect("Mon Dieu ceci est un texte en français"));
 var_dump(lang_detect("Hear hear, this is in ENglish"));
 var_dump(lang_detect("Olà esto es español"));
*/

class Trigram
{
	public $n = 3; // default is 3-gram

	public $threshold = 300;

	// list all trigrams in a string with their frequencies
	public function trigrams($txt) {
		$r = [];
		if (($l = strlen($txt) - ($this->n - 1)) >= 0) {
			for ($i = 0; $i < $l; $i++) {
				$r[substr($txt, $i, $this->n)]++;
			}
		}

		arsort($r);
		$r = array_slice($r, 0, $this->threshold);
		return $r;
	}

	public function trigram_scalar($a, $b) {
		arsort($a);
		arsort($b);
		$score = $na = $nb = $i = 0;
		$v = array_values($b);
		foreach ($a as $t => $freq) {
			$score += $freq * $b[$t];
			$na += $freq * $freq;
			$nb += $v[$i] * $v[$i];
			$i++;
		}

		if ($na * $nb) {
			return $score / sqrt($na * $nb);
		}
	}

	public function trigram_distance($a, $b) {
		$max = max(count($a), count($b));
		$v = [];
		$i = 0;
		foreach ($b as $tri => $score) {
			$v[$tri] = $i++;
		}

		$j = 0;
		$distance = 0;
		foreach ($a as $tri => $score) {
			$distance += isset($v[$tri])
				? abs($v[$tri] - $j)
				: $max;
		}
		return $distance;
	}

	public function trigram_score($a, $b) {
		$x = max(count($a), count($b));
		return 1 - $this->trigram_distance($a, $b) / $x / $x;
	}
}

function lang_detect_debork($x) {
	$x = str_replace('&nbsp;', ' ', $x);
	$x = preg_replace(',[\s[:punct:]]+,S', ' ', $x);
	$x = strtolower($x);
	return $x;
}

function lang_detect($txt, $langs = null) {
	static $grams = [];

	$langs ??= ['fr', 'en', 'es'];

	$sc = [];
	$t = new Trigram();

	// Our n-gram database, copied from http://guess-language.googlecode.com/
	// is based on 3-grams
	$t->n = 3;
	$dir = __DIR__ . '/trigrams/';

	$txt = lang_detect_debork($txt);

	foreach ($langs as $lang) {
		if (!isset($grams[$lang])) {
			$gram = [];
			if ($g = file($dir . $lang)) {
				foreach ($g as $l) {
					[$tri, $rank] = preg_split(",\t+,S", trim($l));
					$gram[$tri] = $rank;
				}
			}
			$grams[$lang] = $gram;
		}
		if ($grams[$lang]) {
			$sc[$lang] = $t->trigram_score($t->trigrams($txt), $grams[$lang]);
		}
	}

	arsort($sc);
	$lang = key($sc);
	$score = current($sc);
	next($sc);
	$score2 = current($sc);
	next($sc);
	return [$lang, $score - $score2];
}
