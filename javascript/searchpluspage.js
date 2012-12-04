/*
 *@author nicolaas[at]sunnysideup.co.nz
 **/

;(function($) {
	$(document).ready(function() {
		searchpluspage.init();
	});

	var searchpluspage = {
		init: function() {
			jQuery("#MainSearch input.text").attr("name", "Search");
		}
	}
})(jQuery);
