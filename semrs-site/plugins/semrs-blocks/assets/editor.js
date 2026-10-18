/* SEMRS Blocks editor: builds the sidebar controls and a live server-rendered
 * preview for every block registered in PHP. No build step; uses WordPress globals. */
(function (wp, cfg) {
	'use strict';
	if (!wp || !cfg) { return; }
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;
	var __ = wp.i18n.__;

	function iconSvg(name, size) {
		var inner = cfg.iconSvgs[name] || cfg.iconSvgs.star;
		return el('span', {
			className: 'semrs-ed-icon',
			dangerouslySetInnerHTML: { __html: '<svg width="' + (size || 20) + '" height="' + (size || 20) + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg>' }
		});
	}

	function IconPicker(props) {
		var st = wp.element.useState('');
		var q = st[0], setQ = st[1];
		var names = cfg.icons.filter(function (n) { return !q || n.indexOf(q.toLowerCase()) > -1; });
		return el('div', { className: 'semrs-ed-iconpicker' },
			el('p', { className: 'semrs-ed-label' }, props.label),
			el(c.TextControl, { placeholder: __('Search icons…', 'semrs-blocks'), value: q, onChange: setQ, __nextHasNoMarginBottom: true }),
			el('div', { className: 'semrs-ed-icongrid', role: 'listbox' },
				names.map(function (n) {
					return el('button', {
						key: n, type: 'button', title: n, 'aria-label': n, 'aria-selected': props.value === n,
						className: 'semrs-ed-iconbtn' + (props.value === n ? ' is-selected' : ''),
						onClick: function () { props.onChange(n); }
					}, iconSvg(n, 20));
				})));
	}

	function MediaControl(props) {
		var MU = be.MediaUpload, MUC = be.MediaUploadCheck;
		var media = wp.data.useSelect(function (s) { return props.value ? s('core').getMedia(props.value) : null; }, [props.value]);
		return el('div', { className: 'semrs-ed-media' },
			el('p', { className: 'semrs-ed-label' }, props.label),
			media && media.source_url ? el('img', { src: (media.media_details && media.media_details.sizes && media.media_details.sizes.thumbnail ? media.media_details.sizes.thumbnail.source_url : media.source_url), alt: '' }) : null,
			el(MUC, null, el(MU, {
				onSelect: function (m) { props.onChange(m.id); }, allowedTypes: ['image'], value: props.value,
				render: function (o) { return el(c.Button, { variant: 'secondary', onClick: o.open }, props.value ? __('Replace image', 'semrs-blocks') : __('Choose image', 'semrs-blocks')); }
			})),
			props.value ? el(c.Button, { variant: 'link', isDestructive: true, onClick: function () { props.onChange(0); } }, __('Remove', 'semrs-blocks')) : null);
	}

	function GalleryControl(props) {
		var MU = be.MediaUpload, MUC = be.MediaUploadCheck;
		var ids = props.value || [];
		return el('div', { className: 'semrs-ed-media' },
			el('p', { className: 'semrs-ed-label' }, props.label + ' (' + ids.length + ')'),
			el(MUC, null, el(MU, {
				onSelect: function (items) { props.onChange(items.map(function (m) { return m.id; })); }, allowedTypes: ['image'], multiple: true, gallery: true, value: ids,
				render: function (o) { return el(c.Button, { variant: 'secondary', onClick: o.open }, ids.length ? __('Edit images', 'semrs-blocks') : __('Choose images', 'semrs-blocks')); }
			})));
	}

	function control(key, spec, attrs, set) {
		var val = attrs[key];
		var on = function (v) { var o = {}; o[key] = v; set(o); };
		var common = { key: key, label: spec.label, help: spec.help, __nextHasNoMarginBottom: true };
		switch (spec.type) {
			case 'textarea': return el(c.TextareaControl, Object.assign(common, { value: val || '', onChange: on, rows: 4 }));
			case 'number': return el(c.TextControl, Object.assign(common, { type: 'number', value: val, onChange: function (v) { on(parseFloat(v) || 0); } }));
			case 'range': return el(c.RangeControl, Object.assign(common, { value: val, min: spec.min || 0, max: spec.max || 100, onChange: on }));
			case 'toggle': return el(c.ToggleControl, Object.assign(common, { checked: !!val, onChange: on }));
			case 'select':
				return el(c.SelectControl, Object.assign(common, { value: val, onChange: on, options: Object.keys(spec.options).map(function (k) { return { value: k, label: spec.options[k] }; }) }));
			case 'category': return el(c.SelectControl, Object.assign(common, { value: val, onChange: on, options: cfg.categories }));
			case 'posttype': return el(c.SelectControl, Object.assign(common, { value: val, onChange: on, options: cfg.postTypes }));
			case 'color':
				return el('div', { key: key, className: 'semrs-ed-color' },
					el('p', { className: 'semrs-ed-label' }, spec.label),
					el(c.ColorPalette, { value: val || undefined, onChange: function (v) { on(v || ''); }, clearable: true }));
			case 'datetime':
				return el('div', { key: key, className: 'semrs-ed-date' },
					el('p', { className: 'semrs-ed-label' }, spec.label),
					el(c.DateTimePicker, { currentDate: val || undefined, onChange: on, is12Hour: false }));
			case 'icon': return el(IconPicker, { key: key, label: spec.label, value: val, onChange: on });
			case 'media': return el(MediaControl, { key: key, label: spec.label, value: val, onChange: on });
			case 'gallery': return el(GalleryControl, { key: key, label: spec.label, value: val, onChange: on });
			case 'url': return el(c.TextControl, Object.assign(common, { type: 'url', value: val || '', onChange: on, placeholder: 'https://' }));
			default: return el(c.TextControl, Object.assign(common, { value: val || '', onChange: on }));
		}
	}

	function inspector(def, props) {
		var keys = Object.keys(def.controls);
		var panels = def.panels && Object.keys(def.panels).length ? def.panels : null;
		var used = {};
		var panelEls = [];
		if (panels) {
			Object.keys(panels).forEach(function (title, i) {
				panelEls.push(el(c.PanelBody, { key: title, title: title, initialOpen: i === 0 },
					panels[title].map(function (k) { used[k] = true; return def.controls[k] ? control(k, def.controls[k], props.attributes, props.setAttributes) : null; })));
			});
		}
		var rest = keys.filter(function (k) { return !used[k]; });
		if (rest.length) {
			panelEls.unshift(el(c.PanelBody, { key: '_main', title: __('Settings', 'semrs-blocks'), initialOpen: true },
				rest.map(function (k) { return control(k, def.controls[k], props.attributes, props.setAttributes); })));
		}
		return el(be.InspectorControls, null, panelEls);
	}

	// Container look in the editor (mirrors semrs_blocks_render_container).
	var GRADIENTS = { navy: 'linear-gradient(135deg,#0b1f3a,#1e3a66)', sunrise: 'linear-gradient(135deg,#fbf4e0,#f7d9a8)', ocean: 'linear-gradient(135deg,#0b4f6c,#20a4c8)', mint: 'linear-gradient(135deg,#e6f6ef,#c9eedd)', soft: 'linear-gradient(180deg,#f6f7f9,#ffffff)' };
	function useImageUrl(id) {
		return wp.data.useSelect(function (sel) { var m = id ? sel('core').getMedia(id) : null; return m ? m.source_url : ''; }, [id]);
	}
	function containerProps(a, img) {
		var layers = [];
		if (img) { layers.push('url(' + img + ') center/cover no-repeat'); }
		if (GRADIENTS[a.gradient]) { layers.push(GRADIENTS[a.gradient]); }
		var st = { '--c-gap': (a.gap || 0) + 'px', '--c-cols': a.columns || 3, '--c-cols-t': a.columnsTablet || 2 };
		if (a.padTop) { st.paddingTop = a.padTop + 'px'; }
		if (a.padBottom) { st.paddingBottom = a.padBottom + 'px'; }
		if (a.padSides) { st['--c-pad-x'] = a.padSides + 'px'; }
		if (a.minHeight) { st.minHeight = a.minHeight + 'px'; }
		if (a.radius) { st.borderRadius = a.radius + 'px'; }
		if (layers.length) { st.background = layers.join(',') + (a.bg ? ',' + a.bg : ''); } else if (a.bg) { st.backgroundColor = a.bg; }
		if (a.textColor) { st.color = a.textColor; }
		var cls = ['semrs-container', 'semrs-container--' + (a.layout || 'stack'), 'semrs-container--w-' + (a.width || 'container'), 'semrs-container--v-' + (a.valign || 'stretch'), 'semrs-container--h-' + (a.justify || 'start')];
		if (img && a.overlay) { cls.push('has-overlay'); }
		if (a.textColor) { cls.push('semrs-has-color'); }
		return { className: cls.join(' '), style: st };
	}

	function innerEdit(name, def) {
		return function (props) {
			var a = props.attributes, set = props.setAttributes;
			var insp = inspector(def, props);
			if (name === 'semrs/container') {
				var img = useImageUrl(a.image);
				var cp = containerProps(a, img);
				var bp = be.useBlockProps({ className: 'semrs-block semrs-block--container' });
				var ib = be.useInnerBlocksProps({ className: 'semrs-container__inner' }, { orientation: a.layout === 'row' ? 'horizontal' : 'vertical', renderAppender: be.InnerBlocks.ButtonBlockAppender });
				return el(Fragment, null, insp, el('div', bp,
					el('div', cp,
						img && a.overlay ? el('span', { className: 'semrs-container__overlay', style: { background: a.overlay, opacity: (a.overlayOpacity || 0) / 100 } }) : null,
						el('div', ib))));
			}
			if (name === 'semrs/tab' || name === 'semrs/accordion-item') {
				var cbp = be.useBlockProps({ className: 'semrs-ed-child ' + (name === 'semrs/tab' ? 'semrs-ed-tab' : 'semrs-ed-acc') });
				var cib = be.useInnerBlocksProps({ className: 'semrs-ed-child__body' }, { template: [['core/paragraph', { placeholder: __('Write the content…', 'semrs-blocks') }]] });
				return el(Fragment, null, insp, el('div', cbp,
					el('div', { className: 'semrs-ed-child__head' },
						el('span', { className: 'semrs-ed-child__label' }, name === 'semrs/tab' ? __('Tab', 'semrs-blocks') : __('Question', 'semrs-blocks')),
						el(be.PlainText, { value: a.title || '', onChange: function (v) { set({ title: v }); }, placeholder: name === 'semrs/tab' ? __('Tab title', 'semrs-blocks') : __('Question?', 'semrs-blocks'), className: 'semrs-ed-child__title', 'aria-label': __('Title', 'semrs-blocks') })),
					el('div', cib)));
			}
			// Tabs and Accordion: a list of child items.
			var pbp = be.useBlockProps({ className: 'semrs-ed-parent semrs-ed-' + name.replace('semrs/', '') });
			var pib = be.useInnerBlocksProps(pbp, { allowedBlocks: def.allowed, template: def.template, renderAppender: be.InnerBlocks.ButtonBlockAppender });
			return el(Fragment, null, insp, el('div', pib));
		};
	}

	function currentPostId() {
		var ed = wp.data.select('core/editor');
		return (ed && ed.getCurrentPostId && ed.getCurrentPostId()) || 0;
	}

	Object.keys(cfg.blocks).forEach(function (name) {
		var def = cfg.blocks[name];
		var settings = {
			icon: def.icon,
			description: def.description || undefined,
			example: def.example ? { attributes: def.example } : undefined,
			edit: function (props) {
				var bp = be.useBlockProps();
				return el(Fragment, null,
					inspector(def, props),
					el('div', bp,
						el(SSR, {
							block: name, attributes: props.attributes, httpMethod: 'POST', urlQueryArgs: currentPostId() ? { post_id: currentPostId() } : {},
							EmptyResponsePlaceholder: function () { return el('p', { className: 'semrs-ed-empty' }, def.title + ': ' + __('adjust the settings in the sidebar.', 'semrs-blocks')); }
						})));
			},
			save: function () { return null; }
		};
		if (def.inner) {
			settings.edit = innerEdit(name, def);
			settings.save = function () { return el(be.InnerBlocks.Content); };
			if (def.parent) { settings.parent = def.parent; }
			if (def.allowed) { settings.allowedBlocks = def.allowed; }
		}
		wp.blocks.registerBlockType(name, settings);
	});

	// Locked Pro blocks (only when SEMRS Pro is not installed): visible in the
	// inserter with a PRO label; in the editor they explain what Pro adds.
	// They save nothing and print nothing on the site.
	Object.keys(cfg.teasers || {}).forEach(function (name) {
		var t = cfg.teasers[name];
		if (wp.blocks.getBlockType(name)) { return; }
		wp.blocks.registerBlockType(name, {
			apiVersion: 3,
			title: t.title + ' · PRO',
			description: t.description,
			category: 'semrs',
			icon: t.icon,
			keywords: ['pro'],
			supports: { html: false, reusable: false },
			edit: function () {
				return el('div', be.useBlockProps({ className: 'semrs-locked' }),
					el('span', { className: 'semrs-locked__badge' }, el(c.Dashicon, { icon: 'lock', size: 14 }), ' PRO'),
					el('strong', null, t.title),
					el('p', null, t.description),
					el('p', { className: 'semrs-locked__note' }, __('This block is part of SEMRS Pro. It shows nothing on your site until SEMRS Pro is active.', 'semrs-blocks')),
					el(c.Button, { variant: 'primary', href: cfg.proUrl, target: '_blank', rel: 'noopener' }, __('See SEMRS Pro', 'semrs-blocks')));
			},
			save: function () { return null; }
		});
	});
})(window.wp, window.SEMRS_BLOCKS);
