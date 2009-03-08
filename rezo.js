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
		$('#une,#depeches,#english')
			.memove('#marge');
		$('#marge').show();
	};

	var etroit = function() {
		if (!$('body').is('.large')) return;
		$('body').removeClass('large');
		$('#marge').hide();
		$.restaure();
	}

	var large_ou_etroit = function() {
		if ($(window).width() > 1036)
			large();
		else
			etroit();
	};

	// document.ready()
	$(function(){

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

		// Couper les dates repetitives
		var vu = [];
		var image = 1;
		$('#contenu .date').each(function(){
			var t = $(this).attr('class'); 
			if (vu[t])
				$(this).hide();
			else {
				vu[t]=1;
				$(this).show();
			}
		});

		// Supprimer les abbr[title] des microformats
		$('abbr.updated').attr('title', '');

		// large ou etroit ?
		$('#accueil').click(large);
		$('#entete').click(etroit);
		large_ou_etroit();
		$(window).resize(large_ou_etroit);
	});

})(jQuery);