/* SEMRS SEO sidebar for the block editor: score in the toolbar, then General (snippet editor,
 * focus keywords, checks), Advanced (robots, canonical, breadcrumb), Schema and Social tabs.
 * Fields are post meta saved with the post; the checks run on the server (semrs_seo_analyze). */
(function (wp, cfg) {
	'use strict';
	if (!wp || !wp.plugins || !cfg) { return; }
	var el = wp.element.createElement, useState = wp.element.useState, useEffect = wp.element.useEffect, useRef = wp.element.useRef, Fragment = wp.element.Fragment;
	var C = wp.components, __ = wp.i18n.__;
	var editorPkg = wp.editor && wp.editor.PluginSidebar ? wp.editor : wp.editPost;
	var PluginSidebar = editorPkg.PluginSidebar, PluginSidebarMoreMenuItem = editorPkg.PluginSidebarMoreMenuItem;
	var useSelect = wp.data.useSelect, useDispatch = wp.data.useDispatch;
	var MediaUpload = wp.blockEditor && wp.blockEditor.MediaUpload;

	var LIMITS = { title: 60, titlePx: 580, slug: 75, desc: 160, descPx: 920 };
	var VARS = ['%title%', '%sep%', '%sitename%', '%sitedesc%', '%excerpt%', '%focuskw%', '%currentyear%', '%category%'];
	var canvas = document.createElement('canvas').getContext('2d');
	function px(text, font) { canvas.font = font; return Math.round(canvas.measureText(text || '').width); }
	function plain(html) { var d = document.createElement('div'); d.innerHTML = html || ''; return (d.textContent || '').replace(/\s+/g, ' ').trim(); }

	function useMeta() {
		var meta = useSelect(function (s) { return s('core/editor').getEditedPostAttribute('meta') || {}; }, []);
		var editPost = useDispatch('core/editor').editPost;
		return [function (k) { var v = meta['_semrs_' + k]; return v === undefined || v === null ? '' : String(v); }, function (k, v) { var m = {}; m['_semrs_' + k] = v; editPost({ meta: m }); }];
	}
	function usePost() {
		return useSelect(function (s) {
			var e = s('core/editor');
			return { title: e.getEditedPostAttribute('title') || '', slug: e.getEditedPostAttribute('slug') || '', excerpt: e.getEditedPostAttribute('excerpt') || '', content: e.getEditedPostContent ? e.getEditedPostContent() : '', thumb: !!e.getEditedPostAttribute('featured_media'), link: e.getPermalink ? e.getPermalink() : '' };
		}, []);
	}
	function fill(tpl, post, get) {
		var excerpt = post.excerpt ? plain(post.excerpt) : plain(post.content).slice(0, 155);
		var map = { '%title%': post.title, '%seo_title%': post.title, '%sep%': cfg.sep, '%sitename%': cfg.site, '%site%': cfg.site, '%sitedesc%': cfg.tagline, '%tagline%': cfg.tagline, '%excerpt%': excerpt, '%focuskw%': get('focus') || cfg.phFocus, '%currentyear%': String(new Date().getFullYear()), '%category%': cfg.category, '%primary_category%': cfg.category, '%page%': '' };
		return String(tpl || '').replace(/%[a-z_]+%/gi, function (m) { return map[m] !== undefined ? map[m] : ''; }).replace(/\s{2,}/g, ' ').trim();
	}
	function ProBadge() { return cfg.pro ? null : el('span', { className: 'semrs-sb-pro', title: __('Unlock with SEMRS Pro', 'semrs-core') }, 'PRO'); }
	function ProNote() { return cfg.pro ? null : el('p', { className: 'semrs-sb-pronote' }, el('span', { className: 'dashicons dashicons-lock' }), ' ', __('These items unlock with SEMRS Pro.', 'semrs-core'), ' ', el('a', { href: cfg.proUrl, target: '_blank', rel: 'noopener' }, __('Get Pro', 'semrs-core'))); }

	// "AI" suggestions (SEMRS Pro AI SEO module, the site owner's own provider key).
	function AiSuggest(props) {
		var st = useState({ busy: false, list: null, msg: '' }), s = st[0], set = st[1];
		var ai = cfg.ai, post = props.post;
		if (!cfg.pro || !ai) {
			return el('div', { className: 'semrs-sb-ai' }, el(C.Button, { size: 'small', variant: 'secondary', disabled: true, icon: 'superhero-alt' }, __('Write with AI', 'semrs-core')), el(ProBadge));
		}
		function run() {
			if (!ai.ready) { set({ busy: false, list: null, msg: ai.message }); return; }
			set({ busy: true, list: null, msg: '' });
			var f = new FormData();
			f.append('action', 'semrs_ai_suggest'); f.append('nonce', ai.nonce); f.append('post_id', cfg.postId); f.append('field', props.field);
			f.append('post_title', post.title); f.append('content', post.content); f.append('focus', props.get('focus') || cfg.phFocus);
			fetch(cfg.ajax, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				set(r && r.success ? { busy: false, list: r.data.suggestions, msg: r.data.suggestions.length ? '' : __('No suggestions.', 'semrs-core') } : { busy: false, list: null, msg: (r && r.data && r.data.message) || __('The AI request failed.', 'semrs-core') });
			}).catch(function () { set({ busy: false, list: null, msg: __('The AI request failed.', 'semrs-core') }); });
		}
		return el('div', { className: 'semrs-sb-ai' },
			el(C.Button, { size: 'small', variant: 'secondary', icon: 'superhero-alt', isBusy: s.busy, disabled: s.busy, onClick: run }, s.busy ? __('Writing…', 'semrs-core') : __('Write with AI', 'semrs-core')),
			s.msg ? el('p', { className: 'semrs-sb-help' }, s.msg, !ai.ready ? el(Fragment, null, ' ', el('a', { href: ai.settings }, __('AI settings', 'semrs-core'))) : null) : null,
			s.list && s.list.length ? el('ul', { className: 'semrs-sb-ai__list' }, s.list.map(function (t, i) {
				return el('li', { key: i }, el('button', { type: 'button', onClick: function () { props.onPick(t); set({ busy: false, list: null, msg: '' }); } }, t), el('span', null, t.length + ' ' + __('chars', 'semrs-core')));
			})) : null);
	}

	function Meter(props) {
		var pct = Math.min(100, Math.round(props.value / props.max * 100));
		var cls = props.value === 0 ? '' : (props.value > props.max ? 'bad' : (pct >= 50 ? 'good' : 'ok'));
		return el('div', { className: 'semrs-sb-meter' },
			el('span', { className: 'semrs-sb-meter__text ' + cls }, props.label),
			el('span', { className: 'semrs-sb-meter__bar' }, el('i', { className: cls, style: { width: pct + '%' } })));
	}
	function Serp(props) {
		var url = (cfg.home.replace(/\/$/, '') + '/' + (props.slug ? props.slug + '/' : '')).replace(/^https?:\/\//, '');
		return el('div', { className: 'semrs-sb-serp' + (props.mobile ? ' is-mobile' : '') },
			el('div', { className: 'semrs-sb-serp__url' }, url),
			el('div', { className: 'semrs-sb-serp__title' }, props.title.length > 60 ? props.title.slice(0, 57) + '…' : props.title),
			el('div', { className: 'semrs-sb-serp__desc' }, props.desc.length > 160 ? props.desc.slice(0, 157) + '…' : props.desc));
	}
	function VarPicker(props) {
		return el(C.Dropdown, {
			popoverProps: { placement: 'bottom-end' },
			renderToggle: function (t) { return el(C.Button, { icon: 'arrow-down-alt2', label: __('Insert variable', 'semrs-core'), onClick: t.onToggle, 'aria-expanded': t.isOpen, size: 'small' }); },
			renderContent: function (t) { return el(C.MenuGroup, { label: __('Variables', 'semrs-core') }, VARS.map(function (v) { return el(C.MenuItem, { key: v, onClick: function () { props.onPick(v); t.onClose(); } }, v); })); }
		});
	}

	function SnippetEditor(props) {
		var get = props.get, set = props.set, post = props.post;
		var editPost = useDispatch('core/editor').editPost;
		var mobile = useState(false);
		var tRaw = get('title'), dRaw = get('description');
		var title = fill(tRaw || cfg.phTitle || cfg.tpl, post, get), desc = fill(dRaw || cfg.phDesc || '%excerpt%', post, get);
		return el(C.Modal, { title: __('Preview Snippet Editor', 'semrs-core'), onRequestClose: props.onClose, className: 'semrs-sb-modal' },
			el('div', { className: 'semrs-sb-modal__preview' },
				el(C.ButtonGroup, null,
					el(C.Button, { isPressed: !mobile[0], onClick: function () { mobile[1](false); } }, __('Desktop', 'semrs-core')),
					el(C.Button, { isPressed: mobile[0], onClick: function () { mobile[1](true); } }, __('Mobile', 'semrs-core'))),
				el(Serp, { title: title, desc: desc, slug: post.slug, mobile: mobile[0] })),
			el('div', { className: 'semrs-sb-field' },
				el('div', { className: 'semrs-sb-field__head' }, el('strong', null, __('Title', 'semrs-core')),
					el(Meter, { value: title.length, max: LIMITS.title, label: title.length + ' / ' + LIMITS.title + ' (' + px(title, '20px Arial') + 'px / ' + LIMITS.titlePx + 'px)' })),
				el('div', { className: 'semrs-sb-row' },
					el(C.TextControl, { __nextHasNoMarginBottom: true, value: tRaw, placeholder: cfg.phTitle || cfg.tpl, onChange: function (v) { set('title', v); } }),
					el(VarPicker, { onPick: function (v) { set('title', ((tRaw || cfg.tpl) + ' ' + v).trim()); } })),
				el('p', { className: 'semrs-sb-help' }, __('This is what will appear in the first line when this post shows up in the search results. Use the focus keyword at the beginning.', 'semrs-core')),
				el(AiSuggest, { field: 'title', post: post, get: get, onPick: function (v) { set('title', v); } })),
			el('div', { className: 'semrs-sb-field' },
				el('div', { className: 'semrs-sb-field__head' }, el('strong', null, __('Permalink', 'semrs-core')),
					el(Meter, { value: post.slug.length, max: LIMITS.slug, label: post.slug.length + ' / ' + LIMITS.slug })),
				el(C.TextControl, { __nextHasNoMarginBottom: true, value: post.slug, onChange: function (v) { editPost({ slug: v.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/-{2,}/g, '-') }); } }),
				el('p', { className: 'semrs-sb-help' }, __('This is the unique URL of this page, displayed below the post title in the search results.', 'semrs-core'))),
			el('div', { className: 'semrs-sb-field' },
				el('div', { className: 'semrs-sb-field__head' }, el('strong', null, __('Description', 'semrs-core')),
					el(Meter, { value: desc.length, max: LIMITS.desc, label: desc.length + ' / ' + LIMITS.desc + ' (' + px(desc, '14px Arial') + 'px / ' + LIMITS.descPx + 'px)' })),
				el('div', { className: 'semrs-sb-row' },
					el(C.TextareaControl, { __nextHasNoMarginBottom: true, rows: 3, value: dRaw, placeholder: cfg.phDesc || '%excerpt%', onChange: function (v) { set('description', v); } }),
					el(VarPicker, { onPick: function (v) { set('description', ((dRaw || '') + ' ' + v).trim()); } })),
				el('p', { className: 'semrs-sb-help' }, __('This is what will appear as the description when this post shows up in the search results. Use the focus keyword at the beginning.', 'semrs-core')),
				el(AiSuggest, { field: 'description', post: post, get: get, onPick: function (v) { set('description', v); } })));
	}

	function Checks(props) {
		var r = props.result;
		if (!r) { return el('p', { className: 'semrs-sb-help' }, el(C.Spinner), ' ', __('Checking…', 'semrs-core')); }
		return el(Fragment, null, Object.keys(r.groups).map(function (g) {
			var items = r.checks.filter(function (c) { return c.group === g; });
			if (!items.length) { return null; }
			var fails = items.filter(function (c) { return !c.ok; }).length;
			return el(C.PanelBody, { key: g, initialOpen: fails > 0, title: el('span', { className: 'semrs-sb-group' }, r.groups[g], el('span', { className: 'semrs-sb-badge ' + (fails ? 'bad' : 'good') }, fails ? '× ' + fails + ' ' + (fails === 1 ? __('Error', 'semrs-core') : __('Errors', 'semrs-core')) : '✓ ' + __('All good', 'semrs-core'))) },
				el('ul', { className: 'semrs-sb-checks' }, items.map(function (c, i) {
					return el('li', { key: i, className: c.ok ? 'ok' : 'no' }, c.label, !c.ok && c.fix && props.onFix ? el('button', { type: 'button', className: 'semrs-sb-fix', onClick: function () { props.onFix(c.fix); } }, '✦ ' + __('Fix with AI', 'semrs-core')) : null);
				})));
		}));
	}

	function GeneralTab(props) {
		var get = props.get, set = props.set, post = props.post;
		var open = useState(false);
		var title = fill(get('title') || cfg.phTitle || cfg.tpl, post, get), desc = fill(get('description') || cfg.phDesc || '%excerpt%', post, get);
		return el(Fragment, null,
			el(C.PanelBody, { title: __('Preview', 'semrs-core'), initialOpen: true },
				el(Serp, { title: title, desc: desc, slug: post.slug }),
				el(C.Button, { variant: 'primary', onClick: function () { open[1](true); }, style: { marginTop: '8px' } }, __('Edit Snippet', 'semrs-core')),
				open[0] && el(SnippetEditor, { get: get, set: set, post: post, onClose: function () { open[1](false); } })),
			el(C.PanelBody, { title: __('Focus Keyword', 'semrs-core'), initialOpen: true },
				el(C.TextControl, { __nextHasNoMarginBottom: true, label: __('Focus keyword', 'semrs-core'), hideLabelFromVision: true, value: get('focus'), placeholder: cfg.phFocus || __('Example: SEO for small business', 'semrs-core'), onChange: function (v) { set('focus', v); } }),
				el(AiSuggest, { field: 'focus', post: post, get: get, onPick: function (v) { set('focus', v); } }),
				el('div', { className: 'semrs-sb-sub' }, __('More focus keywords (comma separated, up to 4)', 'semrs-core'), el(ProBadge)),
				el(C.TextControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, value: get('focus_more'), onChange: function (v) { set('focus_more', v); } }),
				el(C.CheckboxControl, { __nextHasNoMarginBottom: true, label: __('This post is Pillar Content', 'semrs-core'), checked: get('pillar') === '1', onChange: function (v) { set('pillar', v ? '1' : '0'); } })),
			el('div', { className: 'semrs-sb-score' }, el('span', { className: 'semrs-sb-pill ' + props.cls }, props.result ? props.result.score + ' / 100' : '… / 100'), el(C.Button, { variant: 'link', onClick: props.recheck }, __('Re-check now', 'semrs-core'))),
			el(Checks, { result: props.result, onFix: function (field) {
				if ('focus' === field) { var inp = document.querySelector('.semrs-sb input[placeholder]'); if (inp) { inp.scrollIntoView({ block: 'center' }); inp.focus(); } return; }
				open[1](true); // The snippet editor has Write with AI for the title and description.
			} }),
			props.result && props.result.links && props.result.links.length ? el(C.PanelBody, { title: __('Internal link ideas', 'semrs-core'), initialOpen: false },
				el('ul', { className: 'semrs-sb-links' }, props.result.links.map(function (l, i) { return el('li', { key: i }, el('a', { href: l.url, target: '_blank', rel: 'noopener' }, l.title), el(C.Button, { size: 'small', variant: 'secondary', onClick: function () { navigator.clipboard && navigator.clipboard.writeText(l.url); } }, __('Copy', 'semrs-core'))); }))) : null);
	}

	function AdvancedTab(props) {
		var get = props.get, set = props.set;
		var flag = function (k, label) { return el(C.CheckboxControl, { key: k, __nextHasNoMarginBottom: true, label: label, checked: get(k) === '1' || (get(k) === '' && cfg.ph[k]), onChange: function (v) { set(k, v ? '1' : '0'); } }); };
		return el(Fragment, null,
			el(C.PanelBody, { title: __('Robots Meta', 'semrs-core'), initialOpen: true },
				flag('noindex', __('No Index — hide from Google (also leaves the sitemap and turns ads off)', 'semrs-core')),
				flag('nofollow', __('No Follow — don\'t pass authority through this page\'s links', 'semrs-core')),
				flag('noarchive', __('No Archive — no cached copy in search results', 'semrs-core')),
				flag('nosnippet', __('No Snippet — no text preview in search results', 'semrs-core')),
				flag('noimageindex', __('No Image Index — images on this page aren\'t indexed', 'semrs-core'))),
			el(C.PanelBody, { title: el('span', null, __('Advanced Robots Meta', 'semrs-core'), el(ProBadge)), initialOpen: true },
				el(C.TextControl, { __nextHasNoMarginBottom: true, type: 'number', min: -1, disabled: !cfg.pro, label: __('Max snippet (characters, -1 = no limit)', 'semrs-core'), value: get('max_snippet'), onChange: function (v) { set('max_snippet', v); } }),
				el(C.TextControl, { __nextHasNoMarginBottom: true, type: 'number', min: -1, disabled: !cfg.pro, label: __('Max video preview (seconds, -1 = no limit)', 'semrs-core'), value: get('max_video'), onChange: function (v) { set('max_video', v); } }),
				el(C.SelectControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, label: __('Max image preview', 'semrs-core'), value: get('max_image'), options: [{ label: __('Large (default)', 'semrs-core'), value: '' }, { label: __('Standard', 'semrs-core'), value: 'standard' }, { label: __('None', 'semrs-core'), value: 'none' }], onChange: function (v) { set('max_image', v); } }),
				el(ProNote)),
			el(C.PanelBody, { title: __('Canonical URL', 'semrs-core'), initialOpen: true },
				el(C.TextControl, { __nextHasNoMarginBottom: true, type: 'url', value: get('canonical'), placeholder: props.post.link, help: __('Leave empty to use this page\'s own address.', 'semrs-core'), onChange: function (v) { set('canonical', v); } })),
			el(C.PanelBody, { title: __('Breadcrumbs', 'semrs-core'), initialOpen: true },
				el(C.TextControl, { __nextHasNoMarginBottom: true, label: __('Breadcrumb title', 'semrs-core'), value: get('breadcrumb_title'), placeholder: props.post.title, help: __('Shorter name for this page in the breadcrumb trail.', 'semrs-core'), onChange: function (v) { set('breadcrumb_title', v); } })));
	}

	function SchemaTab(props) {
		var get = props.get, set = props.set, link = props.post.link;
		return el(C.PanelBody, { title: __('Schema', 'semrs-core'), initialOpen: true },
			el(C.SelectControl, { __nextHasNoMarginBottom: true, label: __('Schema type', 'semrs-core'), value: get('schema'), options: cfg.schemaTypes, onChange: function (v) { set('schema', v); } }),
			el('p', { className: 'semrs-sb-help' }, __('Organization, WebSite, WebPage, BreadcrumbList and Article schema are added automatically. FAQ schema is built from FAQ/Details blocks.', 'semrs-core')),
			el(C.CheckboxControl, { __nextHasNoMarginBottom: true, label: __('Don\'t build FAQ schema from Details blocks', 'semrs-core'), checked: get('faq_off') === '1', onChange: function (v) { set('faq_off', v ? '1' : ''); } }),
			link ? el('p', null,
				el(C.Button, { variant: 'secondary', href: 'https://search.google.com/test/rich-results?url=' + encodeURIComponent(link), target: '_blank' }, __('Test in Google Rich Results', 'semrs-core')), ' ',
				el(C.Button, { variant: 'link', href: 'https://validator.schema.org/#url=' + encodeURIComponent(link), target: '_blank' }, __('Schema validator', 'semrs-core'))) : null);
	}

	function ImageField(props) {
		return el('div', { className: 'semrs-sb-image' },
			el(C.TextControl, { __nextHasNoMarginBottom: true, type: 'url', label: props.label, value: props.value, disabled: props.disabled, placeholder: props.placeholder, onChange: props.onChange }),
			MediaUpload && !props.disabled ? el(MediaUpload, { allowedTypes: ['image'], onSelect: function (m) { props.onChange(m.url); }, render: function (o) { return el(C.Button, { variant: 'secondary', size: 'small', onClick: o.open }, __('Choose image', 'semrs-core')); } }) : null,
			props.value ? el('img', { src: props.value, alt: '' }) : null);
	}

	function SocialTab(props) {
		var get = props.get, set = props.set, post = props.post;
		var seoTitle = fill(get('title') || cfg.phTitle || cfg.tpl, post, get), seoDesc = fill(get('description') || cfg.phDesc || '%excerpt%', post, get);
		var custom = get('tw_custom') === '1';
		return el(Fragment, null,
			el(C.PanelBody, { title: __('Facebook', 'semrs-core'), initialOpen: true },
				el(C.TextControl, { __nextHasNoMarginBottom: true, label: __('Title', 'semrs-core'), value: get('og_title'), placeholder: seoTitle, onChange: function (v) { set('og_title', v); } }),
				el(AiSuggest, { field: 'og_title', post: post, get: get, onPick: function (v) { set('og_title', v); } }),
				el(C.TextareaControl, { __nextHasNoMarginBottom: true, label: __('Description', 'semrs-core'), rows: 3, value: get('og_description'), placeholder: seoDesc, onChange: function (v) { set('og_description', v); } }),
				el(AiSuggest, { field: 'og_description', post: post, get: get, onPick: function (v) { set('og_description', v); } }),
				el(ImageField, { label: __('Image (empty = featured image)', 'semrs-core'), value: get('og_image'), onChange: function (v) { set('og_image', v); } })),
			el(C.PanelBody, { title: el('span', null, __('X (Twitter)', 'semrs-core'), el(ProBadge)), initialOpen: true },
				el(C.ToggleControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, label: __('Use the Facebook details on X', 'semrs-core'), checked: !custom, onChange: function (v) { set('tw_custom', v ? '0' : '1'); } }),
				custom || !cfg.pro ? el(Fragment, null,
					el(C.SelectControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, label: __('Card type', 'semrs-core'), value: get('tw_card'), options: [{ label: __('Automatic', 'semrs-core'), value: '' }, { label: __('Summary with large image', 'semrs-core'), value: 'summary_large_image' }, { label: __('Summary', 'semrs-core'), value: 'summary' }], onChange: function (v) { set('tw_card', v); } }),
					el(C.TextControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, label: __('Title', 'semrs-core'), value: get('tw_title'), placeholder: get('og_title') || seoTitle, onChange: function (v) { set('tw_title', v); } }),
					el(C.TextareaControl, { __nextHasNoMarginBottom: true, disabled: !cfg.pro, label: __('Description', 'semrs-core'), rows: 3, value: get('tw_description'), placeholder: get('og_description') || seoDesc, onChange: function (v) { set('tw_description', v); } }),
					el(ImageField, { disabled: !cfg.pro, label: __('Image', 'semrs-core'), value: get('tw_image'), placeholder: get('og_image'), onChange: function (v) { set('tw_image', v); } })) : null,
				el(ProNote)));
	}

	function scoreClass(r) { return !r ? '' : (r.score >= 80 ? 'good' : (r.score >= 50 ? 'ok' : 'bad')); }

	function Plugin() {
		var m = useMeta(), get = m[0], set = m[1];
		var post = usePost();
		var res = useState(null), result = res[0], setResult = res[1];
		var timer = useRef(0);
		function run() {
			var f = new FormData();
			f.append('action', 'semrs_seo_analyze'); f.append('nonce', cfg.nonce); f.append('post_id', cfg.postId);
			f.append('content', post.content); f.append('post_title', post.title); f.append('slug', post.slug); f.append('thumb', post.thumb ? 1 : 0);
			f.append('focus', get('focus')); f.append('focus_more', get('focus_more')); f.append('title', get('title')); f.append('description', get('description')); f.append('pillar', get('pillar') === '1' ? 1 : 0);
			fetch(cfg.ajax, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) { if (r && r.success) { setResult(r.data); } }).catch(function () {});
		}
		useEffect(function () { clearTimeout(timer.current); timer.current = setTimeout(run, result ? 1500 : 200); return function () { clearTimeout(timer.current); }; },
			[post.content, post.title, post.slug, post.thumb, get('focus'), get('focus_more'), get('title'), get('description'), get('pillar')]);
		var cls = scoreClass(result);
		var icon = el('span', { className: 'semrs-sb-pill semrs-sb-pill--icon ' + cls }, (result ? result.score : '…') + ' / 100');
		var tab = function (t) {
			var p = { get: get, set: set, post: post, result: result, cls: cls, recheck: run };
			return t.name === 'advanced' ? el(AdvancedTab, p) : t.name === 'schema' ? el(SchemaTab, p) : t.name === 'social' ? el(SocialTab, p) : el(GeneralTab, p);
		};
		return el(Fragment, null,
			el(PluginSidebarMoreMenuItem, { target: 'semrs-seo', icon: 'chart-area' }, 'SEMRS SEO'),
			el(PluginSidebar, { name: 'semrs-seo', title: 'SEMRS SEO', icon: icon, className: 'semrs-sb' },
				el(C.TabPanel, { className: 'semrs-sb-tabs', tabs: [
					{ name: 'general', title: el(Fragment, null, el('span', { className: 'dashicons dashicons-admin-generic' }), ' ', __('General', 'semrs-core')) },
					{ name: 'advanced', title: el(Fragment, null, el('span', { className: 'dashicons dashicons-portfolio' }), ' ', __('Advanced', 'semrs-core')) },
					{ name: 'schema', title: el(Fragment, null, el('span', { className: 'dashicons dashicons-editor-code' }), ' ', __('Schema', 'semrs-core')) },
					{ name: 'social', title: el(Fragment, null, el('span', { className: 'dashicons dashicons-share' }), ' ', __('Social', 'semrs-core')) }
				] }, tab)));
	}

	wp.plugins.registerPlugin('semrs-seo', { render: Plugin });
})(window.wp, window.semrsSeoSidebar);
