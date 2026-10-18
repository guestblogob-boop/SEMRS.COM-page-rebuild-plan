/* SEMRS Pro: "Scroll animation" setting in the Advanced panel of every block.
 * The class is added on the server when the page renders, so saved markup
 * never changes and blocks never show "invalid content". */
(function (wp) {
	'use strict';
	var el = wp.element.createElement, __ = wp.i18n.__;
	var InspectorAdvancedControls = wp.blockEditor.InspectorAdvancedControls;
	var SelectControl = wp.components.SelectControl;

	wp.hooks.addFilter('blocks.registerBlockType', 'semrs-pro/animation-attr', function (settings) {
		settings.attributes = Object.assign({}, settings.attributes, { semrsAnimation: { type: 'string', default: '' } });
		return settings;
	});

	wp.hooks.addFilter('editor.BlockEdit', 'semrs-pro/animation-control', wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			if (!props.isSelected) { return el(BlockEdit, props); }
			return el(wp.element.Fragment, null,
				el(BlockEdit, props),
				el(InspectorAdvancedControls, null,
					el(SelectControl, {
						label: __('Scroll animation (SEMRS Pro)', 'semrs-pro'),
						value: props.attributes.semrsAnimation || '',
						options: [
							{ value: '', label: __('None', 'semrs-pro') },
							{ value: 'fade-up', label: __('Fade up', 'semrs-pro') },
							{ value: 'fade-in', label: __('Fade in', 'semrs-pro') },
							{ value: 'zoom-in', label: __('Zoom in', 'semrs-pro') },
							{ value: 'slide-left', label: __('Slide in from right', 'semrs-pro') },
							{ value: 'slide-right', label: __('Slide in from left', 'semrs-pro') }
						],
						help: __('Plays once when the block scrolls into view. Skipped for visitors who prefer reduced motion.', 'semrs-pro'),
						onChange: function (v) { props.setAttributes({ semrsAnimation: v }); }
					})
				)
			);
		};
	}, 'withSemrsAnimation'));
})(window.wp);
