/* PikaCart admin helpers: media picker, listing type switch, SEO panel. */
(function ($) {
	'use strict';
	var cfg = window.DMA || {};

	/* Media picker: <button class="dm-media-pick" data-target="input-id"> */
	$(document).on('click', '.dm-media-pick', function (e) {
		e.preventDefault();
		var target = $(this).data('target');
		var frame = wp.media({ title: 'Choose image', library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			$('#' + target).val(a.id).trigger('change');
			var url = (a.sizes && (a.sizes.thumbnail || a.sizes.medium) || a).url;
			$('#' + target + '_prev').html('<img src="' + url + '" style="max-height:60px;border-radius:6px;vertical-align:middle">');
		});
		frame.open();
	});

	/* Product photos (gallery) box: multi-select, remove, live "x of 4" count */
	function galSync() {
		var $list = $('.dm-gal-list'), ids = $list.children('li').map(function () { return $(this).data('id'); }).get();
		$('#dm_gallery_ids').val(ids.join(','));
		var thumb = $('#_thumbnail_id').length ? (parseInt($('#_thumbnail_id').val(), 10) > 0 ? 1 : 0) : (parseInt($('.dm-gal-count').data('have-thumb'), 10) || 0);
		var n = Math.min(ids.length + thumb, 4);
		$('.dm-gal-count').text(n + ' of 4 photos added').toggleClass('is-ok', ids.length + thumb >= 4);
		$('.dm-gal-add').prop('disabled', ids.length >= 5);
	}
	$(document).on('click', '.dm-gal-add', function (e) {
		e.preventDefault();
		var frame = wp.media({ title: 'Add product photos', library: { type: 'image' }, multiple: 'add', button: { text: 'Add photos' } });
		frame.on('select', function () {
			var $list = $('.dm-gal-list');
			frame.state().get('selection').each(function (m) {
				var a = m.toJSON();
				if ($list.children('li').length >= 5 || $list.children('li[data-id="' + a.id + '"]').length) { return; }
				var url = (a.sizes && (a.sizes.thumbnail || a.sizes.medium) || a).url;
				$list.append($('<li>').attr('data-id', a.id).append($('<img alt="">').attr('src', url), '<button type="button" class="dm-gal-remove" aria-label="Remove photo">&times;</button>'));
			});
			galSync();
		});
		frame.open();
	});
	$(document).on('click', '.dm-gal-remove', function () { $(this).closest('li').remove(); galSync(); });
	$(document).on('change', '#_thumbnail_id', galSync);
	$(document).ajaxComplete(function () { if ($('.dm-gal-list').length) { galSync(); } });

	/* Confirm links */
	$(document).on('click', 'a[data-confirm]', function (e) {
		if (!window.confirm('Are you sure?')) { e.preventDefault(); }
	});

	/* Listing type switch (digital / service / partner offer) */
	function syncType($wrap) {
		var type = $wrap.find('input[name$="[listing_type]"]:checked, input[name="listing_type"]:checked').val() || 'digital';
		var $scope = $wrap.closest('form');
		$scope.find('.dm-f-digital').toggle(type === 'digital');
		$scope.find('.dm-f-service').toggle(type === 'service');
		$scope.find('.dm-f-affiliate').toggle(type === 'affiliate');
		var $lab = $scope.find('label[for="dm_price"]');
		if ($lab.length) { $lab.text($lab.data('label-' + type) || $lab.text()); }
		$wrap.find('.dm-type-opt').each(function () { $(this).toggleClass('is-on', $(this).find('input').is(':checked')); });
	}
	$('[data-listing-editor]').each(function () {
		var $w = $(this);
		$w.on('change', 'input[type="radio"]', function () { syncType($w); });
		syncType($w);
	});

	/* SEO panel */
	$('[data-seo-box]').each(function () {
		var $box = $(this);
		var $t = $box.find('[data-seo-title]');
		var $d = $box.find('[data-seo-desc]');
		var $kw = $('#dmf_focus_kw');
		var $checks = $box.find('[data-seo-checks]');
		var $dupes = $box.find('[data-seo-dupes]');
		var site = cfg.site || '';
		var timer = null;

		function editor() {
			try {
				if (window.wp && wp.data && wp.data.select('core/editor')) {
					var ed = wp.data.select('core/editor');
					return { title: ed.getEditedPostAttribute('title') || '', content: ed.getEditedPostContent() || '', excerpt: ed.getEditedPostAttribute('excerpt') || '', slug: ed.getEditedPostAttribute('slug') || '' };
				}
			} catch (e) {}
			return { title: $('#title').val() || '', content: $('#content').val() || '', excerpt: $('#excerpt').val() || '', slug: $('#post_name').val() || $('#editable-post-name-full').text() || '' };
		}
		function strip(html) { return $('<div>').html(html).text().replace(/\s+/g, ' ').trim(); }
		function counter(el, lo, hi) {
			var n = el.val().length;
			var $c = $box.find('[data-count-for="' + el.attr('id') + '"]');
			var cls = n === 0 ? '' : (n >= lo && n <= hi ? 'is-good' : 'is-warn');
			$c.text(n + ' chars').attr('class', cls);
		}
		function render() {
			var ed = editor();
			var title = $t.val() || (ed.title ? ed.title + ' | ' + site : '');
			var desc = $d.val() || strip(ed.excerpt || ed.content).slice(0, 155);
			$box.find('[data-serp-title]').text(title.length > 62 ? title.slice(0, 60) + '…' : title);
			$box.find('[data-serp-desc]').text(desc.length > 160 ? desc.slice(0, 157) + '…' : desc);
			counter($t, 50, 60);
			counter($d, 120, 160);
			var kw = ($kw.val() || '').toLowerCase().trim();
			var text = strip(ed.content).toLowerCase();
			var first = text.split(' ').slice(0, 100).join(' ');
			var items = [];
			function add(ok, msg) { items.push('<li class="' + (ok ? 'is-good' : 'is-bad') + '">' + (ok ? '✓ ' : '✗ ') + msg + '</li>'); }
			if (kw) {
				add(title.toLowerCase().indexOf(kw) > -1, 'Focus keyword in SEO title');
				add((ed.title || '').toLowerCase().indexOf(kw) > -1, 'Focus keyword in the page title (H1)');
				add((ed.slug || '').replace(/-/g, ' ').indexOf(kw) > -1, 'Focus keyword in the URL slug');
				add(first.indexOf(kw) > -1, 'Focus keyword in the first 100 words');
				add(desc.toLowerCase().indexOf(kw) > -1, 'Focus keyword in the meta description');
			} else {
				add(false, 'Add a focus keyword (the phrase people search for)');
			}
			var words = text ? text.split(' ').length : 0;
			var min = $box.data('type') === 'post' ? 900 : 150;
			add(words >= min, words + ' words in the description (aim ' + min + '+)');
			add(title.length >= 50 && title.length <= 60, 'SEO title 50–60 characters');
			add(desc.length >= 120 && desc.length <= 160, 'Meta description 120–160 characters');
			$checks.html(items.join(''));
			clearTimeout(timer);
			timer = setTimeout(function () {
				$.post(cfg.ajax, { action: 'dm_seo_dupes', nonce: cfg.nonce, post: $box.data('post'), title: $t.val(), desc: $d.val(), kw: $kw.val() }, function (res) {
					$dupes.html(res && res.success && res.data.length ? '<p class="dm-seo-warn">' + res.data.map(function (m) { return $('<span>').text(m).html(); }).join('<br>') + '</p>' : '');
				});
			}, 700);
		}
		$t.add($d).add($kw).on('input', render);
		$('#title, #content, #excerpt').on('input', render);
		if (window.wp && wp.data && wp.data.subscribe) {
			var last = '';
			wp.data.subscribe(function () {
				var e = editor();
				var sig = e.title + e.slug + e.excerpt + e.content.length;
				if (sig !== last) { last = sig; clearTimeout(window.__dmSeoT); window.__dmSeoT = setTimeout(render, 400); }
			});
		}
		render();
	});
})(jQuery);
