<?php

	/*
		La Fonction Sale()
		(c)2005 James <klike@free.fr>
		d'après le bouton memo et le script spip_unparse
	*/

	function correspondances_standards() {
		return array(
			//Mise en page
			",<(i|em)( [^>\r]*)?".">(.+)</\\1>,Uims" => "{\\3}", //Italique
			",<(b|h[4-6]|strong)( [^>]*)?".">(.+)</\\1>,Uims" => "{{\\3}}", //Gras
			",<(h[1-3])( [^>]*)?".">(.+)</\\1>,Uims" => "\r{{{ \\3 }}}\r", //Intertitre

			//Liens, ancres & notes
			",<a[ \t\n\r][^<>]*href=[^<>]*(http[^<>'\"]*)[^<>]*>(.*?)<\/a>,Uims" => "[\\2->\\1]", //Lien externe

			//Paragraphes
			",<(p)( [^>]*)?".">(.+)</\\1>,Uims" => "\n\n\\3", //Paragr.
			",<br( [^>]*)?".">,Uims" => "\n_ ", //Saut de ligne
			",<hr( [^>]*)?".">,Uims" => "\r----\r", //Saut de page
			",<(pre)( [^>]*)?".">(.+)</\\1>,Uims" => "<poesie>\n\\3\n</poesie>", //Poesie

			//Images & Documents
		);
	}

function correspondances_a_bas_le_html() {

	return array(
	
	// on ne veut pas des heads / html / body 
	",<head>.*<\/head>,Uims" => "",
	",<html>,Uims" => "",
	",<\/html>,Uims" => "",
	",<body.*>,Uims" => "",
	",<\/body>,Uims" => "",

	// on ne veux pas des tables
	",<table.*>,Uims" => "",
	",<\/table.*>,Uims" => "",
	",<tr.*>,Uims" => "",
	",<\/tr>,Uims" => "",
	",<td.*>,Uims" => "",
	",<\/td>,Uims" => "",


	// on ne veux pas des div
	",<div.*>,Uims" => "",
	",<\/div.*>,Uims" => "",
	
	// divers et variés 
	",<csobj.*>,Uims" => "",
	",<\/csobj>,Uims" => "",
	",<csscriptdict.*>,Uims" => "",
	",<\/csscriptdict>,Uims" => "",
	",<spacer.*>,Uims" => "",
	
	// javascript sur les liens 
	",target=\".*\",Uims" => "",
	",onmouseover=\".*\",Uims" => "",
	",onmouseout=\".*\",Uims" => "",
	",onclick=\".*\",Uims" => "",
	

	// c est pas du html mais je le met ici quand meme
	",\t,Uims" => "",
	

		);
}



	function spip_avant_sale($contenu) {
		if(function_exists('avant_sale'))
			return avant_sale($contenu);

		// PRETRAITEMENTS
		$contenu = str_replace("\n\r", "\r", $contenu); // echapper au greedyness de preg_replace
		$contenu = str_replace("\n", "\r", $contenu);

		// virer les commentaires html (qui cachent souvent css et jajascript)
		$contenu = preg_replace("/<!--.*-->/Uims", "", $contenu);

		$contenu = preg_replace("/<(script|style)\b.+?<\/\\1>/i", "", $contenu);
		return $contenu;
	}

	function spip_apres_sale($contenu) {
		if(function_exists('apres_sale'))
			return apres_sale($contenu);

		// POST TRAITEMENT
		$contenu = str_replace("\r", "\n", $contenu);
		
		
		
		
		return $contenu;
	}

	function sale($contenu_sale, $correspondances = '') {
		$contenu_propre = $contenu_sale;
		
		//Pré  Traitement
		$contenu_propre = spip_avant_sale($contenu_propre);
		
		//Traitement
		if(empty($correspondances))
			$correspondances = correspondances_standards();

		foreach($correspondances as $motif => $remplacement)
			$contenu_propre = preg_replace($motif, $remplacement, $contenu_propre);

		//Post Traitement
		$contenu_propre = spip_apres_sale($contenu_propre);

		foreach(correspondances_a_bas_le_html() as $motif => $remplacement)
			$contenu_propre = preg_replace($motif, $remplacement, $contenu_propre);

		return $contenu_propre;
	}

?>
