<?php

define('_url_minuscules', true);
define('_MARQUEUR_URL', serialize(array('rubrique1' => 'sources/', 'rubrique2' => '', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '@', 'auteur2' => '', 'mot1' => 'themes/', 'mot2' => '')));

function urls_rezo($i, $entite, $args='', $ancre='') {
  $f = charger_fonction('propres', 'urls');

  // supprimer le themes/ ou sources/
  if (!is_numeric($i))
    $i = preg_replace(',^.*/,', '/', $i);

  return $f($i, $entite, $args, $ancre);
}

