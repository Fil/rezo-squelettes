<?php

function hatom2rss($uri) {
	$uri = str_replace('%2F', '/', $uri);

	// choper les liens presents
	$type='a'; // a priori les articles
	$max = 10;

	// sauf pour /backend/tout ou /feed/
	switch($uri) {
		case 'tout':
		case '/themes/tout':
			$type='[ab]'; 
			$max = 100;
			$uri = 'tout';
			break;
		case '/themes/audio':
			$type='[ab]';
			$max = 20;
			$uri = 'audio';
			break;
		case '/themes/%2Ffeed%2F':
			$uri = 'tout';
			$max = 20;
			break;
		case '/themes/bestof':
			$uri = 'bestof';
			$max = 20;
			break;
	}

	// lire la page hatom
	$feed = recuperer_page(url_absolue($uri));

	preg_match_all(
	',<div\s+class="hentry\b[^<>"]*"\s+id="'.$type.'(\d+)".*<abbr class="updated" title="(.*)">,UmsS',
	$feed, $regs, PREG_SET_ORDER);


	// trier par date et prendre les n plus recents
	// et tous les plus recents que 3 jours
	$items = $recents = array();
	$troisjours = date('Y-m-d H:i:s', time()-3*24*3600);
	foreach($regs as $reg) {
		if ($reg[2] > $troisjours)
			$recents[$reg[2].'-'.$reg[1]] = $reg[1];
		else
			$items[$reg[2].'-'.$reg[1]] = $reg[1];
	}

	krsort($recents);
	$manquants = $max - count($recents);
	if ($manquants > 0) {
		krsort($items);
		$recents = array_merge($recents, array_slice($items,0,$manquants));
	}

	// recuperer les items correspondants
	foreach($recents as $cle=>$id)
		$recents[$cle] = microcache($id, 'inc-rss-item');

	return join("\n\t", $recents);
}


?>
