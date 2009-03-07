;(function($) {

	// document.ready()
	$(function(){

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