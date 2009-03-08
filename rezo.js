;(function($) {

	// document.ready()
	$(function(){

		// cliquer un lien provoque un redirect pour les stats
		// mais on remet le bon url dans le DOM
		$('.hentry a[rel=bookmark]')
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
		$('.articles .hentry a[rel=bookmark]')
		.hover(function(){
			$(this).parents('.hentry').find('.introduction').show();
		}, function() {
			$(this).parents('.hentry').find('.introduction').hide();
		});

		// Couper les dates repetitives
		var vu = [];
		var image = 1;
		$('.articles .date').each(function(){
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

	});

})(jQuery);