/**
 * Super Admin screens: charts, copy buttons, media picker, confirmations.
 */
( function () {
	'use strict';

	var __ = window.wp && wp.i18n ? wp.i18n.__ : function ( s ) { return s; };

	function brand() {
		return getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim() || '#4F46E5';
	}
	function accent() {
		return getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-accent' ).trim() || '#F97316';
	}

	function charts() {
		var data = window.PKC_CHARTS;
		if ( ! data || ! window.Chart ) {
			return;
		}
		var Chart = window.Chart;
		Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
		Chart.defaults.color = '#687089';
		var grid = { color: '#eef0f5' };
		var money = function ( v ) { return '₹' + Number( v ).toLocaleString( 'en-IN' ); };

		var rev = document.getElementById( 'pkc-chart-revenue' );
		if ( rev ) {
			new Chart( rev, {
				type: 'bar',
				data: { labels: data.daily.labels, datasets: [ { label: __( 'Revenue', 'pikacart' ), data: data.daily.revenue, backgroundColor: brand(), borderRadius: 6, maxBarThickness: 22 } ] },
				options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function ( c ) { return money( c.parsed.y ); } } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: grid, ticks: { callback: money } } } }
			} );
		}
		var mon = document.getElementById( 'pkc-chart-monthly' );
		if ( mon ) {
			new Chart( mon, {
				type: 'line',
				data: { labels: data.monthly.labels, datasets: [ { label: __( 'Revenue', 'pikacart' ), data: data.monthly.values, borderColor: brand(), backgroundColor: 'rgba(79,70,229,.08)', fill: true, tension: 0.35, pointRadius: 3 } ] },
				options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function ( c ) { return money( c.parsed.y ); } } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: grid, ticks: { callback: money } } } }
			} );
		}
		var sig = document.getElementById( 'pkc-chart-signups' );
		if ( sig ) {
			new Chart( sig, {
				type: 'line',
				data: { labels: data.daily.labels, datasets: [ { label: __( 'Sign-ups', 'pikacart' ), data: data.daily.signups, borderColor: accent(), backgroundColor: 'rgba(249,115,22,.10)', fill: true, tension: 0.35, pointRadius: 2 } ] },
				options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: grid, ticks: { precision: 0 } } } }
			} );
		}
		var cat = document.getElementById( 'pkc-chart-category' );
		if ( cat ) {
			var palette = [ brand(), accent(), '#0f9f6e', '#2563eb', '#db2777', '#0891b2', '#ca8a04', '#7c3aed', '#64748b', '#dc2626', '#16a34a' ];
			if ( ! data.category.values.length ) {
				cat.parentNode.innerHTML = '<p class="pkc-empty">' + __( 'No accounts yet.', 'pikacart' ) + '</p>';
				return;
			}
			new Chart( cat, {
				type: 'doughnut',
				data: { labels: data.category.labels, datasets: [ { data: data.category.values, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' } ] },
				options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } }
			} );
		}
	}

	function copyButtons() {
		document.querySelectorAll( '[data-copy]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var input = document.getElementById( btn.dataset.copy );
				input.select();
				var done = function () {
					var old = btn.textContent;
					btn.textContent = __( 'Copied!', 'pikacart' );
					setTimeout( function () { btn.textContent = old; }, 1600 );
				};
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( input.value ).then( done, function () { document.execCommand( 'copy' ); done(); } );
				} else {
					document.execCommand( 'copy' );
					done();
				}
			} );
		} );
	}

	function mediaPickers() {
		document.querySelectorAll( '[data-media]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! window.wp || ! wp.media ) {
					return;
				}
				var frame = wp.media( { title: __( 'Choose image', 'pikacart' ), library: { type: 'image' }, multiple: false } );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					document.getElementById( btn.dataset.media ).value = att.url;
				} );
				frame.open();
			} );
		} );
	}

	function confirms() {
		document.querySelectorAll( 'form[data-confirm]' ).forEach( function ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				if ( ! window.confirm( form.dataset.confirm ) ) {
					e.preventDefault();
				}
			} );
		} );
	}

	function colorCodes() {
		document.querySelectorAll( '.pkc-color input[type=color]' ).forEach( function ( input ) {
			input.addEventListener( 'input', function () {
				input.nextElementSibling.textContent = input.value;
			} );
		} );
	}

	// The chart library is printed after this file, so draw once the page has loaded.
	if ( document.readyState === 'complete' ) {
		charts();
	} else {
		window.addEventListener( 'load', charts );
	}
	copyButtons();
	mediaPickers();
	confirms();
	colorCodes();
}() );
