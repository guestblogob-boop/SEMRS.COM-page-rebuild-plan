/* SEMRS Blocks: "Visibility" panel on every block (hide on phone / tablet /
 * desktop). Hidden blocks are dimmed in the editor so they stay editable. */
(function (wp, cfg) {
	'use strict';
	var el = wp.element.createElement, __ = wp.i18n.__;
	var be = wp.blockEditor, c = wp.components;
	var DEVICES = [['Mobile', __('Hide on phones', 'semrs-blocks')], ['Tablet', __('Hide on tablets', 'semrs-blocks')], ['Desktop', __('Hide on desktops', 'semrs-blocks')]];

	wp.hooks.addFilter('blocks.registerBlockType', 'semrs-blocks/visibility-attrs', function (settings) {
		var extra = {};
		DEVICES.forEach(function (d) { extra['semrsHide' + d[0]] = { type: 'boolean', default: false }; });
		settings.attributes = Object.assign({}, settings.attributes, extra);
		return settings;
	});

	wp.hooks.addFilter('editor.BlockEdit', 'semrs-blocks/visibility-panel', wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			if (!props.isSelected) { return el(BlockEdit, props); }
			var a = props.attributes;
			var on = DEVICES.filter(function (d) { return a['semrsHide' + d[0]]; }).length;
			return el(wp.element.Fragment, null,
				el(BlockEdit, props),
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: __('Visibility', 'semrs-blocks') + (on ? ' (' + on + ')' : ''), initialOpen: false, className: 'semrs-ed-visibility' },
						DEVICES.map(function (d) {
							return el(c.ToggleControl, {
								key: d[0], label: d[1], checked: !!a['semrsHide' + d[0]], __nextHasNoMarginBottom: true,
								onChange: function (v) { var o = {}; o['semrsHide' + d[0]] = v; props.setAttributes(o); }
							});
						}),
						el('p', { className: 'semrs-ed-help' }, __('Phones: under 782 px. Tablets: 782–1023 px. Desktops: 1024 px and wider.', 'semrs-blocks')),
						cfg && !cfg.pro ? el('p', { className: 'semrs-ed-help' },
							__('Show only to logged-in visitors, certain roles, between dates or on certain pages with ', 'semrs-blocks'),
							el('a', { href: cfg.proUrl, target: '_blank', rel: 'noopener' }, 'SEMRS Pro'), '.') : null)));
		};
	}, 'withSemrsVisibility'));

	// Dim hidden blocks in the editor.
	wp.hooks.addFilter('editor.BlockListBlock', 'semrs-blocks/visibility-dim', wp.compose.createHigherOrderComponent(function (BlockListBlock) {
		return function (props) {
			var a = props.attributes || {};
			var cls = DEVICES.filter(function (d) { return a['semrsHide' + d[0]]; }).map(function (d) { return 'semrs-ed-hide-' + d[0].toLowerCase(); }).join(' ');
			return cls ? el(BlockListBlock, Object.assign({}, props, { className: [props.className, 'semrs-ed-has-hide', cls].filter(Boolean).join(' ') })) : el(BlockListBlock, props);
		};
	}, 'withSemrsVisibilityDim'));
})(window.wp, window.SEMRS_EXT);
