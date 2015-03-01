$(function(){
});
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
	$('*[title]').tooltipster();
})