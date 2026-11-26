// Data graphics for the SEMRS blog articles, drawn as SVG (brand colors,
// cited figures only). blog-images.mjs turns them into WebP files.
const W = 1200, H = 675;
const C = { blue: '#0b2fd1', navy: '#0a1650', orange: '#f7941d', orangeDk: '#b45309', ink: '#151b33', mute: '#5a6383', soft: '#eef1ff', warm: '#fff3e3', line: '#dfe3ef', bg: '#f8f9ff', green: '#0f7b4d' };
const F = "Segoe UI, Arial, Helvetica, sans-serif";
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

const frame = (title, sub, body, source) => `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
<rect width="${W}" height="${H}" fill="${C.bg}"/>
<rect width="${W}" height="10" fill="${C.blue}"/><rect width="260" height="10" fill="${C.orange}"/>
<text x="60" y="88" font-family="${F}" font-size="40" font-weight="700" fill="${C.ink}">${esc(title)}</text>
${sub ? `<text x="60" y="128" font-family="${F}" font-size="22" fill="${C.mute}">${esc(sub)}</text>` : ''}
${body}
<line x1="60" y1="${H - 52}" x2="${W - 60}" y2="${H - 52}" stroke="${C.line}" stroke-width="2"/>
<text x="60" y="${H - 22}" font-family="${F}" font-size="17" fill="${C.mute}">${esc(source)}</text>
<text x="${W - 60}" y="${H - 22}" text-anchor="end" font-family="${F}" font-size="18" font-weight="700" fill="${C.blue}">SEMRS</text>
</svg>`;

const wrap = (text, max) => { const out = []; let line = ''; for (const w of text.split(' ')) { if ((line + ' ' + w).trim().length > max) { out.push(line.trim()); line = w; } else line += ' ' + w; } if (line.trim()) out.push(line.trim()); return out; };
const tspans = (x, y, lines, size, color, weight = 400, lh = 1.3) => lines.map((l, i) => `<text x="${x}" y="${y + i * size * lh}" font-family="${F}" font-size="${size}" font-weight="${weight}" fill="${color}">${esc(l)}</text>`).join('');

// Numbered step cards in a grid.
function steps(title, sub, items, cols, source, accentLast) {
	const rows = Math.ceil(items.length / cols);
	const gx = 60, gy = 160, gw = W - 120, gh = H - 160 - 80, gap = 20;
	const cw = (gw - gap * (cols - 1)) / cols, ch = (gh - gap * (rows - 1)) / rows;
	const body = items.map(([h, d], i) => {
		const x = gx + (i % cols) * (cw + gap), y = gy + Math.floor(i / cols) * (ch + gap);
		const hot = accentLast && i === items.length - 1;
		return `<rect x="${x}" y="${y}" width="${cw}" height="${ch}" rx="16" fill="${hot ? C.warm : '#fff'}" stroke="${hot ? C.orange : C.line}" stroke-width="2"/>
<circle cx="${x + 42}" cy="${y + 44}" r="24" fill="${hot ? C.orange : C.blue}"/>
<text x="${x + 42}" y="${y + 52}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="#fff">${i + 1}</text>
${tspans(x + 80, y + 52, wrap(h, Math.floor(cw / 14.5)), 24, C.ink, 700)}
${tspans(x + 24, y + (ch > 200 ? 118 : 106), wrap(d, Math.floor(cw / 11.5)), ch > 200 ? 22 : 20, C.mute, 400, 1.35)}`;
	}).join('');
	return frame(title, sub, body, source);
}

// Horizontal bars.
function hbars(title, sub, rows, max, fmt, source, note) {
	const x0 = 380, x1 = W - 140, y0 = 175, bh = 64, gap = 30;
	const ticks = [0, 0.25, 0.5, 0.75, 1].map((t) => `<line x1="${x0 + (x1 - x0) * t}" y1="${y0 - 14}" x2="${x0 + (x1 - x0) * t}" y2="${y0 + rows.length * (bh + gap) - gap + 8}" stroke="${C.line}" stroke-width="1.5"/>
<text x="${x0 + (x1 - x0) * t}" y="${y0 + rows.length * (bh + gap) - gap + 34}" text-anchor="middle" font-family="${F}" font-size="16" fill="${C.mute}">${fmt(max * t)}</text>`).join('');
	const bars = rows.map(([label, v, hot], i) => {
		const y = y0 + i * (bh + gap), w = (x1 - x0) * (v / max);
		return `<text x="${x0 - 20}" y="${y + bh / 2 + 8}" text-anchor="end" font-family="${F}" font-size="22" font-weight="600" fill="${C.ink}">${esc(label)}</text>
<rect x="${x0}" y="${y}" width="${w}" height="${bh}" rx="8" fill="${hot ? C.orange : C.blue}"/>
<text x="${x0 + w + 12}" y="${y + bh / 2 + 8}" font-family="${F}" font-size="24" font-weight="700" fill="${hot ? C.orangeDk : C.blue}">${fmt(v)}</text>`;
	}).join('');
	const n = note ? `<text x="60" y="${H - 72}" font-family="${F}" font-size="19" fill="${C.ink}">${esc(note)}</text>` : '';
	return frame(title, sub, ticks + bars + n, source);
}

// Grouped vertical bars (two series).
function gbars(title, sub, groups, series, fmt, source) {
	const x0 = 140, x1 = W - 80, yb = H - 120, top = 190;
	const max = Math.max(...groups.flatMap((g) => g[1])) * 1.15;
	const gw = (x1 - x0) / groups.length;
	const bw = 120;
	let body = `<line x1="${x0}" y1="${yb}" x2="${x1}" y2="${yb}" stroke="${C.ink}" stroke-width="2"/>`;
	groups.forEach(([label, vals], gi) => {
		const cx = x0 + gw * gi + gw / 2;
		const gmax = Math.max(...vals) * 1.15;
		vals.forEach((v, si) => {
			const h = (yb - top) * (v / gmax), x = cx - bw - 10 + si * (bw + 20);
			body += `<rect x="${x}" y="${yb - h}" width="${bw}" height="${h}" rx="8" fill="${si ? C.orange : C.blue}"/>
<text x="${x + bw / 2}" y="${yb - h - 12}" text-anchor="middle" font-family="${F}" font-size="26" font-weight="700" fill="${si ? C.orangeDk : C.blue}">${fmt(v)}</text>`;
		});
		body += `<text x="${cx}" y="${yb + 36}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="${C.ink}">${esc(label)}</text>`;
	});
	body += series.map((s, i) => `<rect x="${W - 420 + i * 200}" y="150" width="22" height="22" rx="4" fill="${i ? C.orange : C.blue}"/><text x="${W - 390 + i * 200}" y="168" font-family="${F}" font-size="19" fill="${C.ink}">${esc(s)}</text>`).join('');
	return frame(title, sub, body, source);
}

// Two-column comparison table.
function compare(title, sub, heads, rows, source) {
	const x = 60, y = 160, w = W - 120, c0 = 300, rh = (H - 160 - 90) / (rows.length + 1);
	const cw = (w - c0) / 2;
	let b = `<rect x="${x}" y="${y}" width="${w}" height="${rh * (rows.length + 1)}" rx="14" fill="#fff" stroke="${C.line}" stroke-width="2"/>`;
	b += `<rect x="${x + c0}" y="${y}" width="${cw}" height="${rh}" fill="${C.blue}"/><rect x="${x + c0 + cw}" y="${y}" width="${cw}" height="${rh}" rx="0" fill="${C.orange}"/>`;
	b += heads.map((h, i) => `<text x="${x + c0 + cw * i + cw / 2}" y="${y + rh / 2 + 9}" text-anchor="middle" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(h)}</text>`).join('');
	rows.forEach(([k, a, bb], i) => {
		const yy = y + rh * (i + 1);
		if (i % 2 === 0) b += `<rect x="${x + 1}" y="${yy}" width="${w - 2}" height="${rh}" fill="${C.soft}"/>`;
		b += `<text x="${x + 24}" y="${yy + rh / 2 + 8}" font-family="${F}" font-size="21" font-weight="700" fill="${C.ink}">${esc(k)}</text>`;
		[a, bb].forEach((t, j) => { b += `<text x="${x + c0 + cw * j + cw / 2}" y="${yy + rh / 2 + 8}" text-anchor="middle" font-family="${F}" font-size="20" fill="${C.ink}">${esc(t)}</text>`; });
	});
	return frame(title, sub, b, source);
}

// Funnel / stages.
function funnel(title, sub, stages, source, note) {
	const y0 = 165, sh = 72, gap = 14, maxw = 1000, minw = 600;
	const b = stages.map(([label, val], i) => {
		const w = maxw - (maxw - minw) * (i / (stages.length - 1 || 1)), x = (W - w) / 2, y = y0 + i * (sh + gap);
		const hot = i === stages.length - 1;
		return `<rect x="${x}" y="${y}" width="${w}" height="${sh}" rx="12" fill="${hot ? C.orange : C.blue}" opacity="${hot ? 1 : 1 - i * 0.12}"/>
<text x="${x + 28}" y="${y + sh / 2 + 9}" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(label)}</text>
<text x="${x + w - 28}" y="${y + sh / 2 + 9}" text-anchor="end" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(val)}</text>`;
	}).join('');
	const n = note ? `<text x="${W / 2}" y="${H - 72}" text-anchor="middle" font-family="${F}" font-size="19" fill="${C.ink}">${esc(note)}</text>` : '';
	return frame(title, sub, b + n, source);
}

// Three overlapping circles.
function venn(title, sub, circles, center, source) {
	const cx = 600, r = 130;
	const pos = [[cx - 100, 320], [cx + 100, 320], [cx, 452]];
	const cols = [C.blue, C.orange, C.navy];
	let b = circles.map(([h], i) => `<circle cx="${pos[i][0]}" cy="${pos[i][1]}" r="${r}" fill="${cols[i]}" fill-opacity="0.16" stroke="${cols[i]}" stroke-width="3"/>`).join('');
	const lab = [[230, 280], [970, 280], [930, 480]];
	b += circles.map(([h, d], i) => `<text x="${lab[i][0]}" y="${lab[i][1]}" text-anchor="middle" font-family="${F}" font-size="28" font-weight="700" fill="${cols[i]}">${esc(h)}</text>${wrap(d, 22).map((l, k) => `<text x="${lab[i][0]}" y="${lab[i][1] + 30 + k * 24}" text-anchor="middle" font-family="${F}" font-size="18" fill="${C.ink}">${esc(l)}</text>`).join('')}`).join('');
	b += `<text x="${cx}" y="372" text-anchor="middle" font-family="${F}" font-size="20" font-weight="700" fill="${C.ink}">${esc(center)}</text>`;
	return frame(title, sub, b, source);
}


// ---- Graphic kit 2 (2026-11): more layouts, icons and themes so each article looks different. ----
// Themes: light (default), dark (navy background) and warm (cream background).
const THEMES = {
	light: { bg: C.bg, ink: C.ink, mute: C.mute, card: '#fff', line: C.line, a: C.blue, b: C.orange, bDk: C.orangeDk },
	dark: { bg: '#0a1650', ink: '#ffffff', mute: '#c9d2f5', card: '#13226b', line: '#2a3c8f', a: '#5b7cff', b: C.orange, bDk: '#ffb45e' },
	warm: { bg: '#fff8ee', ink: C.ink, mute: '#6b5a44', card: '#ffffff', line: '#f1dcc0', a: C.orangeDk, b: C.blue, bDk: C.blue },
};
const frameT = (th, title, sub, body, source) => `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
<rect width="${W}" height="${H}" fill="${th.bg}"/>
<rect width="${W}" height="10" fill="${th.a}"/><rect width="260" height="10" fill="${th.b}"/>
<text x="60" y="88" font-family="${F}" font-size="40" font-weight="700" fill="${th.ink}">${esc(title)}</text>
${sub ? `<text x="60" y="128" font-family="${F}" font-size="22" fill="${th.mute}">${esc(sub)}</text>` : ''}
${body}
<line x1="60" y1="${H - 52}" x2="${W - 60}" y2="${H - 52}" stroke="${th.line}" stroke-width="2"/>
<text x="60" y="${H - 22}" font-family="${F}" font-size="17" fill="${th.mute}">${esc(source)}</text>
<text x="${W - 60}" y="${H - 22}" text-anchor="end" font-family="${F}" font-size="18" font-weight="700" fill="${th.b}">SEMRS</text>
</svg>`;
const tsp = (x, y, lines, size, color, weight = 400, anchor = 'start', lh = 1.3) => lines.map((l, i) => `<text x="${x}" y="${y + i * size * lh}" text-anchor="${anchor}" font-family="${F}" font-size="${size}" font-weight="${weight}" fill="${color}">${esc(l)}</text>`).join('');

// Line icons on a 24x24 grid.
const ICON = {
	search: '<circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/>',
	pin: '<path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
	star: '<path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z"/>',
	phone: '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
	mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
	chart: '<path d="M4 20V11M10 20V5M16 20v-6M2 20h20"/>',
	dollar: '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
	shield: '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/>',
	clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	check: '<path d="M4 12l5 5L20 6"/>',
	link: '<path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
	gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9L7 7M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
	user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
	megaphone: '<path d="M3 10v4h4l8 5V5L7 10z"/><path d="M19 9a4 4 0 0 1 0 6"/>',
	doc: '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h6"/>',
	bolt: '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
	bulb: '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>',
	target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
	home: '<path d="M3 11l9-8 9 8v10H3z"/><path d="M9 21v-6h6v6"/>',
	lock: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
	smile: '<circle cx="12" cy="12" r="9"/><path d="M8 14a5 5 0 0 0 8 0M9 9h.01M15 9h.01"/>',
	map: '<path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
	list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
};
const icon = (name, x, y, size, color, sw = 2) => `<g transform="translate(${x},${y}) scale(${size / 24})" fill="none" stroke="${color}" stroke-width="${sw * 24 / size}" stroke-linecap="round" stroke-linejoin="round">${ICON[name] || ICON.check}</g>`;

// Cards with an icon badge (items: [icon, head, desc]).
function iconCards(title, sub, items, cols, source, theme = 'light') {
	const th = THEMES[theme];
	const rows = Math.ceil(items.length / cols);
	const gx = 60, gy = 160, gw = W - 120, gh = H - 160 - 80, gap = 20;
	const cw = (gw - gap * (cols - 1)) / cols, ch = (gh - gap * (rows - 1)) / rows;
	const body = items.map(([ic, h, d], i) => {
		const x = gx + (i % cols) * (cw + gap), y = gy + Math.floor(i / cols) * (ch + gap);
		const tint = i % 2 ? th.b : th.a;
		return `<rect x="${x}" y="${y}" width="${cw}" height="${ch}" rx="18" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>
<rect x="${x + 20}" y="${y + 20}" width="56" height="56" rx="14" fill="${tint}" fill-opacity="${theme === 'dark' ? 0.35 : 0.12}"/>
${icon(ic, x + 32, y + 32, 32, tint === th.b ? th.bDk : th.a)}
${tsp(x + 92, y + 56, wrap(h, Math.floor((cw - 100) / 13.5)).slice(0, 2), 23, th.ink, 700)}
${tsp(x + 22, y + (ch > 200 ? 118 : 104), wrap(d, Math.floor(cw / 11.5)).slice(0, 3), 19, th.mute, 400)}`;
	}).join('');
	return frameT(th, title, sub, body, source);
}

// A loop: nodes around a circle with arrows (items: [head, desc]), centre label.
function cycle(title, sub, items, centre, source, theme = 'light') {
	const th = THEMES[theme];
	const cx = 600, cy = 398, R = 165, n = items.length;
	let b = `<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${th.line}" stroke-width="6" stroke-dasharray="2 14" stroke-linecap="round"/>`;
	b += `<circle cx="${cx}" cy="${cy}" r="92" fill="${th.a}"/>${tsp(cx, cy - 6, wrap(centre, 12).slice(0, 2), 22, '#fff', 700, 'middle')}`;
	items.forEach(([h, d], i) => {
		const ang = -Math.PI / 2 + i * 2 * Math.PI / n;
		const x = cx + R * Math.cos(ang), y = cy + R * Math.sin(ang);
		const hot = i === n - 1;
		b += `<circle cx="${x}" cy="${y}" r="30" fill="${hot ? th.b : th.card}" stroke="${hot ? th.b : th.a}" stroke-width="3"/><text x="${x}" y="${y + 8}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="${hot ? '#fff' : th.a}">${i + 1}</text>`;
		const right = Math.cos(ang) >= -0.2, left = !right;
		const tx = right ? x + 46 : left ? x - 46 : x, anchor = right ? 'start' : left ? 'end' : 'middle';
		const ty = y - 6;
		b += tsp(tx, ty, [h], 21, th.ink, 700, anchor) + tsp(tx, ty + 24, wrap(d, 24).slice(0, 2), 17, th.mute, 400, anchor);
		// Arrow head along the loop between this node and the next.
		const a2 = ang + Math.PI / n, ax = cx + R * Math.cos(a2), ay = cy + R * Math.sin(a2), rot = (a2 * 180 / Math.PI) + 90;
		b += `<path d="M -9 -7 L 7 0 L -9 7 z" transform="translate(${ax},${ay}) rotate(${rot - 90})" fill="${th.b}"/>`;
	});
	return frameT(th, title, sub, b, source);
}

// Horizontal timeline (items: [tag, head, desc]); two rows when more than 5.
function timeline(title, sub, items, source, theme = 'light') {
	const th = THEMES[theme];
	const rowsN = items.length > 5 ? 2 : 1, per = Math.ceil(items.length / rowsN);
	let b = '';
	for (let r = 0; r < rowsN; r++) {
		const part = items.slice(r * per, (r + 1) * per);
		const y = rowsN === 1 ? 360 : 250 + r * 200, x0 = 110, x1 = W - 110, step = (x1 - x0) / Math.max(1, per - 1);
		b += `<line x1="${x0 - 30}" y1="${y}" x2="${x1 + 30}" y2="${y}" stroke="${th.line}" stroke-width="8" stroke-linecap="round"/>`;
		part.forEach(([tag, h, d], i) => {
			const x = per === 1 ? (x0 + x1) / 2 : x0 + i * step, k = r * per + i, hot = k === items.length - 1;
			b += `<circle cx="${x}" cy="${y}" r="16" fill="${hot ? th.b : th.a}" stroke="${th.bg}" stroke-width="5"/>`;
			b += `<rect x="${x - 44}" y="${y - 58}" width="88" height="30" rx="15" fill="${hot ? th.b : th.a}" fill-opacity="${theme === 'dark' ? 0.5 : 0.14}"/>${tsp(x, y - 37, [tag], 17, hot ? th.bDk : th.a, 700, 'middle')}`;
			b += tsp(x, y + 48, wrap(h, 16).slice(0, 2), 20, th.ink, 700, 'middle') + tsp(x, y + (wrap(h, 16).length > 1 ? 98 : 74), wrap(d, 20).slice(0, 2), 16, th.mute, 400, 'middle');
		});
	}
	return frameT(th, title, sub, b, source);
}

// Concentric circles (outer to inner), with a legend (rings: [label, desc]).
function rings(title, sub, ringsList, source, theme = 'light') {
	const th = THEMES[theme];
	const cx = 330, cy = 392, rmax = 210, n = ringsList.length;
	const cols = [th.a, '#3b5bf0', th.b, th.bDk];
	let b = '';
	ringsList.forEach(([l], i) => { const r = rmax - i * (rmax - 60) / Math.max(1, n - 1); b += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${cols[i % cols.length]}" fill-opacity="${0.14 + i * 0.16}" stroke="${cols[i % cols.length]}" stroke-width="3"/>`; });
	ringsList.forEach(([l], i) => { const r = rmax - i * (rmax - 60) / Math.max(1, n - 1); const y = i === n - 1 ? cy + 8 : cy - r + 34; b += `<text x="${cx}" y="${y}" text-anchor="middle" font-family="${F}" font-size="${i === n - 1 ? 20 : 19}" font-weight="700" fill="${i === n - 1 ? '#fff' : th.ink}">${esc(l)}</text>`; });
	ringsList.forEach(([l, d], i) => {
		const y = 200 + i * (360 / n);
		b += `<rect x="640" y="${y}" width="500" height="${360 / n - 18}" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/><rect x="640" y="${y}" width="10" height="${360 / n - 18}" rx="5" fill="${cols[i % cols.length]}"/>`;
		b += tsp(670, y + 34, [l], 22, th.ink, 700) + tsp(670, y + 62, wrap(d, 42).slice(0, 2), 18, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// Pyramid (levels top to bottom: [head, desc]); the base is the foundation.
function pyramid(title, sub, levels, source, theme = 'light') {
	const th = THEMES[theme];
	const top = 165, bottom = H - 80, n = levels.length, lh = (bottom - top) / n, cx = 340, half = 300;
	let b = '';
	levels.forEach(([h, d], i) => {
		const y0 = top + i * lh, y1 = y0 + lh - 6, w0 = half * (i / n) + 30, w1 = half * ((i + 1) / n) + 30;
		const fill = i === 0 ? th.b : th.a, op = i === 0 ? 1 : 1 - i * 0.12;
		b += `<path d="M ${cx - w0} ${y0} L ${cx + w0} ${y0} L ${cx + w1} ${y1} L ${cx - w1} ${y1} Z" fill="${fill}" opacity="${op}"/>`;
		b += `<text x="${cx}" y="${(y0 + y1) / 2 + 8}" text-anchor="middle" font-family="${F}" font-size="21" font-weight="700" fill="#fff">${esc(h)}</text>`;
		b += `<line x1="${cx + w1 + 14}" y1="${(y0 + y1) / 2}" x2="700" y2="${(y0 + y1) / 2}" stroke="${th.line}" stroke-width="2" stroke-dasharray="4 6"/>`;
		b += tsp(716, (y0 + y1) / 2 - 4, [h], 21, th.ink, 700) + tsp(716, (y0 + y1) / 2 + 22, wrap(d, 44).slice(0, 1), 18, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// Tree: root on the left, branches in the middle, leaves on the right (branches: [head, [leaf, leaf]]).
function tree(title, sub, root, branches, source, theme = 'light') {
	const th = THEMES[theme];
	const top = 175, bottom = H - 85, n = branches.length, bh = (bottom - top) / n;
	const rx = 70, ry = (top + bottom) / 2;
	let b = `<rect x="${rx}" y="${ry - 60}" width="230" height="120" rx="20" fill="${th.a}"/>${tsp(rx + 115, ry - 6, wrap(root, 16).slice(0, 2), 23, '#fff', 700, 'middle')}`;
	branches.forEach(([h, leaves], i) => {
		const by = top + i * bh + bh / 2, bx = 400;
		b += `<path d="M ${rx + 230} ${ry} C ${rx + 300} ${ry}, ${bx - 70} ${by}, ${bx} ${by}" fill="none" stroke="${th.a}" stroke-width="3"/>`;
		b += `<rect x="${bx}" y="${by - 30}" width="290" height="60" rx="30" fill="${th.card}" stroke="${th.b}" stroke-width="3"/>${tsp(bx + 145, by + 8, [h], 21, th.ink, 700, 'middle')}`;
		leaves.forEach((l, j) => {
			const ly = by + (j - (leaves.length - 1) / 2) * 36, lx = 760;
			b += `<path d="M ${bx + 290} ${by} C ${bx + 330} ${by}, ${lx - 40} ${ly}, ${lx} ${ly}" fill="none" stroke="${th.line}" stroke-width="2"/>`;
			b += `<circle cx="${lx + 8}" cy="${ly}" r="6" fill="${th.b}"/>${tsp(lx + 24, ly + 7, [l], 18, th.mute)}`;
		});
	});
	return frameT(th, title, sub, b, source);
}

// Checklist in two columns (items: [head, desc]).
function checklist(title, sub, items, source, theme = 'light') {
	const th = THEMES[theme];
	const per = Math.ceil(items.length / 2), rh = Math.min(118, (H - 165 - 85) / per);
	let b = '';
	items.forEach(([h, d], i) => {
		const col = Math.floor(i / per), x = 60 + col * 550, y = 170 + (i % per) * rh;
		b += `<rect x="${x}" y="${y}" width="530" height="${rh - 14}" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
		b += `<rect x="${x + 20}" y="${y + (rh - 14) / 2 - 18}" width="36" height="36" rx="8" fill="${th.a}"/>${icon('check', x + 26, y + (rh - 14) / 2 - 12, 24, '#fff', 3)}`;
		b += tsp(x + 76, y + (rh - 14) / 2 - 4, [h], 22, th.ink, 700) + tsp(x + 76, y + (rh - 14) / 2 + 22, wrap(d, 44).slice(0, 1), 18, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// Big-number tiles joined by arrows (tiles: [value, label]).
function statTiles(title, sub, tiles, source, theme = 'light', note) {
	const th = THEMES[theme];
	const n = tiles.length, gap = 46, w = (W - 120 - gap * (n - 1)) / n, y = 220, h = 250;
	let b = '';
	tiles.forEach(([v, l], i) => {
		const x = 60 + i * (w + gap), hot = i === n - 1;
		b += `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="22" fill="${hot ? th.b : th.card}" stroke="${hot ? th.b : th.line}" stroke-width="2"/>`;
		b += `<text x="${x + w / 2}" y="${y + 112}" text-anchor="middle" font-family="${F}" font-size="${v.length > 6 ? 46 : 58}" font-weight="800" fill="${hot ? '#fff' : th.a}">${esc(v)}</text>`;
		b += tsp(x + w / 2, y + 162, wrap(l, Math.floor(w / 12)).slice(0, 3), 19, hot ? '#fff' : th.mute, 600, 'middle');
		if (i < n - 1) b += `<path d="M ${x + w + 10} ${y + h / 2} l 24 0 m -10 -10 l 10 10 l -10 10" fill="none" stroke="${th.b}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>`;
	});
	if (note) b += tsp(W / 2, y + h + 70, [note], 20, th.ink, 400, 'middle');
	return frameT(th, title, sub, b, source);
}

// A winding path with milestones (items: [head, desc]).
function roadPath(title, sub, items, source, theme = 'light') {
	const th = THEMES[theme];
	const n = items.length, xs = items.map((_, i) => 120 + i * ((W - 240) / (n - 1))), ys = items.map((_, i) => (i % 2 ? 460 : 320));
	let d = `M ${xs[0]} ${ys[0]}`;
	for (let i = 1; i < n; i++) { const mx = (xs[i - 1] + xs[i]) / 2; d += ` C ${mx} ${ys[i - 1]}, ${mx} ${ys[i]}, ${xs[i]} ${ys[i]}`; }
	let b = `<path d="${d}" fill="none" stroke="${th.line}" stroke-width="26" stroke-linecap="round"/><path d="${d}" fill="none" stroke="${th.card}" stroke-width="3" stroke-dasharray="14 12"/>`;
	items.forEach(([h, desc], i) => {
		const x = xs[i], y = ys[i], up = i % 2 === 0, hot = i === n - 1;
		b += `<circle cx="${x}" cy="${y}" r="26" fill="${hot ? th.b : th.a}"/><text x="${x}" y="${y + 8}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="#fff">${i + 1}</text>`;
		const ty = up ? y - 96 : y + 56;
		b += tsp(x, ty, wrap(h, 14).slice(0, 2), 20, th.ink, 700, 'middle') + tsp(x, ty + (wrap(h, 14).length > 1 ? 50 : 26), wrap(desc, 18).slice(0, 2), 16, th.mute, 400, 'middle');
	});
	return frameT(th, title, sub, b, source);
}


// ---- Graphic kit 3 (2026-11): purpose-built layouts (matrix, layers, wireframe, badges, phones, hub, flowchart, map, form, house). ----
// Two-axis matrix (items: [label, x 0-1, y 0-1, hot]).
function quadrant(title, sub, xLab, yLab, items, source, theme = 'light') {
	const th = THEMES[theme];
	const x0 = 170, x1 = 1110, y0 = 165, y1 = 560, mx = (x0 + x1) / 2, my = (y0 + y1) / 2;
	let b = `<rect x="${x0}" y="${y0}" width="${x1 - x0}" height="${y1 - y0}" rx="16" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	b += `<rect x="${mx}" y="${y0}" width="${x1 - mx}" height="${my - y0}" rx="0" fill="${th.b}" fill-opacity=".08"/><rect x="${x0}" y="${my}" width="${mx - x0}" height="${y1 - my}" fill="${th.a}" fill-opacity=".07"/>`;
	b += `<line x1="${mx}" y1="${y0}" x2="${mx}" y2="${y1}" stroke="${th.line}" stroke-width="2" stroke-dasharray="6 6"/><line x1="${x0}" y1="${my}" x2="${x1}" y2="${my}" stroke="${th.line}" stroke-width="2" stroke-dasharray="6 6"/>`;
	b += `<text x="${(x0 + x1) / 2}" y="${y1 + 36}" text-anchor="middle" font-family="${F}" font-size="19" font-weight="700" fill="${th.mute}">${esc(xLab)} →</text>`;
	b += `<text transform="translate(${x0 - 34},${(y0 + y1) / 2}) rotate(-90)" text-anchor="middle" font-family="${F}" font-size="19" font-weight="700" fill="${th.mute}">${esc(yLab)} →</text>`;
	items.forEach(([l, x, y, hot]) => {
		const px = x0 + 40 + x * (x1 - x0 - 80), py = y1 - 40 - y * (y1 - y0 - 80);
		const w = l.length * 10.5 + 44;
		b += `<rect x="${px - w / 2}" y="${py - 22}" width="${w}" height="44" rx="22" fill="${hot ? th.b : th.a}"/><text x="${px}" y="${py + 7}" text-anchor="middle" font-family="${F}" font-size="19" font-weight="700" fill="#fff">${esc(l)}</text>`;
	});
	return frameT(th, title, sub, b, source);
}

// Stacked layers from bottom (first) to top (last), each narrower (items: [head, desc, icon]).
function layers(title, sub, items, source, theme = 'light') {
	const th = THEMES[theme];
	const n = items.length, bottom = H - 95, lh = 88, gap = 12;
	let b = '';
	items.forEach(([h, d, ic], i) => {
		const w = 980 - i * 120, x = (W - w) / 2, y = bottom - (i + 1) * lh - i * gap, hot = i === n - 1;
		b += `<rect x="${x}" y="${y}" width="${w}" height="${lh}" rx="16" fill="${hot ? th.b : th.a}" opacity="${hot ? 1 : 0.55 + i * 0.15}"/>`;
		b += icon(ic || 'check', x + 26, y + lh / 2 - 16, 32, '#fff', 2.2);
		b += `<text x="${x + 76}" y="${y + 38}" font-family="${F}" font-size="24" font-weight="800" fill="#fff">${i + 1}. ${esc(h)}</text>`;
		b += `<text x="${x + 76}" y="${y + 66}" font-family="${F}" font-size="18" fill="#fff" fill-opacity=".92">${esc(d)}</text>`;
	});
	return frameT(th, title, sub, b, source);
}

// Browser-window wireframe with numbered callouts (parts: [label, desc]); 6 parts expected.
function wireframe(title, sub, parts, source, theme = 'light') {
	const th = THEMES[theme];
	const bx = 60, by = 160, bw = 640, bh = 420;
	let b = `<rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="16" fill="${th.card}" stroke="${th.line}" stroke-width="3"/>`;
	b += `<rect x="${bx}" y="${by}" width="${bw}" height="40" rx="16" fill="${th.line}"/><rect x="${bx}" y="${by + 24}" width="${bw}" height="16" fill="${th.line}"/>`;
	b += [0, 1, 2].map((i) => `<circle cx="${bx + 24 + i * 22}" cy="${by + 20}" r="7" fill="${[C.orange, '#f6c343', C.green][i]}"/>`).join('');
	b += `<rect x="${bx + 90}" y="${by + 10}" width="420" height="20" rx="10" fill="${th.card}"/>`;
	// Page regions: header, title, image, text, sidebar, CTA.
	const R = [[bx + 20, by + 58, bw - 40, 34], [bx + 20, by + 108, 400, 30], [bx + 20, by + 152, 400, 140], [bx + 20, by + 306, 400, 86], [bx + 440, by + 108, 180, 200], [bx + 440, by + 324, 180, 68]];
	R.forEach(([x, y, w, h], i) => {
		b += `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="8" fill="${i === 5 ? th.b : th.a}" fill-opacity="${i === 5 ? 0.9 : 0.12}" stroke="${i === 5 ? th.b : th.a}" stroke-width="2" stroke-dasharray="${i === 5 ? '' : '6 5'}"/>`;
		if (i === 3) b += [0, 1, 2, 3].map((k) => `<rect x="${x + 14}" y="${y + 14 + k * 18}" width="${w - 28 - (k === 3 ? 120 : 0)}" height="8" rx="4" fill="${th.mute}" fill-opacity=".35"/>`).join('');
		if (i === 2) b += icon('doc', x + w / 2 - 20, y + h / 2 - 20, 40, th.a, 2);
		b += `<circle cx="${x + w - 2}" cy="${y + 2}" r="15" fill="${th.b}"/><text x="${x + w - 2}" y="${y + 8}" text-anchor="middle" font-family="${F}" font-size="16" font-weight="800" fill="#fff">${i + 1}</text>`;
	});
	parts.forEach(([l, d], i) => {
		const y = 175 + i * 66;
		b += `<circle cx="760" cy="${y + 16}" r="17" fill="${th.b}"/><text x="760" y="${y + 23}" text-anchor="middle" font-family="${F}" font-size="17" font-weight="800" fill="#fff">${i + 1}</text>`;
		b += tsp(790, y + 14, [l], 21, th.ink, 700) + tsp(790, y + 40, [d], 17, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// Two badges side by side with points (cards: [name, tag, points[]]).
function badgePair(title, sub, cards, source, theme = 'dark') {
	const th = THEMES[theme];
	let b = '';
	cards.forEach(([name, tag, pts], i) => {
		const x = 60 + i * 550, y = 160, w = 530, h = 410, col = i ? th.b : C.green;
		b += `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="22" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
		b += `<g transform="translate(${x + 40},${y + 34})"><path d="M48 0 L96 18 V54 C96 86 74 108 48 118 C22 108 0 86 0 54 V18 Z" fill="${col}"/>${icon('check', 24, 34, 48, '#fff', 3)}</g>`;
		b += tsp(x + 160, y + 78, [name], 30, th.ink, 800) + tsp(x + 160, y + 112, [tag], 19, th.mute);
		pts.forEach((p, k) => { const py = y + 190 + k * 52; b += `<circle cx="${x + 52}" cy="${py - 7}" r="7" fill="${col}"/>` + tsp(x + 74, py, wrap(p, 40).slice(0, 1), 20, th.ink); });
	});
	return frameT(th, title, sub, b, source);
}

// Phone mock-ups showing ad formats (formats: [name, kind]) kind: photo|video|carousel|story|grid.
function phones(title, sub, formats, source, theme = 'light') {
	const th = THEMES[theme];
	const n = formats.length, pw = 170, ph = 330, gap = (W - 120 - n * pw) / (n - 1), y = 170;
	let b = '';
	formats.forEach(([name, kind], i) => {
		const x = 60 + i * (pw + gap), sx = x + 12, sy = y + 34, sw = pw - 24, sh = ph - 68;
		b += `<rect x="${x}" y="${y}" width="${pw}" height="${ph}" rx="26" fill="${th.ink}" fill-opacity="${theme === 'dark' ? 0.25 : 0.9}"/><rect x="${sx}" y="${sy}" width="${sw}" height="${sh}" rx="8" fill="${th.card}"/>`;
		b += `<rect x="${x + pw / 2 - 26}" y="${y + 14}" width="52" height="8" rx="4" fill="${th.bg}"/>`;
		const ac = i % 2 ? th.b : th.a;
		if (kind === 'photo') b += `<rect x="${sx + 10}" y="${sy + 30}" width="${sw - 20}" height="${sw - 20}" rx="6" fill="${ac}" fill-opacity=".25"/>${icon('home', sx + sw / 2 - 20, sy + 30 + (sw - 20) / 2 - 20, 40, ac)}`;
		if (kind === 'video') b += `<rect x="${sx + 10}" y="${sy + 30}" width="${sw - 20}" height="${sw + 20}" rx="6" fill="${ac}" fill-opacity=".25"/><circle cx="${sx + sw / 2}" cy="${sy + 30 + (sw + 20) / 2}" r="26" fill="${ac}"/><path d="M ${sx + sw / 2 - 8} ${sy + 30 + (sw + 20) / 2 - 12} l 20 12 l -20 12 z" fill="#fff"/>`;
		if (kind === 'carousel') b += [0, 1, 2].map((k) => `<rect x="${sx + 10 + k * 26}" y="${sy + 30 + k * 6}" width="${sw - 72}" height="${sw - 32}" rx="6" fill="${ac}" fill-opacity="${0.18 + k * 0.18}" stroke="${ac}" stroke-width="2"/>`).join('') + [0, 1, 2].map((k) => `<circle cx="${sx + sw / 2 - 14 + k * 14}" cy="${sy + sw + 30}" r="4" fill="${k === 0 ? ac : th.line}"/>`).join('');
		if (kind === 'story') b += `<rect x="${sx}" y="${sy}" width="${sw}" height="${sh}" rx="8" fill="${ac}" fill-opacity=".85"/><rect x="${sx + 10}" y="${sy + 10}" width="${sw - 20}" height="4" rx="2" fill="#fff"/><rect x="${sx + 20}" y="${sy + sh - 52}" width="${sw - 40}" height="34" rx="17" fill="#fff"/><text x="${sx + sw / 2}" y="${sy + sh - 30}" text-anchor="middle" font-family="${F}" font-size="14" font-weight="700" fill="${ac}">Swipe up</text>`;
		if (kind === 'grid') b += [0, 1, 2, 3, 4, 5, 6, 7, 8].map((k) => `<rect x="${sx + 8 + (k % 3) * ((sw - 16) / 3)}" y="${sy + 30 + Math.floor(k / 3) * ((sw - 16) / 3)}" width="${(sw - 16) / 3 - 4}" height="${(sw - 16) / 3 - 4}" rx="3" fill="${k === 4 ? th.b : ac}" fill-opacity="${k === 4 ? 0.9 : 0.2}"/>`).join('');
		if (kind !== 'story') b += `<rect x="${sx + 10}" y="${sy + sh - 44}" width="${sw - 40}" height="8" rx="4" fill="${th.mute}" fill-opacity=".35"/><rect x="${sx + 10}" y="${sy + sh - 28}" width="${sw - 70}" height="8" rx="4" fill="${th.mute}" fill-opacity=".25"/>`;
		b += tsp(x + pw / 2, y + ph + 40, [name], 21, th.ink, 700, 'middle');
	});
	return frameT(th, title, sub, b, source);
}

// Hub and spokes (items: [icon, head, desc]).
function spokes(title, sub, centre, items, source, theme = 'light') {
	const th = THEMES[theme];
	const cx = 600, cy = 395, n = items.length, rx = 400, ry = 165;
	let b = '';
	items.forEach(([ic, h, d], i) => {
		const ang = -Math.PI / 2 + i * 2 * Math.PI / n, x = cx + rx * Math.cos(ang), y = cy + ry * Math.sin(ang);
		b += `<line x1="${cx}" y1="${cy}" x2="${x}" y2="${y}" stroke="${th.line}" stroke-width="3"/>`;
	});
	b += `<circle cx="${cx}" cy="${cy}" r="86" fill="${th.a}"/><circle cx="${cx}" cy="${cy}" r="100" fill="none" stroke="${th.a}" stroke-opacity=".3" stroke-width="8"/>${tsp(cx, cy - 4, wrap(centre, 12).slice(0, 2), 22, '#fff', 800, 'middle')}`;
	items.forEach(([ic, h, d], i) => {
		const ang = -Math.PI / 2 + i * 2 * Math.PI / n, x = cx + rx * Math.cos(ang), y = cy + ry * Math.sin(ang), w = 250, hh = 84;
		b += `<rect x="${x - w / 2}" y="${y - hh / 2}" width="${w}" height="${hh}" rx="16" fill="${th.card}" stroke="${i % 2 ? th.b : th.a}" stroke-width="2.5"/>`;
		b += `<circle cx="${x - w / 2 + 34}" cy="${y}" r="22" fill="${i % 2 ? th.b : th.a}"/>${icon(ic, x - w / 2 + 22, y - 12, 24, '#fff', 2.2)}`;
		b += tsp(x - w / 2 + 66, y - 6, [h], 19, th.ink, 700) + tsp(x - w / 2 + 66, y + 18, wrap(d, 20).slice(0, 1), 15, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// Flowchart with one yes/no decision (steps before, question, yes path, no path).
function flowchart(title, sub, before, question, yes, no, source, theme = 'light') {
	const th = THEMES[theme];
	const box = (x, y, w, h, t, fill, ink) => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="14" fill="${fill}" stroke="${th.line}" stroke-width="2"/>${tsp(x + w / 2, y + h / 2 + (wrap(t, Math.floor(w / 11)).length > 1 ? -4 : 7), wrap(t, Math.floor(w / 11)).slice(0, 2), 18, ink, 700, 'middle')}`;
	const arrow = (x1, y1, x2, y2) => `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${th.a}" stroke-width="3"/><circle cx="${x2}" cy="${y2}" r="5" fill="${th.a}"/>`;
	let b = '', x = 60;
	const y = 300, w = 190, h = 80;
	before.forEach((t, i) => { b += box(x, y, w, h, t, th.card, th.ink); b += arrow(x + w, y + h / 2, x + w + 40, y + h / 2); x += w + 40; });
	const dx = x + 90, dy = y + h / 2;
	b += `<path d="M ${dx} ${dy - 90} L ${dx + 100} ${dy} L ${dx} ${dy + 90} L ${dx - 100} ${dy} Z" fill="${th.b}"/>${tsp(dx, dy - 4, wrap(question, 14).slice(0, 3), 17, '#fff', 700, 'middle')}`;
	b += `<line x1="${x}" y1="${dy}" x2="${dx - 100}" y2="${dy}" stroke="${th.a}" stroke-width="3"/>`;
	const ex = dx + 150;
	b += arrow(dx, dy - 90, dx, 200) + arrow(dx, 200, ex, 200) + `<text x="${dx + 12}" y="${dy - 100}" font-family="${F}" font-size="18" font-weight="800" fill="${C.green}">Yes</text>`;
	b += arrow(dx, dy + 90, dx, 500) + arrow(dx, 500, ex, 500) + `<text x="${dx + 12}" y="${dy + 116}" font-family="${F}" font-size="18" font-weight="800" fill="${th.bDk}">No</text>`;
	yes.forEach((t, i) => { b += box(ex + i * 0, 160 + i * 0, W - 60 - ex, 80, t, th.card, th.ink); });
	no.forEach((t, i) => { b += box(ex, 460, W - 60 - ex, 80, t, th.b, '#fff'); });
	return frameT(th, title, sub, b, source);
}

// Stylized neighborhood map with labelled pins (pins: [label, icon]).
function mapPins(title, sub, pins, source, theme = 'light') {
	const th = THEMES[theme];
	const mx = 60, my = 160, mw = W - 120, mh = 410;
	let b = `<rect x="${mx}" y="${my}" width="${mw}" height="${mh}" rx="20" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	// Streets and blocks.
	for (let i = 1; i < 6; i++) b += `<rect x="${mx + i * mw / 6 - 7}" y="${my}" width="14" height="${mh}" fill="${th.line}" opacity=".7"/>`;
	for (let i = 1; i < 3; i++) b += `<rect x="${mx}" y="${my + i * mh / 3 - 7}" width="${mw}" height="14" fill="${th.line}" opacity=".7"/>`;
	b += `<path d="M ${mx} ${my + mh - 60} C ${mx + 300} ${my + mh - 160}, ${mx + 700} ${my + 60}, ${mx + mw} ${my + 120}" fill="none" stroke="${th.a}" stroke-opacity=".25" stroke-width="22"/>`;
	const spots = [[0.12, 0.25], [0.36, 0.18], [0.55, 0.3], [0.92, 0.08], [0.2, 0.62], [0.47, 0.72], [0.7, 0.62], [0.9, 0.78], [0.08, 0.88], [0.56, 0.95]];
	pins.forEach(([l, ic], i) => {
		const [fx, fy] = spots[i % spots.length], x = mx + 40 + fx * (mw - 80), y = my + 50 + fy * (mh - 110), col = i % 2 ? th.b : th.a;
		b += `<path d="M ${x} ${y + 34} C ${x - 6} ${y + 20}, ${x - 24} ${y + 6}, ${x - 24} ${y - 12} A 24 24 0 1 1 ${x + 24} ${y - 12} C ${x + 24} ${y + 6}, ${x + 6} ${y + 20}, ${x} ${y + 34} Z" fill="${col}"/>${icon(ic, x - 11, y - 24, 22, '#fff', 2.2)}`;
		const w = l.length * 9.6 + 22, lx = fx > 0.72 ? x - 30 - w : x + 30;
		b += `<rect x="${lx}" y="${y - 30}" width="${w}" height="32" rx="16" fill="${th.bg}" stroke="${col}" stroke-width="2"/><text x="${lx + w / 2}" y="${y - 8}" text-anchor="middle" font-family="${F}" font-size="16" font-weight="700" fill="${th.ink}">${esc(l)}</text>`;
	});
	return frameT(th, title, sub, b, source);
}

// A paper form mock-up with ticked fields (fields: [label]) and side notes (notes: [head, desc]).
function formMock(title, sub, formTitle, fields, notes, source, theme = 'light') {
	const th = THEMES[theme];
	const fx = 90, fy = 160, fw = 520, fh = 420;
	let b = `<rect x="${fx + 10}" y="${fy + 10}" width="${fw}" height="${fh}" rx="10" fill="${th.line}"/><rect x="${fx}" y="${fy}" width="${fw}" height="${fh}" rx="10" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	b += `<rect x="${fx}" y="${fy}" width="${fw}" height="54" rx="10" fill="${th.a}"/><rect x="${fx}" y="${fy + 40}" width="${fw}" height="14" fill="${th.a}"/>${tsp(fx + 24, fy + 35, [formTitle], 21, '#fff', 800)}`;
	fields.forEach((f, i) => {
		const y = fy + 86 + i * 48;
		b += `<rect x="${fx + 24}" y="${y - 18}" width="26" height="26" rx="5" fill="${th.card}" stroke="${th.a}" stroke-width="2"/>${icon('check', fx + 26, y - 16, 22, th.b, 3)}`;
		b += tsp(fx + 64, y + 2, [f], 18, th.ink, 600) + `<line x1="${fx + 64}" y1="${y + 14}" x2="${fx + fw - 24}" y2="${y + 14}" stroke="${th.line}" stroke-width="1.5"/>`;
	});
	b += `<path d="M ${fx + 300} ${fy + fh - 40} c 20 -30 40 10 60 -10 s 30 -20 50 0" fill="none" stroke="${th.a}" stroke-width="3" stroke-linecap="round"/>${tsp(fx + 24, fy + fh - 30, ['Signature and date'], 16, th.mute)}`;
	notes.forEach(([h, d], i) => {
		const y = 185 + i * 128;
		b += `<rect x="680" y="${y}" width="460" height="108" rx="16" fill="${th.card}" stroke="${th.line}" stroke-width="2"/><rect x="680" y="${y}" width="8" height="108" rx="4" fill="${i % 2 ? th.b : th.a}"/>`;
		b += tsp(708, y + 38, [h], 21, th.ink, 700) + tsp(708, y + 66, wrap(d, 40).slice(0, 2), 17, th.mute);
	});
	return frameT(th, title, sub, b, source);
}

// House outline with callouts around it (labels: [head, desc]), 8 expected.
function houseCallouts(title, sub, labels, source, theme = 'light') {
	const th = THEMES[theme];
	const cx = 600, base = 520;
	let b = `<path d="M ${cx - 170} ${base} V ${base - 170} L ${cx} ${base - 290} L ${cx + 170} ${base - 170} V ${base} Z" fill="${th.a}" fill-opacity=".12" stroke="${th.a}" stroke-width="4" stroke-linejoin="round"/>`;
	b += `<path d="M ${cx - 200} ${base - 150} L ${cx} ${base - 310} L ${cx + 200} ${base - 150}" fill="none" stroke="${th.b}" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>`;
	b += `<rect x="${cx - 40}" y="${base - 110}" width="80" height="110" rx="6" fill="${th.a}"/><rect x="${cx - 140}" y="${base - 140}" width="70" height="60" rx="6" fill="${th.card}" stroke="${th.a}" stroke-width="3"/><rect x="${cx + 70}" y="${base - 140}" width="70" height="60" rx="6" fill="${th.card}" stroke="${th.a}" stroke-width="3"/><line x1="${cx - 260}" y1="${base}" x2="${cx + 260}" y2="${base}" stroke="${th.line}" stroke-width="6" stroke-linecap="round"/>`;
	const pos = [[80, 175], [80, 290], [80, 405], [80, 520], [860, 175], [860, 290], [860, 405], [860, 520]];
	const anchors = [[cx - 150, base - 200], [cx - 120, base - 110], [cx - 40, base - 60], [cx - 200, base], [cx + 120, base - 230], [cx + 105, base - 110], [cx + 40, base - 60], [cx + 200, base]];
	labels.forEach(([h, d], i) => {
		const [x, y] = pos[i], [ax, ay] = anchors[i], right = i >= 4;
		b += `<line x1="${right ? x : x + 260}" y1="${y + 26}" x2="${ax}" y2="${ay}" stroke="${th.line}" stroke-width="2" stroke-dasharray="4 5"/><circle cx="${ax}" cy="${ay}" r="6" fill="${th.b}"/>`;
		b += `<rect x="${x}" y="${y}" width="260" height="72" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>` + tsp(x + 16, y + 30, [h], 19, th.ink, 700) + tsp(x + 16, y + 54, wrap(d, 28).slice(0, 1), 15, th.mute);
	});
	return frameT(th, title, sub, b, source);
}


// ---- Graphic kit 4 (2026-11): hierarchy, versus, search mock-up, pin board, calendar, pipeline, scorecard, email mock-up, journey map, price columns. ----
// Top-down hierarchy (root, branches: [label, [children]]).
function hierarchy(title, sub, root, branches, foot, source, theme = 'light') {
	const th = THEMES[theme];
	const n = branches.length, colW = (W - 120) / n;
	let b = `<rect x="${W / 2 - 110}" y="160" width="220" height="56" rx="14" fill="${th.a}"/>${tsp(W / 2, 195, [root], 22, '#fff', 800, 'middle')}`;
	branches.forEach(([lab, kids], i) => {
		const cx = 60 + colW * i + colW / 2;
		b += `<path d="M ${W / 2} 216 V 240 H ${cx} V 262" fill="none" stroke="${th.line}" stroke-width="3"/>`;
		b += `<rect x="${cx - colW / 2 + 14}" y="262" width="${colW - 28}" height="52" rx="12" fill="${th.card}" stroke="${th.b}" stroke-width="3"/>${tsp(cx, 295, [lab], 19, th.ink, 700, 'middle')}`;
		kids.forEach((k, j) => {
			const y = 344 + j * 58;
			b += `<line x1="${cx}" y1="${y - 30}" x2="${cx}" y2="${y}" stroke="${th.line}" stroke-width="2"/>`;
			b += `<rect x="${cx - colW / 2 + 28}" y="${y}" width="${colW - 56}" height="40" rx="10" fill="${j === kids.length - 1 ? th.b : th.a}" fill-opacity="${j === kids.length - 1 ? 0.18 : 0.1}"/>${tsp(cx, y + 26, [k], 16, th.ink, 600, 'middle')}`;
		});
	});
	if (foot) b += tsp(W / 2, H - 70, [foot], 18, th.mute, 400, 'middle');
	return frameT(th, title, sub, b, source);
}

// Versus split: two columns with a VS medallion (sides: [name, icon, rows[]]).
function versus(title, sub, left, right, source, theme = 'dark') {
	const th = THEMES[theme];
	let b = '';
	[left, right].forEach(([name, ic, rows], i) => {
		const x = i ? 640 : 60, w = 500, col = i ? th.b : th.a;
		b += `<rect x="${x}" y="160" width="${w}" height="415" rx="22" fill="${th.card}" stroke="${col}" stroke-width="3"/>`;
		b += `<circle cx="${x + 54}" cy="212" r="30" fill="${col}"/>${icon(ic, x + 38, 196, 32, '#fff', 2.2)}${tsp(x + 100, 222, [name], 27, th.ink, 800)}`;
		rows.forEach((r, k) => { const y = 286 + k * 56; b += `<line x1="${x + 24}" y1="${y - 30}" x2="${x + w - 24}" y2="${y - 30}" stroke="${th.line}" stroke-width="1.5"/>` + icon('check', x + 26, y - 18, 22, col, 3) + tsp(x + 60, y, [r], 19, th.ink); });
	});
	b += `<circle cx="600" cy="368" r="40" fill="${th.bg}" stroke="${th.b}" stroke-width="4"/><text x="600" y="378" text-anchor="middle" font-family="${F}" font-size="26" font-weight="900" fill="${th.b}">VS</text>`;
	return frameT(th, title, sub, b, source);
}

// Search results mock-up with numbered callouts (query, callouts: [head, desc]).
function serpMock(title, sub, query, callouts, source, theme = 'light') {
	const th = THEMES[theme];
	const x = 60, w = 600;
	let b = `<rect x="${x}" y="155" width="${w}" height="430" rx="18" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	b += `<rect x="${x + 20}" y="172" width="${w - 40}" height="42" rx="21" fill="${th.bg}" stroke="${th.line}" stroke-width="2"/>${icon('search', x + 34, 181, 22, th.mute, 2)}${tsp(x + 66, 199, [query], 18, th.ink)}`;
	// Ad row with badge.
	b += `<rect x="${x + 20}" y="228" width="${w - 40}" height="64" rx="10" fill="${th.a}" fill-opacity=".08"/><circle cx="${x + 50}" cy="260" r="16" fill="${C.green}"/>${icon('check', x + 41, 251, 18, '#fff', 3)}${tsp(x + 78, 254, ['Sponsored · Local Services Ad (example)'], 15, th.mute)}${tsp(x + 78, 278, ['Example Electric Co.  ★★★★★'], 18, th.ink, 700)}`;
	// Map pack.
	b += `<rect x="${x + 20}" y="304" width="${w - 40}" height="96" rx="10" fill="${th.line}" opacity=".55"/>`;
	[[150, 340], [330, 322], [470, 362]].forEach(([px, py], i) => { b += `<circle cx="${x + px}" cy="${py}" r="12" fill="${i ? th.a : th.b}"/>`; });
	[0, 1, 2].forEach((k) => { b += `<rect x="${x + 20}" y="${408 + k * 40}" width="${w - 40}" height="34" rx="8" fill="${th.bg}"/>${tsp(x + 36, 430 + k * 40, [['Your business · Open 24 hours', 'Competitor A', 'Competitor B'][k]], 16, k ? th.mute : th.ink, k ? 400 : 700)}`; });
	b += `<rect x="${x + 20}" y="532" width="${w - 140}" height="12" rx="6" fill="${th.a}" fill-opacity=".35"/><rect x="${x + 20}" y="552" width="${w - 220}" height="10" rx="5" fill="${th.mute}" fill-opacity=".3"/>`;
	const marks = [[x + w - 20, 252], [x + w - 20, 352], [x + w - 20, 424], [x + w - 20, 540]];
	callouts.forEach(([h, d], i) => {
		const y = 175 + i * 102, [mx, my] = marks[i] || marks[0];
		b += `<path d="M ${mx} ${my} C ${mx + 40} ${my}, 690 ${y + 30}, 720 ${y + 30}" fill="none" stroke="${th.b}" stroke-width="2" stroke-dasharray="5 5"/><circle cx="${mx}" cy="${my}" r="6" fill="${th.b}"/>`;
		b += `<rect x="720" y="${y}" width="420" height="84" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>${tsp(740, y + 34, [h], 20, th.ink, 700)}${tsp(740, y + 60, wrap(d, 44).slice(0, 1), 16, th.mute)}`;
	});
	return frameT(th, title, sub, b, source);
}

// Pin board: masonry cards of different heights (cards: [head, desc, icon]).
function pinBoard(title, sub, cards, source, theme = 'warm') {
	const th = THEMES[theme];
	const cols = 4, cw = (W - 120 - 3 * 18) / 4, heights = [150, 200, 120, 180, 170, 130, 200, 140];
	const yCol = [160, 160, 160, 160];
	let b = '';
	cards.forEach(([h, d, ic], i) => {
		const c = i % cols, x = 60 + c * (cw + 18), y = yCol[c], hh = Math.min(heights[i % heights.length], H - 80 - y - 10);
		if (hh < 90) return;
		const col = [th.a, th.b, C.green, th.a][i % 4];
		b += `<rect x="${x}" y="${y}" width="${cw}" height="${hh}" rx="18" fill="${th.card}" stroke="${th.line}" stroke-width="2"/><rect x="${x}" y="${y}" width="${cw}" height="${Math.min(60, hh / 2)}" rx="18" fill="${col}" fill-opacity=".16"/>`;
		b += icon(ic, x + 18, y + 16, 28, col, 2.2) + tsp(x + 56, y + 38, [h], 19, th.ink, 700);
		b += tsp(x + 18, y + 86, wrap(d, Math.floor(cw / 10.5)).slice(0, Math.max(1, Math.floor((hh - 70) / 22))), 16, th.mute);
		yCol[c] = y + hh + 16;
	});
	return frameT(th, title, sub, b, source);
}

// Month calendar with colored pillar chips (pillars: [name, color index]); plan: {day: [pillarIndex, label]}.
function calendarGrid(title, sub, monthLabel, pillars, plan, source, theme = 'light') {
	const th = THEMES[theme];
	const cols = [th.a, th.b, C.green, '#7c3aed'];
	const gx = 60, gy = 190, cw = 112, ch = 64;
	let b = tsp(gx, 180, [monthLabel], 20, th.ink, 800);
	['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach((d, i) => { b += tsp(gx + i * cw + cw / 2, gy + 18, [d], 15, th.mute, 700, 'middle'); });
	for (let d = 1; d <= 28; d++) {
		const i = d - 1, x = gx + (i % 7) * cw, y = gy + 30 + Math.floor(i / 7) * ch;
		b += `<rect x="${x + 3}" y="${y + 3}" width="${cw - 6}" height="${ch - 6}" rx="8" fill="${th.card}" stroke="${th.line}" stroke-width="1.5"/>${tsp(x + 12, y + 22, [String(d)], 13, th.mute, 700)}`;
		if (plan[d]) { const [p, l] = plan[d]; b += `<rect x="${x + 8}" y="${y + 30}" width="${cw - 16}" height="24" rx="12" fill="${cols[p]}"/>${tsp(x + cw / 2, y + 47, [l], 13, '#fff', 700, 'middle')}`; }
	}
	pillars.forEach(([name, desc], i) => {
		const y = 200 + i * 92;
		b += `<rect x="870" y="${y}" width="270" height="78" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/><rect x="870" y="${y}" width="10" height="78" rx="5" fill="${cols[i]}"/>${tsp(896, y + 32, [name], 19, th.ink, 700)}${tsp(896, y + 56, [desc], 15, th.mute)}`;
	});
	return frameT(th, title, sub, b, source);
}

// Pipeline: sources -> processing -> check gate -> outputs.
function pipeline(title, sub, sources, middle, gate, outputs, source, theme = 'dark') {
	const th = THEMES[theme];
	let b = '';
	sources.forEach((s, i) => { const y = 180 + i * 70; b += `<rect x="60" y="${y}" width="250" height="54" rx="12" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>${icon('doc', 76, y + 15, 24, th.a, 2)}${tsp(110, y + 34, [s], 17, th.ink, 600)}<path d="M 310 ${y + 27} C 360 ${y + 27}, 360 380, 400 380" fill="none" stroke="${th.a}" stroke-width="3" stroke-opacity=".6"/>`; });
	b += `<rect x="400" y="320" width="200" height="120" rx="22" fill="${th.a}"/>${icon('bolt', 480, 334, 40, '#fff', 2.2)}${tsp(500, 404, wrap(middle, 16).slice(0, 2), 19, '#fff', 800, 'middle')}`;
	b += `<line x1="600" y1="380" x2="660" y2="380" stroke="${th.b}" stroke-width="4"/><path d="M 660 320 L 740 380 L 660 440 Z" fill="${th.b}"/>${icon('check', 674, 368, 24, '#fff', 3)}${tsp(700, 470, wrap(gate, 18).slice(0, 2), 16, th.ink, 700, 'middle')}`;
	outputs.forEach((o, i) => { const y = 220 + i * 84; b += `<path d="M 740 380 C 780 380, 780 ${y + 30}, 820 ${y + 30}" fill="none" stroke="${th.b}" stroke-width="3" stroke-opacity=".7"/><rect x="820" y="${y}" width="320" height="60" rx="14" fill="${th.card}" stroke="${th.b}" stroke-width="2"/>${tsp(840, y + 37, [o], 18, th.ink, 700)}`; });
	return frameT(th, title, sub, b, source);
}

// Fill-in scorecard (questions vs blank columns).
function scorecard(title, sub, cols, rows, source, theme = 'light') {
	const th = THEMES[theme];
	const x = 60, y = 160, w = W - 120, c0 = 640, cw = (w - c0) / cols.length, rh = (H - 160 - 85) / (rows.length + 1);
	let b = `<rect x="${x}" y="${y}" width="${w}" height="${rh * (rows.length + 1)}" rx="14" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	b += `<rect x="${x}" y="${y}" width="${w}" height="${rh}" rx="14" fill="${th.a}"/><rect x="${x}" y="${y + rh - 14}" width="${w}" height="14" fill="${th.a}"/>${tsp(x + 24, y + rh / 2 + 7, ['Question to ask'], 19, '#fff', 800)}`;
	cols.forEach((c, i) => { b += tsp(x + c0 + cw * i + cw / 2, y + rh / 2 + 7, [c], 18, '#fff', 800, 'middle'); });
	rows.forEach((r, k) => {
		const yy = y + rh * (k + 1);
		if (k % 2) b += `<rect x="${x + 1}" y="${yy}" width="${w - 2}" height="${rh}" fill="${th.bg}"/>`;
		b += tsp(x + 24, yy + rh / 2 + 6, [r], 17, th.ink, 600);
		cols.forEach((_, i) => { const cx = x + c0 + cw * i + cw / 2; b += `<rect x="${cx - 12}" y="${yy + rh / 2 - 12}" width="24" height="24" rx="5" fill="none" stroke="${th.mute}" stroke-width="2"/>`; });
	});
	return frameT(th, title, sub, b, source);
}

// Email mock-up with stamps and callouts (subject, stamps: [label], notes: [head, desc]).
function emailMock(title, sub, from, subject, stamps, notes, source, theme = 'warm') {
	const th = THEMES[theme];
	const x = 60, y = 160, w = 560, h = 420;
	let b = `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="16" fill="${th.card}" stroke="${th.line}" stroke-width="2"/>`;
	b += `<rect x="${x}" y="${y}" width="${w}" height="92" rx="16" fill="${th.a}" fill-opacity=".1"/><circle cx="${x + 40}" cy="${y + 46}" r="20" fill="${th.a}"/>${icon('mail', x + 29, y + 35, 22, '#fff', 2)}`;
	b += tsp(x + 74, y + 40, [from], 16, th.mute) + tsp(x + 74, y + 68, [subject], 19, th.ink, 800);
	[0, 1, 2, 3, 4, 5].forEach((k) => { b += `<rect x="${x + 30}" y="${y + 120 + k * 26}" width="${w - 60 - (k % 3) * 70}" height="10" rx="5" fill="${th.mute}" fill-opacity=".28"/>`; });
	b += `<rect x="${x + 30}" y="${y + 290}" width="200" height="40" rx="20" fill="${th.b}"/>${tsp(x + 130, y + 316, ['Book a review'], 16, '#fff', 700, 'middle')}`;
	b += tsp(x + 30, y + h - 28, ['Firm name · postal address · Unsubscribe'], 13, th.mute);
	stamps.forEach((s, i) => { const sx = x + w - 200 + (i % 2) * -10, sy = y + 140 + i * 74; b += `<g transform="rotate(-8 ${sx + 85} ${sy + 22})"><rect x="${sx}" y="${sy}" width="170" height="44" rx="8" fill="none" stroke="${[C.green, th.b, th.a][i % 3]}" stroke-width="3"/>${tsp(sx + 85, sy + 28, [s], 15, [C.green, th.bDk, th.a][i % 3], 800, 'middle')}</g>`; });
	notes.forEach(([hh, d], i) => { const ny = 175 + i * 132; b += `<rect x="670" y="${ny}" width="470" height="112" rx="16" fill="${th.card}" stroke="${th.line}" stroke-width="2"/><circle cx="700" cy="${ny + 36}" r="16" fill="${[C.green, th.b, th.a][i % 3]}"/><text x="700" y="${ny + 42}" text-anchor="middle" font-family="${F}" font-size="16" font-weight="800" fill="#fff">${i + 1}</text>${tsp(728, ny + 42, [hh], 20, th.ink, 700)}${tsp(700, ny + 74, wrap(d, 48).slice(0, 2), 16, th.mute)}`; });
	return frameT(th, title, sub, b, source);
}

// Journey map: stages across with icon, question row and answer row (stages: [icon, stage, question, answer]).
function journeyMap(title, sub, stages, source, theme = 'light') {
	const th = THEMES[theme];
	const n = stages.length, cw = (W - 200) / n, x0 = 180;
	let b = tsp(60, 300, ['Patient'], 17, th.mute, 800) + tsp(60, 322, ['asks'], 17, th.mute, 800) + tsp(60, 450, ['You'], 17, th.mute, 800) + tsp(60, 472, ['provide'], 17, th.mute, 800);
	b += `<path d="M ${x0} 232 H ${W - 40}" stroke="${th.line}" stroke-width="10" stroke-linecap="round"/>`;
	stages.forEach(([ic, st, q, a], i) => {
		const x = x0 + i * cw, cx = x + cw / 2, hot = i === n - 2;
		b += `<circle cx="${cx}" cy="232" r="30" fill="${hot ? th.b : th.a}"/>${icon(ic, cx - 14, 218, 28, '#fff', 2.2)}${tsp(cx, 190, [st], 18, th.ink, 800, 'middle')}`;
		b += `<rect x="${x + 6}" y="276" width="${cw - 12}" height="110" rx="12" fill="${th.a}" fill-opacity=".08"/>${tsp(cx, 306, wrap(q, 16).slice(0, 3), 15, th.ink, 600, 'middle')}`;
		b += `<rect x="${x + 6}" y="404" width="${cw - 12}" height="140" rx="12" fill="${th.card}" stroke="${hot ? th.b : th.line}" stroke-width="2"/>${tsp(cx, 434, wrap(a, 16).slice(0, 4), 15, th.mute, 400, 'middle')}`;
	});
	return frameT(th, title, sub, b, source);
}

// Price columns (plans: [name, price, period, features[], featured]).
function priceColumns(title, sub, plans, note, source, theme = 'dark') {
	const th = THEMES[theme];
	const n = plans.length, gap = 28, w = (W - 120 - gap * (n - 1)) / n;
	let b = '';
	plans.forEach(([name, price, per, feats, hot], i) => {
		const x = 60 + i * (w + gap), y = hot ? 160 : 180, h = hot ? 400 : 370;
		b += `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="22" fill="${hot ? th.b : th.card}" stroke="${hot ? th.b : th.line}" stroke-width="2"/>`;
		if (typeof hot === 'string') b += `<rect x="${x + w / 2 - 70}" y="${y - 16}" width="140" height="32" rx="16" fill="${th.bg}" stroke="${th.b}" stroke-width="2"/>${tsp(x + w / 2, y + 6, [String(hot)], 14, th.bDk, 800, 'middle')}`;
		const ink = hot ? '#fff' : th.ink, mute = hot ? '#fff' : th.mute;
		b += tsp(x + w / 2, y + 56, [name], 24, ink, 800, 'middle');
		b += `<text x="${x + w / 2}" y="${y + 128}" text-anchor="middle" font-family="${F}" font-size="54" font-weight="900" fill="${hot ? '#fff' : th.a}">${esc(price)}</text>${tsp(x + w / 2, y + 156, [per], 16, mute, 600, 'middle')}`;
		feats.forEach((f, k) => { const fy = y + 200 + k * 36; b += icon('check', x + 26, fy - 16, 20, hot ? '#fff' : C.green, 3) + tsp(x + 56, fy, wrap(f, Math.floor((w - 70) / 9)).slice(0, 1), 16, ink); });
	});
	if (note) b += tsp(W / 2, H - 66, [note], 17, th.mute, 400, 'middle');
	return frameT(th, title, sub, b, source);
}

const SEMRS = 'Source: SEMRS guide';
export const GRAPHICS = {
	'small-business-loan-approval-by-lender': () => hbars('Where small businesses get fully approved', 'Share of loan applicants fully approved, by lender type', [['Small banks', 54, true], ['Large banks', 45], ['Online lenders', 30]], 60, (v) => Math.round(v) + '%', 'Source: Federal Reserve Small Business Credit Survey, 2025 Report on Employer Firms', 'Survey of 7,653 US employer firms, fielded September–November 2024.'),
	'email-marketing-platform-checklist': () => steps('How to choose an email marketing platform', 'Six checks before you commit to a platform', [
		['Set your goal', 'Newsletter, online store or service enquiries'], ['Count your list', 'Contacts and how often you send'], ['Check integrations', 'Website forms, CRM and online store'],
		['Test the editor', 'Build one real email and view it on a phone'], ['Price the next tier', 'What you pay at double your list size'], ['Plan your exit', 'Export contacts with their consent records'],
	], 3, SEMRS),
	'general-liability-insurance-cost-bands': () => hbars('What small businesses pay for general liability', 'Share of Insureon customers by monthly premium (median about $45 a month)', [['Under $30', 22], ['$30 to $60', 41, true], ['Over $60', 37]], 60, (v) => Math.round(v) + '%', 'Source: Insureon, general liability insurance cost, updated March 30, 2026', '"Over $60" is the remaining share. Your premium depends on your industry, location and limits.'),
	'financial-advisor-marketing-plan': () => steps('A 6-step financial advisor marketing plan', 'Each step builds on the one before; compliance review runs through all of them', [
		['Pick a niche', 'One audience you can describe in a sentence'], ['Answer-first website', 'Niche pages, fees, process and credentials'], ['Local SEO and content', 'Business Profile plus one helpful article a week'],
		['Google Ads and leads', 'Tight niche keywords and a matching landing page'], ['Referrals and LinkedIn', 'CPAs, estate attorneys and useful posts'], ['Review and archive', 'Compliance approval and records for every piece'],
	], 3, SEMRS + ' · SEC Marketing Rule 206(4)-1 and FINRA Rule 2210 apply', true),
	'insurance-agency-90-day-marketing-plan': () => steps('A 90-day insurance agency marketing plan', 'Quick wins first, then paid channels, then lead sources you own', [
		['Weeks 1–4: foundations', 'Business Profile, review requests, quote path on every page, call and form tracking'],
		['Weeks 5–8: paid search', 'Google Ads on two products, negative keywords, one niche landing page'],
		['Weeks 9–12: own your leads', 'Weekly coverage articles, referral partners, renewal and review emails'],
	], 3, SEMRS + ' · check state insurance rules and the TCPA before calling or texting'),
	'google-ads-legal-cpc-cpl-2025': () => gbars('Legal clicks and leads cost far more', 'Google Ads averages in the 2025 benchmarks (US campaigns)', [['Cost per click', [5.26, 8.58]], ['Cost per lead', [70.11, 131.63]]], ['All industries', 'Attorneys & legal'], (v) => '$' + v.toFixed(2), 'Source: WordStream by LocaliQ 2025 Google Ads benchmarks (Apr 2024–Mar 2025 data)'),
	'seo-retainer-cost-by-provider': () => hbars('Average monthly SEO retainer by provider', 'Self-reported averages from an industry survey of SEO providers', [['Freelancers', 1348], ['Local SEO', 1557], ['Agencies', 3209, true], ['Consultancies', 3250]], 3600, (v) => '$' + Math.round(v).toLocaleString('en-US'), 'Source: Ahrefs SEO pricing survey', 'Most common single price band: $501–$1,000 a month. 78% of providers charge a monthly retainer.'),
	'google-ads-budget-to-customers': () => funnel('How a Google Ads budget turns into customers', 'Worked example from this guide, not a forecast', [['Monthly budget', '$500'], ['Clicks at $5 each', '100'], ['Leads at a 5% conversion rate', '5'], ['Customers at a 25% close rate', '≈ 1']], 'Example only: your CPC, conversion rate and close rate will differ', 'If one customer is worth more than $500 to you, this campaign is profitable.'),
	'digital-marketing-pricing-models': () => steps('How digital marketing agencies charge', 'Compare quotes by what is delivered each month, not the headline price', [
		['Monthly retainer', 'Ongoing SEO, content or social work for a fixed fee'], ['Project fee', 'A defined deliverable: a website, an audit, a migration'],
		['Hourly', 'Consulting, training or ad-hoc tasks'], ['Percent of ad spend', 'Common for paid ads; check incentives to overspend'],
	], 2, SEMRS),
	'local-services-ads-vs-google-ads-comparison': () => compare('Local Services Ads vs Google Ads', 'Two ways to pay Google for local leads', ['Local Services Ads', 'Google Ads (search)'], [
		['You pay for', 'Leads (calls, messages)', 'Clicks'], ['Placement', 'Top of eligible searches', 'Top and bottom of results'], ['Badge', 'Google Guaranteed / Screened', 'None'],
		['Keyword control', 'Limited', 'Full'], ['Landing page', 'Your LSA profile', 'Any page on your site'], ['Best for', 'Phone-driven local services', 'Any business, precise targeting'],
	], SEMRS + ' · availability depends on category and location'),
	'wcag-pour-principles': () => steps('The four WCAG principles of accessibility', 'Most accessibility fixes map to one of these', [
		['Perceivable', 'Alt text, captions, enough color contrast'], ['Operable', 'Works with a keyboard, visible focus, no traps'],
		['Understandable', 'Clear language, labeled forms, helpful errors'], ['Robust', 'Clean code that works with screen readers'],
	], 2, 'Source: W3C Web Content Accessibility Guidelines (WCAG) 2.2'),
	'ai-content-workflow-human-approval': () => steps('A safe AI content workflow', 'AI drafts; people decide what is true and what gets published', [
		['Brief by a person', 'Audience, goal, keyword, must-have points'], ['AI outline and draft', 'Speed on structure and first wording'], ['Expert fact check', 'Sources, examples, removes filler'],
		['Editor review', 'Voice, links, claims, compliance'], ['Human approval', 'A person signs off before publishing'], ['Measure and update', 'Search Console data, refresh yearly'],
	], 3, SEMRS + ' · how SEMRS OS runs two human approval gates on every job', true),
	'crm-sales-pipeline-stages': () => funnel('A simple small business sales pipeline', 'Set your CRM stages to match how you really sell', [['New lead', 'from forms, calls, ads'], ['Contacted', 'within minutes'], ['Qualified', 'need, budget, timing'], ['Proposal sent', 'quote or estimate'], ['Won', 'customer']], SEMRS, 'Track the source of every lead so you can see which marketing brings revenue.'),
	'google-local-ranking-factors': () => venn('How Google ranks local businesses', 'Google names three main factors for local results', [['Relevance', 'Categories, services and content that match the search'], ['Distance', 'How close you are to the searcher; not something you control'], ['Prominence', 'Reviews, links, mentions and how well known you are']], 'Map pack', 'Source: Google Business Profile help, "How to improve your local ranking on Google"'),
	'seo-for-small-business-roadmap': () => steps('SEO for small business: a 9-step roadmap', 'Work through the steps in order; results compound over months', [
		['Find searches', 'Service, question, comparison'], ['Service pages', 'One topic per page'], ['On-page basics', 'Titles, headings, links'],
		['Local SEO', 'Profile, reviews, citations'], ['Technical SEO', 'Speed, mobile, sitemap'], ['Helpful content', 'Answer real questions'],
		['Earn links', 'Partners, press, resources'], ['AI search', 'Clear answers, schema'], ['Measure', 'Leads, not just rankings'],
	], 3, SEMRS, true),
	'personal-injury-lawyer-advertising-plan': () => steps('A personal injury lawyer advertising plan', 'Channels closest to the moment of need come first', [
		['Profile and reviews', 'Business Profile complete, review requests after every case'], ['Call tracking and intake', 'One number per channel, fast friendly answers'], ['Local Services Ads', 'Pay per lead with the Google Screened badge'],
		['Google search ads', 'One case type per ad group, matching pages'], ['SEO and content', 'Case-type pages and real client questions'], ['Bar rules review', 'No promised results; keep a copy of every ad'],
	], 3, SEMRS + ' · state bar advertising rules apply (ABA Model Rules 7.1–7.3)', true),
	'google-ads-first-campaign-steps': () => steps('Your first Google Ads campaign in 8 steps', 'Track leads first, then start small and focused', [
		['Tracking first', 'Calls, forms, bookings'], ['Search only', 'Display Network off'], ['Set location', 'People in your area'], ['10–20 keywords', 'Clear buying intent'],
		['Negatives', 'jobs, free, DIY, course'], ['Three ads', 'Service, area, next step'], ['Matching page', 'One service per page'], ['Steady budget', 'At least one month'],
	], 4, SEMRS),
	'business-credit-card-ad-spend-checks': () => steps('Choosing a business credit card for ad spend', 'Six checks before you apply', [
		['Total your ad spend', 'Google, Meta, LinkedIn and others each month'], ['Pay in full?', 'If not, a low rate beats any reward'], ['Bonus categories', 'Do your ad platforms count as advertising?'],
		['Fee vs rewards', 'At your real spend, not the cap'], ['Yearly caps', 'Bonus rates often stop at a set amount'], ['Personal guarantee', 'You may be liable for the debt'],
	], 3, SEMRS + ' · general information, not financial advice', true),
	'dental-marketing-new-patients': () => steps('8 ways dentists win new patients', 'Fix the free basics first, then add paid search', [
		['Profile', 'Dentist category, hours, insurance'], ['Reviews', 'Ask every happy patient'], ['Treatments', 'One page per treatment'], ['Local SEO', 'Answer real questions'],
		['Google Ads', 'Implants, aligners, emergencies'], ['Easy booking', 'Phone on top, online booking'], ['Reminders', 'Email and text with consent'], ['Social posts', 'No patient data without consent'],
	], 4, SEMRS + ' · HIPAA and the FTC review rule apply'),
	'website-design-cost-packages': () => hbars('Website design cost: one agency’s published prices', 'One-time SEMRS website packages, November 2026', [['Starter, 5 pages', 399], ['Business, 12 pages', 799, true], ['Growth, 25 pages', 1499], ['E-commerce Starter', 1799]], 2000, (v) => '$' + Math.round(v).toLocaleString('en-US'), 'Source: SEMRS pricing page (semrs.com/pricing), November 2026'),
	'generative-engine-optimization-signals': () => venn('What helps a page get cited in AI answers', 'Generative engine optimization builds on SEO basics', [['Findable', 'Crawlable, indexed, eligible for snippets'], ['Clear', 'Direct answers, specific facts, good headings'], ['Trusted', 'Sources, real authors, reviews, mentions']], 'Cited', SEMRS + ' · Google: no extra requirements for AI Overviews'),
	'website-builders-wordpress-vs-hosted': () => compare('WordPress vs hosted website builders', 'Wix, Squarespace and Shopify are hosted; WordPress runs on your own hosting', ['WordPress', 'Hosted builders'], [
		['Ownership', 'You own the files', 'Lives on the platform'], ['Upkeep', 'You or a care plan', 'Included in the plan'], ['Flexibility', 'Very high', 'Medium (stores: high)'],
		['SEO control', 'Full', 'Basics covered'], ['Monthly cost', 'Hosting, domain, plugins', 'Plan, apps, fees'], ['Moving later', 'Easy', 'Usually a rebuild'],
	], SEMRS + ' · prices and features change; check each vendor'),
	'google-ads-agency-questions': () => steps('9 questions to ask a Google Ads agency', 'Ask them before you sign anything', [
		['Account owner?', 'Yours, with admin access'], ['Lead tracking?', 'Calls, forms, bookings'], ['Fees in dollars?', 'Setup and monthly, in writing'],
		['Contract length?', 'Monthly terms with notice'], ['Who manages it?', 'Experience and workload'], ['First 90 days?', 'A clear written plan'],
		['Monthly report?', 'Cost per lead, next steps'], ['Recommendations?', 'Reviewed, not auto-applied'], ['Examples?', 'Real, checkable results'],
	], 3, SEMRS, true),
	'marketing-automation-first-flows': () => steps('Marketing automations to set up first', 'Simple flows that stop missed follow-ups', [
		['Welcome series', '3–5 emails over two weeks'], ['Enquiry follow-up', 'Instant reply, team reminder'], ['Review request', 'A day after the job'],
		['Re-booking reminder', 'When a service is due again'], ['Win-back email', 'For customers who went quiet'], ['Review monthly', 'Opens, bookings, unsubscribes'],
	], 3, SEMRS + ' · follow CAN-SPAM and get consent for texts', true),
	'keyword-research-six-steps': () => steps('Keyword research in 6 steps', 'A simple process for beginners, with free tools', [
		['Seed topics', 'Services, places and customer questions'], ['Expand ideas', 'Search suggestions, Search Console, Keyword Planner'], ['Check intent', 'Learn, compare, buy or local'],
		['Judge difficulty', 'Who ranks now? Pick specific phrases'], ['Map to pages', 'One main keyword per page'], ['Track and update', 'Search Console every month'],
	], 3, SEMRS, true),
	'line-of-credit-vs-term-loan': () => compare('Line of credit vs term loan', 'Two ways to fund a marketing budget', ['Line of credit', 'Term loan'], [
		['You get', 'Draw as needed', 'One lump sum'], ['Interest on', 'What you draw', 'The full amount'], ['Repayment', 'Flexible, reusable', 'Fixed payments'],
		['Rate', 'Often variable', 'Often fixed'], ['Best for', 'Seasonal ads, cash flow', 'A website or launch'], ['Watch for', 'Draw and annual fees', 'Origination fees'],
	], SEMRS + ' · general information, not financial advice'),
	'med-spa-marketing-plan': () => steps('How to grow a med spa', 'Trust first: real results, reviews and clear treatment pages', [
		['Profile', 'Treatments, photos, hours'], ['Treatments', 'One page per treatment'], ['Reviews', 'Ask after follow-up visits'], ['Real results', 'Consented, typical photos'],
		['Targeted ads', 'High-value treatments'], ['Memberships', 'Packages and rebooking'], ['Videos', 'Aftercare and myths'], ['The rules', 'FTC, HIPAA, state boards'],
	], 4, SEMRS, true),
	'merchant-services-fee-on-100-sale': () => hbars('Merchant services: fee on a $100 card sale', 'Standard US rates published by two providers, November 2026', [['Square, in person', 2.75], ['Stripe, in person', 2.75], ['Stripe, online', 3.2], ['Square, online', 3.6, true]], 4, (v) => '$' + v.toFixed(2), 'Source: Square and Stripe pricing pages (2.6% + 15¢, 2.7% + 5¢, 2.9% + 30¢, 3.3% + 30¢)'),
	'wordpress-hosting-checks': () => steps('Checks for choosing WordPress hosting', 'Compare these, not just the first-year price', [
		['Server specs', 'PHP 8.3+, MySQL 8.4+ or MariaDB 10.11+'], ['Speed', 'Location, SSD, caching'], ['Security', 'SSL, firewall, malware scans'], ['Backups', 'Daily, easy to restore'],
		['Support', 'WordPress-savvy help'], ['Staging', 'Test updates safely'], ['Renewal price', 'After the first year'], ['Your accounts', 'Domain and hosting in your name'],
	], 4, 'Source: WordPress.org requirements · SEMRS guide', true),
	'life-insurance-leads-bought-vs-own': () => compare('Bought leads vs your own leads', 'Why agents build their own life insurance lead sources', ['Bought leads', 'Your own leads'], [
		['Speed', 'Immediate', 'Weeks to months'], ['Cost per lead', 'Pay for every lead', 'Falls over time'], ['Exclusivity', 'Often shared', 'Yours alone'],
		['Trust', 'Low at first', 'Higher; they found you'], ['Consent records', 'Depend on the vendor', 'You control them'], ['Best use', 'Volume while you build', 'Steady long-term growth'],
	], SEMRS + ' · follow Do Not Call and TCPA consent rules'),
	'local-seo-services-checklist': () => steps('What local SEO services should include', 'Ask for each of these in writing', [
		['Profile', 'Categories, services, photos, posts'], ['Area pages', 'Real local details, no copies'], ['Reviews', 'Ask every customer, reply to all'], ['Citations', 'Same name, address, phone'],
		['Local schema', 'Structured business details'], ['Tech basics', 'Fast, mobile, crawlable'], ['Local links', 'Partners, sponsors, news'], ['Lead reports', 'Calls and forms, not only ranks'],
	], 4, 'Source: SEMRS guide · Google: no way to pay for a better local ranking', true),
	'google-reviews-process': () => funnel('A simple Google reviews routine', 'Ask every customer the same way, every time', [['Job finished, customer happy', 'same day'], ['Ask in person', 'short script'], ['Send a direct link or QR code', 'text or email'], ['Customer leaves a review', 'no incentives'], ['Reply to every review', 'within days']], SEMRS + ' · Google bans incentives and selective asking'),
	'social-media-platform-choice': () => steps('Choosing a social media management platform', 'Match the tool to your channels and team', [
		['List your channels', 'And how often you post'], ['Who posts?', 'Just you, or a team'], ['Try free first', 'A real week of posts'],
		['Check reports', 'Clicks and leads, not likes'], ['Price the next step', 'One more channel or user'], ['Plan content first', 'Customer questions drive posts'],
	], 3, SEMRS, true),
	'real-estate-advertising-ideas': () => steps('8 real estate advertising ideas', 'Own one area and give people a reason to call', [
		['Profile and reviews', 'Map results for agents'], ['Area guides', 'One page per neighborhood'], ['Valuations', 'Attract future sellers'], ['Video tours', 'Listings and market news'],
		['Search ads', 'Seller and buyer intent'], ['Social ads', 'Housing ad category'], ['Direct mail', 'Same streets monthly'], ['Partners', 'Lenders, builders, events'],
	], 4, SEMRS + ' · Fair Housing Act and housing ad targeting limits apply'),
	'mortgage-marketing-ideas': () => steps('Mortgage marketing ideas', 'Steady referrals and search first, then ads', [
		['Referrals', 'Agents, builders, planners'], ['Profile', 'Reviews and map results'], ['Buyer guides', 'Real first-time questions'], ['Programs', 'FHA, VA, refinance and more'],
		['Search ads', 'High-intent phrases'], ['Client emails', 'Refinances and referrals'], ['Short videos', 'One step per video'], ['Compliance', 'APR, RESPA, NMLS ID'],
	], 4, SEMRS + ' · Regulation Z and RESPA apply', true),
	'digital-marketing-strategy-steps': () => steps('A digital marketing strategy, step by step', 'Write it down, fund a few channels well, review monthly', [
		['Customer', 'Who, where, what problem'], ['Advantage', 'One clear sentence'], ['Goals', 'Leads or bookings a month'], ['2–3 channels', 'Where your customers buy'],
		['Budget', 'Fewer channels, done properly'], ['Content plan', 'Answer customer questions'], ['Measure', 'Track from day one'], ['Update yearly', 'Or when things change'],
	], 4, 'Source: SEMRS guide · based on the SBA marketing plan outline', true),
	'keyword-planner-tree': () => tree('Google Keyword Planner: two tools, four answers', 'Free with a Google Ads account; no ads needed', 'Keyword Planner', [
		['Discover new keywords', ['Seed words or a page', 'Related ideas by theme']], ['Search volume, forecasts', ['Paste your own list', 'Download to a sheet']],
		['Numbers to read', ['Monthly searches (ranges)', 'Top of page bids']], ['Use for SEO', ['Group close variants', 'One keyword per page']],
	], 'Source: Google Ads Help (Use Keyword Planner) · SEMRS guide'),
	'website-redesign-timeline': () => timeline('Website redesign timeline', 'Keep what ranks, redirect what moves, test before launch', [
		['Week 1', 'Goals', 'Leads you want'], ['Week 1', 'Audit', 'Traffic and links'], ['Week 2', 'Keep or cut', 'Improve or merge'], ['Week 2', 'Content', 'Write before design'], ['Week 3', 'URL map', 'Old to new'],
		['Week 3-5', 'Staging build', 'Mobile first'], ['Week 5', 'Redirects', '301, keep 1 year+'], ['Week 6', 'Test', 'Forms, tracking'], ['Launch', 'Monitor', 'Sitemap, errors'],
	], 'Source: Google Search Central site move guide · example timeline, not a quote'),
	'near-me-seo-icons': () => iconCards('How to show up for near me searches', 'Google ranks local results by relevance, distance and prominence', [
		['pin', 'Business Profile', 'Category, services and real photos'], ['clock', 'Accurate hours', 'Including holidays, for "open now"'], ['star', 'Steady reviews', 'Ask every customer, reply to all'], ['map', 'Area pages', 'Only places you really serve'],
		['list', 'Consistent details', 'Same name, address and phone'], ['phone', 'Fast on phones', 'Call button at the top'], ['doc', 'Local schema', 'Structured business details'], ['shield', 'No stuffing', 'Never add "near me" to your name'],
	], 4, 'Source: Google Business Profile Help · SEMRS guide'),
	'google-ads-match-type-rings': () => rings('Google Ads keyword match types', 'Each match type reaches a narrower set of searches', [
		['Broad match', 'roof repair: related searches, even without your words'], ['Phrase match', '"roof repair": searches that include its meaning'], ['Exact match', '[roof repair]: same meaning or intent'],
	], 'Source: Google Ads Help (About keyword matching options)'),
	'email-campaign-sequence': () => timeline('A three-email campaign', 'One goal, one audience, one call to action', [
		['Day 1', 'Announcement', 'The offer and why it matters'], ['Day 4', 'Reminder', 'Questions or a customer story'], ['Day 8', 'Last chance', 'Short note before it ends'], ['Day 10', 'Measure', 'Clicks, replies, bookings'],
	], 'Source: SEMRS guide · follow CAN-SPAM and authenticate your domain', 'warm'),
	'chatgpt-marketing-cycle': () => cycle('A safe ChatGPT workflow for marketing', 'AI drafts; people check facts and approve', [
		['Brief', 'Audience, goal, facts'], ['AI draft', 'Outline or first version'], ['Fact check', 'Every number and claim'], ['Add expertise', 'Your examples, your voice'], ['Review', 'Links, policy, compliance'], ['Approve', 'A person publishes'],
	], 'People decide', 'Source: SEMRS guide · Google: appropriate AI use is not against its guidelines', 'dark'),
	'direct-mail-path': () => roadPath('A direct mail campaign, start to finish', 'Small area, one offer, tracked response', [
		['Pick an area', 'Where best customers live'], ['One offer', 'Clear and honest'], ['Design', 'Headline, photo, offer'], ['Track it', 'Number, QR, short URL'], ['Send', 'Timed to the season'], ['Repeat', 'Same streets again'], ['Measure', 'Bookings vs cost'],
	], 'Source: SEMRS guide · USPS EDDM: $0.26 per retail flat (November 2026)'),
	'final-expense-checklist': () => checklist('Before you buy final expense leads', 'Compare vendors by cost per policy, not cost per lead', [
		['Exclusive or shared?', 'Shared leads go to several agents'], ['How fresh?', 'Minutes old beats days old'], ['How was consent collected?', 'Ask for the form wording and records'],
		['Return policy', 'Wrong numbers and fake details'], ['Test a small batch', 'Before any big order'], ['Track cost per policy', 'Not just cost per lead'],
	], 'Source: SEMRS guide · follow Do Not Call and consent rules'),
	'dental-seo-pyramid': () => pyramid('Dental SEO: build from the base up', 'Strong foundations first, then content and links', [
		['Links', 'Local partners, sponsors and directories'], ['Patient guides', 'Answers reviewed by a dentist'], ['Reviews', 'Steady new reviews, private replies'], ['Treatment pages', 'One helpful page per treatment'], ['Profile and site', 'Business Profile, fast mobile site, schema'],
	], 'Source: SEMRS guide · HIPAA and the FTC review rule apply', 'warm'),
	'advertising-budget-tiles': () => statTiles('Advertising budget: work back from a customer', 'Example numbers only; replace them with your own', [
		['$1,800', 'Profit from one customer in year one'], ['$450', 'What you can pay to win one'], ['4', 'New customers wanted each month'], ['$1,800', 'Monthly ad budget'],
	], 'Source: SEMRS guide · worked example, not a benchmark', 'light', 'If 1 in 4 leads becomes a customer, aim for about $112 per lead.'),
	'quick-business-funding-matrix': () => quadrant('Quick business funding: speed vs cost', 'A general guide; real terms vary by lender, so compare each offer', 'Faster to receive', 'Higher cost', [
		['SBA-backed loan', 0.08, 0.12], ['Line of credit (set up early)', 0.82, 0.22], ['Invoice financing', 0.55, 0.5], ['Business credit card', 0.9, 0.52], ['Online term loan', 0.58, 0.76], ['Merchant cash advance', 0.86, 0.94, true],
	], 'Source: SEMRS guide · general information, not financial advice'),
	'technical-seo-layers': () => layers('Technical SEO: four layers, bottom up', 'A problem in a lower layer blocks everything above it', [
		['Crawl', 'Googlebot is not blocked by robots.txt or errors', 'search'], ['Render', 'The page returns HTTP 200 and loads its content', 'doc'], ['Index', 'Indexable content, no stray noindex or wrong canonical', 'list'], ['Rank', 'Fast, secure, mobile-friendly and helpful', 'chart'],
	], 'Source: Google Search Central (technical requirements) · SEMRS guide', 'dark'),
	'wordpress-blog-wireframe': () => wireframe('Anatomy of a WordPress blog post', 'What every business blog post page should have', [
		['Clear menu', 'Services and contact one click away'], ['Question as the title', 'Answer it in the first paragraph'], ['Real photo', 'Your work or team, compressed'], ['Short sections', 'Headings, lists and examples'], ['Related links', 'Matching service and other posts'], ['Next step', 'Quote form or phone number'],
	], 'Source: SEMRS guide'),
	'google-guaranteed-badges': () => badgePair('Google Guaranteed vs Google Screened', 'Both badges appear on Local Services Ads after Google screening', [
		['Google Guaranteed', 'Mostly home services', ['Plumbers, electricians, cleaners', 'Business and license checks', 'Background checks in some categories', 'Backed by Google guarantee program terms']],
		['Google Screened', 'Professional services', ['Lawyers, financial planners, real estate', 'Owner identity and business checks', 'Lawyers: state bar license checks', 'About 3 to 4 weeks to screen on average']],
	], 'Source: Google Local Services Help (screening and verification requirements)', 'dark'),
	'instagram-ads-formats': () => phones('Instagram ads formats at a glance', 'Match the format to the goal', [
		['Photo ad', 'photo'], ['Video ad', 'video'], ['Carousel', 'carousel'], ['Stories ad', 'story'], ['Explore ad', 'grid'],
	], 'Source: Meta for Business (Instagram ads)'),
	'ugc-hub': () => spokes('Using user generated content the right way', 'Every customer post you share should pass these checks', 'Customer post', [
		['check', 'Ask permission', 'Save the reply'], ['user', 'Credit the creator', 'Name or handle'], ['dollar', 'Disclose incentives', 'Free product or pay'], ['shield', 'Keep it honest', 'No edits to meaning'], ['star', 'Typical results', 'Not only the best'], ['lock', 'Respect privacy', 'Other people, places'],
	], 'Source: FTC Endorsement Guides FAQ · SEMRS guide', 'warm'),
	'sales-automation-flow': () => flowchart('Sales automation: the speed-to-lead workflow', 'Automate the reminders; keep the conversation human', ['New lead from form or call', 'Saved in CRM, owner assigned', 'Instant reply sent'], 'Called within one hour?', ['Log call, book next step'], ['Alert owner and manager'], 'Source: SEMRS guide · marketing texts need prior written consent'),
	'local-advertising-map': () => mapPins('Local advertising ideas around your area', 'Mix online and offline channels where your customers are', [
		['Business Profile', 'pin'], ['Search ads', 'search'], ['Local Services Ads', 'shield'], ['Social ads', 'megaphone'], ['Directories', 'list'], ['Direct mail', 'mail'], ['Sponsorships', 'star'], ['Van and shop signs', 'home'], ['Community events', 'calendar'], ['Partner referrals', 'link'],
	], 'Source: SEMRS guide'),
	'health-insurance-consent-form': () => formMock('Health insurance leads: document consent first', 'What CMS model consent form covers for Marketplace agents', 'Consumer consent', [
		'Name of consumer or authorized representative', 'Agent or broker name and National Producer Number', 'What the agent may do with the application', 'How personal information is used and protected', 'How long consent lasts and how to revoke it',
	], [['Before you help', 'Document consent before assisting with enrollment'], ['Before you submit', 'Document that the consumer reviewed the application'], ['Any format', 'Recorded call, text, email, e-signature or paper']], 'Source: CMS Model Consent Form for Marketplace Agents and Brokers', 'warm'),
	'listing-marketing-house': () => houseCallouts('Listing marketing: everything a listing needs', 'Present the home well and you win the next seller too', [
		['Professional photos', 'Good light, honest angles'], ['Floor plan', 'Layout at a glance'], ['Specific description', 'Sizes, updates, nearby'], ['Signs and flyers', 'QR code to the page'],
		['Video walkthrough', 'Plus short vertical clips'], ['Property web page', 'All details and a form'], ['Social and email', 'Housing ad category'], ['Open house', 'Promote and follow up'],
	], 'Source: SEMRS guide · fair housing rules apply to every listing ad'),
	'ecommerce-seo-hierarchy': () => hierarchy('Ecommerce SEO site structure', 'Each level targets a narrower search', 'Home page', [
		['Running shoes', ['Trail running', 'Road running', 'Product: Model X']], ['Hiking boots', ['Waterproof boots', 'Lightweight boots', 'Product: Model Y']], ['Accessories', ['Socks', 'Insoles', 'Product: Model Z']],
	], 'Categories rank for broad searches; products rank for specific ones. Keep every product a few clicks from home.', 'Source: Google Search Central (SEO for ecommerce sites) · example store'),
	'page-builder-versus': () => versus('Block editor vs page builder', 'Pick one approach for the whole site', ['Block editor', 'doc', ['Built into WordPress, free', 'Lighter, faster pages', 'Content stays standard blocks', 'Updated with WordPress', 'Patterns for reusable sections']], ['Page builder', 'gear', ['Drag-and-drop canvas', 'Very detailed design controls', 'Big template libraries', 'Many features paid yearly', 'Harder to switch away later']], 'Source: WordPress.org (Block Editor) · SEMRS guide'),
	'electrician-search-mock': () => serpMock('Where electricians can show up on Google', 'An example search; each spot is won a different way', 'emergency electrician near me', [
		['1. Local Services Ad', 'Pay per lead after Google screening'], ['2. Map results', 'Business Profile, reviews and distance'], ['3. Your listing', 'Accurate hours and steady reviews'], ['4. Website results', 'Service and area pages that answer'],
	], 'Source: Google Business Profile Help · Google Local Services Help · example layout'),
	'pinterest-ads-board': () => pinBoard('Pinterest ads at a glance', 'Goals, formats and creative that suit plan-ahead buyers', [
		['Awareness', 'Reach people who do not know you yet', 'megaphone'], ['Consideration', 'Get clicks to the right page', 'link'], ['Leads and sales', 'Sign-ups, enquiries and purchases', 'dollar'], ['Image ads', 'Tall images with the product in use', 'home'],
		['Idea ads', 'Several steps or looks in one ad', 'bulb'], ['Video', 'Premiere Spotlight and short clips', 'target'], ['Targeting', 'Keywords, interests, audiences', 'search'], ['Measure', 'Pinterest tag and UTM links', 'chart'],
	], 'Source: Pinterest Business · SEMRS guide'),
	'content-strategy-calendar': () => calendarGrid('A content strategy month on one page', 'Four pillars, a steady rhythm, one goal', 'Example month (4 weeks)', [['Costs', 'Prices and what affects them'], ['How-to', 'Guides that answer questions'], ['Proof', 'Case studies and reviews'], ['News', 'Seasonal tips and updates']], {
		2: [1, 'Guide'], 4: [0, 'Social'], 7: [3, 'Email'], 9: [0, 'Cost guide'], 11: [2, 'Social'], 14: [1, 'Video'], 16: [2, 'Case study'], 18: [1, 'Social'], 21: [3, 'Email'], 23: [1, 'Guide'], 25: [0, 'Social'], 28: [2, 'Review'],
	}, 'Source: SEMRS guide · Google: create people-first content'),
	'ai-market-research-pipeline': () => pipeline('AI market research pipeline', 'Real data in, AI summaries out, a person checks every fact', ['Census and BLS data', 'Your customer reviews', 'Survey answers', 'Competitor pages', 'Sales call notes'], 'AI summarizes and compares', 'A person checks every fact', ['Customer themes', 'Market size by area', 'Competitor gaps', 'One test to run'], 'Source: SBA market research guide · SEMRS guide'),
	'ecommerce-agency-scorecard': () => scorecard('Ecommerce agency scorecard', 'Tick each answer you are happy with, then compare', ['Agency A', 'Agency B', 'Agency C'], [
		'Do we own the store, ads and analytics?', 'Is there a clear 90-day plan?', 'Do reports show revenue and profit?', 'Can we see stores you worked on?', 'Who works on our account?', 'Fees in dollars, with minimum term?', 'How do we leave, and what do we keep?', 'No guaranteed rankings or sales?',
	], 'Source: SEMRS guide'),
	'advisor-email-mock': () => emailMock('Financial advisor email marketing, done right', 'Useful content, compliance first', 'Your Advisory Firm <news@example.com>', 'What the rate change means for your savings', ['Principal approved', 'Archived', 'Opt-out included'], [
		['Approve before use', 'Retail communications to 25+ retail investors in 30 days need principal approval'], ['Keep records', 'Copies, dates of use and the approving principal'], ['CAN-SPAM basics', 'Honest subject, postal address and a working unsubscribe'],
	], 'Source: FINRA Rule 2210 · FTC CAN-SPAM guide · example email'),
	'dental-implant-journey': () => journeyMap('Dental implant marketing: the patient journey', 'Meet patients at every stage of a long decision', [
		['search', 'Research', 'Are implants right for me?', 'Implants vs dentures and bridges guide'], ['dollar', 'Cost', 'How much will it cost?', 'Honest ranges and what changes the price'], ['star', 'Trust', 'Who should I choose?', 'Experience, real cases, implant reviews'],
		['calendar', 'Financing', 'Can I afford it?', 'Payment plans and financing options'], ['phone', 'Booking', 'How do I start?', 'Easy online consultation request'], ['smile', 'Aftercare', 'What happens next?', 'Recovery guides and follow-up'],
	], 'Source: SEMRS guide · FTC health claims guidance and HIPAA apply'),
	'website-maintenance-prices': () => priceColumns('Website maintenance cost: one published example', 'SEMRS care plans, November 2026 (hosting and domain extra)', [
		['Care Basic', '$39', 'per month', ['Backups', 'Uptime monitoring', 'Security and plugin updates', '30 min of edits a month']],
		['Care Growth', '$119', 'per month', ['Everything in Basic', '2 hours of edits a month', 'Monthly SEO health report', '1 blog post a month'], true],
		['Care Pro', '$239', 'per month', ['Everything in Growth', '5 hours of edits a month', 'Conversion suggestions', '4 blog posts a month']],
	], null, 'Source: SEMRS pricing page, November 2026'),
};
