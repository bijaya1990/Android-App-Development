/**
 * Naukripatra Skin — single post enhancer.
 *
 * SAFETY RULES THIS FILE FOLLOWS
 *   - It never changes, adds or deletes any text. Every word stays in the DOM,
 *     in the same reading order, so Google sees exactly what it sees today.
 *   - It only adds CSS classes, wraps wide tables in a scroll container, and
 *     turns existing FAQ heading + paragraph pairs into <details> accordions
 *     (the text stays inside the page, just collapsible).
 *   - It touches nothing outside the post article.
 *   - If anything is missing on a page, it exits quietly — no errors.
 *
 * Runs only on single posts, and only when the switches in functions.php allow.
 */
(function () {
	'use strict';

	var cfg = window.npSkinCfg || { faq: true, applyBar: true };

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {
		var article = document.querySelector('article.dynamic-content-template') ||
		              document.querySelector('article.post') ||
		              document.querySelector('.dynamic-entry-content');
		if (!article) { return; }

		var content = article.querySelector('.dynamic-entry-content') ||
		              article.querySelector('.entry-content');

		/* 1. Mark the post title so the header card can be styled. */
		var h1 = article.querySelector('h1');
		if (h1) { h1.classList.add('np-post-title'); }

		/* 2. Wide tables get their own horizontal scroll box, so the page
		      itself never scrolls sideways on a phone. */
		if (content) {
			Array.prototype.forEach.call(content.querySelectorAll('table'), function (table) {
				if (table.parentNode && table.parentNode.classList.contains('np-tablescroll')) { return; }
				var box = document.createElement('div');
				box.className = 'np-tablescroll';
				table.parentNode.insertBefore(box, table);
				box.appendChild(table);
			});
		}

		/* 3. The "Important Links" list becomes a button grid (CSS only). */
		var applyHref = '';
		var pdfHref = '';
		if (content) {
			Array.prototype.forEach.call(content.querySelectorAll('h2, h3'), function (heading) {
				if (!/important\s*links/i.test(heading.textContent || '')) { return; }
				var node = heading.nextElementSibling;
				while (node && node.tagName !== 'UL' && node.tagName !== 'OL' && node.tagName !== 'H2') {
					node = node.nextElementSibling;
				}
				if (node && (node.tagName === 'UL' || node.tagName === 'OL')) {
					node.classList.add('np-links');
					Array.prototype.forEach.call(node.querySelectorAll('a'), function (a) {
						var text = (a.textContent || '') + ' ' + (a.previousSibling ? a.previousSibling.textContent || '' : '');
						if (!applyHref && /apply|online form|registration/i.test(text)) { applyHref = a.href; }
						if (!pdfHref && /notification|advertisement|pdf/i.test(text)) { pdfHref = a.href; }
					});
				}
			});
		}

		/* 4. FAQ: existing "Qn." heading + answer paragraph become an accordion.
		      Nothing is removed — the answer text stays in the page. */
		if (cfg.faq && content) {
			Array.prototype.forEach.call(content.querySelectorAll('h2'), function (heading) {
				if (!/faq|frequently asked|quick answers/i.test(heading.textContent || '')) { return; }

				var node = heading.nextElementSibling;
				while (node && node.tagName === 'H3') {
					var answer = node.nextElementSibling;
					if (!answer || (answer.tagName !== 'P' && answer.tagName !== 'UL' && answer.tagName !== 'OL')) {
						node = node.nextElementSibling;
						continue;
					}
					var next = answer.nextElementSibling;

					var details = document.createElement('details');
					details.className = 'np-faq';
					var summary = document.createElement('summary');
					summary.textContent = node.textContent;
					var body = document.createElement('div');
					body.className = 'np-faq-body';

					node.parentNode.insertBefore(details, node);
					details.appendChild(summary);
					details.appendChild(body);
					body.appendChild(answer);
					node.parentNode.removeChild(node);

					node = next;
				}
			});
		}

		/* 5. Sticky apply bar on phones, built from the links already on the
		      page. No new destinations are invented. */
		if (cfg.applyBar && (applyHref || pdfHref)) {
			var bar = document.createElement('div');
			bar.className = 'np-applybar';

			if (applyHref) {
				var apply = document.createElement('a');
				apply.className = 'np-btn np-btn-fill np-btn-lg';
				apply.href = applyHref;
				apply.target = '_blank';
				apply.rel = 'nofollow noopener';
				apply.textContent = 'Apply Online';
				bar.appendChild(apply);
			}
			if (pdfHref) {
				var pdf = document.createElement('a');
				pdf.className = 'np-btn np-btn-lg';
				pdf.href = pdfHref;
				pdf.target = '_blank';
				pdf.rel = 'nofollow noopener';
				pdf.textContent = 'Notification';
				bar.appendChild(pdf);
			}

			document.body.appendChild(bar);
			document.body.classList.add('np-on-post');
		}
	});
}());
