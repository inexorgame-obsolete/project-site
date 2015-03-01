;(function ($, document, window, undefined){
	var api = '';
	$.fn.displayComment = function (data, container, isanswer) {
		console.log(data);
		if(data.error != undefined)
			console.warn("Error: invalid input. Message: " + data.error);
		var selector = $(this);
		var prepend = (selector.data('comment-action') == 'next' && selector.parent().hasClass('comments')) || (selector.data('comment-action') == 'previous' && !(selector.data('comment-answers') == true) && selector.parent().hasClass('answer'));
		if(!(container instanceof jQuery))
			var container = selector.parent();
		var highest = 0;
		selector.remove();
		$.each(data, function (index, value) {
			if(value != undefined && !isNaN(index)) {
				if(parseInt(value.id) > highest) highest = parseInt(value.id);
				$.template('comments/comment', {
					id : value.id,
					userid : value.creator.id,
					username : value.creator.ingame_name.escaped(),
					comment : value.comment.escaped(),
					date : value.date,
					datestring : moment(value.date, time_string).fromNow(),
					userimage : value.creator.avatar,
					seeanswers : (value.count_answers>0 ? '&sdot; <a data-comment-answers="true" data-comment-reference="' + value.id + '" data-comment-action="next">Show answers (' + value.count_answers + ')</a>':undefined),
					answer : (isanswer ? 'answer' : undefined)
				}, function (markup) {
					if(prepend)
						container.prepend(markup);
					else
						container.append(markup);
					$('*[data-comment-answer]').enableCommentsAnswer();
					$('*[data-comment-action]').enableCommentsLoad();
				});	
			}
			if(index == 'comments_left' && value != 0)
			{
				$.template('comments/button', {
					number : value,
					answer : (isanswer ? 'answer' : undefined),
					id : highest,
					s  : (value == 0 ? '' : undefined)
				}, function (markup) {
					container.append(markup);
					$('*[data-comment-action]').enableCommentsLoad();
				});
			}
		});
	},
	$.fn.enableCommentsLoad = function () {
		$(this)
			.removeAttr('href')
			.unbind('click')
			.click(function () {
				$.closeAnswer();
				var _this = $(this);
				$.post(api, {
					reference   : _this.data('comment-reference'),
					action      : _this.data('comment-action'),
					get_answers : (_this.data('comment-answers') == true) 
				},
				function (data) {
					// if ( are they new answers )
					if(_this.data('comment-answers') == true)
						_this.displayComment(data, _this.parent().parent(), true);
					// else if ( are they more answers to a post)
					else if(data[0] != undefined && data[0].answer_to != null)
						_this.displayComment(data, _this.parent(), true);
					// else (they are more commentes (not answers))
					else
						_this.displayComment(data);
				});
			});
	},
	$.fn.closeAnswer = function (id) {
		var _this = $(this);
		_this.text("Cancel").click(function () {
			$.closeAnswer();
			_this.enableCommentsAnswer();
		});
	},
	$.closeAnswer = function () {
		$('.create-answer').remove();
	},
	$.fn.answerAjaxSubmit = function () {
		var _this = $(this);
		_this.submit(function () {
			var _comment = $('*[name="comment"]', _this);
			if(_comment.val().length == 0)
			{
				_comment.addClass("error");
				setTimeout(function () {_comment.removeClass("error");}, 1500)
				return false;
			};
			var data = _this.getFormData();
			$.post(api + 'create', data, 
			function (data)
			{
				if(data.error != undefined)
				{
					$('*[name="comments-submit"]', _this).after('<span class="comments-error">' + data.error + '</span>');
				}
				if(data.success == true)
				{
					$.template('comments/comment', {
						id : data.id,
						userid : data.creator.id,
						username : data.creator.ingame_name.escaped(),
						comment : data.comment.escaped(),
						date : data.date,
						datestring : moment(data.date, time_string).fromNow(),
						userimage : data.creator.avatar,
						answer : 'answer'
					}, function (markup) {
						_this.parent().append(markup);
						_this.remove();
						$('*[data-comment-answer]').enableCommentsAnswer();
					});
				}
			});
			return false;
		});
	},
	$.fn.commentAjaxSubmit = function () {
		var _this = $(this);
		_this.submit(function () {
			var _comment = $('*[name="comment"]', _this);
			if(_comment.val().length == 0)
			{
				_comment.addClass("error");
				setTimeout(function () {_comment.removeClass("error");}, 1500)
				return false;
			}
			var data = _this.getFormData();
			$.post(api + 'create', data, 
			function (data)
			{
				if(data.error != undefined)
				{
					$('*[name="comments-submit"]', _this).after('<span class="comments-error">' + data.error + '</span>');
				}
				if(data.success == true)
				{
					$.template('comments/comment', {
						id : data.id,
						userid : data.creator.id,
						username : data.creator.ingame_name.escaped(),
						comment : data.comment.escaped(),
						date : data.date,
						datestring : moment(data.date, time_string).fromNow(),
						userimage : data.creator.avatar
					}, function (markup) {
						_this.parent().siblings('.comments-container').prepend(markup);
						$('*[name=comment]').val('');
						$('*[data-comment-answer]').enableCommentsAnswer();
					});
				}
			});
			return false;
		});
	},
	$.fn.enableCommentsAnswer = function () {
		var _this = $(this);
		_this.text("Answer");
		_this.unbind('click');
		_this.removeAttr('href').click(function () {
			$(".create-answer").remove();
			_this.enableCommentsAnswer();
			var __this = $(this);
			var aid = __this.data('comment-answer');
			$.template('comments/form', {
				answerid : aid,
				disabled : (logged_in ? undefined : 'disabled'),
				placeholder : (logged_in ? undefined : 'You have to log in or create an account to answer.'),
				location : window.location.origin + window.location.pathname + '?answers=' + aid + '#comment-' + aid,
				randomstring : Date.now()
			}, function (markup) {
				__this.parent().parent().append(markup);
				$('.create-answer').answerAjaxSubmit();
				__this.unbind('click').closeAnswer(aid);
			});
		});
	},
	$.setCommentApi = function (s)
	{
		api = s;
	},
	$.fn.getFormData = function () {
		var ret = {};
		$('*[name]', this).each(function () {
			var _this = $(this);
			ret[_this.attr('name')] = _this.val();
		});
		return ret;
	}
}(jQuery, document, window));
$(document).ready(function () {
	$.setCommentApi(base_url + 'commentsapi/');
	$('*[data-comment-action]').enableCommentsLoad();
	$('*[data-comment-answer]').enableCommentsAnswer();
	$('.create-comment').commentAjaxSubmit();
});