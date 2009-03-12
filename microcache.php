<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

$cle = md5('rezo:'.serialize($contexte_inclus));

$microcache = _DIR_CACHE.$cle;

if (isset($_GET['var_mode'])
OR !@file_exists($microcache)
OR filemtime($microcache) < time() - 60*10) {
	$contenu = recuperer_fond($contexte_inclus['fond'], $contexte_inclus);
	spip_log('ecrire '.($GLOBALS['ecrire']++).' '.$microcache.' '.$contexte_inclus['id_article']);
	ecrire_fichier($microcache, $contenu);
} else {
	spip_log('  lire '.($GLOBALS['lire']++).' '.$microcache.' '.$contexte_inclus['id_article']);
	lire_fichier($microcache, $contenu);
}

echo $contenu;
