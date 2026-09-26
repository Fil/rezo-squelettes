// Page /plus, article nouveau : pendant qu'on affiche le formulaire,
// demander au bookmarklet (javascript/bookmarklet.js) le contenu de la
// page d'origine, en extraire laius, tags et logo, et remplir les champs
// encore vides auxquels l'utilisateur n'a pas touche.
(function () {
	var form = document.querySelector('form.formulaire_crayon');
	if (!form || !window.opener) {
		return;
	}
	var url = new URLSearchParams(location.search).get('url') || '';

	// un champ du formulaire, d'apres la fin de son nom (content_xxx_descriptif)
	function champ(nom) {
		return form.querySelector('[name$="_' + nom + '"]');
	}

	// ne plus toucher a un champ des que l'utilisateur y a saisi quelque chose
	['descriptif', 'surtitre'].forEach(function (nom) {
		var c = champ(nom);
		if (c) {
			c.addEventListener('input', function () {
				c.dataset.touche = 1;
			});
		}
	});

	function remplir(nom, valeur) {
		var c = champ(nom);
		if (c && valeur && !c.dataset.touche && !c.value.trim()) {
			c.value = valeur;
		}
	}

	var etat = document.createElement('small');
	etat.textContent = 'Lecture de la page…';
	form.insertBefore(etat, form.firstChild);

	function couper(s, n) {
		s = String(s || '').replace(/\s+/g, ' ').trim();
		return s.length > n ? s.slice(0, n).replace(/\s+\S*$/, '') + '…' : s;
	}

	function extraire(data) {
		var doc = new DOMParser().parseFromString(data.html, 'text/html');
		var base = doc.querySelector('base[href]');
		base = base ? new URL(base.getAttribute('href'), data.url).href : data.url;

		var meta = function (sel) {
			var m = doc.querySelector(sel);
			return m ? couper(m.getAttribute('content'), 600) : '';
		};
		var main = doc.querySelector('article, main, [role=main]') || doc.body;
		if (main) {
			main.querySelectorAll('script, style, noscript').forEach(function (e) {
				e.remove();
			});
		}

		// les tags : microformat relTag
		var tags = [];
		doc.querySelectorAll('a[rel~=tag]').forEach(function (a) {
			var t = couper(a.textContent, 100);
			if (t && tags.indexOf(t) < 0) {
				tags.push(t);
			}
		});

		// le logo : une image de classe logo
		var logo = Array.prototype.find.call(doc.images, function (i) {
			return /logos?\b/i.test(i.className) && i.getAttribute('src');
		});

		return {
			desc: meta('meta[name=description]') || meta('meta[property="og:description"]') || couper(main && main.textContent, 600),
			tags: tags.join(', '),
			logo: logo ? new URL(logo.getAttribute('src'), base).href : ''
		};
	}

	var fini = false;
	function terminer(msg) {
		fini = true;
		etat.textContent = msg;
		setTimeout(function () {
			etat.remove();
		}, 3000);
	}

	window.addEventListener('message', function (e) {
		if (fini || e.source !== window.opener || !e.data || typeof e.data.html !== 'string') {
			return;
		}
		try {
			var infos = extraire(e.data);
			remplir('descriptif', infos.desc);
			remplir('surtitre', infos.tags);
			// le serveur recupere le logo, si l'article n'en a pas deja un
			if (infos.logo) {
				fetch(location.pathname + '?' + new URLSearchParams({url: url, logo: infos.logo}), {credentials: 'same-origin'});
			}
			terminer('Page lue.');
		} catch (err) {
			console.error(err);
			terminer('Impossible de lire la page.');
		}
	});

	window.opener.postMessage('rezo', '*');
	setTimeout(function () {
		if (!fini) {
			terminer('Pas de réponse de la page (ancien bookmarklet ?)');
		}
	}, 5000);
})();
