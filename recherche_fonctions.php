<?php

	if (strlen($r = urlencode($_REQUEST['recherche']))) {
		$url = $GLOBALS['contexte']['id_rubrique']
			? 'sources/'.$r
			: 'themes/'.$r;
	}
	else
		$url = '';

	$url = strtolower($GLOBALS['meta']['url_site'].'/'.$url);

	include_spip('inc/headers');
	redirige_par_entete($url);

