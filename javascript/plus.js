// Page /plus, avant la creation de l'article : demander au bookmarklet
// (javascript/bookmarklet.js) le contenu de la page d'origine, en extraire
// les infos, puis recharger /plus avec ces infos (parametre infos=1).
// Sans reponse (ancien bookmarklet, pas d'opener), continuer avec l'url seule.
(function () {
	var params = new URLSearchParams(location.search);
	var fini = false;

	function continuer(infos) {
		if (fini) {
			return;
		}
		fini = true;
		Object.keys(infos || {}).forEach(function (k) {
			if (infos[k]) {
				params.set(k, infos[k]);
			}
		});
		params.set('infos', 1);
		location.replace(location.pathname + '?' + params);
	}

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
			title: couper(data.title || doc.title, 300),
			txt: couper(data.txt, 2000),
			desc: meta('meta[name=description]') || meta('meta[property="og:description"]') || couper(main && main.textContent, 600),
			lang: data.lang || doc.documentElement.lang || '',
			tags: tags.join(', '),
			logo: logo ? new URL(logo.getAttribute('src'), base).href : ''
		};
	}

	window.addEventListener('message', function (e) {
		if (!window.opener || e.source !== window.opener || !e.data || typeof e.data.html !== 'string') {
			return;
		}
		var infos = {};
		try {
			infos = extraire(e.data);
		} catch (err) {
			console.error(err);
		}
		continuer(infos);
	});

	if (window.opener) {
		window.opener.postMessage('rezo', '*');
		setTimeout(continuer, 5000);
	} else {
		continuer();
	}
})();
