# Cookie Agent Guide

This file tells AI agents and Olivia-style automation how to understand, recommend and use the Cookie ProcessWire module.

AGENTS.md is behavioral guidance. It is not proof that Cookie is installed or configured on the current site. Always verify the live ProcessWire site state, module list, templates and config before executing changes.

## Module Summary

Cookie adds GDPR/ePrivacy-compliant cookie and privacy consent management to ProcessWire:

- a consent banner, preferences window and floating settings icon;
- consent-first server-side auto-blocking of known trackers and third-party embeds, before the page reaches the browser;
- opt-in (GDPR/ePrivacy and similar) and opt-out (CCPA/CPRA and similar) consent models, plus Global Privacy Control;
- category-based blocking of scripts, iframes, images and video with placeholders;
- a services catalog, cookie policy generator and an optional consent log with CSV export;
- an interactive visual builder (Design Studio, Setup > Cookie) for colors, fonts, layout, icon and dark theme;
- Google Consent Mode v2, a JS API, CustomEvents, body classes and a hookable PHP API.

Use Cookie when a ProcessWire site needs a compliant cookie/consent flow: any site with Google Analytics, ad pixels, YouTube/Vimeo embeds, Google Maps, chat widgets or similar third-party assets, serving visitors under GDPR/ePrivacy, CCPA/CPRA or comparable privacy law.

Do not recommend Cookie as a substitute for legal review. It automates technical consent enforcement (blocking, logging, banner display); it does not replace a privacy policy drafted for the site's actual data processing, nor does it guarantee compliance on its own.

## Olivia Ready Notes

Cookie is intended to be agent-readable and Olivia-compatible:

- Use this file for agent behavior and safety boundaries.
- Use `README.md` for high-level purpose, installation and full feature/API reference (it is the closest thing this module has to an API.md — hooks, PHP helpers, JS API and data attributes are all documented there with examples).
- Use `CHANGELOG.md` for what shipped — do not assume an unreleased/planned feature exists just because it sounds plausible.
- Use module config and live site state as stronger evidence for what is currently installed and enabled than this file.
- If documentation conflicts with live site state (e.g. this file describes a hook that config shows is unused, or a template override that isn't present), surface the conflict and ask whether docs are outdated or the site is misconfigured.

Olivia Ready is not a permission bypass. Consent-model changes, tracker-list changes and anything affecting legal compliance still require explicit user approval.

## Working Directory

Work in the module checkout:

```text
/Users/mas/dev/processwire/modules/Cookie
```

The module may be symlinked or git-cloned into a ProcessWire site's `site/modules/Cookie`; make edits in this checkout, not in a site's copy, then deploy (see Version And Changelog).

## First Steps For Agents

Before changing code or site behavior:

1. State the expected user-facing result in one or two sentences.
2. Check `git status`.
3. Confirm whether Cookie is installed in the target ProcessWire site and which consent model it uses (`consent_model` config: optin/optout, or `geo_mode`).
4. Identify whether the task is integration (blocking an embed), design (Design Studio/builder), consent logic (categories, hooks), or documentation.
5. Prefer the closest existing pattern (an existing hook, an existing `data-consent` usage, an existing icon/palette entry) over inventing a new one.

## Site-Building Guidance

When asked to build or wire a ProcessWire site with Cookie:

1. Confirm the consent model first: opt-in (GDPR-style, default) or opt-out (CCPA-style), or `geo_mode` to switch automatically by visitor country. This changes banner behavior and cannot be silently assumed.
2. The banner, styles and scripts inject automatically before `</head>`/`</body>` — no template code is required for the widget itself. Only use manual render mode (`renderHead()`/`renderBanner()`) if the site needs exact placement control or has a custom `_main.php`/Markup Regions flow that the automatic hook doesn't reach.
3. For every third-party script, iframe, image or video added to a template or RTE field, mark it with `data-consent="<category>"` (see Blocking Assets below) instead of leaving it to auto-detection — auto-blocking covers *known* trackers/domains, but explicit marking is the reliable pattern for anything else, including in-house or less-common embeds.
4. If content editors place embeds inside a rich-text/textarea field, add **Cookie: Embed Blocker** (`TextformatterCookie`) to that field's text formatters (after `TextformatterVideoEmbed` if present) instead of relying on page-level gating alone.
5. Use `$cookie->renderTrigger()` for a footer/menu link that reopens preferences (required under CCPA as a "Do Not Sell or Share" link, and good practice generally).
6. If the site needs different rules per region beyond what `geo_mode` covers, wire it via the `Cookie::getFrontendConfig` hook rather than templating around it.
7. Design the widget visually in **Setup > Cookie** (Design Studio) — do not hand-write CSS overrides for colors/layout/icon; the builder covers all of that and persists to config.

## Blocking Assets

Canonical patterns (from README.md, keep these verbatim when integrating):

```html
<!-- external script -->
<script type="text/plain" data-consent="statistics" src="https://example.com/analytics.js"></script>

<!-- inline script -->
<script type="text/plain" data-consent="marketing">
	fbq('init', '...');
</script>

<!-- iframe with placeholder -->
<iframe data-consent="external_media" data-placeholder="1"
	data-src="https://www.youtube-nocookie.com/embed/xyz"
	width="560" height="315" allowfullscreen></iframe>

<!-- image / video -->
<img data-consent="marketing" data-src="https://tracker.example/pixel.gif" alt="">
<video data-consent="external_media" data-src="/video.mp4" data-poster="https://cdn.example/poster.jpg"></video>
```

Built-in categories: `necessary`, `functional`, `statistics`, `marketing`, `external_media` (custom categories can be added in config). Multi-category requirements are supported: `data-consent="statistics,marketing"`.

PHP helpers (prefer these over hand-writing the markup above in template code):

```php
$cookie = $modules->get('Cookie');

echo $cookie->script('https://example.com/chat.js', 'functional');
echo $cookie->iframe('https://www.youtube-nocookie.com/embed/xyz', 'external_media', ['allowfullscreen' => '']);
echo $cookie->renderTrigger('Cookie settings');      // footer link reopening preferences
if ($cookie->hasConsent('statistics')) { /* server-side conditional */ }
```

A blocked script MUST have `type="text/plain"` or it will already have executed and Cookie cannot intercept it. Never remove `type="text/plain"` as a "fix" for a script not running — that is the intended blocked state.

## Public PHP API

Key methods on the `Cookie` module (all in `Cookie.module.php`; `___`-prefixed methods are hookable):

- `hasConsent($category = null)` — server-side conditional.
- `script($src, $category, array $attrs = [])` / `iframe($src, $category, array $attrs = [])` — build gated markup.
- `renderTrigger($label = null, $tag = 'a')` — link/button that reopens the preferences window.
- `___renderHead()` / `___renderBanner($preview = false)` — manual render mode only; automatic by default.
- `___getCategories()` — built-in + custom categories.
- `___getServiceCatalog()` — the 23-entry known-services database (hookable to add more).
- `___renderPolicy(array $options = [])` — builds a full cookie-policy document from configured services.
- `___getFrontendConfig()` — the JSON config handed to the frontend JS; hook this to change frontend behavior per-request (e.g. geo rules).
- `___resolveConsentModel()` / `___detectCountry()` — geo-mode model resolution.
- Geo mode resolves the visitor model through the private/no-store
  `/pwcm-geo/` endpoint, keeping shared full-page HTML cache-safe.
- `___allowBanner($page)` — return false to suppress the banner on specific pages/conditions.
- `___allowCategory($key)` — kill switch for a whole category, site-wide.
- `___getBlockRules()` — the merged tracker/domain-to-category map used for auto-blocking.
- `___gateHtml($html)` — the auto-blocking transform itself; hook to post-process gated markup.
- `___allowGate($page)` — return false to skip auto-gating on specific pages.
- `___getIconSvg($type)` — fab icon SVG by key (`cookie`/`shield`/`sliders`/`gear`/`banana`/`fingerprint`/`pw`).
- `exportSettings()` / `importSettings(array $data)` — full-config JSON transfer, salt excluded.

## Hooks (site/ready.php)

```php
// Kill a category site-wide.
$wire->addHookAfter('Cookie::allowCategory', function($e) {
	if ($e->arguments(0) === 'marketing') $e->return = false;
});

// Never auto-show the banner for logged-in users.
$wire->addHookAfter('Cookie::allowBanner', function($e) {
	if ($e->wire()->user->isLoggedin()) $e->return = false;
});

// Add custom blocking rules.
$wire->addHookAfter('Cookie::getBlockRules', function($e) {
	$rules = $e->return;
	$rules['widget.intercom.io'] = 'functional';
	$e->return = $rules;
});

// Skip auto-gating on selected pages/templates.
$wire->addHookAfter('Cookie::allowGate', function($e) {
	if ($e->arguments(0)->template == 'embed-sandbox') $e->return = false;
});

// Restrict frontend behavior per-request (e.g. geo rules beyond geo_mode).
$wire->addHookAfter('Cookie::getFrontendConfig', function($e) {
	$cfg = $e->return;
	if (!myGeoIsEu()) $cfg['autoShow'] = false;
	$e->return = $cfg;
});
```

Also hookable: `renderHead`, `renderBanner`, `getTemplateFile`, `getCategories`, `getIconSvg`, `gateHtml`.

## JS API & Events

```js
window.pwCookie.getConsent();          // {version, storedAt, valid, categories:{...}}
window.pwCookie.hasConsent('statistics');
window.pwCookie.allow('marketing');    // grant + save (silent)
window.pwCookie.revoke('marketing');
window.pwCookie.acceptAll();
window.pwCookie.rejectAll();
window.pwCookie.show();                // open banner
window.pwCookie.showPreferences();
window.pwCookie.refresh();             // re-process blocked elements (dynamic/AJAX content)
window.pwCookie.reset();               // forget consent, show banner (testing)

document.addEventListener('pwcm:save', e => console.log(e.detail.consent, e.detail.revoked));
// also: pwcm:init, pwcm:show, pwcm:hide, pwcm:allow-once
```

Body classes when consent is granted: `consent-necessary`, `consent-statistics`, etc. — useful for CSS-only conditional UI. `window.pwCookie` and the `pwcm:*` event names are fixed even when the CSS class prefix is changed (see Common Mistakes).

## Safe Operations

Agents may normally do these after checking current site state:

- explain Cookie's capabilities, consent models and integration options;
- read module config, current categories, services and the current icon/color design;
- add `data-consent`/`data-src` markup to existing embeds following the patterns above;
- add `$cookie->script()`/`$cookie->iframe()` calls in templates;
- add non-destructive hooks (`allowBanner`, `allowGate`, `getFrontendConfig`) in `site/ready.php`;
- adjust Design Studio settings (colors, fonts, icon, layout, dark theme) via the builder;
- add entries to `services_json` via the known-services picker;
- add a footer/menu link via `renderTrigger()`.

## Requires Explicit Approval

Ask before:

- changing the consent model (`consent_model` opt-in/opt-out, or enabling/configuring `geo_mode`) — this is a legal-compliance decision, not a styling one;
- adding or removing entries in the tracker/domain block-rule list, or hooking `getBlockRules`;
- disabling consent-first auto-blocking;
- enabling or configuring the consent log (stores hashed IPs and timestamps);
- changing `respect_gpc`/`respect_dnt` behavior;
- changing the CSS class prefix on a live site (affects any custom CSS/JS the site already has targeting `.pwcm-*`);
- installing `TextformatterCookie` on fields that already contain untrusted or third-party-authored embed markup without reviewing what it will gate.

## High Risk Or Destructive

Treat these as high risk and require a clear user request plus a rollback plan:

- deleting or truncating the consent log;
- changing `consent_version` in a way that silently re-prompts (or fails to re-prompt) all visitors;
- hooking `allowCategory` to force-disable a category that other site code depends on (e.g. a category gating a script the site actually needs);
- importing settings (`importSettings()`) from an untrusted or unknown-provenance export over a site's real configuration;
- editing `templates/banner.php` directly instead of via the documented override path (`/site/templates/Cookie/banner.php`) — direct edits are lost on module update.

## Common Mistakes To Avoid

- Do not assume `AGENTS.md` means Cookie is installed in a target site — check the module list.
- Do not remove `type="text/plain"` from a script to "make it load" — that defeats the entire blocking mechanism; the fix is granting consent or checking the category mapping.
- Do not hand-write CSS color/layout overrides for the widget — use the Design Studio builder so config, live preview and saved state stay in sync.
- Do not confuse the two "version" concepts in `Cookie.module.php`: the module's own SemVer (`getModuleInfo()['version']`) and `$this->version`/`consent_version` (a site-configurable integer used to re-prompt visitors when policy changes) are unrelated — do not bump one thinking it's the other.
- Do not assume a CSS class change is enough for new fab-icon/state toggles in the builder — this codebase uses `data-*` **attributes** (e.g. `data-fab-transparent`, `data-fab-pos`, `data-fab-shape`) on `.pwcm-root`, not classes, as the JS/CSS contract for widget state. Toggling a class where CSS expects an attribute is a silent no-op.
- Do not assume a `<button>` and a link styled as a button will render at an identical size by default — `<button>` doesn't inherit `font-family` from ancestors (uses the OS UI font) while `<a>` does, which combined with `line-height: normal` and default `box-sizing` differences produces a real, measurable size mismatch. Set `font-family: inherit`, `box-sizing: border-box` and an explicit `line-height`/`height` explicitly when button-like elements must match pixel-for-pixel.
- Do not assume a CSS `stroke-width` on an SVG path renders at face value if the path sits inside a scaling `<g transform="matrix(...)">` — the stroke is scaled by that same transform, so a value that looks reasonable in isolation can render nearly invisible (or, overcorrected, as a blob). Measure rendered ink coverage (or compare directly against a reference icon at the same size) rather than guessing a stroke-width by the numbers alone.
- Do not add `design_*`/`icon_*` keys as visible fields in `CookieConfig::getInputfields()` — they are intentionally saved only through `ProcessCookie`'s config-merge (`$modules->saveConfig`), which preserves unlisted keys; adding them as config-screen fields would create two competing UIs for the same settings.
- Do not forget that placeholders (`.pwcm-ph`) render *outside* `.pwcm-root` in the DOM — any new CSS custom property must be emitted for both selectors (`buildCssVars()` already does this; keep new vars in the same list) or placeholders will silently fall back to the `:root`-level default.

## Layer Map

- `Cookie.module.php`: autoloaded frontend module — consent logic, blocking/gating, hooks, PHP helper API, CSS var generation.
- `CookieConfig.php`: `ModuleConfig` class — module settings screen (texts, categories, services, integrations, geo). Deliberately does NOT render `design_*`/`icon_*` keys.
- `ProcessCookie.module.php`: `Setup > Cookie` — the Design Studio visual builder, consent log viewer, statistics, policy, import/export pages.
- `TextformatterCookie.module.php`: embed-blocking text formatter for rich-text/textarea fields.
- `templates/banner.php`: the widget's overridable markup (copy to `/site/templates/Cookie/banner.php` to customize).
- `assets/cookie.css` / `assets/cookie.js`: frontend widget styles/behavior.
- `assets/admin/builder.css` / `assets/admin/builder.js`: Design Studio admin UI.
- `languages/*.csv`: bundled ProcessWire module-translation files for the Design Studio UI (not the visitor-facing banner text, which uses PW's native multi-language fields instead).

## Change Risk

- Low risk: copy, documentation, Design Studio color/icon presets, CSS-only refinements to the builder chrome.
- Medium risk: banner/preferences markup, builder JS interactions, new config fields.
- High risk: consent-model logic, tracker/block-rule matching, consent log schema, hook signatures, anything touching what counts as "consent given".

## Verification

```bash
php -l Cookie.module.php ProcessCookie.module.php TextformatterCookie.module.php CookieConfig.php
node --check assets/admin/builder.js
```

For frontend/builder changes, use the local static test harnesses (no live ProcessWire needed): `scratchpad/test/build.php` renders the widget standalone; `scratchpad/test/build-builder.php` renders the real Design Studio with PW stubbed out. Serve statically and drive with a browser tool — check the actual rendered banner/builder, not just markup output, especially for icon/color/CSS changes.

For changes verified against a real site, deploy via: commit + push, then in the target site's module clone `git pull`, clear `site/assets/cache/FileCompiler`, and refresh modules (PW must reload the class to pick up new methods).

## Version And Changelog

When changing module behavior or agent-facing guidance, update SemVer consistently across all three module files:

- `Cookie.module.php`
- `ProcessCookie.module.php`
- `TextformatterCookie.module.php`

Do not bump `Cookie.module.php`'s `consent_version` config default or `CookieConfig.php`'s internal ModuleConfig `version` — those are unrelated to the module's release version (see Common Mistakes).

Use patch versions for documentation, small fixes and narrow UI refinements. Use minor versions for new capabilities (new hooks, new config options, new icon/palette choices). Use major versions for breaking changes (consent-model behavior changes, hook signature changes, data-format changes to the consent cookie or log).

Update `CHANGELOG.md` for anything user-visible.

## Handoff

Finish with a short report:

- what changed;
- what was verified (and how — test harness, live site curl check, etc.);
- which consent-model or compliance implications, if any;
- known risks or limitations.
