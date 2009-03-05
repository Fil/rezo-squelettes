<?php

// A partir d'un titre renvoyer titre, auteur, source
function retitrage($titre, $quoi='titre') {
  static $r = array();
  if (!isset($r[$c = md5($titre)])) {
    $r[$c] = array();
    if (preg_match(',^(.*)\s+\(([^(]+)\)$,UimsS', trim($titre), $regs)) {
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