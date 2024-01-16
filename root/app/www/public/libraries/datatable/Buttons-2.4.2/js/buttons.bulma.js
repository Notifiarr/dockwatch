/*! Bulma integration for DataTables' Buttons
 * © SpryMedia Ltd - datatables.net/license
 */

(function( factory ){
	if ( typeof define === 'function' && define.amd ) {
		// AMD
		define( ['jquery', 'datatables.net-bm', 'datatables.net-buttons'], function ( $ ) {
			return factory( $, window, document );
		} );
	}
	else if ( typeof exports === 'object' ) {
		// CommonJS
		var jq = require('jquery');
		var cjsRequires = function (root, $) {
			if ( ! $.fn.dataTable ) {
				require('datatables.net-bm')(root, $);
			}

			if ( ! $.fn.dataTable.Buttons ) {
				require('datatables.net-buttons')(root, $);
			}
		};

		if (typeof window === 'undefined') {
			module.exports = function (root, $) {
				if ( ! root ) {
					// CommonJS environments without a window global must pass a
					// root. This will give an error otherwise
					root = window;
				}

				if ( ! $ ) {
					$ = jq( root );
				}

				cjsRequires( root, $ );
				return factory( $, root, root.document );
			};
		}
		else {
			cjsRequires( window, jq );
			module.exports = factory( jq, window, window.document );
		}
	}
	else {
		// Browser
		factory( jQuery, window, document );
	}
}(function( $, window, document, undefined ) {
'use strict';
var DataTable = $.fn.dataTable;



$.extend(true, DataTable.Buttons.defaults, {
	dom: {
		container: {
			className: 'dt-buttons field is-grouped'
		},
		button: {
			className: 'button is-light',
			active: 'is-active',
			disabled: 'is-disabled'
		},
		collection: {
			action: {
				tag: 'div',
				className: 'dropdown-content',
				dropHtml: ''
			},
			button: {
				tag: 'a',
				className: 'dt-button dropdown-item',
				active: 'dt-button-active',
				disabled: 'is-disabled',
				spacer: {
					className: 'dropdown-divider',
					tag: 'hr'
				}
			},
			closeButton: false,
			container: {
				className: 'dt-button-collection dropdown-menu',
				content: {
					className: 'dropdown-content'
				}
			}
		},
		split: {
			action: {
				tag: 'button',
				className: 'dt-button-split-drop-button button is-light',
				closeButton: false
			},
			dropdown: {
				tag: 'button',
				dropHtml: '<i class="fa fa-angle-down" aria-hidden="true"></i>',
				className: 'button is-light',
				closeButton: false,
				align: 'split-left',
				splitAlignClass: 'dt-button-split-left'
			},
			wrapper: {
				tag: 'div',
				className: 'dt-button-split dropdown-trigger buttons has-addons',
				closeButton: false
			}
		}
	},
	buttonCreated: function (config, button) {
		// For collections
		if (config.buttons) {
			// Wrap the dropdown content in a menu element
			config._collection = $('<div class="dropdown-menu"/>').append(config._collection);

			// And add the collection dropdown icon
			$(button).append(
				'<span class="icon is-small">' +
					'<i class="fa fa-angle-down" aria-hidden="true"></i>' +
					'</span>'
			);
		}

		return button;
	}
});


return DataTable;
}));
