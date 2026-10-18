/* Image Compressor & WebP Converter: canvas re-encode in the browser. Files never leave the device. */
SemrsTools.register('image-compressor', function (root, api) {
	var h = api.h;
	var MAX_FILES = 20, MAX_MB = 25;
	root.classList.add('semrs-tool--wide');
	var s = api.load(api.id) || {};
	var webpOk = document.createElement('canvas').toDataURL('image/webp').indexOf('data:image/webp') === 0;
	var format = h('select', null,
		webpOk ? h('option', { value: 'image/webp', text: 'WebP (smallest, recommended)' }) : null,
		h('option', { value: 'image/jpeg', text: 'JPEG' }),
		h('option', { value: 'image/png', text: 'PNG (lossless, larger)' }));
	format.value = s.format && (s.format !== 'image/webp' || webpOk) ? s.format : (webpOk ? 'image/webp' : 'image/jpeg');
	var quality = h('input', { type: 'range', min: 40, max: 95, step: 1, value: s.quality || 80 });
	var qLabel = h('span', { text: quality.value + '%' });
	var maxW = h('select', null, ['0', '2560', '1920', '1600', '1200', '800'].map(function (v) { return h('option', { value: v, text: v === '0' ? 'Keep original size' : 'Max width ' + v + ' px' }); }));
	maxW.value = s.maxW || '1920';
	var file = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp,image/gif,image/bmp', multiple: true, style: 'display:none' });
	var drop = h('div', { class: 'semrs-drop', tabindex: '0', role: 'button', 'aria-label': 'Choose images to compress' },
		h('strong', { text: 'Drop images here or click to choose' }), h('br'),
		h('span', { class: 'semrs-tool__hint', text: 'JPG, PNG, WebP or GIF · up to ' + MAX_FILES + ' files · ' + MAX_MB + ' MB each · processed on your device' }));
	var list = h('div', { class: 'semrs-img-list' });
	var summary = h('div');
	var results = [];

	var settings = api.panel('Settings');
	settings.append(
		h('div', { class: 'semrs-tool__row' },
			api.field('Output format', format),
			api.field('Resize', maxW),
			h('div', { class: 'semrs-tool__field' }, h('label', null, 'Quality: ', qLabel), quality)),
		drop, file);
	var out = api.panel('Results');
	out.append(summary, list);
	root.append(settings, out);

	function kb(b) { return b > 1048576 ? (b / 1048576).toFixed(2) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
	function ext(type) { return type === 'image/webp' ? 'webp' : (type === 'image/png' ? 'png' : 'jpg'); }

	function loadImage(f) {
		if (window.createImageBitmap) { return createImageBitmap(f); }
		return new Promise(function (res, rej) { var i = new Image(); i.onload = function () { res(i); }; i.onerror = rej; i.src = URL.createObjectURL(f); });
	}

	function compress(f) {
		return loadImage(f).then(function (img) {
			var w = img.width, hgt = img.height, limit = parseInt(maxW.value, 10);
			if (limit && w > limit) { hgt = Math.round(hgt * limit / w); w = limit; }
			var c = document.createElement('canvas');
			c.width = w; c.height = hgt;
			var ctx = c.getContext('2d');
			if (format.value === 'image/jpeg') { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, hgt); }
			ctx.drawImage(img, 0, 0, w, hgt);
			if (img.close) { img.close(); }
			return new Promise(function (res) { c.toBlob(function (b) { res({ blob: b, w: w, h: hgt }); }, format.value, parseInt(quality.value, 10) / 100); });
		});
	}

	function handle(files) {
		files = Array.prototype.slice.call(files, 0, MAX_FILES);
		if (!files.length) { return; }
		api.track('tool_used', { tool_id: api.id, files: files.length });
		results = [];
		list.innerHTML = '';
		summary.innerHTML = '';
		var jobs = files.map(function (f) {
			var row = h('div', { class: 'semrs-img-item' }, h('img', { alt: '', src: URL.createObjectURL(f) }), h('div', null, h('strong', { text: f.name }), h('br'), h('span', { class: 'semrs-tool__hint', text: 'Compressing…' })), h('span'));
			list.append(row);
			if (!/^image\//.test(f.type) || f.size > MAX_MB * 1048576) {
				row.children[1].lastChild.textContent = 'Skipped: not an image or larger than ' + MAX_MB + ' MB.';
				return Promise.resolve();
			}
			return compress(f).then(function (r) {
				var saved = 1 - r.blob.size / f.size;
				var name = f.name.replace(/\.[^.]+$/, '') + '.' + ext(format.value);
				var bigger = r.blob.size >= f.size && format.value !== 'image/png';
				var use = bigger ? f : r.blob;
				results.push({ name: bigger ? f.name : name, blob: use, before: f.size, after: use.size });
				row.children[1].lastChild.textContent = kb(f.size) + ' → ' + kb(use.size) + ' · ' + r.w + '×' + r.h + (bigger ? ' · original kept (already optimised)' : '');
				row.children[2].append(
					!bigger && saved > 0 ? h('span', { class: 'semrs-good', text: '−' + Math.round(saved * 100) + '% ' }) : null,
					api.downloadBtn(function () { return use; }, bigger ? f.name : name, use.type, 'Download', api.id));
			}).catch(function () { row.children[1].lastChild.textContent = 'Could not read this image.'; });
		});
		Promise.all(jobs).then(function () {
			if (!results.length) { return; }
			var before = results.reduce(function (a, r) { return a + r.before; }, 0), after = results.reduce(function (a, r) { return a + r.after; }, 0);
			summary.append(h('div', { class: 'semrs-tool__stats' },
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: results.length }), h('span', { text: 'images' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: kb(before) + ' → ' + kb(after) }), h('span', { text: 'total size' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: Math.max(0, Math.round((1 - after / before) * 100)) + '%' }), h('span', { text: 'smaller' }))));
			if (results.length > 1) {
				var all = h('button', { type: 'button', class: 'semrs-btn', text: 'Download all (' + results.length + ')' });
				all.addEventListener('click', function () {
					results.forEach(function (r, i) {
						setTimeout(function () {
							var a = h('a', { href: URL.createObjectURL(r.blob), download: r.name });
							document.body.appendChild(a); a.click();
							setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
						}, i * 350);
					});
					api.track('tool_download', { tool_id: api.id, files: results.length });
				});
				summary.append(h('div', { class: 'semrs-tool__actions' }, all));
			}
		});
	}

	drop.addEventListener('click', function () { file.click(); });
	drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); file.click(); } });
	file.addEventListener('change', function () { handle(file.files); file.value = ''; });
	['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
	['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
	drop.addEventListener('drop', function (e) { handle(e.dataTransfer.files); });
	quality.addEventListener('input', function () { qLabel.textContent = quality.value + '%'; });
	root.addEventListener('change', function () { api.store(api.id, { format: format.value, quality: quality.value, maxW: maxW.value }); });
	summary.append(h('p', { class: 'semrs-tool__hint', text: 'Choose images to see the results. Change settings and re-add images to compare.' }));
});
