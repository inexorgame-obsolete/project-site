$(document).ready(function () {
	var windowHeight = $(window).height();
	var windowWidth = $(window).width();
	$('.image-mover').css('background-position', '50% 50%');
	$(document).mousemove(function (e) {
		console.log();
		$('.image-mover').css('background-position', (e.pageX / windowWidth * 100) + '% ' + (e.pageY / windowHeight * 60 + 20) + '%');
		// console.log((e.pageY / windowHeight * 10 + 45));
	});
});