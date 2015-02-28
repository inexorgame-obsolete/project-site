$(function(){
});
$(document).ready(function () {
	$('*[data-moment]').each(function () {
		var m = $(this).data('moment');
		$(this).text(moment(m, time_string).fromNow());
	});
})