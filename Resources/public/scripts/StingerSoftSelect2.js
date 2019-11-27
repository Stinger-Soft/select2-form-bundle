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
		factory(jQuery, jQuery.fn.select2, window, document);
	}
}
(function (jQuery, select2, window, document, undefined) {

	StingerSoftSelect2 = function(){};

	/**
	 * @return The select2 object generated
	 */
	StingerSoftSelect2.init = function(selector, options) {
		"use strict";
		var $field = jQuery(selector);
		if(!$field[0].reportValidity && $field.prop('required')) {
			$field.on('invalid', function () {
				$field.closest('.form-group, .select-parent').addClass('has-error');
				$field.one('change', function () {
					$field.closest('.form-group, .select-parent').removeClass('has-error');
				});
			});
		}
		return jQuery(selector).select2(options);
	};

	/**
	 * Remove the title attribute which is automatically added by Select2
	 *
	 * @param selector
	 */
	StingerSoftSelect2.removeTitle = function(selector) {
		"use strict";

		var $element = jQuery(selector);
		$element.next('.select2-container').find('.select2-selection__rendered').removeAttr('title');
		$element.on('select2:select select:unselect', function() {
			var $select2Container = $element.next('.select2-container');
			$select2Container.find('.select2-selection__rendered').removeAttr('title');
		});
	};

	StingerSoftSelect2.addSelectionTooltip = function (selector, placement) {
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

	StingerSoftSelect2.addTooltip = function(selector, placement){
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

	StingerSoftSelect2.require = jQuery.fn.select2.amd.require;


	StingerSoftSelect2.matcher = function(){};
	StingerSoftSelect2.matcher.combat = StingerSoftSelect2.require('select2/compat/matcher');

	StingerSoftSelect2.matcher.internal = function(){};
	StingerSoftSelect2.matcher.internal.matched = 0;
	StingerSoftSelect2.matcher.internal.not_matched = 1;
	StingerSoftSelect2.matcher.internal.abstain = 2;

	StingerSoftSelect2.matcher.internal.diacritics = StingerSoftSelect2.require('select2/diacritics');

	StingerSoftSelect2.matcher.internal.stripDiacritics = function stripDiacritics (text) {
		"use strict";
		// Used 'uni range + named function' from http://jsperf.com/diacritics/18
		function match(a) {
			return StingerSoftSelect2.matcher.internal.diacritics[a] || a;
		}
		return text.replace(/[^\u0000-\u007E]/g, match);
	};

	StingerSoftSelect2.matcher.internal.must = function(label, term){
		"use strict";
		if(label.indexOf(term) < 0) {
			return StingerSoftSelect2.matcher.internal.not_matched;
		}
		return StingerSoftSelect2.matcher.internal.abstain;
	};

	StingerSoftSelect2.matcher.internal.may = function(label, term){
		"use strict";
		if(label.indexOf(term) >= 0) {
			return StingerSoftSelect2.matcher.internal.matched;
		}
		return StingerSoftSelect2.matcher.internal.abstain;
	};

	StingerSoftSelect2.matcher.internal.matcher = function(params, data, callable, abstainMatch, labelPath) {
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
		var original = StingerSoftSelect2.matcher.internal.stripDiacritics(label).toUpperCase();
		var term = StingerSoftSelect2.matcher.internal.stripDiacritics(params.term).toUpperCase();

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

				var matches = StingerSoftSelect2.matcher.internal.matcher(params, child, callable, abstainMatch, labelPath);

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
			return StingerSoftSelect2.matcher.internal.matcher(params, match, callable, abstainMatch, labelPath);
		}

		// Check if the text contains the term
		var res = term.split(" ");
		for(var i = 0; i < res.length; i++) {
			var check = callable(original, res[i]);
			if (check === StingerSoftSelect2.matcher.internal.matched) {
				return data;
			}
			if (check === StingerSoftSelect2.matcher.internal.not_matched) {
				return null;
			}
		}

		return abstainMatch ? data : null;
	};

	StingerSoftSelect2.matcher.and = function(params, data, labelPath) {
		"use strict";
		return StingerSoftSelect2.matcher.internal.matcher(params, data, StingerSoftSelect2.matcher.internal.must, true, labelPath);
	};

	StingerSoftSelect2.matcher.or = function(params, data, labelPath) {
		"use strict";
		return StingerSoftSelect2.matcher.internal.matcher(params, data, StingerSoftSelect2.matcher.internal.may, false, labelPath);
	};

	StingerSoftSelect2.matcher.hierarchical_and = function(query, element){
		"use strict";
		return StingerSoftSelect2.matcher.and(query, element, 'path_text');
	};

	StingerSoftSelect2.matcher.hierarchical_or = function(query, element){
		"use strict";
		return StingerSoftSelect2.matcher.or(query, element, 'path_text');
	};

	StingerSoftSelect2.templateSelection = function(){};
	StingerSoftSelect2.templateSelection.hierarchical = function(data, container){
		"use strict";
		return data.path_text;
	};

	StingerSoftSelect2.templateResult = function(){};
	StingerSoftSelect2.templateResult.hierarchical = function(data){
		"use strict";
		if (!data.id || !data.level) {
			return data.text;
		}
		return jQuery('<span style="padding-left: '+(data.level*20)+'px;">'+data.text+'</span>');
	};


	StingerSoftSelect2.ajax = function(){};
	StingerSoftSelect2.ajax.dataMapper = function(){};
	StingerSoftSelect2.ajax.dataMapper.noop = function(data) {
		"use strict";
		return {results: data};
	};

	StingerSoftSelect2.ajax.dataMapper.labelToText = function(data) {
		"use strict";
		var results = [];
		jQuery(data).each(function(i, item) {
			//If children, build optgroup format
			if(item.hasOwnProperty('children')) {
				var children = StingerSoftSelect2.ajax.dataMapper.labelToText(item.children).results;
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

	StingerSoftSelect2.ajax.dataMapper.labelOnly = function(data) {
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
	StingerSoftSelect2.escapeMarkup = function(){};
	StingerSoftSelect2.escapeMarkup.raw = function(text) {
		"use strict";
		return text;
	};

	return StingerSoftSelect2;
}));
