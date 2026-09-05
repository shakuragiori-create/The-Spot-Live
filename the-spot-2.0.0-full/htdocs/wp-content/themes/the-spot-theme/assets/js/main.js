/*  The Spot — Fast Food & Tea
    Front-end behaviour: nav, menu tabs + search, scroll reveals, counters,
    review slider, gallery lightbox, reservation / message forms, back-to-top.
    Vanilla JS, no dependencies. Every animation respects prefers-reduced-motion.
--------------------------------------------------------------------------- */
(function () {
	'use strict';

	var REDUCE = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var DATA = window.TS_DATA || {};

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	/* ---------------- NAV ---------------- */
	function initNav() {
		var nav = $('#nav');
		var links = $('#navLinks');
		var ham = $('#hamBtn');

		if (nav) {
			var ticking = false;
			var onScroll = function () {
				if (ticking) { return; }
				ticking = true;
				window.requestAnimationFrame(function () {
					nav.classList.toggle('scrolled', window.pageYOffset > 30);
					ticking = false;
				});
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();
		}

		if (!ham || !links) { return; }

		var close = function () {
			links.classList.remove('open');
			ham.classList.remove('on');
			ham.setAttribute('aria-expanded', 'false');
		};

		ham.addEventListener('click', function () {
			var open = links.classList.toggle('open');
			ham.classList.toggle('on', open);
			ham.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		$$('a', links).forEach(function (a) { a.addEventListener('click', close); });

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { close(); }
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth > 860) { close(); }
		});
	}

	/* ---------------- STAGGER INDEXES ---------------- */
	function initStagger() {
		$$('.mgrid').forEach(function (grid) {
			$$('.mi', grid).forEach(function (item, i) {
				item.style.setProperty('--i', i % 14);
			});
		});
		$$('[data-stagger]').forEach(function (wrap) {
			Array.prototype.slice.call(wrap.children).forEach(function (child, i) {
				child.style.setProperty('--i', i);
			});
		});
	}

	/* ---------------- MENU TABS ---------------- */
	function initTabs() {
		var tabs = $$('.tab');
		if (!tabs.length) { return; }

		tabs.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var slug = btn.getAttribute('data-t');
				var panel = document.getElementById('t-' + slug);

				tabs.forEach(function (b) {
					b.classList.remove('on');
					b.setAttribute('aria-selected', 'false');
				});
				$$('.cat').forEach(function (c) { c.classList.remove('on'); });

				btn.classList.add('on');
				btn.setAttribute('aria-selected', 'true');
				if (panel) { panel.classList.add('on'); }

				if (btn.scrollIntoView) {
					btn.scrollIntoView({ block: 'nearest', inline: 'center', behavior: REDUCE ? 'auto' : 'smooth' });
				}
			});
		});
	}

	/* ---------------- MENU SEARCH ---------------- */
	function initSearch() {
		var input = $('#mSearch');
		if (!input) { return; }

		var clear = $('#mClear');
		var count = $('#mCount');
		var cats = $$('.cat');

		var apply = function () {
			var q = input.value.trim().toLowerCase();

			if (q.length < 2) {
				document.body.classList.remove('searching');
				$$('.mi-hide').forEach(function (el) { el.classList.remove('mi-hide'); });
				cats.forEach(function (c) { c.classList.remove('cat-empty'); });
				if (count) { count.textContent = ''; }
				return;
			}

			document.body.classList.add('searching');
			var total = 0;

			cats.forEach(function (cat) {
				var visibleInCat = 0;

				$$('.platter', cat).forEach(function (feature) {
					var text = feature.textContent.toLowerCase();
					var hit = text.indexOf(q) !== -1;
					feature.classList.toggle('mi-hide', !hit);
					if (hit) { visibleInCat++; total++; }
				});

				$$('.mgrid', cat).forEach(function (grid) {
					var shown = 0;
					$$('.mi', grid).forEach(function (item) {
						var name = ($('.mi-name', item) || item).textContent.toLowerCase();
						var hit = name.indexOf(q) !== -1;
						item.classList.toggle('mi-hide', !hit);
						if (hit) { shown++; }
					});

					grid.classList.toggle('mi-hide', shown === 0);

					var heading = grid.previousElementSibling;
					if (heading && heading.classList.contains('cat-ttl')) {
						heading.classList.toggle('mi-hide', shown === 0);
					}

					visibleInCat += shown;
					total += shown;
				});

				cat.classList.toggle('cat-empty', visibleInCat === 0);
			});

			if (count) {
				count.textContent = total
					? total + (total === 1 ? ' item matches “' : ' items match “') + input.value.trim() + '”'
					: 'Nothing matches “' + input.value.trim() + '”. Try “momo”, “burger” or “tea”.';
			}
		};

		var timer = null;
		input.addEventListener('input', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(apply, 120);
		});

		input.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { input.value = ''; apply(); }
		});

		if (clear) {
			clear.addEventListener('click', function () {
				input.value = '';
				apply();
				input.focus();
			});
		}
	}

	/* ---------------- SCROLL REVEAL ---------------- */
	function initReveal() {
		var items = $$('.reveal');
		if (!items.length) { return; }

		if (REDUCE || !('IntersectionObserver' in window)) {
			items.forEach(function (el) { el.classList.add('in'); });
			return;
		}

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('in');
					io.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

		items.forEach(function (el) { io.observe(el); });
	}

	/* ---------------- STAT COUNTERS ---------------- */
	function runCounter(el) {
		var target = parseFloat(el.getAttribute('data-count'));
		var prefix = el.getAttribute('data-prefix') || '';
		var suffix = el.getAttribute('data-suffix') || '';

		if (isNaN(target)) { return; }

		if (REDUCE) {
			el.textContent = prefix + target + suffix;
			return;
		}

		var start = null;
		var duration = 1300;

		var step = function (ts) {
			if (start === null) { start = ts; }
			var p = Math.min((ts - start) / duration, 1);
			var eased = 1 - Math.pow(1 - p, 3);
			el.textContent = prefix + Math.round(target * eased) + suffix;
			if (p < 1) { window.requestAnimationFrame(step); }
		};

		window.requestAnimationFrame(step);
	}

	function initCounters() {
		var nums = $$('[data-count]');
		if (!nums.length) { return; }

		if (!('IntersectionObserver' in window)) {
			nums.forEach(runCounter);
			return;
		}

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					runCounter(entry.target);
					io.unobserve(entry.target);
				}
			});
		}, { threshold: 0.5 });

		nums.forEach(function (el) { io.observe(el); });
	}

	/* ---------------- REVIEW SLIDER ---------------- */
	function initReviews() {
		var track = $('#revTrack');
		if (!track) { return; }

		var slides = $$('.rev', track);
		if (slides.length < 1) { return; }

		var dots = $$('.rev-dot');
		var index = 0;
		var timer = null;

		var go = function (i) {
			index = (i + slides.length) % slides.length;
			track.style.transform = 'translate3d(-' + (index * 100) + '%,0,0)';
			dots.forEach(function (d, di) { d.classList.toggle('on', di === index); });
		};

		var play = function () {
			if (REDUCE || slides.length < 2) { return; }
			stop();
			timer = window.setInterval(function () { go(index + 1); }, 6500);
		};
		var stop = function () {
			if (timer) { window.clearInterval(timer); timer = null; }
		};

		var prev = $('#revPrev');
		var next = $('#revNext');
		if (prev) { prev.addEventListener('click', function () { go(index - 1); play(); }); }
		if (next) { next.addEventListener('click', function () { go(index + 1); play(); }); }
		dots.forEach(function (d, di) {
			d.addEventListener('click', function () { go(di); play(); });
		});

		var wrap = $('#revWrap');
		if (wrap) {
			wrap.addEventListener('mouseenter', stop);
			wrap.addEventListener('mouseleave', play);
			wrap.addEventListener('focusin', stop);

			var startX = null;
			wrap.addEventListener('touchstart', function (e) {
				startX = e.touches[0].clientX;
				stop();
			}, { passive: true });
			wrap.addEventListener('touchend', function (e) {
				if (startX === null) { return; }
				var dx = e.changedTouches[0].clientX - startX;
				if (Math.abs(dx) > 40) { go(index + (dx < 0 ? 1 : -1)); }
				startX = null;
				play();
			});
		}

		go(0);
		play();
	}

	/* ---------------- GALLERY LIGHTBOX ---------------- */
	function initGallery() {
		var box = $('#lightbox');
		var items = $$('.gitem');
		if (!box || !items.length) { return; }

		var img = $('#lbImg');
		var closeBtn = $('#lbClose');
		var prevBtn = $('#lbPrev');
		var nextBtn = $('#lbNext');
		var current = 0;
		var lastFocus = null;

		var show = function (i) {
			current = (i + items.length) % items.length;
			var src = items[current].getAttribute('data-full');
			var alt = items[current].getAttribute('data-alt') || 'The Spot photo';
			if (src) {
				img.setAttribute('src', src);
				img.setAttribute('alt', alt);
			}
		};

		var open = function (i) {
			lastFocus = document.activeElement;
			show(i);
			box.classList.add('open');
			box.setAttribute('aria-hidden', 'false');
			document.body.style.overflow = 'hidden';
			if (closeBtn) { closeBtn.focus(); }
		};

		var close = function () {
			box.classList.remove('open');
			box.setAttribute('aria-hidden', 'true');
			document.body.style.overflow = '';
			if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
		};

		items.forEach(function (item, i) {
			item.addEventListener('click', function () { open(i); });
		});

		if (closeBtn) { closeBtn.addEventListener('click', close); }
		if (prevBtn) { prevBtn.addEventListener('click', function () { show(current - 1); }); }
		if (nextBtn) { nextBtn.addEventListener('click', function () { show(current + 1); }); }

		box.addEventListener('click', function (e) {
			if (e.target === box) { close(); }
		});

		document.addEventListener('keydown', function (e) {
			if (!box.classList.contains('open')) { return; }
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowLeft') { show(current - 1); }
			if (e.key === 'ArrowRight') { show(current + 1); }
		});
	}

	/* ---------------- CONTACT / RESERVATION FORMS ---------------- */
	function initFormSwitch() {
		var buttons = $$('.fswitch button');
		if (!buttons.length) { return; }

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = btn.getAttribute('data-panel');
				buttons.forEach(function (b) {
					b.classList.toggle('on', b === btn);
					b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
				});
				$$('.fpanel').forEach(function (p) {
					p.classList.toggle('on', p.id === target);
				});
			});
		});
	}

	function setNonce(value) {
		$$('input[name="nonce"]').forEach(function (input) { input.value = value; });
	}

	function refreshNonce() {
		if (!DATA.ajaxUrl) { return Promise.resolve(''); }

		var body = new URLSearchParams();
		body.set('action', 'rp_refresh_nonce');

		return fetch(DATA.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				var fresh = res && res.success && res.data ? res.data.nonce : '';
				if (fresh) { setNonce(fresh); }
				return fresh || '';
			})
			.catch(function () { return ''; });
	}

	function initForms() {
		var forms = $$('form[data-rp-action]');
		if (!forms.length || !DATA.ajaxUrl) { return; }

		forms.forEach(function (form) {
			var msg = $('.fmsg', form);
			var button = $('.fsub', form);
			var busy = false;

			var say = function (text, ok) {
				if (!msg) { return; }
				msg.textContent = text;
				msg.className = 'fmsg on ' + (ok ? 'ok' : 'err');
			};

			var send = function (retried) {
				var body = new URLSearchParams();
				body.set('action', form.getAttribute('data-rp-action'));

				new FormData(form).forEach(function (value, key) {
					body.set(key, value);
				});

				return fetch(DATA.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString()
				})
					.then(function (r) {
						return r.json().then(function (json) { return { status: r.status, json: json }; });
					})
					.then(function (res) {
						// A page served from cache can carry an expired security
						// token — fetch a fresh one and try once more.
						if (res.status === 403 && !retried) {
							return refreshNonce().then(function (fresh) {
								if (!fresh) { throw new Error('expired'); }
								return send(true);
							});
						}

						if (!res.json || !res.json.success) {
							var m = res.json && res.json.data && res.json.data.message
								? res.json.data.message
								: 'Sorry, something went wrong. Please call or WhatsApp us instead.';
							throw new Error(m);
						}

						say(res.json.data && res.json.data.message ? res.json.data.message : 'Thank you!', true);
						form.reset();
						return true;
					});
			};

			form.addEventListener('submit', function (e) {
				e.preventDefault();
				if (busy) { return; }
				busy = true;

				var label = button ? button.textContent : '';
				if (button) {
					button.disabled = true;
					button.textContent = 'Sending…';
				}
				if (msg) { msg.className = 'fmsg'; }

				send(false)
					.catch(function (err) {
						say(err && err.message && err.message !== 'expired'
							? err.message
							: 'Sorry, we could not send that. Please call or WhatsApp us instead.', false);
					})
					.then(function () {
						busy = false;
						if (button) {
							button.disabled = false;
							button.textContent = label;
						}
					});
			});
		});
	}

	/* ---------------- BACK TO TOP ---------------- */
	function initToTop() {
		var btn = $('#toTop');
		if (!btn) { return; }

		var ticking = false;
		window.addEventListener('scroll', function () {
			if (ticking) { return; }
			ticking = true;
			window.requestAnimationFrame(function () {
				btn.classList.toggle('show', window.pageYOffset > 600);
				ticking = false;
			});
		}, { passive: true });

		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: REDUCE ? 'auto' : 'smooth' });
		});
	}

	/* ---------------- BOOT ---------------- */
	function boot() {
		initNav();
		initStagger();
		initTabs();
		initSearch();
		initReveal();
		initCounters();
		initReviews();
		initGallery();
		initFormSwitch();
		initForms();
		initToTop();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
