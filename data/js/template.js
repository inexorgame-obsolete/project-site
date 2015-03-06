;(function ($, document, window, undefined){
	var templateCache = {};
	var globals = {};
	var location = '';
	
	// How to recognize a variable, 
	// fallbackVariables are vars where the second matching the default (fallback) value is.
	var regex = {
		fallbackVariable : /<%=([^:%]*):([^%]*)%>/gim,
		variable : /<%=([^%]*)%>/gim
	};

	/**
	 * Replaces the variables with its values.
	 * Also replaces globals.
	 * Values which are jQuery-objects will be parsed.
	 * @param  string markup the template-markup
	 * @param  string data   array containing the variables (index = variable-name)
	 * @return string        the html-markup
	 */
	function setVariables(markup, data)
	{
		/**
		 * Replaces a variable with a value.
		 * Designed to use with .replace-method.
		 * @param  string match    the string to match for variables
		 * @param  string variable the value
		 * @param  string fallback the fallback-value, if no variable is set
		 * @return string          the markup with replaced variables.
		 */
		function _replace(match, variable, fallback)
		{
			if(variable == variable.toUpperCase() && globals[variable] != undefined)
				return _parse(globals[variable]);
			else if(data[variable] != undefined && data[variable] !== false && data[variable] !== null)
				return _parse(data[variable]);
			else if(typeof fallback == 'string')
				return fallback;
			else
				console.warn("No variable " + variable + " in templating found!");
			return '';
		};

		markup = markup.replace(regex.fallbackVariable, _replace);
		markup = markup.replace(regex.variable, _replace);
		return markup;
	}

	/**
	 * Parses an object, jquery-object or array to a string
	 * @param  mixed  object the object to be parsed
	 * @return string        the parsed object
	 */
	function _parse(object)
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

	

	/**
	 * Forces to reload a template via ajax
	 * @param  string   template template-name to load
	 * @param  function callback callback to execute after reloaded. First and only parameter is the template-markup.
	 */
	$.reloadTemplate = function (template, callback)
	{
		$.get(location + template + '.html', function (data) {
			templateCache[template] = data;
			if(typeof callback == "function")
				callback(data);
		});
	},

	/**
	 * Loads a template if it was not already loaded
	 * @param  string template template-name to load
	 */
	$.loadTemplate = function (template) {
		if(typeof template == 'object')
		{
			$.each(template, function (i, v) {
				$.loadTemplate(v);
			});
			return;
		}
		if(templateCache[template] == undefined)
			$.reloadTemplate(template);
	},

	/**
	 * Gets a template and calls a callback (1st param: template-markup)
	 * @param  string   template template-name
	 * @param  function callback callback to execute after loaded
	 */
	$.getTemplate = function (template, callback) {
		if(templateCache[template] != undefined)
			callback(templateCache[template]);
		else
			$.reloadTemplate(template, callback);
	},

	/**
	 * Sets the location-url for the templates
	 * @param string loc location of the urls
	 */
	$.setTemplateLocation = function (loc) {
		location = loc;
	},

	/**
	 * Loads a template, replaces its content-variables with data and executes a callback with the markup (without variables)
	 * @param  string   template template-name
	 * @param  object   data     object containing the variables (index) and values
	 * @param  function callback function to execute after loaded
	 */
	$.template = function (template, data, callback) {
		$.getTemplate(template, function (template) {
			template = setVariables(template, data);
			callback(template);
		});
	},

	/**
	 * Sets a global variable. Globals are upper-case only.
	 * @param string name    the variable-name. must be UC-ONLY.
	 * @param string value   value of the variable.
	 */
	$.setTemplateGlobal = function (name, value)
	{
		globals[name] = value;
	},

	/**
	 * Replaces a template with data and returns it
	 * Template has to be loaded. Not asynchronous
	 * @param  string template template-name
	 * @param  object data     object-name
	 * @return string          template-markup without variables
	 */
	$.localTemplate = function (template, data)
	{
		if(templateCache[template] == undefined)
			return '';
		return setVariables(templateCache[template], data)
	}
}(jQuery, document, window));
