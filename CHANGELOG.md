# Changelog

All notable changes to Cookie are documented here.

## [1.0.0] - 2026-07-04

### Added

- First public release of Cookie for ProcessWire.
- GDPR/ePrivacy consent banner, preferences window and floating settings icon.
- Consent-first server-side auto-blocking of known trackers and embeds (GTM, Google Analytics, Yandex Metrika, Meta Pixel, Hotjar, Clarity, DoubleClick, TikTok, YouTube, Vimeo, Google Maps and more), gated before the page reaches the browser.
- Opt-in (GDPR/ePrivacy, UK GDPR/PECR, LGPD, Law 25, POPIA, KVKK) and opt-out (CCPA/CPRA and other US state laws) consent models, plus Global Privacy Control support.
- Geo mode for automatic consent model selection by visitor country.
- Category-based blocking of scripts, iframes, images and video, including multi-category requirements and placeholders with "Load once" / "Always allow" actions.
- Custom categories on top of the built-in five (necessary, functional, statistics, marketing, external media).
- A curated services catalog (23 known services) with a one-click picker that fills the services list per category.
- Cookie policy generator that builds a ready policy document from configured services.
- Optional consent log with salted IP hashing, an admin viewer and CSV export.
- Google Consent Mode v2 with denied-by-default signals and configurable category mapping.
- Consent expiry and consent versioning to re-prompt visitors.
- Body classes, a JavaScript API, CustomEvents and a MutationObserver for AJAX-loaded content.
- Video preview posters: YouTube/Vimeo thumbnails cached server-side so a visitor's IP never reaches the video host before consent.
- Settings export/import as a single JSON blob.
- Consent statistics dashboard with acceptance breakdowns and a 30-day volume chart.
- A hookable API for every decision point: block or restrict categories, the banner, gating rules and gated markup.
- Multi-language texts using ProcessWire's native language fields, with ready-made presets for 8 languages and per-language targeting for countries with multiple official languages.
- Bundled admin-interface translations (German, French, Italian, Spanish, Dutch) for the Design Studio UI.
- An interactive two-pane visual builder (Design Studio, Setup > Cookie) with a live preview.
- Layouts (box / bar / modal), an 8-point position picker, dim overlay, corner radius, shadows and spacing, all editable live.
- A floating settings icon with 7 built-in choices, adjustable shape, size, position, color and shadow, including a transparent/icon-only mode.
- 50 one-click color presets (including football, hockey and basketball club palettes), 25 icon color presets and 18 dark-theme presets.
- A dark theme (`prefers-color-scheme: dark`) with its own color set, edited live with a dark-preview toggle.
- Desktop/mobile preview toggle in the builder.
- Ad-blocker resilience: all CSS classes and ids derive from a configurable prefix.
- Accessibility support: dialog roles, focus trap, ESC handling, `aria-live` toast, visible focus and `prefers-reduced-motion`.
- Template overrides via a copyable `banner.php`.
- `TextformatterCookie`, an embed-blocking text formatter for gating iframes/scripts inside formatted text fields.
