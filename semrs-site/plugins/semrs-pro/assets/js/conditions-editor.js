/* SEMRS Pro: "Display conditions" panel on every block. Rules are applied
 * on the server when the page is built; the editor always shows the block. */
(function (wp, cfg) {
	'use strict';
	var el = wp.element.createElement, __ = wp.i18n.__;
	var be = wp.blockEditor, c = wp.components;
	var ATTRS = {
		semrsShowTo: { type: 'string', default: '' },
		semrsRoles: { type: 'array', default: [], items: { type: 'string' } },
		semrsFrom: { type: 'string', default: '' },
		semrsUntil: { type: 'string', default: '' },
		semrsPages: { type: 'array', default: [], items: { type: 'string' } }
	};

	wp.hooks.addFilter('blocks.registerBlockType', 'semrs-pro/conditions-attrs', function (settings) {
		settings.attributes = Object.assign({}, settings.attributes, ATTRS);
		return settings;
	});

	function checklist(label, options, value, onChange) {
		value = value || [];
		return el('fieldset', { className: 'semrs-cond-list' },
			el('legend', { className: 'semrs-ed-label' }, label),
			options.map(function (o) {
				return el(c.CheckboxControl, {
					key: o.value, label: o.label, checked: value.indexOf(o.value) > -1, __nextHasNoMarginBottom: true,
					onChange: function (on) { onChange(on ? value.concat([o.value]) : value.filter(function (v) { return v !== o.value; })); }
				});
			}));
	}

	wp.hooks.addFilter('editor.BlockEdit', 'semrs-pro/conditions-panel', wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			if (!props.isSelected) { return el(BlockEdit, props); }
			var a = props.attributes, set = props.setAttributes;
			var active = (a.semrsShowTo ? 1 : 0) + ((a.semrsRoles || []).length ? 1 : 0) + (a.semrsFrom || a.semrsUntil ? 1 : 0) + ((a.semrsPages || []).length ? 1 : 0);
			return el(wp.element.Fragment, null,
				el(BlockEdit, props),
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: __('Display conditions', 'semrs-pro') + (active ? ' (' + active + ')' : ''), initialOpen: false },
						el(c.SelectControl, {
							label: __('Show to', 'semrs-pro'), value: a.semrsShowTo || '', __nextHasNoMarginBottom: true,
							options: [{ value: '', label: __('Everyone', 'semrs-pro') }, { value: 'in', label: __('Logged-in visitors only', 'semrs-pro') }, { value: 'out', label: __('Logged-out visitors only', 'semrs-pro') }],
							onChange: function (v) { set({ semrsShowTo: v }); }
						}),
						checklist(__('Only these roles (none ticked = all)', 'semrs-pro'), cfg.roles || [], a.semrsRoles, function (v) { set({ semrsRoles: v }); }),
						el(c.TextControl, { label: __('Show from (site time)', 'semrs-pro'), type: 'datetime-local', value: a.semrsFrom || '', onChange: function (v) { set({ semrsFrom: v }); }, __nextHasNoMarginBottom: true }),
						el(c.TextControl, { label: __('Show until (site time)', 'semrs-pro'), type: 'datetime-local', value: a.semrsUntil || '', onChange: function (v) { set({ semrsUntil: v }); }, __nextHasNoMarginBottom: true }),
						checklist(__('Only on (none ticked = everywhere)', 'semrs-pro'), cfg.pages || [], a.semrsPages, function (v) { set({ semrsPages: v }); }),
						el('p', { className: 'semrs-ed-help' }, __('Using a page cache? Login, role and date rules are decided when the page is built, so exclude those pages from the cache.', 'semrs-pro')))));
		};
	}, 'withSemrsConditions'));
})(window.wp, window.SEMRS_COND || {});
