;(function($) {

	var memo = {};

	$.fn.memove = function(dest) {
		return $(this).each(function(){
			var id = this.id;
			if (!$(this).parent('.hold').length)
				$(this).wrap('<div class="hold"><\/div>');
			memo[id] = $(this).parent();
			$(this).appendTo(dest);
		});
	};
	$.restaure = function() {
		$.each(memo, function(i) { $('#'+i).appendTo(memo[i]); });
		memo = {};
	};

	var large = function() {
		if ($('body').is('.large')) return;
		$('body').addClass('large');
		$('#citation')
			.memove('#accueil');
		$('#nuage')
			.memove('#entete');
		/* .add() pour conserver l'ordre */
		$('#une').add('#depeches').add('#english')
			.memove('#marge');
	};

	var etroit = function() {
		if (!$('body').is('.large')) return;
		$('body').removeClass('large');
		$.restaure();
	}

	var large_ou_etroit = function() {
		if ($.cookie('affichage') == '1024')
			return large();
		if ($.cookie('affichage') == '800')
			return etroit();
		return ($(window).width() > 1048)
			? large()
			: etroit();
	};

	$('head').append('<style id="antiflickr" type="text/css">.central {display:none;}<\/style>');

	// document.ready()
	$(function(){

		// large ou etroit ?
		large_ou_etroit();
		$(window).resize(large_ou_etroit);

		// Couper les dates repetitives
		var vu = [];
		$('#contenu>.articles>*>.date').each(function(){
			var t = $(this).attr('class'); 
			if (vu[t])
				$(this).hide();
			else {
				vu[t]=1;
				$(this).show();
			}
		});

		// ressortir quelques images
		var image=0;
		$('#contenu>.articles>.hentry').each(function(){
			var im = $('img.spip_logos', this);
			if (im.length && image<=0) {
				$('a[rel=bookmark]', this)
				.clone()
				.html('')
				.append(im)
				.insertAfter($('.date:eq(0)',this));
				image=4;
			}
			image--;
		});

		// cliquer un lien provoque un redirect pour les stats
		// mais on remet le bon url dans le DOM
		$('a[rel=bookmark]')
		.click(function() {
			var id  = $(this).parents('.hentry').attr('id');
			if (id && id.match(/^a\d+/)) {
				var url = this.href;
				var me = this;
				this.href = '/'+id.substr(1);
				setTimeout(function(){me.href=url;},100);
			}
		});

		// le survol des liens affiche leur bloc introduction
		$('#contenu a[rel=bookmark]')
		.hover(function(){
			$(this).parents('.hentry').find('.introduction').show();
		}, function() {
			$(this).parents('.hentry').find('.introduction').hide();
		});

		// Supprimer les abbr[title] des microformats
		$('abbr.updated').attr('title', '');

		$('#antiflickr').remove();
		$('.central').show(); // pour nav qui n'auraient pas compris la ligne precedente

		// Ajouter les boutons cookie/preference dans le pied de page
		$('<span>Vos pr&#233;f&#233;rences d&#8217;affichage&nbsp;:  \
		<a href="1024">large</a>,\
		<a href="800">&#233;troit</a>,\
		<a href="auto">automatique</a>.</span>')
		.find('a')
		.click(function(){
			$.cookie('affichage',$(this).attr('href'));
			large_ou_etroit();
			return false;
		})
		.end() .appendTo('#footer .central');
	});

})(jQuery);