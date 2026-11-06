/* SEMRS Tools core: tiny helpers shared by every tool. No dependencies. */
(function () {
	'use strict';
	var registry = {};
	var cfg = window.SEMRS_TOOLS || {};

	/** Create an element: h('div', {class: 'x', onclick: fn}, child, 'text'). */
	function h(tag, attrs) {
		var el = document.createElement(tag);
		attrs = attrs || {};
		Object.keys(attrs).forEach(function (k) {
			var v = attrs[k];
			if (v === null || v === undefined || v === false) { return; }
			if (k.slice(0, 2) === 'on' && typeof v === 'function') { el.addEventListener(k.slice(2), v); }
			else if (k === 'class') { el.className = v; }
			else if (k === 'text') { el.textContent = v; }
			else if (k === 'value') { el.value = v; }
			else if (v === true) { el.setAttribute(k, ''); }
			else { el.setAttribute(k, v); }
		});
		for (var i = 2; i < arguments.length; i++) { append(el, arguments[i]); }
		return el;
	}
	function append(el, c) {
		if (c === null || c === undefined || c === false) { return; }
		if (Array.isArray(c)) { c.forEach(function (x) { append(el, x); }); return; }
		el.appendChild(typeof c === 'string' || typeof c === 'number' ? document.createTextNode(String(c)) : c);
	}

	var uid = 0;
	/** Labelled field wrapper around an input/select/textarea. */
	function field(label, input, hint) {
		if (!input.id) { input.id = 'semrs-f' + (++uid); }
		return h('div', { class: 'semrs-tool__field' },
			h('label', { for: input.id, text: label }),
			input,
			hint ? h('p', { class: 'semrs-tool__hint', text: hint }) : null);
	}

	var counted = {};
	/** GA4 event if gtag exists (and events are on); Pro usage report counts runs, copies and downloads. */
	function track(name, params) {
		params = params || {};
		if (cfg.track !== false) {
			try { if (typeof window.gtag === 'function') { window.gtag('event', name, params); } } catch (e) {}
		}
		var ev = { tool_used: 'use', tool_run: 'run', tool_copy: 'copy', tool_download: 'download', cta_click: 'cta' }[name];
		var key = params.tool_id + ':' + ev;
		if (cfg.usage && ev && params.tool_id && !counted[key] && navigator.sendBeacon) {
			counted[key] = 1; // Once per page view and action, so reloads of results don't inflate counts.
			try { navigator.sendBeacon(cfg.rest + 'usage?tool=' + encodeURIComponent(params.tool_id) + '&ev=' + ev); } catch (e) {}
		}
	}

	function flash(btn, text) {
		var old = btn.textContent;
		btn.textContent = text;
		setTimeout(function () { btn.textContent = old; }, 1500);
	}

	/** Copy-to-clipboard button. */
	function copyBtn(getText, label, toolId) {
		var b = h('button', { type: 'button', class: 'wp-element-button semrs-btn', text: label || 'Copy' });
		b.addEventListener('click', function () {
			var t = getText();
			var done = function () { flash(b, 'Copied ✓'); track('tool_copy', { tool_id: toolId }); };
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(t).then(done, function () { fallbackCopy(t); done(); });
			} else { fallbackCopy(t); done(); }
		});
		return b;
	}
	function fallbackCopy(t) {
		var ta = h('textarea', { style: 'position:fixed;top:-1000px;opacity:0' });
		ta.value = t;
		document.body.appendChild(ta);
		ta.select();
		try { document.execCommand('copy'); } catch (e) {}
		ta.remove();
	}

	/** Download button for text or a Blob. */
	function downloadBtn(getData, filename, mime, label, toolId) {
		var b = h('button', { type: 'button', class: 'wp-element-button semrs-btn semrs-btn--ghost', text: label || 'Download' });
		b.addEventListener('click', function () {
			var data = getData();
			var blob = data instanceof Blob ? data : new Blob([data], { type: mime || 'text/plain' });
			var a = h('a', { href: URL.createObjectURL(blob), download: typeof filename === 'function' ? filename() : filename });
			document.body.appendChild(a);
			a.click();
			setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
			track('tool_download', { tool_id: toolId });
		});
		return b;
	}

	/** Remember inputs per tool (per-visitor convenience only). */
	function store(id, data) { if (cfg.remember === false) { return; } try { localStorage.setItem('semrs-tool-' + id, JSON.stringify(data)); } catch (e) {} }
	function load(id) { if (cfg.remember === false) { return null; } try { return JSON.parse(localStorage.getItem('semrs-tool-' + id) || 'null'); } catch (e) { return null; } }

	var canvas;
	/** Rendered text width in px for a CSS font (e.g. '20px Arial'). */
	function textWidth(text, font) {
		canvas = canvas || document.createElement('canvas');
		var ctx = canvas.getContext('2d');
		ctx.font = font;
		return Math.round(ctx.measureText(text).width);
	}

	function debounce(fn, ms) {
		var t;
		return function () { var a = arguments, s = this; clearTimeout(t); t = setTimeout(function () { fn.apply(s, a); }, ms || 150); };
	}

	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

	/** Count badge: "n / max" coloured good / over. */
	function counter(n, max, unit, min) {
		var cls = 'semrs-tool__count' + (n > max ? ' is-over' : (n >= (min || 1) ? ' is-good' : ''));
		return h('span', { class: cls, text: n + ' / ' + max + (unit ? ' ' + unit : '') });
	}

	/** GET a plugin REST route; resolves {ok, j}. */
	function get(route, params) {
		var q = Object.keys(params || {}).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
		return fetch(cfg.rest + route + (q ? '?' + q : ''), { headers: { Accept: 'application/json' } })
			.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }, function () { return { ok: false, j: {} }; }); });
	}
	function stat(v, label) { return h('div', { class: 'semrs-tool__stat' }, h('b', { text: v }), h('span', { text: label })); }
	/** Checks list from [[state ok|warn|bad, text], …]. */
	function checks(list) { var ul = h('ul', { class: 'semrs-tool__checks' }); list.forEach(function (c) { ul.append(h('li', { class: c[0], text: c[1] })); }); return ul; }

	function mount(root) {
		var id = root.getAttribute('data-semrs-tool');
		if (root.__semrs || !registry[id]) { return; }
		root.__semrs = true;
		root.innerHTML = '';
		root.classList.add('semrs-tool');
		var used = false;
		root.addEventListener('input', function () { if (!used) { used = true; track('tool_used', { tool_id: id }); } }, true);
		var api = {
			id: id, cfg: cfg, h: h, field: field, track: track, copyBtn: copyBtn, downloadBtn: downloadBtn,
			store: store, load: load, textWidth: textWidth, debounce: debounce, esc: esc, counter: counter,
			get: get, stat: stat, checks: checks,
			panel: function (title) { return h('div', { class: 'semrs-tool__panel' }, title ? h('h2', { class: 'semrs-tool__title', text: title }) : null); }
		};
		registry[id](root, api);
		var label = root.getAttribute('data-service-label');
		var url = root.getAttribute('data-service-url');
		var text = root.getAttribute('data-service-text');
		if (url && (label || text)) {
			var go = function () { track('cta_click', { tool_id: id, destination: url }); };
			root.parentNode.insertBefore(
				h('div', { class: 'semrs-service-cta' },
					text
						? h('p', null, text + ' ', h('a', { href: url, onclick: go, text: (label || 'Learn more') + ' →' }))
						: h('p', null, h('strong', { text: 'Want an expert to handle this? ' }), 'SEMRS ' + label + ' ',
							h('a', { href: url, onclick: go, text: 'See how we can help →' }))),
				root.nextSibling);
		}
	}

	function mountAll() { document.querySelectorAll('[data-semrs-tool]').forEach(mount); }

	window.SemrsTools = {
		register: function (id, fn) { registry[id] = fn; mountAll(); }
	};
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', mountAll); } else { mountAll(); }
})();
