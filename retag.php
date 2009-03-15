<?php

require 'ecrire/inc_version.php';

#if (!spip_query("ALTER TABLE spip_articles ADD retags TINYTEXT NOT NULL"))
#	die(mysql_errno().' '.mysql_error());

convert_mots_retags();
echo "okn";

function convert_mots_retags_push($id_article, $mots) {
	if (!$id_article) return; # premier appel
	$mots = join(' ', $mots);
	spip_query($q = "UPDATE spip_articles SET retags=".sql_quote($mots)." WHERE id_article=".$id_article);
	echo $q."\n";
}

function convert_mots_retags() {
	$s = spip_query("SELECT l.id_article AS id,m.descriptif AS titre FROM spip_mots_articles AS l JOIN spip_mots AS m ON (l.id_mot=m.id_mot) ORDER BY l.id_article");

	$id_article = null;
	$mots = array();
	while ($t = sql_fetch($s)) {
		$id = $t['id'];
		$titre = $t['titre'];
		if ($id != $id_article) {
			convert_mots_retags_push($id_article, $mots);
			$id_article = $id;
			$mots = array();
		}
		$mots[] = $titre;
	}
	convert_mots_retags_push($id_article, $mots);
}
