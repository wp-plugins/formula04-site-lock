/**
 * plugin.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

/*global tinymce:true */

(function() {
	tinymce.PluginManager.add('my_mce_button', function( editor, url ) {
		editor.addButton('my_mce_button', {
			text: 'Formula04 Site Lock FORM',
			tooltip: 'Formula04 Site Lock Password Form',
			icon: false,
			onclick: function() {
				editor.insertContent('[f04sitelockform]');
			}
		});
	});
})();