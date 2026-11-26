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
};
