(function (factory) {
	"use strict";

	if (typeof define === 'function' && define.amd) {
		// AMD
		define(['jquery', 'select2/dist/js/select2.full.js'], function (jQuery, select2) {
			return factory(jQuery, select2, window, document);
		});
	} else if (typeof exports === 'object') {
		// CommonJS
		module.exports = function (root, jQuery, select2) {
			if (!root) {
				// CommonJS environments without a window global must pass a
				// root. This will give an error otherwise
				root = window;
			}

			if (!jQuery) {
				jQuery = typeof window !== 'undefined' ? // jQuery's factory checks for a global window
					require('jquery') :
					require('jquery')(root);
			}
			if (!select2) {
				select2 = require('select2/dist/js/select2.full.js');
			}
			return factory(jQuery, select2, root, root.document);
		};
	} else {
		// Browser
		factory(jQuery, select2, window, document);
	}
}
(function (jQuery, select2, window, document, undefined) {

	var PecSelect2 = window.PecSelect2 = window.PecSelect2 || function () {
	};

	/**
	 * @return The select2 object generated
	 */
	PecSelect2.init = function(selector, options) {
		"use strict";
		var $field = jQuery(selector);
		var modal = $field.closest('.modal');
		if(modal.length > 0 ) {
			options.dropdownParent = modal;
		}
		var canReportValidity = $field.length > 0 && $field[0].reportValidity;
		if(!canReportValidity && $field.prop('required')) {
			$field.on('invalid', function () {
				$field.closest('.form-group, .select-parent').addClass('has-error');
				$field.one('change', function () {
					$field.closest('.form-group, .select-parent').removeClass('has-error');
				});
				if(typeof window._pecSelect2ShowAlert === 'undefined' || window._pecSelect2ShowAlert === true) {
					if(typeof PecDialog !== 'undefined') {
						window._pecSelect2ShowAlert = false;
						var message = Translator.trans('stinger_soft_form.required.validation', {}, 'PecSelect2FormBundle');
						PecDialog.alert({message: message, callback: function(){
								window._pecSelect2ShowAlert = true;
							}
						});
					}
				}
			});
		}
		return jQuery(selector).select2(options);
	};

	/**
	 * Remove the title attribute which is automatically added by Select2
	 *
	 * @param selector
	 */
	PecSelect2.removeTitle = function(selector) {
		"use strict";

		var $element = jQuery(selector);
		$element.next('.select2-container').find('.select2-selection__rendered').removeAttr('title');
		$element.on('select2:select select:unselect', function() {
			var $select2Container = $element.next('.select2-container');
			$select2Container.find('.select2-selection__rendered').removeAttr('title');
		});
	};

	PecSelect2.addSelectionTooltip = function (selector, placement) {
		"use strict";
		var $element = jQuery(selector);
		$element.on('select2:select select:unselect', function() {
			placement = (placement === 'true' || placement === true || placement === "1") ? 'top' : placement;
			var $select2Container = $element.next('.select2-container');
			var $span = $select2Container.find('.select2-selection__rendered');
			$span.tooltip('destroy');
			$span.find('.tooltip').remove();
			setTimeout(function() {
				$span.tooltip({placement: placement});
			}, 150);
		});
	};

	PecSelect2.addTooltip = function(selector, placement){
		"use strict";
		placement = placement === 'true' || placement === true || placement === "1" ? 'auto' : placement;
		jQuery(selector).on('select2-open', function(event){
			jQuery(".select2-results li div").tooltip({
				title: function() {
					return $(this).text();
				},
				placement: placement,
				container: jQuery('.select2-drop-active')
			});
		});
		jQuery(selector).on('select2-close', function(event){
			jQuery('.tooltip').remove();
		});
	};

	PecSelect2.require = jQuery.fn.select2.amd.require;


	PecSelect2.matcher = function(){};

	PecSelect2.matcher.internal = function(){};
	PecSelect2.matcher.internal.matched = 0;
	PecSelect2.matcher.internal.not_matched = 1;
	PecSelect2.matcher.internal.abstain = 2;

	PecSelect2.matcher.internal.diacritics = PecSelect2.require('select2/diacritics');

	PecSelect2.matcher.internal.stripDiacritics = function stripDiacritics (text) {
		"use strict";
		// Used 'uni range + named function' from http://jsperf.com/diacritics/18
		function match(a) {
			return PecSelect2.matcher.internal.diacritics[a] || a;
		}
		return text.replace(/[^\u0000-\u007E]/g, match);
	};

	PecSelect2.matcher.internal.must = function(label, term){
		"use strict";
		if(label.indexOf(term) < 0) {
			return PecSelect2.matcher.internal.not_matched;
		}
		return PecSelect2.matcher.internal.abstain;
	};

	PecSelect2.matcher.internal.may = function(label, term){
		"use strict";
		if(label.indexOf(term) >= 0) {
			return PecSelect2.matcher.internal.matched;
		}
		return PecSelect2.matcher.internal.abstain;
	};

	PecSelect2.matcher.internal.matcher = function(params, data, callable, abstainMatch, labelPath) {
		"use strict";

		// Always return the object if there is nothing to compare
		if ($.trim(params.term) === '') {
			return data;
		}

		//hide disabled results
		if(data.disabled) {
			if(typeof data.element !== "undefined" && !jQuery(data.element.parentElement).data('searchDisabled')) {
				return null;
			}
		}

		var label = data.text;
		if(labelPath && data.hasOwnProperty(labelPath)) {
			label = data[labelPath];
		}
		var original = PecSelect2.matcher.internal.stripDiacritics(label).toUpperCase();
		var term = PecSelect2.matcher.internal.stripDiacritics(params.term).toUpperCase();

		var children = null;

		// hidden children
		if(data._children && data._children.length > 0) {
			children = data._children;
		}
		if (data.children && data.children.length > 0){
			children = data.children;
		}

		// Do a recursive check for options with children
		if(children) {
			// Clone the data object if there are children
			// This is required as we modify the object to remove any non-matches
			var match = jQuery.extend(true, {}, data);

			// Check each child of the option
			for (var c = children.length - 1; c >= 0; c--) {
				var child = children[c];

				var matches = PecSelect2.matcher.internal.matcher(params, child, callable, abstainMatch, labelPath);

				//	If there wasn't a match, remove the object in the array
				if (matches === null) {
					(match.children || match._children).splice(c, 1);
				}
			}

			// If any children matched, return the new object
			if ((match.children || match._children).length > 0) {
				return match;
			}

			// If there were no matching children, check just the plain object
			return PecSelect2.matcher.internal.matcher(params, match, callable, abstainMatch, labelPath);
		}

		// Check if the text contains the term
		var res = term.split(" ");
		for(var i = 0; i < res.length; i++) {
			var check = callable(original, res[i]);
			if (check === PecSelect2.matcher.internal.matched) {
				return data;
			}
			if (check === PecSelect2.matcher.internal.not_matched) {
				return null;
			}
		}

		return abstainMatch ? data : null;
	};

	PecSelect2.matcher.and = function(params, data, labelPath) {
		"use strict";
		return PecSelect2.matcher.internal.matcher(params, data, PecSelect2.matcher.internal.must, true, labelPath);
	};

	PecSelect2.matcher.or = function(params, data, labelPath) {
		"use strict";
		return PecSelect2.matcher.internal.matcher(params, data, PecSelect2.matcher.internal.may, false, labelPath);
	};

	PecSelect2.matcher.hierarchical_and = function(query, element){
		"use strict";
		return PecSelect2.matcher.and(query, element, 'path_text');
	};

	PecSelect2.matcher.hierarchical_or = function(query, element){
		"use strict";
		return PecSelect2.matcher.or(query, element, 'path_text');
	};

	PecSelect2.templateSelection = function(){};
	PecSelect2.templateSelection.hierarchical = function(data, container){
		"use strict";
		return data.path_text;
	};

	PecSelect2.templateResult = function(){};
	PecSelect2.templateResult.hierarchical = function(data){
		"use strict";
		if(data.hasOwnProperty('element') && data.element) {
			for (const prop in data) {
				if (data.hasOwnProperty(prop) && prop.startsWith('data')) {
					jQuery(data.element).attr(prop, data[prop]);
				}
			}
			jQuery(data.element).data('fulldata', data);
		}
		if (!data.id || !data.level) {
			return data.text;
		}
		return jQuery('<span style="padding-left: '+(data.level*20)+'px;">'+data.text+'</span>');
	};

    PecSelect2.templateResult.userRealnameUsername = function (data) {
        "use strict";
		if (data.hasOwnProperty('text') && data.text) {
			return data.text;
		}
		if(!data.firstname && !data.surname) {
			return data.username;
		}
		let result = '';
		if(data.firstname) {
			result += data.firstname + ' ';
		}
		if(data.surname) {
			result += data.surname + ' ';
		}
		if(data.username) {
			result += '(' + data.username + ')';
		}

        return result.trim();
    };


	PecSelect2.ajax = function(){};
	PecSelect2.ajax.dataMapper = function(){};
	PecSelect2.ajax.dataMapper.noop = function(data) {
		"use strict";
		return {results: data};
	};

	PecSelect2.ajax.dataMapper.labelToText = function(data) {
		"use strict";
		var results = [];
		jQuery(data).each(function(i, item) {
			//If children, build optgroup format
			if(item.hasOwnProperty('children')) {
				var children = PecSelect2.ajax.dataMapper.labelToText(item.children).results;
				results.push({
					'text': item.hasOwnProperty('text') ? item.text : item.label,
					'children': children
				});
			} else {
				item.text = item.label;
				results.push(item);
			}
		});
		return {results: results};
	};

	PecSelect2.ajax.dataMapper.labelOnly = function(data) {
		"use strict";
		var results = [];
		jQuery(data).each(function(i, item){
			item.text = item.label;
			item.id = item.label;
			results.push(item);
		});
		return {results: results};
	};

	/**
	 * Raw markup cleaner, to escape text passed to select2
	 */
	PecSelect2.escapeMarkup = function(){};
	PecSelect2.escapeMarkup.raw = function(text) {
		"use strict";
		return text;
	};

	return PecSelect2;
}));
