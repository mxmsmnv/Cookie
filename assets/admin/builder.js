/**
 * Cookie — interactive widget builder (ProcessCookie).
 *
 * Binds the control panel to the live preview: every input change updates
 * CSS custom properties / data attributes on the preview widget instantly.
 * Persists design settings via POST to ./save/.
 */
(function () {
	"use strict";

	function init() {
		var boot = window.pwcmBuilder;
		var wrap = document.getElementById("pwb");
		if (!boot || !wrap) return;

		var P = boot.prefix;
		var root = document.getElementById(P + "-root");
		var viewport = document.getElementById("pwb-viewport");
		var phDemo = document.getElementById("pwb-ph-demo");
		var status = document.getElementById("pwb-status");
		var dirty = false;

		if (!root || !viewport) return;
		root.setAttribute("data-preview", "banner");

		/* ---------------- value → preview appliers ---------------- */

		var varMap = {
			design_color_bg: "bg",
			design_color_text: "text",
			design_color_primary: "primary",
			design_color_primary_text: "primary-text",
			design_color_secondary: "secondary",
			design_color_secondary_text: "secondary-text",
			design_color_link: "link",
			design_color_accent: "accent",
			icon_color_bg: "fab-bg",
			icon_color_fg: "fab-fg"
		};

		// dark_color_* map to the same CSS var suffix; only applied to the preview
		// while the dark preview toggle is on
		var darkVarMap = {
			dark_color_bg: "bg",
			dark_color_text: "text",
			dark_color_primary: "primary",
			dark_color_primary_text: "primary-text",
			dark_color_secondary: "secondary",
			dark_color_secondary_text: "secondary-text",
			dark_color_link: "link",
			dark_color_accent: "accent"
		};
		var previewDark = false;

		var pxMap = {
			design_font_size: "fs",
			design_font_size_title: "fs-title",
			design_font_size_button: "fs-btn",
			design_radius: "radius",
			design_max_width: "maxw",
			design_spacing: "pad",
			icon_size: "fab-size",
			icon_offset_x: "fab-x",
			icon_offset_y: "fab-y"
		};

		var shadowMap = {
			none: "none",
			soft: "0 2px 6px rgba(15,23,42,.06), 0 16px 40px rgba(15,23,42,.16)",
			strong: "0 4px 14px rgba(15,23,42,.12), 0 28px 72px rgba(15,23,42,.34)"
		};

		var iconShadowMap = {
			none: "none",
			soft: "drop-shadow(0 2px 5px rgba(15,23,42,.45))",
			strong: "drop-shadow(0 4px 10px rgba(15,23,42,.65))"
		};

		function setVar(name, value) {
			// placeholders define the vars themselves (they live outside the root),
			// so the live preview must update every carrier element
			var targets = viewport.querySelectorAll("." + P + "-root, ." + P + "-ph");
			for (var i = 0; i < targets.length; i++) {
				targets[i].style.setProperty("--" + P + "-" + name, value);
			}
		}

		function getValue(key) {
			var radio = wrap.querySelector("input[type=radio][data-key='" + key + "']:checked");
			if (radio) return radio.value;
			var input = wrap.querySelector("[data-key='" + key + "']");
			if (!input) return null;
			if (input.type === "checkbox") return input.checked ? "1" : "0";
			return input.value;
		}

		function resolveFont() {
			var family = getValue("design_font_family");
			switch (family) {
				case "system": return "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
				case "serif": return "Georgia,'Times New Roman',serif";
				case "mono": return "ui-monospace,'SF Mono',Menlo,Consolas,monospace";
				case "custom": return getValue("design_font_custom") || "inherit";
				default: return "inherit";
			}
		}

		function resolvePosition() {
			var layout = getValue("design_layout");
			if (layout === "bar") return getValue("design_position_bar") || "bottom";
			if (layout === "modal") return "center";
			return getValue("design_position") || "bottom-right";
		}

		function updateConditionalRows() {
			var layout = getValue("design_layout");
			var font = getValue("design_font_family");
			var rows = wrap.querySelectorAll("[data-show-layout]");
			for (var i = 0; i < rows.length; i++) {
				rows[i].style.display = rows[i].getAttribute("data-show-layout") === layout ? "" : "none";
			}
			var fontRows = wrap.querySelectorAll("[data-show-font]");
			for (var j = 0; j < fontRows.length; j++) {
				fontRows[j].style.display = fontRows[j].getAttribute("data-show-font") === font ? "" : "none";
			}
			var darkOn = getValue("dark_enable") === "1";
			var darkRows = wrap.querySelectorAll("[data-show-dark]");
			for (var dk = 0; dk < darkRows.length; dk++) {
				darkRows[dk].style.opacity = darkOn ? "1" : "0.4";
				darkRows[dk].style.pointerEvents = darkOn ? "" : "none";
			}
			var transparentOn = getValue("icon_transparent") === "1";
			var bgRows = wrap.querySelectorAll("[data-show-icon-bg]");
			for (var ib = 0; ib < bgRows.length; ib++) {
				bgRows[ib].style.opacity = transparentOn ? "0.4" : "1";
				bgRows[ib].style.pointerEvents = transparentOn ? "none" : "";
			}
			var shadowRows = wrap.querySelectorAll("[data-show-icon-shadow]");
			for (var is = 0; is < shadowRows.length; is++) {
				shadowRows[is].style.opacity = transparentOn ? "1" : "0.4";
				shadowRows[is].style.pointerEvents = transparentOn ? "" : "none";
			}
		}

		function apply(key) {
			var value = getValue(key);
			if (value === null) return;

			// light color: takes effect only when the dark preview is off
			if (varMap[key]) { if (!previewDark) setVar(varMap[key], value); return; }
			// dark color: takes effect only when the dark preview is on
			if (darkVarMap[key]) { if (previewDark) setVar(darkVarMap[key], value); return; }
			if (pxMap[key]) { setVar(pxMap[key], parseInt(value, 10) + "px"); return; }

			switch (key) {
				case "design_layout":
					root.setAttribute("data-layout", value);
					root.setAttribute("data-position", resolvePosition());
					updateConditionalRows();
					break;
				case "design_position":
				case "design_position_bar":
					root.setAttribute("data-position", resolvePosition());
					break;
				case "design_overlay":
					root.setAttribute("data-overlay", value === "1" ? "1" : "0");
					break;
				case "design_shadow":
					setVar("shadow", shadowMap[value] || shadowMap.soft);
					break;
				case "design_font_family":
					setVar("font", resolveFont());
					updateConditionalRows();
					break;
				case "design_font_custom":
					if (getValue("design_font_family") === "custom") setVar("font", resolveFont());
					break;
				case "icon_type":
					var fab = root.querySelector("." + P + "-fab");
					if (fab) {
						if (boot.icons[value]) fab.innerHTML = boot.icons[value];
						fab.setAttribute("data-icon", value);
					}
					break;
				case "icon_position":
					root.setAttribute("data-fab-pos", value);
					break;
				case "icon_shape":
					root.setAttribute("data-fab-shape", value);
					break;
				case "icon_show":
					root.classList.toggle("pwb-fab-off", value !== "1");
					break;
				case "icon_transparent":
					root.setAttribute("data-fab-transparent", value === "1" ? "1" : "0");
					updateConditionalRows();
					break;
				case "icon_shadow":
					setVar("fab-icon-shadow", iconShadowMap[value] || iconShadowMap.soft);
					break;
				case "dark_enable":
					updateConditionalRows();
					break;
			}
		}

		function applyAll() {
			var inputs = wrap.querySelectorAll("[data-key]");
			var seen = {};
			for (var i = 0; i < inputs.length; i++) {
				var key = inputs[i].getAttribute("data-key");
				if (seen[key]) continue;
				seen[key] = true;
				apply(key);
			}
		}

		/* ---------------- events ---------------- */

		// keep every range slider's number input in sync, in both directions
		function bindRangeNumberInputs() {
			var fields = wrap.querySelectorAll(".pwb-field-range");
			for (var i = 0; i < fields.length; i++) {
				var range = fields[i].querySelector("input[type=range]");
				var num = fields[i].querySelector(".pwb-range-num");
				if (!range || !num) continue;
				(function (range, num) {
					var min = parseInt(range.min, 10);
					var max = parseInt(range.max, 10);

					// dragging the slider updates the number field live
					range.addEventListener("input", function () {
						num.value = range.value;
					});

					// typing a valid in-range number updates the slider + preview live;
					// out-of-range/incomplete input is left alone so the user can keep typing
					num.addEventListener("input", function () {
						var v = parseInt(num.value, 10);
						if (isNaN(v) || v < min || v > max) return;
						range.value = v;
						range.dispatchEvent(new Event("input", { bubbles: true }));
					});

					// on blur/Enter, clamp to a valid value so the field never gets stuck
					num.addEventListener("change", function () {
						var v = parseInt(num.value, 10);
						if (isNaN(v)) v = parseInt(range.value, 10);
						v = Math.min(max, Math.max(min, v));
						num.value = v;
						range.value = v;
						range.dispatchEvent(new Event("input", { bubbles: true }));
					});
				})(range, num);
			}
		}
		bindRangeNumberInputs();

		wrap.addEventListener("input", function (e) {
			var el = e.target;
			var key = el.getAttribute("data-key");
			if (!key) return;
			apply(key);
			markDirty();
		});

		wrap.addEventListener("change", function (e) {
			var key = e.target.getAttribute("data-key");
			if (!key) return;
			apply(key);
			markDirty();
		});

		// one-click color presets: applies an array of colors to a fixed list of
		// keys, in order — used for both the main design palettes and the
		// floating-icon color presets
		function bindPalettes(selector, dataAttr, keys) {
			var buttons = wrap.querySelectorAll(selector);
			for (var i = 0; i < buttons.length; i++) {
				(function (btn) {
					btn.addEventListener("click", function () {
						var colors;
						try { colors = JSON.parse(btn.getAttribute(dataAttr)); } catch (e) { return; }
						for (var j = 0; j < keys.length; j++) {
							var input = wrap.querySelector("[data-key='" + keys[j] + "']");
							if (input && colors[j]) {
								input.value = colors[j];
								apply(keys[j]);
							}
						}
						markDirty();
					});
				})(buttons[i]);
			}
		}

		bindPalettes(".pwb-palette", "data-palette", [
			"design_color_bg", "design_color_text", "design_color_primary", "design_color_primary_text",
			"design_color_secondary", "design_color_secondary_text", "design_color_link", "design_color_accent"
		]);

		bindPalettes(".pwb-icon-swatch", "data-icon-palette", ["icon_color_bg", "icon_color_fg"]);

		bindPalettes(".pwb-dark-palette", "data-dark-palette", [
			"dark_color_bg", "dark_color_text", "dark_color_primary", "dark_color_primary_text",
			"dark_color_secondary", "dark_color_secondary_text", "dark_color_link", "dark_color_accent"
		]);

		// control group tabs (left panel)
		var navBtns = wrap.querySelectorAll(".pwb-nav-btn");
		var groups = wrap.querySelectorAll(".pwb-group");
		for (var g = 0; g < navBtns.length; g++) {
			(function (btn) {
				btn.addEventListener("click", function () {
					var name = btn.getAttribute("data-group");
					for (var n = 0; n < navBtns.length; n++) navBtns[n].classList.remove("is-active");
					for (var p = 0; p < groups.length; p++) {
						groups[p].classList.toggle("is-active", groups[p].getAttribute("data-group") === name);
					}
					btn.classList.add("is-active");
				});
			})(navBtns[g]);
		}

		// preview tabs
		var tabs = wrap.querySelectorAll(".pwb-tab");
		for (var k = 0; k < tabs.length; k++) {
			(function (tab) {
				tab.addEventListener("click", function () {
					for (var m = 0; m < tabs.length; m++) tabs[m].classList.remove("is-active");
					tab.classList.add("is-active");
					var name = tab.getAttribute("data-tab");
					root.setAttribute("data-preview", name === "placeholder" ? "none" : name);
					if (phDemo) phDemo.hidden = name !== "placeholder";
				});
			})(tabs[k]);
		}

		// device toggle (desktop / mobile preview)
		var browser = document.getElementById("pwb-browser");
		var deviceBtns = wrap.querySelectorAll(".pwb-device");
		for (var dv = 0; dv < deviceBtns.length; dv++) {
			(function (btn) {
				btn.addEventListener("click", function () {
					for (var m = 0; m < deviceBtns.length; m++) deviceBtns[m].classList.remove("is-active");
					btn.classList.add("is-active");
					if (browser) browser.classList.toggle("pwb-browser-mobile", btn.getAttribute("data-device") === "mobile");
				});
			})(deviceBtns[dv]);
		}

		// re-apply every color var for the current theme mode
		function applyColors() {
			var map = previewDark ? darkVarMap : varMap;
			for (var key in map) {
				var value = getValue(key);
				if (value !== null) setVar(map[key], value);
			}
		}

		// dark preview toggle: flips the preview colors to the dark set
		var themeBtn = document.getElementById("pwb-themetoggle");
		if (themeBtn) {
			themeBtn.addEventListener("click", function () {
				previewDark = !previewDark;
				themeBtn.classList.toggle("is-active", previewDark);
				if (browser) browser.classList.toggle("pwb-browser-dark", previewDark);
				if (previewDark) {
					var darkNav = wrap.querySelector(".pwb-nav-btn[data-group='dark']");
					if (darkNav) darkNav.click();
				}
				applyColors();
			});
		}

		/* ---------------- save / reset ---------------- */

		function markDirty() {
			dirty = true;
			if (status) {
				status.textContent = boot.i18n.unsaved;
				status.className = "pwb-status is-dirty";
			}
		}

		function collect() {
			var data = new FormData();
			data.append(boot.csrfName, boot.csrfValue);
			var keys = Object.keys(boot.defaults);
			for (var i = 0; i < keys.length; i++) {
				var key = keys[i];
				if (key === "design_position") {
					data.append(key, resolvePosition());
					continue;
				}
				var value = getValue(key);
				if (value !== null) data.append(key, value);
			}
			return data;
		}

		function post(data, onDone) {
			var xhr = new XMLHttpRequest();
			xhr.open("POST", boot.saveUrl, true);
			xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
			xhr.onload = function () {
				var response = null;
				try { response = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }
				onDone(response && response.ok ? response : null);
			};
			xhr.onerror = function () { onDone(null); };
			xhr.send(data);
		}

		var saveBtn = document.getElementById("pwb-save");
		if (saveBtn) {
			saveBtn.addEventListener("click", function () {
				if (status) {
					status.textContent = boot.i18n.saving;
					status.className = "pwb-status";
				}
				post(collect(), function (response) {
					if (response) {
						dirty = false;
						if (status) {
							status.textContent = boot.i18n.saved;
							status.className = "pwb-status is-saved";
						}
					} else if (status) {
						status.textContent = boot.i18n.error;
						status.className = "pwb-status is-error";
					}
				});
			});
		}

		var resetBtn = document.getElementById("pwb-reset");
		if (resetBtn) {
			resetBtn.addEventListener("click", function () {
				var data = new FormData();
				data.append(boot.csrfName, boot.csrfValue);
				data.append("reset", "1");
				post(data, function (response) {
					if (response) location.reload();
				});
			});
		}

		window.addEventListener("beforeunload", function (e) {
			if (!dirty) return;
			e.preventDefault();
			e.returnValue = "";
		});

		// fit the whole builder into the visible viewport (no page scrolling)
		function fitHeight() {
			if (window.innerWidth <= 1100) {
				wrap.style.height = "";
				return;
			}
			var top = wrap.getBoundingClientRect().top + window.scrollY;
			var h = window.innerHeight - top - 24;
			wrap.style.height = Math.max(480, h) + "px";
		}
		window.addEventListener("resize", fitHeight);
		fitHeight();

		updateConditionalRows();
		applyAll();
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
