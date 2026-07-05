/**
 * Cookie — frontend consent manager for the ProcessWire "Cookie" module.
 *
 * Reads window.pwcmConfig (emitted by the module), manages the consent cookie,
 * unblocks gated elements, renders placeholders, updates body classes,
 * fires Google Consent Mode updates and dispatches CustomEvents.
 *
 * Public API: window.pwCookie
 * Events (on document): pwcm:init, pwcm:show, pwcm:hide, pwcm:save, pwcm:allow-once
 */
(function () {
	"use strict";

	var cfg = window.pwcmConfig || {};
	var P = cfg.prefix || "pwcm";
	var MS_DAY = 86400000;

	var BOT_RE = /bot|spider|crawl|slurp|APIs-Google|AdsBot|Googlebot|mediapartners|Google Favicon|Chrome-Lighthouse|FeedFetcher|Google-Read-Aloud|googleweblight|bing|yandex|baidu|duckduck|yahoo|ecosia|ia_archiver|facebook|instagram|pinterest|reddit|slack|twitter|whatsapp|semrush/i;

	function CookieManager() {
		this.root = document.getElementById(P + "-root");
		if (!this.root) return;

		this.categories = cfg.categories || [];
		this.requiredKeys = [];
		for (var i = 0; i < this.categories.length; i++) {
			if (this.categories[i].required) this.requiredKeys.push(this.categories[i].key);
		}

		this.ephemeral = false; // true for bots / DNT: consent kept in memory only
		this.phId = 0;
		this.lastFocus = null;
		this.openWindow = null; // "banner" | "prefs" | null

		this.el = {
			overlay: this.root.querySelector("." + P + "-overlay"),
			banner: this.root.querySelector("." + P + "-banner"),
			prefs: this.root.querySelector("." + P + "-prefs"),
			toast: this.root.querySelector("." + P + "-toast"),
			fab: this.root.querySelector("." + P + "-fab")
		};
		this.tpl = document.getElementById(P + "-ph-tpl");

		var stored = this.readConsent();
		this.consent = stored.consent;
		this.valid = stored.valid;

		this.bindActions();
		this.bindKeydown();
		this.syncCheckboxes();
		this.updateBodyClasses();
		if (this.valid) this.applyConsentMode();
		this.process();

		if (!this.valid && this.applyBotsAndDnt()) {
			// silent necessary-only consent, no banner, nothing persisted
		} else if (!this.valid && cfg.autoShow !== false) {
			this.show();
		}
		this.updateFab();
		if (cfg.observe) this.observeDom();
		this.emit("pwcm:init", { consent: this.getConsent() });
	}

	CookieManager.prototype = {

		/* ================= consent state ================= */

		defaultConsent: function () {
			// opt-in (GDPR): only required categories granted by default;
			// opt-out (CCPA): everything granted until the visitor refuses,
			// except a valid GPC signal which is a binding opt-out of "sale/share"
			var grantAll = cfg.model === "optout";
			var g = {};
			for (var i = 0; i < this.categories.length; i++) {
				g[this.categories[i].key] = this.categories[i].required === true || grantAll;
			}
			if (grantAll && cfg.gpc && this.hasGpc() && typeof g.marketing === "boolean") {
				g.marketing = false;
			}
			return { v: 0, t: null, g: g };
		},

		hasGpc: function () {
			return navigator.globalPrivacyControl === true || navigator.globalPrivacyControl === "1";
		},

		readConsent: function () {
			var def = this.defaultConsent();
			var raw = this.getCookie(cfg.cookieName || "pwcm_consent");
			if (!raw) return { consent: def, valid: false };
			var data;
			try {
				data = JSON.parse(raw);
			} catch (e) {
				return { consent: def, valid: false };
			}
			if (!data || typeof data.g !== "object" || data.g === null) {
				return { consent: def, valid: false };
			}
			// keep stored choices for pre-selecting checkboxes even when invalid
			for (var key in def.g) {
				if (typeof data.g[key] === "boolean") def.g[key] = data.g[key];
			}
			for (var i = 0; i < this.requiredKeys.length; i++) def.g[this.requiredKeys[i]] = true;
			def.v = parseInt(data.v, 10) || 0;
			def.t = parseInt(data.t, 10) || null;

			var valid = def.v === (cfg.version || 1) && !!def.t;
			if (valid && cfg.expireDays > 0 && Date.now() > def.t + cfg.expireDays * MS_DAY) valid = false;
			return { consent: def, valid: valid };
		},

		writeConsent: function () {
			if (this.ephemeral) return;
			var days = cfg.expireDays > 0 ? cfg.expireDays : 180;
			var expires = new Date(Date.now() + days * MS_DAY).toUTCString();
			var value = encodeURIComponent(JSON.stringify({
				v: this.consent.v,
				t: this.consent.t,
				g: this.consent.g
			}));
			var secure = location.protocol === "https:" ? ";Secure" : "";
			document.cookie = (cfg.cookieName || "pwcm_consent") + "=" + value +
				";expires=" + expires + ";path=/;SameSite=Lax" + secure;
		},

		getCookie: function (name) {
			var parts = ("; " + document.cookie).split("; " + name + "=");
			if (parts.length < 2) return null;
			return decodeURIComponent(parts.pop().split(";").shift());
		},

		applyBotsAndDnt: function () {
			var isBot = cfg.bots && BOT_RE.test(navigator.userAgent);
			var isDnt = cfg.dnt && (navigator.doNotTrack === "1" || window.doNotTrack === "1");
			// in the opt-in model a GPC signal is honored like Do Not Track
			var isGpc = cfg.gpc && cfg.model !== "optout" && this.hasGpc();
			if (!isBot && !isDnt && !isGpc) return false;
			this.ephemeral = true;
			this.consent = this.defaultConsent();
			this.valid = true;
			this.updateBodyClasses();
			return true;
		},

		save: function (silent) {
			var prevGranted = [];
			if (this.valid) {
				for (var key in this.prevSnapshot || {}) {
					if (this.prevSnapshot[key]) prevGranted.push(key);
				}
			}
			this.consent.v = cfg.version || 1;
			this.consent.t = Date.now();
			this.writeConsent();
			this.valid = true;

			// find revoked categories (granted before, denied now)
			var revoked = [];
			for (var i = 0; i < prevGranted.length; i++) {
				if (!this.consent.g[prevGranted[i]]) revoked.push(prevGranted[i]);
			}

			this.syncCheckboxes();
			this.updateBodyClasses();
			this.applyConsentMode();
			this.process();
			this.hideAll();
			this.logConsent();
			this.triggerCustomFunction();
			this.emit("pwcm:save", { consent: this.getConsent(), revoked: revoked });

			if (revoked.length) {
				this.clearCookiesFor(revoked);
				if (cfg.reloadOnRevoke) {
					location.reload();
					return;
				}
			}
			if (!silent) this.showToast();
			this.snapshot();
		},

		snapshot: function () {
			this.prevSnapshot = {};
			for (var key in this.consent.g) this.prevSnapshot[key] = this.consent.g[key];
		},

		clearCookiesFor: function (revokedKeys) {
			var map = cfg.cookiesToClear || {};
			var host = location.hostname;
			for (var i = 0; i < revokedKeys.length; i++) {
				var names = map[revokedKeys[i]] || [];
				for (var j = 0; j < names.length; j++) {
					var name = names[j];
					var expire = "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
					document.cookie = name + expire;
					document.cookie = name + expire + ";domain=" + host;
					document.cookie = name + expire + ";domain=." + host;
				}
			}
		},

		/* ================= UI ================= */

		bindActions: function () {
			var self = this;
			// widget action buttons
			var buttons = this.root.querySelectorAll("[data-action]");
			for (var i = 0; i < buttons.length; i++) {
				(function (btn) {
					btn.addEventListener("click", function (e) {
						e.preventDefault();
						self.handleAction(btn.getAttribute("data-action"));
					});
				})(buttons[i]);
			}
			// delegated triggers anywhere on the page (footer links, AJAX content, placeholders)
			document.addEventListener("click", function (e) {
				var trigger = e.target.closest ? e.target.closest("." + P + "-show-prefs") : null;
				if (trigger) {
					e.preventDefault();
					self.showPreferences();
					return;
				}
				var phBtn = e.target.closest ? e.target.closest("[data-ph]") : null;
				if (phBtn) {
					e.preventDefault();
					self.handlePlaceholderButton(phBtn);
				}
			});
		},

		handleAction: function (action) {
			switch (action) {
				case "accept-all": this.acceptAll(); break;
				case "reject": this.rejectAll(); break;
				case "prefs": this.showPreferences(); break;
				case "save": this.saveFromCheckboxes(); break;
				case "close": this.handleClose(); break;
			}
		},

		handleClose: function () {
			// opt-out model requires no decision: closing is always allowed
			if (this.valid || cfg.model === "optout" || cfg.autoShow === false) {
				this.hideAll();
				this.updateFab();
			} else {
				this.show(); // no decision yet: back to the banner
			}
		},

		bindKeydown: function () {
			var self = this;
			document.addEventListener("keydown", function (e) {
				var prefsOpen = self.openWindow === "prefs";
				var bannerOpen = self.openWindow === "banner";
				if (e.key === "Escape") {
					if (prefsOpen) {
						e.preventDefault();
						self.handleClose();
					} else if (bannerOpen && (self.valid || cfg.model === "optout")) {
						e.preventDefault();
						self.hideAll();
					}
				} else if (e.key === "Tab" && prefsOpen) {
					self.trapFocus(e);
				}
			});
		},

		trapFocus: function (e) {
			var focusable = this.el.prefs.querySelectorAll("button, a[href], input:not([disabled]), summary, [tabindex]:not([tabindex='-1'])");
			if (!focusable.length) return;
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		},

		open: function (el) {
			if (!el) return;
			if (el._pwcmTimer) {
				clearTimeout(el._pwcmTimer);
				el._pwcmTimer = null;
			}
			el.hidden = false;
			requestAnimationFrame(function () {
				requestAnimationFrame(function () {
					el.classList.add("is-open");
				});
			});
		},

		close: function (el) {
			if (!el || el.hidden) return;
			el.classList.remove("is-open");
			if (el._pwcmTimer) clearTimeout(el._pwcmTimer);
			el._pwcmTimer = setTimeout(function () {
				el._pwcmTimer = null;
				el.hidden = true;
			}, 260);
		},

		show: function () {
			this.openWindow = "banner";
			this.close(this.el.prefs);
			this.open(this.el.banner);
			this.setOverlay(this.root.getAttribute("data-overlay") === "1");
			this.updateFab();
			if (cfg.bodyClasses !== false) document.body.classList.add(P + "-open");
			this.emit("pwcm:show", { window: "banner" });
		},

		showPreferences: function () {
			this.openWindow = "prefs";
			this.lastFocus = document.activeElement;
			this.close(this.el.banner);
			this.open(this.el.prefs);
			this.setOverlay(true);
			this.updateFab();
			if (cfg.bodyClasses !== false) document.body.classList.add(P + "-open");
			var self = this;
			setTimeout(function () {
				var first = self.el.prefs.querySelector("button, input:not([disabled])");
				if (first) first.focus();
			}, 60);
			this.emit("pwcm:show", { window: "preferences" });
		},

		hideAll: function () {
			this.openWindow = null;
			this.close(this.el.banner);
			this.close(this.el.prefs);
			this.setOverlay(false);
			if (cfg.bodyClasses !== false) document.body.classList.remove(P + "-open");
			if (this.lastFocus && this.lastFocus.focus) {
				try { this.lastFocus.focus(); } catch (e) { /* detached */ }
				this.lastFocus = null;
			}
			this.updateFab();
			this.emit("pwcm:hide", {});
		},

		setOverlay: function (on) {
			var overlay = this.el.overlay;
			if (!overlay) return;
			if (overlay._pwcmTimer) {
				clearTimeout(overlay._pwcmTimer);
				overlay._pwcmTimer = null;
			}
			if (on) {
				overlay.hidden = false;
				requestAnimationFrame(function () {
					requestAnimationFrame(function () {
						overlay.classList.add("is-open");
					});
				});
			} else {
				overlay.classList.remove("is-open");
				overlay._pwcmTimer = setTimeout(function () {
					overlay._pwcmTimer = null;
					overlay.hidden = true;
				}, 260);
			}
		},

		showToast: function () {
			var timeout = typeof cfg.messageTimeout === "number" ? cfg.messageTimeout : 1500;
			if (!this.el.toast || timeout === 0) return;
			var toast = this.el.toast;
			toast.hidden = false;
			requestAnimationFrame(function () { toast.classList.add("is-open"); });
			setTimeout(function () {
				toast.classList.remove("is-open");
				setTimeout(function () { toast.hidden = true; }, 220);
			}, timeout);
		},

		updateFab: function () {
			if (!this.el.fab) return;
			if (this.el.fab.getAttribute("data-disabled") === "1") {
				this.el.fab.hidden = true;
				return;
			}
			this.el.fab.hidden = this.openWindow !== null;
		},

		syncCheckboxes: function () {
			var boxes = this.root.querySelectorAll("[data-consent-cat]");
			for (var i = 0; i < boxes.length; i++) {
				var key = boxes[i].getAttribute("data-consent-cat");
				if (boxes[i].disabled) continue;
				boxes[i].checked = !!this.consent.g[key];
			}
		},

		saveFromCheckboxes: function () {
			this.snapshotBeforeChange();
			var boxes = this.root.querySelectorAll("[data-consent-cat]");
			for (var i = 0; i < boxes.length; i++) {
				var key = boxes[i].getAttribute("data-consent-cat");
				if (boxes[i].disabled) continue;
				this.consent.g[key] = boxes[i].checked;
			}
			this.save();
		},

		snapshotBeforeChange: function () {
			if (!this.prevSnapshot) this.snapshot();
		},

		/* ================= body classes / integrations ================= */

		updateBodyClasses: function () {
			if (cfg.bodyClasses === false) return;
			for (var key in this.consent.g) {
				document.body.classList.toggle("consent-" + key, !!this.consent.g[key]);
			}
		},

		applyConsentMode: function () {
			if (!cfg.consentMode) return;
			var map = cfg.consentModeMap || {};
			var update = { security_storage: "granted" };
			for (var cat in map) {
				var granted = !!this.consent.g[cat];
				var signals = map[cat] || [];
				for (var i = 0; i < signals.length; i++) {
					update[signals[i]] = granted ? "granted" : "denied";
				}
			}
			window.dataLayer = window.dataLayer || [];
			if (typeof window.gtag === "function") {
				window.gtag("consent", "update", update);
			} else {
				window.dataLayer.push(["consent", "update", update]);
			}
		},

		logConsent: function () {
			if (!cfg.logEndpoint || this.ephemeral) return;
			var payload = JSON.stringify({ v: this.consent.v, g: this.consent.g });
			try {
				if (navigator.sendBeacon) {
					navigator.sendBeacon(cfg.logEndpoint, new Blob([payload], { type: "application/json" }));
				} else {
					var xhr = new XMLHttpRequest();
					xhr.open("POST", cfg.logEndpoint, true);
					xhr.setRequestHeader("Content-Type", "application/json");
					xhr.send(payload);
				}
			} catch (e) { /* logging must never break the page */ }
		},

		triggerCustomFunction: function () {
			var name = cfg.customFunction;
			if (name && typeof window[name] === "function") {
				try { window[name](this.getConsent()); } catch (e) {
					if (window.console) console.error("pwCookie custom function error:", e);
				}
			}
		},

		/* ================= element blocking ================= */

		parseCats: function (el) {
			var raw = el.getAttribute("data-consent") || "";
			return raw.split(/[,|]/).map(function (s) { return s.trim(); }).filter(Boolean);
		},

		allowedFor: function (cats) {
			for (var i = 0; i < cats.length; i++) {
				if (!this.consent.g[cats[i]]) return false;
			}
			return cats.length > 0;
		},

		process: function () {
			var nodes = document.querySelectorAll("[data-consent]");
			for (var i = 0; i < nodes.length; i++) {
				var el = nodes[i];
				if (this.root.contains(el)) continue;
				var cats = this.parseCats(el);
				if (!cats.length) continue;
				if (this.allowedFor(cats)) {
					this.unblock(el);
				} else {
					this.block(el);
				}
			}
		},

		block: function (el) {
			var isScript = el.tagName === "SCRIPT";
			if (isScript) {
				// scripts without a neutralizing type have already executed — never touch them
				var type = (el.getAttribute("type") || "").toLowerCase();
				if (type !== "text/plain" && type !== "optin") {
					if (window.console && !el.hasAttribute("data-pwcm-warned")) {
						console.warn("pwCookie: blocked <script> must use type=\"text/plain\"", el);
						el.setAttribute("data-pwcm-warned", "1");
					}
				}
				return;
			}
			el.classList.add(P + "-blocked");
			if (el.hasAttribute("data-placeholder") && !el.hasAttribute("data-pwcm-ph")) {
				this.renderPlaceholder(el);
			}
		},

		renderPlaceholder: function (el) {
			if (!this.tpl) return;
			var id = String(++this.phId);
			el.setAttribute("data-pwcm-ph", id);

			var wrap = document.createElement("div");
			wrap.innerHTML = this.tpl.innerHTML;
			var ph = wrap.firstElementChild;
			if (!ph) return;
			ph.setAttribute("data-pwcm-ph-for", id);

			var cats = this.parseCats(el);
			var labels = [];
			for (var i = 0; i < this.categories.length; i++) {
				if (cats.indexOf(this.categories[i].key) !== -1) labels.push(this.categories[i].label);
			}
			var msg = ph.querySelector("." + P + "-ph-msg");
			if (msg) {
				var text = el.getAttribute("data-placeholder-message") || msg.textContent;
				msg.textContent = text.replace("{category}", labels.join(", "));
			}
			var alwaysBtn = ph.querySelector("[data-ph='always']");
			if (alwaysBtn && el.getAttribute("data-placeholder-button")) {
				alwaysBtn.textContent = el.getAttribute("data-placeholder-button");
			}

			// video preview poster: server-cached URL (data-ph-poster) preferred,
			// else a client-side guess for YouTube (data-src). Poster loads only
			// when the module downloaded it locally — never leaks IP to the platform.
			if (cfg.videoPreview) {
				var poster = el.getAttribute("data-ph-poster") || this.guessPoster(el);
				var slot = ph.querySelector("." + P + "-ph-poster");
				if (poster && slot) {
					slot.style.backgroundImage = "url('" + poster.replace(/'/g, "%27") + "')";
					slot.hidden = false;
					ph.classList.add(P + "-ph-hasposter");
				}
			}

			el.insertAdjacentElement("afterend", ph);
		},

		// only local/first-party posters are used client-side to avoid leaking
		// the visitor IP to the video host before consent
		guessPoster: function (el) {
			var poster = el.getAttribute("poster") || el.getAttribute("data-poster");
			return poster || "";
		},

		handlePlaceholderButton: function (btn) {
			var ph = btn.closest("[data-pwcm-ph-for]");
			if (!ph) return;
			var id = ph.getAttribute("data-pwcm-ph-for");
			var el = document.querySelector("[data-pwcm-ph='" + id + "']");
			if (!el) { ph.remove(); return; }

			if (btn.getAttribute("data-ph") === "load") {
				// one-time load: unblock this element only, consent unchanged
				this.unblock(el);
				this.emit("pwcm:allow-once", { element: el });
			} else {
				// grant all required categories permanently
				this.snapshotBeforeChange();
				var cats = this.parseCats(el);
				for (var i = 0; i < cats.length; i++) this.consent.g[cats[i]] = true;
				this.save();
			}
		},

		unblock: function (el) {
			if (el.getAttribute("data-pwcm-done") === "1") return;
			if (el.tagName === "SCRIPT") {
				this.unblockScript(el);
			} else {
				this.unblockOther(el);
			}
		},

		unblockScript: function (el) {
			var type = (el.getAttribute("type") || "").toLowerCase();
			if (type !== "text/plain" && type !== "optin") return; // already executed
			var newEl = document.createElement("script");
			// preserve every attribute (classes, ids, data-* of third-party embeds)
			for (var i = 0; i < el.attributes.length; i++) {
				var attr = el.attributes[i];
				if (/^(type|data-consent|data-src|data-type|data-placeholder|data-placeholder-message|data-placeholder-button|data-pwcm-.*)$/.test(attr.name)) continue;
				newEl.setAttribute(attr.name, attr.value);
			}
			newEl.type = el.getAttribute("data-type") || "text/javascript";
			var src = el.getAttribute("data-src") || el.getAttribute("src");
			if (src) newEl.src = src;
			newEl.textContent = el.textContent;
			this.removePlaceholderOf(el);
			el.insertAdjacentElement("afterend", newEl);
			el.remove();
		},

		unblockOther: function (el) {
			// mutate in place: keeps classes, attributes and JS references intact
			var mapping = { "data-src": "src", "data-srcset": "srcset", "data-srcdoc": "srcdoc", "data-poster": "poster" };
			for (var dataAttr in mapping) {
				var value = el.getAttribute(dataAttr);
				if (value !== null) {
					el.setAttribute(mapping[dataAttr], value);
					el.removeAttribute(dataAttr);
				}
			}
			el.classList.remove(P + "-blocked");
			if (el.classList.length === 0) el.removeAttribute("class");
			this.removePlaceholderOf(el);
			el.removeAttribute("data-consent");
			el.removeAttribute("data-placeholder");
			el.removeAttribute("data-placeholder-message");
			el.removeAttribute("data-placeholder-button");
			el.setAttribute("data-pwcm-done", "1");
		},

		removePlaceholderOf: function (el) {
			var id = el.getAttribute("data-pwcm-ph");
			if (!id) return;
			var ph = document.querySelector("[data-pwcm-ph-for='" + id + "']");
			if (ph) ph.remove();
			el.removeAttribute("data-pwcm-ph");
		},

		/* ================= dynamic content ================= */

		observeDom: function () {
			if (!window.MutationObserver) return;
			var self = this;
			var scheduled = false;
			var observer = new MutationObserver(function (mutations) {
				var relevant = false;
				for (var i = 0; i < mutations.length; i++) {
					var added = mutations[i].addedNodes;
					for (var j = 0; j < added.length; j++) {
						var node = added[j];
						if (node.nodeType !== 1) continue;
						if (node.hasAttribute && node.hasAttribute("data-consent")) { relevant = true; break; }
						if (node.querySelector && node.querySelector("[data-consent]")) { relevant = true; break; }
					}
					if (relevant) break;
				}
				if (relevant && !scheduled) {
					scheduled = true;
					requestAnimationFrame(function () {
						scheduled = false;
						self.process();
					});
				}
			});
			observer.observe(document.body, { childList: true, subtree: true });
		},

		/* ================= public API ================= */

		getConsent: function () {
			var g = {};
			for (var key in this.consent.g) g[key] = this.consent.g[key];
			return { version: this.consent.v, storedAt: this.consent.t, valid: this.valid, categories: g };
		},

		hasConsent: function (cat) {
			return this.valid && !!this.consent.g[cat];
		},

		allow: function (cat) {
			this.snapshotBeforeChange();
			if (typeof this.consent.g[cat] === "undefined") return;
			this.consent.g[cat] = true;
			this.save(true);
		},

		revoke: function (cat) {
			this.snapshotBeforeChange();
			if (typeof this.consent.g[cat] === "undefined") return;
			if (this.requiredKeys.indexOf(cat) !== -1) return;
			this.consent.g[cat] = false;
			this.save(true);
		},

		acceptAll: function () {
			this.snapshotBeforeChange();
			for (var key in this.consent.g) this.consent.g[key] = true;
			this.save();
		},

		rejectAll: function () {
			this.snapshotBeforeChange();
			var def = this.defaultConsent();
			this.consent.g = def.g;
			this.save();
		},

		reset: function () {
			document.cookie = (cfg.cookieName || "pwcm_consent") + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
			var def = this.defaultConsent();
			this.consent = def;
			this.valid = false;
			this.prevSnapshot = null;
			this.syncCheckboxes();
			this.updateBodyClasses();
			this.show();
		},

		refresh: function () {
			this.process();
		},

		hide: function () {
			this.hideAll();
		},

		emit: function (name, detail) {
			try {
				document.dispatchEvent(new CustomEvent(name, { detail: detail }));
			} catch (e) { /* very old browsers */ }
		}
	};

	function boot() {
		var manager = new CookieManager();
		if (!manager.root) return;
		manager.snapshot();
		window.pwCookie = manager;
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", boot);
	} else {
		boot();
	}
})();
