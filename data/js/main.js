$.setTemplateGlobal('BASE_URL', base_url);
$.setTemplateGlobal('COMMENTS_API', 'commentsapi');
$.setTemplateGlobal('RATING_API', 'ratingapi');
$.setTemplateLocation(base_url + 'data/templates/');

;(function ($, document, window, undefined){
	$.initTooltipster = function () {
		$('.rating:not(.tooltipstered)').each(function () {
			$(this).tooltipster({
				contentAsHTML: true,
				functionBefore: function (origin, continueTooltip) {
					var _this = $(this);
					var data = _this.attr('data-tooltip');
					var replace = {
						positive : _this.data('positive'),
						negative : _this.data('negative'),
						overall  : _this.data('overall')
					}

					if(replace.positive != undefined)
						data = data.replace(/%positive/gim, replace.positive)

					if(replace.negative != undefined)
						data = data.replace(/%negative/gim, replace.negative)

					if(replace.overall != undefined)
						data = data.replace(/%overall/gim, replace.overall)
					origin.tooltipster('content', data);
					continueTooltip();
				}
			});
		});

		$('*[data-tooltip]:not(.tooltipstered)').each(function () {
			$(this).tooltipster({
				content: $(this).data('tooltip'),
				contentAsHTML: true
			});
		});
		
		$('*[title]:not([disabled]):not(.tooltipstered)').tooltipster();
	}
}(jQuery, document, window));



$(document).ready(function () {
	$('*[data-moment]').each(function () {
		var m = $(this).data('moment');
		$(this).text(moment(m, time_string).fromNow());
	});
	$(document).ajaxError(function (event, jqxhr, settings, thrown) {
		console.warn("Ajax Error (order: event, jqxhr, settings, thrown):");
		console.warn(event);
		console.warn(jqxhr);
		console.warn(settings);
		console.warn(thrown);
	});

	$('a[href|="#nojump"]').click(function (e) {
		e.preventDefault();
		before_anchor_click_offset_top = $(document).scrollTop();
		window.location.hash = $(this).attr('href').substr(1);
		$(window).scrollTop(before_anchor_click_offset_top);
	});
	$.initTooltipster();
});