;(function ($, document, window, undefined){
	var api = '';
	var location = window.location.origin + window.location.pathname;
	var lastrating = '';
	var classes = {};
	$.initRating = function () {
		$('.rating-large:not(.js-ajax-rating), .rating-medium:not(.js-ajax-rating), .rating-small:not(.js-ajax-rating)').addClass('js-ajax-rating').setAjaxRating();
		$.setRatingClasses('up'  , true , ['fa-plus-square']   );
		$.setRatingClasses('up'  , false, ['fa-plus-square-o'] );
		$.setRatingClasses('down', true , ['fa-minus-square']  );
		$.setRatingClasses('down', false, ['fa-minus-square-o']);
	}
	$.rating = function (module, identifier, template, anchor, callback)
	{
		if(template == undefined || template === false || template === null)
		{
			template = 'small';
		}

		if(typeof anchor == 'function')
		{
			template = anchor;
			anchor   = '';
		}

		if(anchor == null || anchor == false || anchor == undefined)
			anchor = '';
		else if(anchor[0] != '#' && anchor.length > 0)
			anchor = '#' + anchor;

		if(typeof callback == 'function')
		{
			$.get(api + '/' + module + '/' + identifier, function (data)
			{
				var template_vars = {
					upvote    : data.user_vote ==  1 ? 'remove' : false,
					downvote  : data.user_vote == -1 ? 'remove' : false,
					location  : window.location.origin + window.location.pathname + anchor,
					userrating: data.user_vote,
					module    : module,
					identifier: identifier,
					disabled  : (logged_in) ? '' : false,
					positiveratings : data.positive,
					negativeratings : data.negative,
					overallratings  : data.ratings,
					rating          : data.positive - data.negative,
					rateiconnegative: data.user_vote == -1 ? '' : false,
					rateiconpositive: data.user_vote ==  1 ? '' : false,
				};
				if(data.own_post == true)
					template_vars.disabled = ' disabled="disabled" title="You can not vote your own posts"';
				$.template('rating/' + template, template_vars, callback);
			});
		}
	},
	$.setLastRating = function (l) {
		lastrating = l;
	},
	$.fn.setAjaxRating = function () {
		$.loadTemplate(['rating/small']);
		var _AllForms = $(this);
		_AllForms.submit(function () {
			return false;
		});
		$('button', _AllForms).each(function () {
			var __button = $(this);
			var _form = __button.closest('form');
			__button.click(function () {
				var old = _form.data('current-user-rating');
				var postData = _form.getFormData();
				postData.rating = __button.val();
				var buttonType = __button.data('rating-button');
				$.post(api, postData, function (data) {
					var _rating = $('.rating', _form);
					var oldoverall = parseInt(_rating.text());
					if(typeof data == 'number')
					{
						if(old == 0)
						{
							_rating.text(oldoverall+data);
							var increase = (data == 1 ? 'positive' : 'negative');
							$('*[data-' + increase + ']', _form).increaseRating(increase);
							$('*[data-overall]', _form).increaseRating('overall');
						} 
						else if(old == 1)
						{
							$('button[data-rating-button!=' + buttonType + ']', _form).removeRatingSubmitted();
							$('*[data-positive]', _form).decreaseRating('positive');

							_rating.text(oldoverall+data-1);

							if(data == -1)
								$('*[data-negative]', _form).increaseRating('negative');
							else
								$('*[data-overall]', _form).decreaseRating('overall');
						} else {
							$('button[data-rating-button!=' + buttonType + ']', _form).removeRatingSubmitted();
							$('*[data-negative]', _form).decreaseRating('negative');

							_rating.text(oldoverall+data+1);

							if(data == 1)
								$('*[data-positive]', _form).increaseRating('positive');
							else
								$('*[data-overall]', _form).decreaseRating('overall');
						}
						if(data == 0)
						{
							__button.removeRatingSubmitted();
						} else {
							__button.setRatingSubmitted();
						}
						_form.data('current-user-rating', data);
					}
				});
			});
		});
	},
	$.fn.changeRating = function (value, type) {
		$(this).each(function () {
			var _this = $(this);
			var number = _this.data(type);
			if(number != undefined)
			{
				_this.data(type, number+value);

				if(_this.data('content-replace') == true)
					_this.text(number+value);
			}
		});
		return this;
	},
	$.fn.decreaseRating = function (type) {
		return $(this).changeRating(-1, type);
	},
	$.fn.increaseRating = function (type) {
		return $(this).changeRating(1, type);
	},
	$.fn.setRatingSubmitted = function () {
		var type = $(this).data('rating-button');
		$(this)
			.removeClass(classes[type].deactivated.join(' '))
			.addClass(classes[type].activated.join(' '))
			.val('remove');
		return this;
	}
	$.fn.removeRatingSubmitted = function () {
		var type = $(this).data('rating-button');
		$(this)
			.removeClass(classes[type].activated.join(' '))
			.addClass(classes[type].deactivated.join(' '))
			.val(type);
		return this;
	},
	$.setRatingApi = function (s)
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
	},
	$.setRatingClasses = function (button, activated, class_array) {
		if(typeof classes[button] != 'object')
			classes[button] = {};
		if(activated)
			classes[button].activated = class_array;
		else
			classes[button].deactivated = class_array;
	};
}(jQuery, document, window));
$(document).ready(function () {
	$.setRatingApi(base_url + 'ratingapi');
	$.initRating();
});