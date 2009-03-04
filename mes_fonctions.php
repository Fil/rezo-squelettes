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
