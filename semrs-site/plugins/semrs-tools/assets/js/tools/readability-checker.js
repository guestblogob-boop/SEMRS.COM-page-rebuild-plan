/* Readability Checker: Flesch Reading Ease, Flesch-Kincaid grade, long sentences, passive voice,
   hard words and adverbs, with the problem sentences highlighted. English text; runs in the browser. */
SemrsTools.register('readability-checker', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var ta = h('textarea', { rows: 14, placeholder: 'Paste your article, page copy or email here…' });
	ta.value = (api.load(api.id) || {}).text || '';
	var input = api.panel('Your text');
	input.append(api.field('Text', ta, 'Stays in your browser. Scores are for English text.'));
	var out = api.panel('Readability');
	root.append(input, out);
	var IRREG = 'been born bought brought built caught chosen done drawn driven eaten fallen felt found forgotten given gone grown heard held hidden hit kept known laid led left lent lost made meant met paid put read run said seen sent set shown shut sold spent spoken stolen taken taught thought thrown told understood won worn written'.split(' ');
	var irr = {};
	IRREG.forEach(function (w) { irr[w] = 1; });
	var COMPLEX = { utilize: 'use', utilise: 'use', facilitate: 'help', commence: 'start', terminate: 'end', approximately: 'about', subsequently: 'later', numerous: 'many', purchase: 'buy', assistance: 'help', demonstrate: 'show', endeavor: 'try', sufficient: 'enough', additional: 'more', 'in order to': 'to', 'at this point in time': 'now', 'due to the fact that': 'because', 'in the event that': 'if', leverage: 'use', obtain: 'get', prior: 'before', regarding: 'about', individuals: 'people' };

	function syllables(w) {
		w = w.toLowerCase().replace(/[^a-z]/g, '');
		if (!w) { return 0; }
		if (w.length <= 3) { return 1; }
		w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
		var m = w.match(/[aeiouy]{1,2}/g);
		return Math.max(1, m ? m.length : 1);
	}
	function sentencesOf(t) { return (t.replace(/\s+/g, ' ').match(/[^.!?]+[.!?]+["')\]]*|[^.!?]+$/g) || []).map(function (x) { return x.trim(); }).filter(function (x) { return /[A-Za-z0-9]/.test(x); }); }
	function wordsOf(s) { return s.match(/[A-Za-z0-9][A-Za-z0-9'’-]*/g) || []; }
	function passive(s) { return /\b(am|is|are|was|were|be|been|being|get|gets|got)\s+(\w+ly\s+)?(\w+ed|\w+en)\b/i.test(s) || wordsOf(s).some(function (w, i, a) { return irr[w.toLowerCase()] && /^(am|is|are|was|were|be|been|being)$/i.test(a[i - 1] || ''); }); }
	function grade(score) { return score >= 90 ? 'Very easy (5th grade)' : score >= 80 ? 'Easy (6th grade)' : score >= 70 ? 'Fairly easy (7th grade)' : score >= 60 ? 'Plain English (8th–9th grade)' : score >= 50 ? 'Fairly hard (10th–12th grade)' : score >= 30 ? 'Hard (college)' : 'Very hard (graduate)'; }

	function render() {
		var t = ta.value.trim();
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Readability' }));
		if (!t) { out.append(h('p', { class: 'semrs-tool__hint', text: 'Paste some text to see the scores.' })); api.store(api.id, { text: '' }); return; }
		var sents = sentencesOf(t), words = wordsOf(t), wc = words.length, sc = Math.max(1, sents.length);
		var syl = 0, hard = 0, adverbs = 0;
		words.forEach(function (w) { var n = syllables(w); syl += n; if (n >= 3 && !/^[A-Z]/.test(w)) { hard++; } if (/ly$/i.test(w) && w.length > 4 && !/^(only|family|early|reply|apply|supply|likely|daily|weekly|monthly|holy|ugly|july|italy)$/i.test(w)) { adverbs++; } });
		var fre = wc ? 206.835 - 1.015 * (wc / sc) - 84.6 * (syl / wc) : 0;
		var fk = wc ? 0.39 * (wc / sc) + 11.8 * (syl / wc) - 15.59 : 0;
		fre = Math.max(0, Math.min(100, fre));
		var longS = sents.filter(function (s) { return wordsOf(s).length > 20; });
		var veryLong = sents.filter(function (s) { return wordsOf(s).length > 30; });
		var pass = sents.filter(passive);
		var paras = t.split(/\n\s*\n/).filter(function (p) { return p.trim(); });
		var longP = paras.filter(function (p) { return wordsOf(p).length > 150; });
		var swaps = [];
		Object.keys(COMPLEX).forEach(function (k) { var re = new RegExp('\\b' + k + '\\b', 'i'); if (re.test(t)) { swaps.push('“' + k + '” → “' + COMPLEX[k] + '”'); } });
		var pct = function (n, d) { return d ? Math.round(n / d * 100) : 0; };
		var c = [];
		c.push([fre >= 60 ? 'ok' : (fre >= 50 ? 'warn' : 'bad'), 'Reading ease ' + Math.round(fre) + ': ' + grade(fre) + '. Aim for 60+ for most web pages.']);
		c.push([pct(longS.length, sc) <= 25 ? 'ok' : 'warn', pct(longS.length, sc) + '% of sentences have more than 20 words (aim for 25% or less).']);
		if (veryLong.length) { c.push(['bad', veryLong.length + ' sentence(s) have more than 30 words. Split them.']); }
		c.push([pct(pass.length, sc) <= 10 ? 'ok' : 'warn', pct(pass.length, sc) + '% of sentences may be passive (aim for 10% or less). Say who does what.']);
		c.push([pct(adverbs, wc) <= 3 ? 'ok' : 'warn', adverbs + ' adverbs ending in -ly (' + pct(adverbs, wc) + '% of words).']);
		if (longP.length) { c.push(['warn', longP.length + ' paragraph(s) have more than 150 words. Break them up for phone readers.']); }
		if (swaps.length) { c.push(['warn', 'Simpler words: ' + swaps.slice(0, 8).join(', ') + '.']); }
		var marked = h('div', { class: 'semrs-read__text' });
		sents.slice(0, 400).forEach(function (s) {
			var n = wordsOf(s).length, cls = n > 30 ? 'is-vlong' : (n > 20 ? 'is-long' : '');
			if (passive(s)) { cls += ' is-passive'; }
			marked.append(cls.trim() ? h('mark', { class: cls.trim(), title: (n > 20 ? n + ' words' : '') + (passive(s) ? ' · may be passive' : ''), text: s }) : s, ' ');
		});
		out.append(
			h('div', { class: 'semrs-tool__stats' },
				api.stat(Math.round(fre), 'reading ease (0–100)'), api.stat(Math.max(0, fk).toFixed(1), 'grade level'),
				api.stat(wc.toLocaleString(), 'words'), api.stat(sc, 'sentences'), api.stat((wc / sc).toFixed(1), 'words / sentence'), api.stat(pct(hard, wc) + '%', 'hard words (3+ syllables)')),
			api.checks(c),
			h('p', { class: 'semrs-serp__label', text: 'Your text, highlighted' }),
			h('p', { class: 'semrs-tool__hint semrs-read__key' }, h('mark', { class: 'is-long', text: 'over 20 words' }), ' ', h('mark', { class: 'is-vlong', text: 'over 30 words' }), ' ', h('mark', { class: 'is-passive', text: 'may be passive' })),
			marked,
			h('p', { class: 'semrs-tool__hint', text: 'Scores are a guide, not a rule. Technical topics can score lower and still be clear for their readers.' }));
		api.store(api.id, { text: t.length < 30000 ? ta.value : '' });
	}
	ta.addEventListener('input', api.debounce(render, 250));
	render();
});
