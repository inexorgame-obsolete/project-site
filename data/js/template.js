;(function ($, document, window, undefined){
	var templateCache = {};
	var globals = {};
	var location = '';
	var regex = {
		fallbackVariable : /<%=([^:%]*):([^%]*)%>/gim,
		variable : /<%=([^%]*)%>/gim
	};
	$.reloadTemplate = function (template, callback)
	{
		$.get(location + template + '.html', function (data) {
			templateCache[template] = data;
			if(typeof callback == "function")
				callback(data);
		});
	},
	$.loadTemplate = function (template) {
		if(templateCache[template] == undefined)
			$.reloadTemplate(template);
	},
	$.getTemplate = function (template, callback) {
		if(templateCache[template] != undefined)
			callback(templateCache[template]);
		else
			$.reloadTemplate(template, callback);
	},
	$.setTemplateLocation = function (loc) {
		location = loc;
	},
	$.template = function (template, data, callback) {
		$.getTemplate(template, function (template) {
			var _parse = function (object)
			{
				if(object instanceof jQuery)
				{
					return object.prop('outerHTML');
				}
				if(typeof object == "object")
				{
					var ret = '';
					$.each(object, function (index, value) {
						ret += _parse(value);
					});
					return ret;
				}
				return object;
			};
			var _replace = function (match, variable, fallback)
			{
				if(variable == variable.toUpperCase() && globals[variable] != undefined)
					return _parse(globals[variable]);
				else if(data[variable] != undefined)
					return _parse(data[variable]);
				else if(fallback !== 0)
					return fallback;
				else
				console.warn("No variable " + variable + " in templating found!");
				return '';
			};
			template = template.replace(regex.fallbackVariable, _replace);
			template = template.replace(regex.variable, _replace);
			callback(template);
		});
	},
	$.setTemplateGlobal = function (name, value)
	{
		globals[name] = value;
	}
}(jQuery, document, window));
