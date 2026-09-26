// Bookmarklet +rezo : ouvre le formulaire /plus dans un nouvel onglet,
// puis, quand la page /plus le demande, lui envoie le contenu de la page
// courante ; l'extraction des infos se fait dans javascript/plus.js.
//
// Ce fichier est transformé en lien javascript: par bookmarklet()
// (mes_fonctions.php), qui met toutes les lignes bout à bout :
// les commentaires doivent occuper une ligne entière, et chaque
// instruction doit finir par un point-virgule.
(function () {
	var site = '__URL_SITE__';
	var infos = {
		url: location.href,
		title: document.title,
		txt: String(window.getSelection()).slice(0, 2000),
		lang: document.documentElement.lang || ''
	};
	var u = site + '/plus?url=' + encodeURIComponent(infos.url) + '&title=' + encodeURIComponent(infos.title) + '&txt=' + encodeURIComponent(infos.txt);
	var w = window.open(u);
	if (!w) {
		location.href = u;
		return;
	}
	var repondre = function (e) {
		if (e.source !== w || e.origin !== new URL(site).origin || e.data !== 'rezo') {
			return;
		}
		window.removeEventListener('message', repondre);
		infos.html = document.documentElement.outerHTML;
		w.postMessage(infos, e.origin);
	};
	window.addEventListener('message', repondre);
})();
