/* Post Job form helpers: overview auto-fill, counters, SERP preview. */
(function () {
	'use strict';
	var form = document.querySelector('[data-np-postform]');
	if (!form) { return; }
	var syn = {
		organization: ['organization', 'organisation', 'recruitment board', 'conducting body'],
		post_name: ['post name', 'name of post'], vacancy: ['vacancy', 'total vacancy', 'no of posts', 'total posts'],
		qualification: ['qualification', 'educational qualification', 'eligibility'], age_limit: ['age limit', 'age'],
		salary: ['salary', 'pay scale', 'stipend'], application_fee: ['application fee', 'fee'],
		application_mode: ['application mode', 'mode of application'], selection_process: ['selection process', 'selection procedure'],
		job_type: ['job type', 'employment type'], job_location: ['job location', 'location'], department: ['department', 'division'],
		last_date: ['last date', 'closing date', 'last date to apply']
	};
	function norm(s) { return s.toLowerCase().replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim(); }

	var btn = form.querySelector('[data-np-autofill]');
	if (btn) {
		btn.addEventListener('click', function () {
			var html = form.querySelector('#np_article').value;
			var doc = new DOMParser().parseFromString(html, 'text/html');
			doc.querySelectorAll('tr').forEach(function (tr) {
				var c = tr.querySelectorAll('th,td');
				if (c.length < 2) { return; }
				var label = norm(c[0].textContent), val = c[1].textContent.replace(/\s+/g, ' ').trim();
				Object.keys(syn).forEach(function (k) {
					var f = form.querySelector('#f_' + k);
					if (!f || f.value || !val) { return; }
					if (syn[k].some(function (l) { return label === l || label.indexOf(l + ' ') === 0; })) { f.value = val; }
				});
			});
		});
	}

	var t = form.querySelector('#y_seo_title'), d = form.querySelector('#y_seo_desc'), title = form.querySelector('#np_title');
	var st = form.querySelector('[data-np-serp-t]'), sd = form.querySelector('[data-np-serp-d]'), cnt = form.querySelector('[data-np-count]');
	function update() {
		var tv = (t && t.value) || (title && title.value) || '', dv = (d && d.value) || '';
		if (st) { st.textContent = tv.slice(0, 60); }
		if (sd) { sd.textContent = dv.slice(0, 155); }
		if (cnt) { cnt.textContent = 'Title: ' + tv.length + '/60 characters. Description: ' + dv.length + '/155 characters.'; }
	}
	[t, d, title].forEach(function (el) { if (el) { el.addEventListener('input', update); } });
	update();
})();
