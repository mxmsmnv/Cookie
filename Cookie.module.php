<?php namespace ProcessWire;

/**
 * Cookie — Privacy & Cookie Consent Management for ProcessWire
 *
 * Consent banner, category-based blocking of scripts/iframes/media, per-service
 * details, consent logging, Google Consent Mode v2, and an interactive visual
 * widget builder (see ProcessCookie).
 *
 * Frontend usage of blocked assets:
 *
 *   <script type="text/plain" data-consent="statistics" src="/js/analytics.js"></script>
 *   <script type="text/plain" data-consent="statistics">console.log('inline');</script>
 *   <iframe data-consent="external_media" data-src="https://www.youtube-nocookie.com/embed/..." data-placeholder="1"></iframe>
 *   <img data-consent="marketing" data-src="https://tracker.example/pixel.gif" alt="">
 *   <video data-consent="external_media" data-src="..." data-poster="..."></video>
 *
 * Multiple categories (all must be granted): data-consent="statistics,marketing"
 * Per-element placeholder texts: data-placeholder-message="..." data-placeholder-button="..."
 *
 * JS API: window.pwCookie — show(), showPreferences(), hide(), refresh(), getConsent(),
 * hasConsent(cat), allow(cat), revoke(cat), acceptAll(), rejectAll(), reset()
 * Events on document: pwcm:init, pwcm:show, pwcm:hide, pwcm:save, pwcm:allow-once
 *
 * @author Cookie module contributors
 * @license MPL-2.0
 */
class Cookie extends WireData implements Module {

	const LOG_TABLE = 'cookie_consent_log';
	const LOG_ENDPOINT = '/pwcm-cl/';
	const GEO_CONFIG_ENDPOINT = '/pwcm-geo/';

	/** @var bool guards against multiple render-hook injections (PageTable etc.) */
	protected $rendered = false;

	/** @var string language suffix for multi-language config values, e.g. "__1023" */
	protected $langSuffix = '';

	public static function getModuleInfo() {
		return [
			'title' => 'Cookie',
			'summary' => 'Privacy & cookie consent management: banner, category-based async loading of scripts/embeds, consent log, Google Consent Mode v2, visual widget builder.',
			'version' => '1.1.0',
			'author' => 'Cookie module contributors',
			'href' => 'https://github.com/mxmsmnv/Cookie',
			'icon' => 'shield',
			'autoload' => true,
			'singular' => true,
			'requires' => ['PHP>=8.2', 'ProcessWire>=3.0.244'],
			'installs' => ['ProcessCookie', 'TextformatterCookie'],
		];
	}

	/* ==================================================================
	 * Bootstrap
	 * ================================================================ */

	public function init() {
		if($this->is_active && $this->geo_mode) {
			$this->wire()->addHook(self::GEO_CONFIG_ENDPOINT, $this, 'hookGeoConfig');
		}

		// consent log endpoint (POST via sendBeacon/fetch from the frontend)
		if($this->enable_logging) {
			$this->wire()->addHook(self::LOG_ENDPOINT, $this, 'hookLogConsent');
		}
	}

	public function ready() {
		$page = $this->wire()->page;
		if(!$page || !$page->id) return;
		if($page->template == 'admin' || $page->template == 'form-builder') return;
		if(!$this->is_active) return;

		$this->langSuffix = $this->resolveLangSuffix();

		if(!$this->render_manually) {
			$page->addHookAfter('render', $this, 'hookPageRender', ['priority' => 150]);
		}
	}

	protected function resolveLangSuffix() {
		$languages = $this->wire()->languages;
		$user = $this->wire()->user;
		if($languages && $user->language && !$user->language->isDefault()) {
			return '__' . $user->language->id;
		}
		return '';
	}

	/**
	 * Inject head/body content into the rendered page (auto mode).
	 * Only the first full-document render is processed: fixes duplicate
	 * injection with PageTable / nested Page::render calls.
	 */
	public function hookPageRender(HookEvent $event) {
		if($this->rendered) return;
		$out = $event->return;
		if(!is_string($out) || strpos($out, '</head>') === false || strripos($out, '</body>') === false) return;
		$this->rendered = true;

		// consent-first: neutralize known third-party scripts/embeds server-side,
		// even when they were not manually marked with data-consent
		if($this->consent_first && $this->allowGate($this->wire()->page)) {
			$out = $this->gateHtml($out);
		}

		$head = $this->renderHead();
		$body = $this->renderBanner();

		// insert before FIRST </head> and before LAST </body> only
		$pos = strpos($out, '</head>');
		$out = substr($out, 0, $pos) . $head . substr($out, $pos);
		$pos = strrpos($out, '</body>');
		$out = substr($out, 0, $pos) . $body . substr($out, $pos);

		$event->return = $out;
	}

	/* ==================================================================
	 * Public render API (for render_manually mode and hooks)
	 * ================================================================ */

	/**
	 * Everything that belongs into <head>: consent-mode default, config JSON, CSS, core JS.
	 * @return string
	 */
	public function ___renderHead() {
		$out = '';
		if($this->consent_mode) $out .= $this->renderConsentModeDefault();
		$out .= $this->renderConfigJson();
		$out .= $this->renderStyles();
		$out .= $this->renderCoreJs();
		return $out;
	}

	/**
	 * Banner + preferences modal + icon button + placeholder blueprint markup.
	 * @param bool $preview render in preview mode (for the ProcessCookie builder)
	 * @return string
	 */
	public function ___renderBanner($preview = false) {
		$file = $this->getTemplateFile('banner');
		$tpl = new TemplateFile($file);
		$tpl->set('module', $this);
		$tpl->set('prefix', $this->cssPrefix());
		$tpl->set('preview', (bool) $preview);
		$tpl->set('categories', $this->getCategories());
		$tpl->set('t', $this->getTexts());
		$tpl->set('design', $this->getDesign());
		$tpl->set('iconSvg', $this->getIconSvg($this->icon_type));
		return $tpl->render();
	}

	/**
	 * Resolve a template file, allowing overrides in /site/templates/Cookie/
	 * @param string $name basename without extension
	 * @return string path
	 */
	public function ___getTemplateFile($name) {
		$config = $this->wire()->config;
		$override = $config->paths->templates . "Cookie/{$name}.php";
		if(is_file($override)) return $override;
		return $config->paths->siteModules . "Cookie/templates/{$name}.php";
	}

	/* ==================================================================
	 * Head parts
	 * ================================================================ */

	/**
	 * Google Consent Mode v2 defaults — must run before gtag.js/GTM.
	 */
	protected function renderConsentModeDefault() {
		$defaults = [
			'ad_storage' => 'denied',
			'ad_user_data' => 'denied',
			'ad_personalization' => 'denied',
			'analytics_storage' => 'denied',
			'functionality_storage' => 'denied',
			'personalization_storage' => 'denied',
			'security_storage' => 'granted',
			'wait_for_update' => 500,
		];
		$json = json_encode($defaults, JSON_UNESCAPED_SLASHES);
		return "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{$json});</script>\n";
	}

	protected function renderConfigJson() {
		$json = json_encode($this->getFrontendConfig(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		return "<script>window.pwcmConfig={$json};</script>\n";
	}

	protected function renderStyles() {
		$mode = $this->output_css ?: 'inline';
		if($mode === 'none') return '';
		$prefix = $this->cssPrefix();
		$out = '';
		if($mode === 'file' && $prefix === 'pwcm') {
			$url = $this->wire()->config->urls->siteModules . 'Cookie/assets/cookie.css?v=' . $this->assetVersion();
			$out .= "<link rel=\"stylesheet\" href=\"{$url}\">\n";
		} else {
			// inline (also fallback when prefix was customized — class names are rewritten)
			$css = file_get_contents($this->assetPath('cookie.css'));
			if($prefix !== 'pwcm') $css = str_replace('pwcm', $prefix, $css);
			$out .= "<style>{$css}</style>\n";
		}
		// configured vars must come after the stylesheet defaults to win the cascade
		$out .= "<style>{$this->buildCssVars()}</style>\n";
		if($this->custom_css) $out .= "<style>{$this->custom_css}</style>\n";
		return $out;
	}

	protected function renderCoreJs() {
		if($this->output_js === 'inline') {
			$js = file_get_contents($this->assetPath('cookie.js'));
			return "<script defer>{$js}</script>\n";
		}
		$url = $this->wire()->config->urls->siteModules . 'Cookie/assets/cookie.js?v=' . $this->assetVersion();
		return "<script src=\"{$url}\" defer></script>\n";
	}

	protected function assetPath($file) {
		return $this->wire()->config->paths->siteModules . 'Cookie/assets/' . $file;
	}

	protected function assetVersion() {
		$info = self::getModuleInfo();
		return $info['version'] . '-' . (int) $this->version;
	}

	/* ==================================================================
	 * Config resolution
	 * ================================================================ */

	/**
	 * Multi-language aware text getter: returns value for current user language
	 * with fallback to the default-language value.
	 * @param string $key
	 * @return string
	 */
	public function txt($key) {
		if($this->langSuffix === '') $this->langSuffix = $this->resolveLangSuffix();
		$value = $this->langSuffix ? $this->get("{$key}{$this->langSuffix}|{$key}") : $this->get($key);
		return (string) $value;
	}

	/**
	 * Resolve a "page or URL" link field: `link_privacy`/`link_imprint` can be set
	 * via a page picker (`{$key}_page`, preferred — automatically follows that
	 * page's URL in the visitor's active language) or as a plain external URL
	 * (fallback, e.g. when the policy is hosted elsewhere).
	 * @param string $key 'link_privacy' or 'link_imprint'
	 * @return string URL, or '' when neither is set
	 */
	public function resolveLink($key) {
		$pageId = (int) $this->get("{$key}_page");
		if($pageId > 0) {
			$page = $this->wire()->pages->get($pageId);
			if($page->id && $page->viewable()) return $page->url;
		}
		return trim($this->txt($key));
	}

	/**
	 * All banner texts resolved for the current language.
	 * @return array
	 */
	public function getTexts() {
		$keys = [
			'txt_banner_title', 'txt_banner_text',
			'txt_btn_accept_all', 'txt_btn_reject', 'txt_btn_prefs',
			'txt_prefs_title', 'txt_prefs_text', 'txt_btn_save', 'txt_close',
			'txt_msg_saved', 'txt_details',
			'txt_ph_message', 'txt_ph_load', 'txt_ph_always',
			'txt_icon_aria', 'txt_privacy', 'txt_imprint',
			'link_privacy', 'link_imprint',
		];
		$t = [];
		foreach($keys as $key) {
			$t[$key] = (strpos($key, 'link_') === 0) ? $this->resolveLink($key) : $this->txt($key);
		}
		return $t;
	}

	/**
	 * Enabled consent categories with labels, descriptions, services and cookie lists.
	 * @return array of ['key','label','desc','required','services'=>[]]
	 */
	public function ___getCategories() {
		$cats = [];
		$cats['necessary'] = [
			'key' => 'necessary',
			'label' => $this->txt('label_necessary'),
			'desc' => $this->txt('desc_necessary'),
			'required' => true,
			'services' => [],
		];
		foreach(['functional', 'statistics', 'marketing', 'external_media'] as $key) {
			if(!$this->get("cat_{$key}")) continue;
			$cats[$key] = [
				'key' => $key,
				'label' => $this->txt("label_{$key}"),
				'desc' => $this->txt("desc_{$key}"),
				'required' => false,
				'services' => [],
			];
		}
		// custom categories, one per line: key=Label|Optional description
		foreach(explode("\n", (string) $this->custom_categories) as $line) {
			$line = trim($line);
			if(!$line || strpos($line, '=') === false) continue;
			list($key, $rest) = explode('=', $line, 2);
			$key = $this->wire()->sanitizer->fieldName(trim($key));
			if(!$key || isset($cats[$key])) continue;
			$parts = explode('|', $rest, 2);
			$cats[$key] = [
				'key' => $key,
				'label' => trim($parts[0]),
				'desc' => isset($parts[1]) ? trim($parts[1]) : '',
				'required' => false,
				'services' => [],
			];
		}
		// hookable kill switch: Cookie::allowCategory may remove categories
		foreach(array_keys($cats) as $key) {
			if($key === 'necessary') continue;
			if(!$this->allowCategory($key)) unset($cats[$key]);
		}
		// attach declared services to their categories
		foreach($this->getServices() as $service) {
			$cat = isset($service['category']) ? $service['category'] : '';
			if(isset($cats[$cat])) $cats[$cat]['services'][] = $service;
		}
		return $cats;
	}

	/**
	 * Curated database of well-known third-party services with GDPR metadata,
	 * used by the "Add service" picker in the module config to auto-fill
	 * services_json. Hookable: extend or override from site code.
	 *
	 * Keyed by slug; each entry: name, category, provider, purpose, duration,
	 * cookies[]. `_group` is only for grouping in the picker UI.
	 * @return array
	 */
	public function ___getServiceCatalog() {
		return [
			// --- statistics ---
			'google-analytics' => ['_group' => 'Analytics', 'name' => 'Google Analytics', 'category' => 'statistics', 'provider' => 'Google LLC', 'purpose' => 'Website traffic analysis', 'duration' => '2 years', 'cookies' => ['_ga', '_ga_*', '_gid', '_gat']],
			'google-tag-manager' => ['_group' => 'Analytics', 'name' => 'Google Tag Manager', 'category' => 'statistics', 'provider' => 'Google LLC', 'purpose' => 'Tag and script management', 'duration' => 'Session', 'cookies' => []],
			'yandex-metrica' => ['_group' => 'Analytics', 'name' => 'Yandex Metrica', 'category' => 'statistics', 'provider' => 'Yandex LLC', 'purpose' => 'Website traffic analysis', 'duration' => '1 year', 'cookies' => ['_ym_uid', '_ym_d', '_ym_isad']],
			'matomo' => ['_group' => 'Analytics', 'name' => 'Matomo', 'category' => 'statistics', 'provider' => 'Matomo / self-hosted', 'purpose' => 'Privacy-friendly web analytics', 'duration' => '13 months', 'cookies' => ['_pk_id', '_pk_ses']],
			'hotjar' => ['_group' => 'Analytics', 'name' => 'Hotjar', 'category' => 'statistics', 'provider' => 'Hotjar Ltd', 'purpose' => 'Behavior analytics and heatmaps', 'duration' => '1 year', 'cookies' => ['_hjSessionUser_*', '_hjSession_*']],
			'microsoft-clarity' => ['_group' => 'Analytics', 'name' => 'Microsoft Clarity', 'category' => 'statistics', 'provider' => 'Microsoft Corp.', 'purpose' => 'Session recording and heatmaps', 'duration' => '1 year', 'cookies' => ['_clck', '_clsk']],
			'plausible' => ['_group' => 'Analytics', 'name' => 'Plausible Analytics', 'category' => 'statistics', 'provider' => 'Plausible Insights OÜ', 'purpose' => 'Cookieless web analytics', 'duration' => 'None', 'cookies' => []],

			// --- marketing ---
			'google-ads' => ['_group' => 'Advertising', 'name' => 'Google Ads', 'category' => 'marketing', 'provider' => 'Google LLC', 'purpose' => 'Conversion tracking and remarketing', 'duration' => '90 days', 'cookies' => ['_gcl_au', 'IDE', 'test_cookie']],
			'facebook-pixel' => ['_group' => 'Advertising', 'name' => 'Meta (Facebook) Pixel', 'category' => 'marketing', 'provider' => 'Meta Platforms Inc.', 'purpose' => 'Ad conversion tracking and remarketing', 'duration' => '3 months', 'cookies' => ['_fbp', 'fr']],
			'tiktok-pixel' => ['_group' => 'Advertising', 'name' => 'TikTok Pixel', 'category' => 'marketing', 'provider' => 'TikTok Technology Ltd', 'purpose' => 'Ad conversion tracking', 'duration' => '13 months', 'cookies' => ['_ttp']],
			'linkedin-insight' => ['_group' => 'Advertising', 'name' => 'LinkedIn Insight Tag', 'category' => 'marketing', 'provider' => 'LinkedIn Corp.', 'purpose' => 'Ad conversion tracking', 'duration' => '6 months', 'cookies' => ['li_sugr', 'bcookie', 'lidc']],
			'hubspot' => ['_group' => 'Advertising', 'name' => 'HubSpot', 'category' => 'marketing', 'provider' => 'HubSpot Inc.', 'purpose' => 'Marketing automation and analytics', 'duration' => '6 months', 'cookies' => ['__hstc', 'hubspotutk', '__hssc', '__hssrc']],

			// --- external media ---
			'youtube' => ['_group' => 'Media & embeds', 'name' => 'YouTube', 'category' => 'external_media', 'provider' => 'Google LLC', 'purpose' => 'Video embedding', 'duration' => '6 months', 'cookies' => ['VISITOR_INFO1_LIVE', 'YSC', 'PREF']],
			'vimeo' => ['_group' => 'Media & embeds', 'name' => 'Vimeo', 'category' => 'external_media', 'provider' => 'Vimeo Inc.', 'purpose' => 'Video embedding', 'duration' => '1 year', 'cookies' => ['vuid', 'player']],
			'google-maps' => ['_group' => 'Media & embeds', 'name' => 'Google Maps', 'category' => 'external_media', 'provider' => 'Google LLC', 'purpose' => 'Map embedding', 'duration' => '6 months', 'cookies' => ['NID']],
			'openstreetmap' => ['_group' => 'Media & embeds', 'name' => 'OpenStreetMap', 'category' => 'external_media', 'provider' => 'OpenStreetMap Foundation', 'purpose' => 'Map embedding', 'duration' => 'Session', 'cookies' => []],
			'instagram' => ['_group' => 'Media & embeds', 'name' => 'Instagram', 'category' => 'external_media', 'provider' => 'Meta Platforms Inc.', 'purpose' => 'Post embedding', 'duration' => '3 months', 'cookies' => ['ig_did', 'csrftoken']],

			// --- functional ---
			'intercom' => ['_group' => 'Functional', 'name' => 'Intercom', 'category' => 'functional', 'provider' => 'Intercom Inc.', 'purpose' => 'Customer messaging / live chat', 'duration' => '9 months', 'cookies' => ['intercom-id-*', 'intercom-session-*']],
			'youtube-nocookie' => ['_group' => 'Functional', 'name' => 'YouTube (privacy mode)', 'category' => 'external_media', 'provider' => 'Google LLC', 'purpose' => 'Video embedding without tracking cookies until playback', 'duration' => 'Session', 'cookies' => []],

			// --- necessary ---
			'stripe' => ['_group' => 'Necessary', 'name' => 'Stripe', 'category' => 'necessary', 'provider' => 'Stripe Inc.', 'purpose' => 'Payment processing and fraud prevention', 'duration' => '1 year', 'cookies' => ['__stripe_mid', '__stripe_sid']],
			'recaptcha' => ['_group' => 'Necessary', 'name' => 'Google reCAPTCHA', 'category' => 'necessary', 'provider' => 'Google LLC', 'purpose' => 'Spam and abuse protection', 'duration' => '6 months', 'cookies' => ['_GRECAPTCHA']],
			'cloudflare' => ['_group' => 'Necessary', 'name' => 'Cloudflare', 'category' => 'necessary', 'provider' => 'Cloudflare Inc.', 'purpose' => 'Security, bot protection and performance', 'duration' => '30 minutes', 'cookies' => ['__cf_bm', 'cf_clearance']],
			'paypal' => ['_group' => 'Necessary', 'name' => 'PayPal', 'category' => 'necessary', 'provider' => 'PayPal Inc.', 'purpose' => 'Payment processing and fraud prevention', 'duration' => '1 year', 'cookies' => ['ts', 'ts_c', 'x-pp-s']],
		];
	}

	/**
	 * Declared services from the services_json config field.
	 * Each: {name, category, provider, purpose, duration, cookies:[names]}
	 * @return array
	 */
	public function getServices() {
		$raw = trim((string) $this->services_json);
		if(!$raw) return [];
		$data = json_decode($raw, true);
		if(!is_array($data)) return [];
		$services = [];
		foreach($data as $item) {
			if(!is_array($item) || empty($item['name'])) continue;
			$services[] = [
				'name' => (string) $item['name'],
				'category' => isset($item['category']) ? (string) $item['category'] : 'necessary',
				'provider' => isset($item['provider']) ? (string) $item['provider'] : '',
				'purpose' => isset($item['purpose']) ? (string) $item['purpose'] : '',
				'duration' => isset($item['duration']) ? (string) $item['duration'] : '',
				'cookies' => isset($item['cookies']) && is_array($item['cookies']) ? array_values(array_map('strval', $item['cookies'])) : [],
			];
		}
		return $services;
	}

	/* ==================================================================
	 * Settings export / import
	 * ================================================================ */

	/**
	 * Full module configuration as a portable array (for moving settings
	 * between sites). The per-site IP-hash salt is intentionally excluded.
	 * @return array
	 */
	public function exportSettings() {
		$defaults = $this->configDefaults();
		$config = $this->wire()->modules->getConfig('Cookie');
		if(!is_array($config)) $config = [];
		$settings = [];
		foreach(array_keys($defaults) as $key) {
			if($key === 'log_salt') continue; // per-site secret
			$settings[$key] = array_key_exists($key, $config) ? $config[$key] : $defaults[$key];
		}
		$info = self::getModuleInfo();
		return [
			'_module' => 'Cookie',
			'_version' => $info['version'],
			'_exported' => date('c'),
			'settings' => $settings,
		];
	}

	/**
	 * Merge imported settings into the module config. Only keys that exist in
	 * the config schema are applied (unknown keys are ignored); the IP-hash salt
	 * is never overwritten.
	 * @param array $data output of exportSettings() or a bare settings array
	 * @return array ['applied' => int, 'skipped' => int]
	 */
	public function importSettings(array $data) {
		$settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : $data;
		$defaults = $this->configDefaults();
		$config = $this->wire()->modules->getConfig('Cookie');
		if(!is_array($config)) $config = [];

		$applied = 0;
		$skipped = 0;
		foreach($settings as $key => $value) {
			if($key === 'log_salt' || !array_key_exists($key, $defaults)) {
				$skipped++;
				continue;
			}
			$config[$key] = $value;
			$applied++;
		}
		$this->wire()->modules->saveConfig('Cookie', $config);
		$this->wire()->log->save('cookie', "Settings imported by {$this->wire()->user->name} ({$applied} keys)");
		return ['applied' => $applied, 'skipped' => $skipped];
	}

	/**
	 * The config schema (default values) — the canonical key whitelist.
	 * @return array
	 */
	public function configDefaults() {
		require_once __DIR__ . '/CookieConfig.php';
		$cfg = $this->wire(new CookieConfig());
		return $cfg->getDefaults();
	}

	/**
	 * Design settings (managed by the ProcessCookie visual builder).
	 * @return array
	 */
	public function getDesign() {
		$keys = [
			'design_layout', 'design_position', 'design_overlay',
			'design_color_bg', 'design_color_text', 'design_color_primary', 'design_color_primary_text',
			'design_color_secondary', 'design_color_secondary_text', 'design_color_link', 'design_color_accent',
			'design_font_family', 'design_font_custom', 'design_font_size', 'design_font_size_title', 'design_font_size_button',
			'design_radius', 'design_shadow', 'design_max_width', 'design_spacing',
			'icon_show', 'icon_type', 'icon_position', 'icon_offset_x', 'icon_offset_y',
			'icon_size', 'icon_shape', 'icon_color_bg', 'icon_color_fg', 'icon_transparent', 'icon_shadow',
			'dark_enable', 'dark_color_bg', 'dark_color_text', 'dark_color_primary', 'dark_color_primary_text',
			'dark_color_secondary', 'dark_color_secondary_text', 'dark_color_link', 'dark_color_accent',
		];
		$design = [];
		foreach($keys as $key) $design[$key] = $this->get($key);
		return $design;
	}

	/** Design keys that map to a --prefix-name CSS variable => [suffix, isColor]. */
	protected function darkVarMap() {
		return [
			'dark_color_bg' => 'bg',
			'dark_color_text' => 'text',
			'dark_color_primary' => 'primary',
			'dark_color_primary_text' => 'primary-text',
			'dark_color_secondary' => 'secondary',
			'dark_color_secondary_text' => 'secondary-text',
			'dark_color_link' => 'link',
			'dark_color_accent' => 'accent',
		];
	}

	/**
	 * CSS custom properties from the design config, scoped to the widget root.
	 * Shared by frontend and the builder live preview.
	 * @return string
	 */
	public function buildCssVars() {
		$p = $this->cssPrefix();
		$d = $this->getDesign();
		$shadows = [
			'none' => 'none',
			'soft' => '0 2px 6px rgba(15,23,42,.06), 0 16px 40px rgba(15,23,42,.16)',
			'strong' => '0 4px 14px rgba(15,23,42,.12), 0 28px 72px rgba(15,23,42,.34)',
		];
		$shadow = isset($shadows[$d['design_shadow']]) ? $shadows[$d['design_shadow']] : $shadows['soft'];
		$iconShadows = [
			'none' => 'none',
			'soft' => 'drop-shadow(0 2px 5px rgba(15,23,42,.45))',
			'strong' => 'drop-shadow(0 4px 10px rgba(15,23,42,.65))',
		];
		$iconShadow = isset($iconShadows[$d['icon_shadow']]) ? $iconShadows[$d['icon_shadow']] : $iconShadows['soft'];
		$font = $this->resolveFontFamily($d);
		$vars = [
			"--{$p}-bg" => $d['design_color_bg'],
			"--{$p}-text" => $d['design_color_text'],
			"--{$p}-primary" => $d['design_color_primary'],
			"--{$p}-primary-text" => $d['design_color_primary_text'],
			"--{$p}-secondary" => $d['design_color_secondary'],
			"--{$p}-secondary-text" => $d['design_color_secondary_text'],
			"--{$p}-link" => $d['design_color_link'],
			"--{$p}-accent" => $d['design_color_accent'],
			"--{$p}-font" => $font,
			"--{$p}-fs" => ((int) $d['design_font_size']) . 'px',
			"--{$p}-fs-title" => ((int) $d['design_font_size_title']) . 'px',
			"--{$p}-fs-btn" => ((int) $d['design_font_size_button']) . 'px',
			"--{$p}-radius" => ((int) $d['design_radius']) . 'px',
			"--{$p}-shadow" => $shadow,
			"--{$p}-maxw" => ((int) $d['design_max_width']) . 'px',
			"--{$p}-pad" => ((int) $d['design_spacing']) . 'px',
			"--{$p}-fab-size" => ((int) $d['icon_size']) . 'px',
			"--{$p}-fab-bg" => $d['icon_color_bg'],
			"--{$p}-fab-fg" => $d['icon_color_fg'],
			"--{$p}-fab-x" => ((int) $d['icon_offset_x']) . 'px',
			"--{$p}-fab-y" => ((int) $d['icon_offset_y']) . 'px',
			"--{$p}-fab-icon-shadow" => $iconShadow,
		];
		$css = '';
		foreach($vars as $name => $value) $css .= "{$name}:{$value};";
		// placeholders are rendered outside the widget root, so they carry the vars too
		$out = ".{$p}-root,.{$p}-ph{{$css}}";

		// dark theme: override the color vars under prefers-color-scheme (or a
		// forced .{p}-dark class used by the builder preview)
		if($d['dark_enable']) {
			$darkCss = '';
			foreach($this->darkVarMap() as $key => $suffix) {
				if(!empty($d[$key])) $darkCss .= "--{$p}-{$suffix}:{$d[$key]};";
			}
			if($darkCss) {
				$out .= "@media(prefers-color-scheme:dark){.{$p}-root:not(.{$p}-force-light),.{$p}-root:not(.{$p}-force-light) .{$p}-ph,.{$p}-ph{{$darkCss}}}";
				$out .= ".{$p}-root.{$p}-force-dark,.{$p}-force-dark .{$p}-ph{{$darkCss}}";
			}
		}
		return $out;
	}

	public function resolveFontFamily(?array $d = null) {
		if($d === null) $d = $this->getDesign();
		switch($d['design_font_family']) {
			case 'system': return "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
			case 'serif': return "Georgia,'Times New Roman',serif";
			case 'mono': return "ui-monospace,'SF Mono',Menlo,Consolas,monospace";
			case 'custom': return $d['design_font_custom'] ?: 'inherit';
			default: return 'inherit';
		}
	}

	/**
	 * Frontend runtime configuration (window.pwcmConfig).
	 * @return array
	 */
	public function ___getFrontendConfig() {
		$categories = [];
		$cookiesToClear = [];
		foreach($this->getCategories() as $cat) {
			$categories[] = [
				'key' => $cat['key'],
				'label' => $cat['label'],
				'required' => $cat['required'],
			];
			foreach($cat['services'] as $service) {
				if(empty($service['cookies'])) continue;
				if(!isset($cookiesToClear[$cat['key']])) $cookiesToClear[$cat['key']] = [];
				$cookiesToClear[$cat['key']] = array_merge($cookiesToClear[$cat['key']], $service['cookies']);
			}
		}
		// Geo mode is resolved through a private/no-store request so a shared
		// full-page cache never persists one visitor's regional consent model.
		$model = $this->geo_mode ? 'optin' : $this->resolveConsentModel();
		return [
			'prefix' => $this->cssPrefix(),
			'cookieName' => $this->cookieName(),
			'version' => (int) $this->version,
			'expireDays' => (int) $this->consent_expire_days,
			'model' => $model === 'optout' ? 'optout' : 'optin',
			'gpc' => (bool) $this->respect_gpc,
			'dnt' => (bool) $this->respect_dnt,
			'bots' => (bool) $this->detect_bots,
			'messageTimeout' => (int) $this->message_timeout,
			// 'none' region: no applicable law → never auto-show, widget stays reachable
			'autoShow' => $model !== 'none' && $this->allowBanner($this->wire()->page),
			'geoConfigUrl' => $this->geo_mode
				? rtrim($this->wire()->config->urls->root, '/') . self::GEO_CONFIG_ENDPOINT
				: '',
			'bodyClasses' => (bool) $this->body_classes,
			'observe' => (bool) $this->observe_dom,
			'reloadOnRevoke' => (bool) $this->reload_on_revoke,
			'customFunction' => (string) $this->custom_function,
			'consentMode' => (bool) $this->consent_mode,
			'consentModeMap' => $this->getConsentModeMap(),
			'logEndpoint' => $this->enable_logging ? rtrim($this->wire()->config->urls->root, '/') . self::LOG_ENDPOINT : '',
			'videoPreview' => (bool) $this->ph_video_posters,
			'categories' => $categories,
			'cookiesToClear' => $cookiesToClear,
		];
	}

	/**
	 * Category → Google Consent Mode v2 signals mapping.
	 * @return array
	 */
	public function getConsentModeMap() {
		$raw = trim((string) $this->consent_mode_map);
		if($raw) {
			$map = json_decode($raw, true);
			if(is_array($map)) return $map;
		}
		return [
			'statistics' => ['analytics_storage'],
			'marketing' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
			'functional' => ['functionality_storage', 'personalization_storage'],
		];
	}

	/* ==================================================================
	 * Geo mode — regional consent model
	 * ================================================================ */

	/**
	 * Resolve the consent model for the current visitor.
	 * With geo mode off, the static `consent_model` setting is used. With geo
	 * mode on, the visitor's country decides: opt-in (GDPR regions), opt-out
	 * (US-style regions), or a default for everyone else. Hookable.
	 *
	 * NOTE: with a full-page cache (ProCache) the emitted config is shared
	 * between visitors — vary the cache by country or exclude the config script.
	 *
	 * @return string 'optin' | 'optout' | 'none'
	 */
	public function ___resolveConsentModel() {
		$static = $this->consent_model === 'optout' ? 'optout' : 'optin';
		if(!$this->geo_mode) return $static;

		$country = $this->detectCountry();
		if($country) {
			if(in_array($country, $this->geoCountryList('geo_optout_countries'), true)) return 'optout';
			if(in_array($country, $this->geoCountryList('geo_optin_countries'), true)) return 'optin';
		}
		$default = (string) $this->geo_default_model;
		return in_array($default, ['optin', 'optout', 'none'], true) ? $default : 'optin';
	}

	/**
	 * Detect the visitor's ISO-3166 country code (uppercase, 2 letters).
	 * Reads the configured header (default CF-IPCountry, set by Cloudflare and
	 * many CDNs/reverse proxies). Override via the `geoCountry` $config value or
	 * by hooking this method to plug in a GeoIP library.
	 * @return string country code or '' when unknown
	 */
	public function ___detectCountry() {
		$override = $this->wire()->config->geoCountry;
		if($override) return strtoupper(substr((string) $override, 0, 2));

		$header = trim((string) $this->geo_header) ?: 'CF-IPCountry';
		$key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
		$value = isset($_SERVER[$key]) ? strtoupper(trim($_SERVER[$key])) : '';
		return preg_match('/^[A-Z]{2}$/', $value) ? $value : '';
	}

	/**
	 * Parse a config field of country codes (comma/space/newline separated).
	 * @param string $field config field name
	 * @return array uppercase 2-letter codes
	 */
	public function geoCountryList($field) {
		$codes = [];
		foreach(preg_split('/[\s,]+/', strtoupper((string) $this->get($field))) as $code) {
			$code = trim($code);
			if(preg_match('/^[A-Z]{2}$/', $code)) $codes[$code] = true;
		}
		return array_keys($codes);
	}

	/**
	 * Request-local regional consent settings for cache-safe frontend startup.
	 */
	public function hookGeoConfig(HookEvent $event) {
		if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
			http_response_code(405);
			header('Allow: GET');
			return '';
		}

		$model = $this->resolveConsentModel();
		$this->wire()->config->ajax = true;
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: private, no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		return json_encode([
			'model' => $model === 'optout' ? 'optout' : 'optin',
			'autoShow' => $model !== 'none' && $this->allowBanner($this->wire()->page),
		], JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Should the banner auto-show on this page?
	 * Suppressed on excluded pages and on privacy/imprint pages (so legal texts
	 * are readable without an overlay), or when only required categories exist.
	 * @param Page|null $page
	 * @return bool
	 */
	public function ___allowBanner($page) {
		if(!$page || !$page->id) return true;

		// nothing to ask when only required categories are configured
		if($this->hide_when_only_necessary) {
			$hasOptional = false;
			foreach($this->getCategories() as $cat) {
				if(!$cat['required']) { $hasOptional = true; break; }
			}
			if(!$hasOptional) return false;
		}

		$excluded = $this->excluded_pages;
		if(is_array($excluded) && in_array($page->id, array_map('intval', $excluded))) return false;

		if($this->auto_exclude_legal) {
			foreach(['link_privacy', 'link_imprint'] as $key) {
				$link = $this->resolveLink($key);
				if(!$link) continue;
				$path = parse_url($link, PHP_URL_PATH);
				if($path && rtrim($path, '/') === rtrim($page->url, '/')) return false;
			}
		}
		return true;
	}

	public function cssPrefix() {
		$prefix = $this->wire()->sanitizer->name((string) $this->css_prefix);
		return $prefix ?: 'pwcm';
	}

	public function cookieName() {
		$name = $this->wire()->sanitizer->name((string) $this->cookie_name);
		return $name ?: 'pwcm_consent';
	}

	/* ==================================================================
	 * Consent-first: server-side gating engine
	 *
	 * Hookable decision points for blocking/restricting via PW hooks:
	 *   Cookie::getBlockRules  — add/remove domain=>category rules
	 *   Cookie::gateHtml       — post-process the gated markup
	 *   Cookie::allowGate      — skip auto-gating on certain pages
	 *   Cookie::allowCategory  — remove a category entirely (kill switch)
	 *   Cookie::allowBanner    — suppress the banner
	 * ================================================================ */

	/**
	 * Domain => category rules used for automatic blocking.
	 * Built-in tracker list (consent-first mode) + the configured embed map;
	 * configured lines override built-ins. Hookable: add rules from code.
	 * @return array
	 */
	public function ___getBlockRules() {
		// tracker map applies in consent-first mode only; embed map always;
		// configured lines override the tracker list, hooks override both
		$rules = $this->consent_first ? $this->parseRuleLines((string) $this->tracker_map) : [];
		return array_merge($rules, $this->parseRuleLines((string) $this->embed_map));
	}

	/**
	 * Parse `domain=category` lines into a rules array.
	 * @param string $text
	 * @return array
	 */
	public function parseRuleLines($text) {
		$rules = [];
		foreach(explode("\n", $text) as $line) {
			$line = trim($line);
			if(!$line || $line[0] === '#' || strpos($line, '=') === false) continue;
			list($domain, $category) = explode('=', $line, 2);
			$domain = strtolower(trim($domain));
			$category = $this->wire()->sanitizer->fieldName(trim($category));
			if($domain && $category) $rules[$domain] = $category;
		}
		return $rules;
	}

	/**
	 * Neutralize third-party <script src> and <iframe> tags matching the block
	 * rules so they load only after consent. Elements already carrying
	 * data-consent (or opted out via data-consent-ignore) are left untouched.
	 * Used for whole-page consent-first gating and by TextformatterCookie.
	 * @param string $html
	 * @return string
	 */
	public function ___gateHtml($html) {
		if(strpos($html, '<script') === false && strpos($html, '<iframe') === false) return $html;
		$rules = $this->getBlockRules();
		if(!count($rules)) return $html;

		$html = preg_replace_callback('/<script\b[^>]*\bsrc\s*=\s*["\'][^"\']+["\'][^>]*>\s*<\/script>/is', function($matches) use ($rules) {
			return $this->gateScriptTag($matches[0], $rules);
		}, $html);

		$html = preg_replace_callback('/<iframe\b[^>]*>/is', function($matches) use ($rules) {
			return $this->gateIframeTag($matches[0], $rules);
		}, $html);

		return $html;
	}

	/**
	 * Should consent-first auto-gating run on this page? Hookable.
	 * @param Page|null $page
	 * @return bool
	 */
	public function ___allowGate($page) {
		return true;
	}

	/**
	 * Is this category available at all? Hookable kill switch: return false to
	 * remove the category — its content stays blocked and the toggle disappears
	 * from the preferences window (stored consent for it is ignored client-side).
	 * @param string $key
	 * @return bool
	 */
	public function ___allowCategory($key) {
		return true;
	}

	/**
	 * @param string $tag full <script ...></script> match
	 * @param array $rules
	 * @return string
	 */
	protected function gateScriptTag($tag, array $rules) {
		if(stripos($tag, 'data-consent') !== false) return $tag; // gated or data-consent-ignore
		if(!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $matches)) return $tag;
		$category = $this->matchBlockCategory($matches[1], $rules);
		if($category === null) return $tag;

		$endOpen = strpos($tag, '>');
		$open = substr($tag, 0, $endOpen);
		$rest = substr($tag, $endOpen);

		if(preg_match('/\btype\s*=\s*["\']([^"\']*)["\']/i', $open, $typeMatch)) {
			if(strtolower($typeMatch[1]) === 'text/plain') return $tag; // already neutral
			$open = preg_replace(
				'/\btype\s*=\s*["\'][^"\']*["\']/i',
				'type="text/plain" data-type="' . $this->wire()->sanitizer->entities1($typeMatch[1]) . '"',
				$open, 1
			);
		} else {
			$open .= ' type="text/plain"';
		}
		return $open . ' data-consent="' . $category . '"' . $rest;
	}

	/**
	 * @param string $tag <iframe ...> opening tag match
	 * @param array $rules
	 * @return string
	 */
	protected function gateIframeTag($tag, array $rules) {
		if(stripos($tag, 'data-consent') !== false) return $tag;
		if(!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $matches)) return $tag;
		$category = $this->matchBlockCategory($matches[1], $rules);
		if($category === null) return $tag;
		$poster = $this->getVideoPoster($matches[1]);
		$posterAttr = $poster ? " data-ph-poster=\"{$this->wire()->sanitizer->entities1($poster)}\"" : '';
		$tag = preg_replace('/\bsrc\s*=/i', 'data-src=', $tag, 1);
		return substr($tag, 0, -1) . " data-consent=\"{$category}\" data-placeholder=\"1\"{$posterAttr}>";
	}

	/**
	 * Locally cached poster image URL for a YouTube/Vimeo embed URL.
	 *
	 * The thumbnail is downloaded once by the server and served from
	 * /site/assets/cache/ — the visitor's browser never contacts the video
	 * platform before consent (GDPR: no IP leak through preview images).
	 *
	 * @param string $url embed URL
	 * @return string local poster URL or '' when unavailable
	 */
	public function getVideoPoster($url) {
		if(!$this->ph_video_posters) return '';
		try {
			$video = $this->extractVideoId($url);
			if(!$video) return '';
			$config = $this->wire()->config;
			$file = $video[0] . '-' . $video[1] . '.jpg';
			$dir = $config->paths->cache . 'Cookie/posters/';
			$posterUrl = $config->urls->cache . 'Cookie/posters/' . $file;
			if(is_file($dir . $file)) return $posterUrl;

			$cache = $this->wire()->cache;
			$missKey = "pwcm-poster-miss-{$file}";
			if($cache && $cache->get($missKey)) return '';

			$src = null;
			if($video[0] === 'yt') {
				$src = "https://i.ytimg.com/vi/{$video[1]}/hqdefault.jpg";
			} else {
				$http = new WireHttp();
				$http->setTimeout(5);
				$json = $http->getJSON('https://vimeo.com/api/oembed.json?url=' . urlencode("https://vimeo.com/{$video[1]}") . '&width=960');
				if(is_array($json) && !empty($json['thumbnail_url'])) $src = $json['thumbnail_url'];
			}
			if($src) {
				if(!is_dir($dir)) $this->wire()->files->mkdir($dir, true);
				$http = new WireHttp();
				$http->setTimeout(5);
				$http->download($src, $dir . $file);
				if(is_file($dir . $file) && filesize($dir . $file) > 0) return $posterUrl;
			}
			if($cache) $cache->save($missKey, '1', 86400);
		} catch(\Throwable $e) {
			// poster is a nicety — never let it break rendering
			try {
				$cache = $this->wire()->cache;
				if($cache && isset($missKey)) $cache->save($missKey, '1', 86400);
			} catch(\Throwable $e2) {
				// ignore
			}
		}
		return '';
	}

	/**
	 * Extract a video platform + id from an embed URL.
	 * @param string $url
	 * @return array|null ['yt'|'vimeo', id]
	 */
	public function extractVideoId($url) {
		if(preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|shorts/|v/|watch\?(?:[^"\']*&)?v=)|youtu\.be/)([A-Za-z0-9_-]{6,15})~i', $url, $m)) {
			return ['yt', $m[1]];
		}
		if(preg_match('~(?:player\.)?vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
			return ['vimeo', $m[1]];
		}
		return null;
	}

	/**
	 * Find the first rule domain contained in the URL.
	 * @param string $url
	 * @param array $rules
	 * @return string|null category
	 */
	public function matchBlockCategory($url, array $rules) {
		$url = strtolower($url);
		foreach($rules as $domain => $category) {
			if(strpos($url, $domain) !== false) return $category;
		}
		return null;
	}

	/* ==================================================================
	 * Icons
	 * ================================================================ */

	/**
	 * Inline SVG for the floating settings button.
	 * @param string $type cookie|shield|sliders|gear|banana|fingerprint|pw
	 * @return string
	 */
	public function ___getIconSvg($type) {
		$icons = [
			'cookie' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">' .
				'<path d="M6 7.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m4.5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-.5 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>' .
				'<path d="M8 0a7.96 7.96 0 0 0-4.075 1.114q-.245.102-.437.28A8 8 0 1 0 8 0m3.25 14.201a1.5 1.5 0 0 0-2.13.71A7 7 0 0 1 8 15a6.97 6.97 0 0 1-3.845-1.15 1.5 1.5 0 1 0-2.005-2.005A6.97 6.97 0 0 1 1 8c0-1.953.8-3.719 2.09-4.989a1.5 1.5 0 1 0 2.469-1.574A7 7 0 0 1 8 1c1.42 0 2.742.423 3.845 1.15a1.5 1.5 0 1 0 2.005 2.005A6.97 6.97 0 0 1 15 8c0 .596-.074 1.174-.214 1.727a1.5 1.5 0 1 0-1.025 2.25 7 7 0 0 1-2.51 2.224Z"/></svg>',
			'shield' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">' .
				'<path fill-rule="evenodd" d="M8 14.933a1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/></svg>',
			'sliders' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">' .
				'<path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1z"/></svg>',
			'gear' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">' .
				'<path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z"/></svg>',
			// the conversation starter — a real, detailed banana illustration
			'banana' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="45.44 93.42 119.12 108.17" fill="currentColor" aria-hidden="true">' .
				'<g transform="translate(0,-2)"><g transform="matrix(0.20670846,0,0,-0.20670846,-76.43309,319.18435)"><g transform="translate(-949.82487,-979.82602)"><g transform="translate(580.90642,1188.3222)" fill-rule="nonzero">' .
				'<path d="m 1393.136,578.0696 c 0.5984,0.8158 1.1281,1.3907 1.4934,2.0584 2.6961,4.9597 5.5287,9.8583 8.0075,14.9267 9.8207,20.0921 18.1247,40.7608 24.1767,62.3462 5.7007,20.3329 9.5721,40.9684 11.6027,61.9668 0.8652,8.9421 1.7955,17.878 2.5044,26.8338 0.9759,12.2957 1.2682,24.6172 0.5334,36.9406 -0.5213,8.7443 -1.9575,17.3386 -4.6576,25.6939 -2.5442,7.8711 -6.05,15.2566 -11.0574,21.8873 -2.3365,3.0952 -5.0053,5.837 -8.1971,8.0689 -4.1637,2.9115 -8.7286,4.1777 -13.8127,3.7214 -2.3407,-0.2122 -4.5551,-0.7171 -6.631,-1.8152 -3.4172,-1.8073 -6.6425,-3.8775 -9.5087,-6.5006 -3.419,-3.1287 -6.5655,-6.5005 -9.3784,-10.1843 -9.4079,-12.3314 -16.8112,-25.8518 -22.5573,-40.1762 -7.3536,-18.3401 -13.3207,-37.1915 -19.2169,-56.053 -4.2862,-13.716 -8.9003,-27.3216 -14.2731,-40.6662 -8.2957,-20.5997 -19.0489,-39.7909 -32.4864,-57.4692 -2.4848,-3.2691 -5.0942,-6.4432 -7.6087,-9.6885 -0.9362,-1.2089 -1.7834,-2.4888 -2.633,-3.7607 -0.1134,-0.1713 -0.1208,-0.5631 0,-0.7152 0.2727,-0.3536 0.6757,-0.9087 0.9916,-0.8848 11.2865,0.7801 22.5393,0.8926 33.8278,-0.3557 12.037,-1.3313 23.8352,-3.5416 35.2954,-7.5218 15.5392,-5.3982 29.3539,-13.4431 40.3817,-25.8735 0.8256,-0.9302 1.8883,-1.6491 3.2037,-2.7791 M 1099.8461,407.6371 c 15.0176,-0.3398 29.3085,0.385 42.2205,1.9495 3.8241,0.4642 7.6777,0.7249 11.4799,1.3313 9.8861,1.5801 19.7051,3.4921 29.4645,5.7757 20.3371,4.7564 39.8996,11.6617 58.7711,20.556 9.2955,4.3831 18.3716,9.1927 27.1398,14.5675 1.6433,1.0074 2.6052,2.2972 2.941,4.2347 2.3189,13.4078 5.0347,26.7368 6.5498,40.2907 1.1159,9.9949 1.5664,19.9639 1.122,29.9881 -0.2824,6.4373 -1.4322,12.7561 -3.0062,18.9879 -1.0726,4.2428 -2.6549,8.3097 -4.6399,12.2167 -2.7022,5.3213 -6.2103,10.0343 -10.6346,14.0579 -1.517,1.3805 -3.113,2.69 -4.0494,4.5902 -0.6004,1.2129 -0.794,2.4593 -0.2805,3.7135 2.2084,5.4081 8.0709,7.257 12.6396,3.9269 3.1269,-2.2775 5.8268,-5.0311 8.3098,-7.9997 7.2531,-8.6715 11.6183,-18.7254 13.961,-29.6798 1.8823,-8.8078 2.8244,-17.7316 2.862,-26.7646 0.038,-9.2815 -0.944,-18.4742 -2.0937,-27.6432 -1.1852,-9.4218 -2.6764,-18.8121 -4.2783,-28.1746 -1.4263,-8.3475 -2.6568,-16.7363 -4.8294,-24.9491 -1.7068,-6.4513 -3.3383,-12.9299 -4.7168,-19.4581 -1.3275,-6.2792 -2.2677,-12.6316 -2.1867,-19.0984 0.038,-3.1149 0.5392,-6.139 1.3668,-9.1137 1.3412,-4.8217 4.4641,-8.0532 9.1928,-9.576 3.1861,-1.0252 6.4747,-1.5111 9.8801,-1.0923 5.3844,0.6637 10.605,1.8607 15.5707,4.1184 22.7151,10.3383 41.5569,25.433 55.8121,45.9733 14.579,21.0047 24.0861,44.1405 27.6215,69.5243 1.7442,12.519 1.7758,25.1248 -0.8531,37.5767 -2.4199,11.4662 -6.9707,22.0119 -14.3897,31.1889 -6.7255,8.3196 -14.9721,14.7411 -24.5876,19.4263 -15,7.3083 -30.9479,10.8954 -47.4963,11.9976 -13.882,0.9224 -27.7224,0.4087 -41.5333,-1.4004 -3.5139,-0.4601 -6.3758,-1.6415 -8.7896,-4.1797 -1.4143,-1.4854 -2.9354,-2.8798 -4.4917,-4.2191 -6.0343,-5.1948 -11.9482,-10.5436 -18.1839,-15.4857 -20.2127,-16.0191 -42.039,-29.4804 -65.3922,-40.4211 -9.7636,-4.5746 -19.6337,-8.9437 -29.97,-12.128 -6.1313,-1.8902 -12.2761,-3.737 -18.4289,-5.5603 -10.0895,-2.9903 -20.1789,-5.9828 -30.2883,-8.9022 -14.1227,-4.0788 -27.3448,-10.3088 -40.4882,-16.7461 -0.6913,-0.3378 -1.3845,-0.6754 -2.0621,-1.0429 -5.4338,-2.9491 -10.617,-6.3324 -15.3198,-10.3126 -8.8629,-7.4981 -15.5964,-16.347 -16.4714,-28.5282 -0.3002,-4.1304 -2.7498,-6.4808 -6.6094,-7.5179 -1.2364,-0.3337 -2.5046,-0.551 -3.7549,-0.8335 -2.2556,-0.5076 -4.5094,-1.0172 -6.7592,-1.5366 -2.28344,-0.5276 -4.36124,-1.517 -6.26544,-2.8661 -1.9376,-1.3747 -3.4606,-3.1465 -4.142,-5.4142 -0.9797,-3.263 -1.0349,-6.6367 0,-9.8819 0.9127,-2.8464 2.7969,-4.8138 5.9652,-5.3609 2.319,-0.4029 4.59464,-0.3022 6.90164,-0.048 5.8959,0.6539 11.7861,0.9343 17.5953,-0.7131 0.4877,-0.1375 1.0173,-0.1375 1.5268,-0.2122 12.7047,-1.831 25.4192,-3.6146 38.116,-5.509 13.9966,-2.0858 28.1035,-2.8343 39.9786,-3.6245 m 178.4937,235.6214 c 0.727,0.1254 1.3827,0.041 1.6315,0.3199 1.1912,1.3292 2.3545,2.7001 3.3775,4.1598 9.0053,12.8666 16.8942,26.3793 23.5884,40.591 5.8152,12.3413 10.354,25.1465 14.4211,38.1555 5.596,17.8937 11.7249,35.6134 18.7508,53.0053 5.2978,13.1096 11.2452,25.9169 18.1268,38.28 4.6359,8.3256 9.8424,16.2621 16.029,23.5269 4.693,5.509 9.9177,10.4253 16.2304,14.0715 3.8298,2.2124 7.9127,3.5989 12.3552,4.0157 6.9389,0.6519 13.234,-1.0724 19.0588,-4.7781 5.4793,-3.4842 10.1131,-7.9266 14.2159,-12.8982 6.9527,-8.4263 11.8136,-17.9707 15.0016,-28.4256 3.3996,-11.1463 4.9757,-22.5749 5.4459,-34.1496 0.304,-7.4369 0.3694,-14.9129 -0.028,-22.3419 -0.7682,-14.3619 -1.8487,-28.6981 -3.7648,-42.981 -1.7441,-12.993 -3.7626,-25.9229 -6.4469,-38.7482 -3.1112,-14.8437 -7.1623,-29.423 -11.9997,-43.8046 -6.5716,-19.5469 -15.0116,-38.2265 -25.113,-56.1834 -0.869,-1.5448 -1.5881,-3.1743 -2.2992,-4.6062 0.9185,-1.7402 2.388,-2.3033 3.7352,-3.038 5.4318,-2.9648 10.5043,-6.4569 15.1401,-10.5577 7.0161,-6.2041 12.594,-13.5577 16.8072,-21.8834 5.5978,-11.0575 9.6589,-22.652 11.7092,-34.9301 0.64,-3.8379 0.7407,-7.6519 0.7268,-11.5115 -0.025,-3.8479 0.1184,-7.6995 0.2647,-11.5454 0.058,-1.5584 0.5867,-3.0081 1.4538,-4.3314 1.5486,-2.3646 3.7333,-3.5517 6.5262,-3.2276 3.6106,0.4186 6.9981,1.5348 10.0146,3.6759 4.6654,3.3086 8.5228,7.4031 11.8767,11.9996 5.6316,7.7249 9.8902,16.1888 13.7004,24.9292 6.0105,13.789 7.5731,28.1569 6.1863,42.9948 -1.3786,14.7668 -5.3371,28.7455 -12.5605,41.7484 -3.5118,6.319 -7.6796,12.1597 -12.7343,17.3427 -5.985,6.1409 -12.8489,11.0275 -20.7634,14.3914 -1.6554,0.7031 -3.352,1.3134 -4.9816,2.0682 -3.5042,1.6196 -5.3746,5.7696 -3.4212,9.7437 0.2243,0.4583 0.4208,0.9422 0.7152,1.355 0.9322,1.3096 2.1706,2.1509 3.8041,2.3605 2.3704,0.304 4.5589,-0.3024 6.7554,-1.1319 13.7673,-5.2028 25.3816,-13.4414 34.7384,-24.7774 12.2462,-14.8377 19.3057,-31.959 22.2152,-50.9153 1.2919,-8.4265 1.7503,-16.8705 1.2149,-25.3541 -1.0174,-16.0902 -4.8313,-31.4556 -12.5426,-45.7108 -7.3322,-13.5559 -17.0403,-25.0064 -30.0257,-33.5196 -3.4525,-2.2636 -7.0732,-4.1519 -10.9111,-5.6709 -3.9228,-1.5507 -7.9444,-1.9514 -12.1042,-1.602 -3.7627,0.3142 -6.8955,1.9773 -9.5818,4.4878 -1.91,1.7877 -3.2946,4.0216 -3.5929,6.6546 -0.3181,2.7949 -0.4603,5.6295 -0.4367,8.444 0.1567,16.345 -2.143,32.2496 -8.2307,47.5122 -3.7607,9.4238 -9.0207,17.8621 -16.2797,25.0004 -2.1984,2.163 -4.4405,4.2903 -7.2176,5.7106 -0.32,0.1615 -0.9026,-0.084 -1.3154,-0.253 -0.1736,-0.07 -0.3398,-0.4483 -0.3142,-0.6636 0.1159,-1.0151 0.1929,-2.0583 0.484,-3.0301 3.9662,-13.226 4.9382,-26.7781 3.8538,-40.4369 -2.6036,-32.8459 -14.119,-62.4054 -33.587,-88.9167 -1.2126,-1.6513 -2.5598,-3.2178 -3.9582,-4.7191 -14.4784,-15.5389 -30.8414,-28.6704 -49.7046,-38.5424 -6.6053,-3.4567 -13.4474,-6.3861 -20.75,-8.1263 -6.3503,-1.5148 -12.74,-1.4892 -19.1499,-0.6575 -1.505,0.1928 -3.0121,0.699 -4.4164,1.2975 -10.7394,4.5825 -17.4492,12.3827 -18.9327,24.1966 -0.6103,4.8671 -0.482,9.7697 0.1639,14.6147 0.7447,5.5977 1.5961,11.1877 3.042,16.6689 0.6537,2.4789 1.1415,5.0034 1.6254,7.5218 0.081,0.4287 -0.1183,1.2345 -0.3911,1.3432 -0.6359,0.2507 -1.499,0.4286 -2.0838,0.1856 -1.6552,-0.6853 -3.2255,-1.5782 -4.8116,-2.4217 -14.3183,-7.6242 -29.2907,-13.7317 -44.5948,-19.0253 -10.439,-3.6126 -21.0815,-6.5458 -31.8663,-8.9734 -23.4025,-5.2681 -47.0913,-7.9463 -71.0806,-7.9128 -6.6822,0 -13.3763,-0.084 -20.0386,0.318 -11.0199,0.6697 -22.0359,1.4992 -32.8325,4.0906 -6.7394,1.6178 -13.5125,3.109 -20.3033,4.4958 -3.7706,0.772 -7.5948,1.3706 -11.4228,1.7559 -5.6452,0.5708 -11.2884,0.5331 -16.7853,-1.2624 -4.7052,-1.5386 -9.5307,-1.9613 -14.46484,-1.5603 -6.8776,0.559 -12.0528,3.7371 -15.9402,9.4595 -4.3474,6.3958 -6.216,13.4512 -6.5044,21.0479 -0.106,2.8642 0.4958,5.6315 1.2997,8.3435 0.8257,2.7851 2.6013,4.9835 4.7189,6.9191 4.4621,4.0829 9.7931,6.5519 15.5193,8.223 2.4572,0.7169 4.9934,1.1673 7.77674,1.8035 0.6456,1.2403 1.4042,2.5557 2.0344,3.9326 1.8093,3.97 3.6166,7.9442 5.3291,11.9579 1.4282,3.3539 3.4923,6.2794 5.8228,9.0307 9.1316,10.779 20.2265,18.8181 33.2511,24.3587 9.9515,4.2329 20.1968,7.5314 30.5568,10.528 17.2794,4.9992 34.3001,10.7532 51.1091,17.1487 28.953,11.0161 56.2092,25.3029 81.5494,43.0937 18.7707,13.1787 36.0499,28.1314 51.2158,45.4164 0.6774,0.7723 1.4003,1.5071 2.0403,2.3091 0.8298,1.0409 1.2266,2.1926 0.9401,3.5753 -2.5538,12.3471 -5.6213,24.5026 -11.2645,35.9115 -9.0426,18.2866 -23.8254,29.2808 -43.2596,34.2052 -9.9985,2.534 -20.2816,3.1127 -30.6101,2.6429 -16.5682,-0.7545 -32.6367,-4.0434 -48.1167,-9.8784 -23.7105,-8.9319 -44.476,-22.3219 -61.0719,-41.7127 -8.78,-10.2593 -15.6084,-21.6426 -19.464,-34.687 -1.0291,-3.4764 -1.8369,-6.949 -1.3156,-10.609 0.2568,-1.8073 1.9694,-3.4113 3.7511,-3.1288 2.0128,0.3179 4.069,0.6953 5.9474,1.444 3.3361,1.3291 6.5873,2.8838 9.8088,4.4798 15.2706,7.5629 31.2619,13.0681 47.9271,16.5503 10.838,2.2657 21.7768,3.5574 32.9093,2.9746 7.5415,-0.3949 14.7293,-2.1193 21.6761,-4.93 11.7409,-4.7506 20.0487,-13.2201 25.7532,-24.3367 1.993,-3.8813 3.6345,-7.9525 4.6949,-12.2071 1.1122,-4.4542 -2.2772,-9.003 -6.8855,-9.1985 -2.7692,-0.1184 -4.9895,1.1514 -6.5143,3.4408 -0.6993,1.0527 -1.1536,2.2775 -1.6513,3.4547 -1.1971,2.8343 -2.1589,5.7894 -3.5652,8.5133 -4.4147,8.5506 -11.016,14.7372 -20.1693,18.0516 -5.3587,1.9396 -10.8498,3.0655 -16.5661,3.1979 -7.2334,0.1688 -14.3838,-0.5787 -21.4551,-1.9476 -7.0575,-1.367 -14.0219,-3.109 -20.8701,-5.3727 -13.4295,-4.4403 -26.6418,-9.3726 -39.2972,-15.7346 -2.7476,-1.3827 -5.5978,-2.5838 -8.4717,-3.6799 -1.9042,-0.7268 -3.9052,-1.3234 -5.9159,-1.6277 -6.5242,-0.9856 -13.0324,2.2934 -16.2029,8.059 -2.1669,3.9466 -2.9449,8.2091 -2.4431,12.6651 0.6635,5.91 2.2715,11.5788 4.5982,17.0504 4.8591,11.4187 11.2766,21.9112 18.9029,31.6726 13.9806,17.8955 31.2581,31.7755 51.2887,42.4024 14.6504,7.7724 30.0968,13.1529 46.3469,16.2343 7.5769,1.436 15.2664,2.0287 22.9659,2.55 10.5815,0.715 21.0759,0.2243 31.4576,-1.8389 25.4114,-5.0486 44.6303,-18.7943 57.5444,-41.31 4.1103,-7.166 7.2275,-14.7449 9.7676,-22.5847 0.6279,-1.9377 1.2758,-3.8714 1.904,-5.7715"/>' .
				'<path d="m 1054.3031,430.4947 c -4.535,0 -7.3597,-0.025 -10.1821,0 -1.0233,0 -2.0564,0.063 -3.0677,0.2218 -3.3993,0.5373 -5.807,3.5671 -5.7301,7.1364 0.07,3.188 2.3684,5.9237 5.588,6.392 2.0227,0.2942 4.0869,0.3851 6.1349,0.4345 14.127,0.3318 28.2162,1.3234 42.2344,2.9845 25.5615,3.026 50.5283,8.7504 74.8099,17.376 20.8762,7.417 40.6898,17.0307 59.5473,28.6312 10.5101,6.4648 20.4851,13.6822 30.0353,21.4904 2.7813,2.2753 5.493,4.6437 8.3789,6.779 4.3356,3.2117 10.0045,1.4854 12.0488,-3.524 0.7469,-1.829 0.4958,-3.5986 -0.618,-5.1117 -0.899,-1.2188 -2.0484,-2.2854 -3.2099,-3.2749 -12.1201,-10.3582 -24.9255,-19.7801 -38.4635,-28.2024 -32.3524,-20.1336 -67.3023,-33.812 -104.4602,-41.9935 -19.0747,-4.2012 -38.3411,-7.085 -57.8484,-8.282 -5.6334,-0.3477 -11.2629,-0.7882 -15.1976,-1.0667"/>' .
				'</g></g></g></g></svg>',
			'fingerprint' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">' .
				'<path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33"/>' .
				'</svg>',
			// ProcessWire's own swirl logo mark — a branding homage
			'pw' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">' .
				'<path d="M234.02346,56.2065308 C226.258185,44.611679 213.340949,31.3634673 200.370381,22.7873303 C173.426584,4.33370224 142.216153,-2.21573572 114.611028,0.642976614 C85.8219124,3.7470262 61.1714319,14.5951995 40.9049183,32.6861551 C22.1317268,49.454423 9.73715371,69.9560838 4.27586162,89.5083961 C-1.24942998,109.060708 -0.513435538,127.162331 1.45988289,140.794549 C3.53986718,154.629436 10.4304818,172.037714 10.4304818,172.037714 C11.8384712,175.376434 13.7904564,176.731123 14.8037821,177.296465 C19.8384108,180.048509 28.105015,177.627137 34.4516337,170.50169 C34.7716313,170.06435 34.9422967,169.45634 34.7716313,168.944332 C33.000978,162.128223 32.3609828,156.997474 31.7316543,153.029411 C30.2916651,145.178619 29.65167,132.026409 30.6116627,119.866214 C31.0916591,113.284776 32.3716494,106.244663 34.6116325,98.8632122 C38.9422665,84.281646 48.0728642,69.0600695 62.3447564,56.4092007 C77.715307,42.7876498 97.4271581,34.3715154 116.16835,32.1954806 C122.738967,31.4061347 135.240206,30.6487893 150.290759,34.3608485 C153.501401,35.1608613 167.282631,38.7555854 182.023853,48.7397449 C192.754438,56.0358614 201.394373,65.0386719 207.346328,73.9454809 C213.404949,82.44695 219.986232,96.783179 221.916885,107.279347 C224.647531,119.226204 224.647531,131.88774 222.706212,144.218603 C220.30623,156.570801 215.975596,168.581659 209.24498,179.152495 C204.605015,187.344626 194.983755,198.171465 183.613174,206.299595 C173.362585,213.510377 161.66134,218.715793 149.650764,221.595839 C143.57081,223.035862 137.469522,223.95321 131.218903,224.15588 C125.661612,224.32655 118.291001,224.15588 113.117706,223.2812 C105.427098,222.054513 103.82711,220.091815 102.067123,217.425106 C102.067123,217.425106 100.840466,215.505075 100.499135,210.36366 C100.616467,163.376243 100.595134,175.920443 100.595134,151.525387 C100.595134,144.63461 100.371136,138.383844 100.435135,132.709086 C100.755133,123.396937 101.54446,116.996835 108.20041,110.063391 C113.01104,104.953976 119.741656,101.87126 127.154934,101.87126 C129.405583,101.87126 137.160191,101.977929 143.97614,107.642019 C151.282751,113.74345 152.509409,122.084916 152.797407,124.314285 C154.461394,137.359827 145.842792,147.077316 142.536151,149.541355 C138.440182,152.613404 134.760209,154.106761 132.274895,154.981442 C126.984268,156.752137 121.170979,157.264145 115.944352,156.922806 C115.144358,156.869472 114.41903,157.392147 114.259031,158.19216 L112.499044,167.322972 C110.781724,174.256417 114.632361,176.795124 116.872345,177.691138 C124.029624,179.899173 130.376243,180.816521 137.896186,180.251179 C149.426765,179.440499 160.797346,174.896427 170.450607,165.893616 C178.663878,158.085492 183.346509,148.453338 184.946497,137.679832 C186.546485,125.722308 184.466501,112.847436 179.015875,101.945928 C173.021254,89.9244028 162.674665,79.8869091 149.042768,74.393488 C135.272206,68.9747348 124.317622,68.7827317 110.195062,72.3881226 L110.035063,72.4414568 C100.861799,75.5988406 93.0111915,79.4922361 84.8405865,87.9297042 C79.2406288,93.7537973 74.6539968,100.804577 71.8593512,108.762037 C69.0860388,116.783498 68.3393778,122.767594 68.2113788,132.069076 C68.0407134,138.959853 68.3713775,145.359955 68.3713775,151.354717 L68.3713775,190.832681 C68.3713775,203.462216 67.9447141,205.648918 68.3713775,212.145022 C68.6060424,216.454424 69.2033713,221.329168 71.091357,226.566585 C73.0326757,232.337344 77.1073116,238.257439 79.9019571,240.988149 C83.8165942,245.158882 88.7978899,248.508269 93.693853,250.588302 C104.904435,255.569715 120.125653,256.359061 132.466893,255.879054 C140.637498,255.569715 148.85077,254.439031 156.904042,252.529667 C173.010587,248.700272 188.477137,241.734828 202.077034,232.070673 C216.658258,221.798509 229.330162,207.782285 236.327442,195.878095 C245.298041,181.733869 251.100664,165.861616 254.119308,149.552022 C256.839287,133.210428 256.711288,116.452827 253.063316,100.356569 C250.183338,85.4229975 242.492729,69.0387358 233.61813,55.8118579 L234.02346,56.2065308 Z"/>' .
				'</svg>',
		];
		return isset($icons[$type]) ? $icons[$type] : $icons['cookie'];
	}

	/* ==================================================================
	 * Template helpers (server-side API)
	 * ================================================================ */

	/**
	 * Does the current visitor have consent for the given category?
	 * Server-side read of the consent cookie — usable for conditional markup.
	 * @param string|null $category null = any valid consent stored
	 * @return bool
	 */
	public function hasConsent($category = null) {
		$raw = $this->wire()->input->cookie->get($this->cookieName());
		if(!$raw) return false;
		$data = json_decode($raw, true);
		if(!is_array($data) || !isset($data['v']) || (int) $data['v'] !== (int) $this->version) return false;
		if($category === null) return true;
		return !empty($data['g'][$category]);
	}

	/**
	 * Link/button that re-opens the preferences window (e.g. in the footer).
	 * @param string|null $label
	 * @param string $tag a|button
	 * @return string
	 */
	public function renderTrigger($label = null, $tag = 'a') {
		$p = $this->cssPrefix();
		$label = $label !== null ? $label : $this->txt('txt_btn_prefs');
		$label = $this->wire()->sanitizer->entities1($label);
		if($tag === 'button') return "<button type=\"button\" class=\"{$p}-show-prefs\">{$label}</button>";
		return "<a href=\"#\" class=\"{$p}-show-prefs\" role=\"button\">{$label}</a>";
	}

	/**
	 * Consent-gated external script tag.
	 * @param string $src
	 * @param string $category
	 * @param array $attrs extra attributes
	 * @return string
	 */
	public function script($src, $category = 'statistics', array $attrs = []) {
		$s = $this->wire()->sanitizer;
		$out = "<script type=\"text/plain\" data-consent=\"{$s->entities1($category)}\" src=\"{$s->entities1($src)}\"";
		foreach($attrs as $key => $value) $out .= " {$s->entities1($key)}=\"{$s->entities1($value)}\"";
		return $out . '></script>';
	}

	/**
	 * Consent-gated iframe with placeholder.
	 * @param string $src
	 * @param string $category
	 * @param array $attrs extra attributes
	 * @return string
	 */
	public function iframe($src, $category = 'external_media', array $attrs = []) {
		$s = $this->wire()->sanitizer;
		$out = "<iframe data-consent=\"{$s->entities1($category)}\" data-src=\"{$s->entities1($src)}\" data-placeholder=\"1\"";
		foreach($attrs as $key => $value) $out .= " {$s->entities1($key)}=\"{$s->entities1($value)}\"";
		return $out . '></iframe>';
	}

	/**
	 * Generate a ready cookie-policy document from the configured categories and
	 * services. Drop it into a template — `echo $modules->get('Cookie')->renderPolicy();`
	 * — or copy the HTML from Setup > Cookie > Policy.
	 *
	 * Hookable so the wording/markup can be adjusted from site code.
	 *
	 * @param array $options managePrefs (bool, default true), headings (bool, default true)
	 * @return string HTML
	 */
	public function ___renderPolicy(array $options = []) {
		$s = $this->wire()->sanitizer;
		$p = $this->cssPrefix();
		$managePrefs = !isset($options['managePrefs']) || $options['managePrefs'];
		$headings = !isset($options['headings']) || $options['headings'];

		$out = "<div class=\"{$p}-policy\">";

		if($headings) {
			$out .= '<h2>' . $s->entities1($this->_('Cookie Policy')) . '</h2>';
		}
		$out .= '<p class="' . $p . '-policy-updated"><em>' .
			sprintf($s->entities1($this->_('Last updated: %s')), date($this->_('F j, Y'))) . '</em></p>';

		$intro = trim($this->txt('policy_intro'));
		if($intro) $out .= '<p>' . nl2br($s->entities1($intro)) . '</p>';

		$out .= '<h3>' . $s->entities1($this->_('What are cookies?')) . '</h3>';
		$out .= '<p>' . $s->entities1($this->_('Cookies are small text files stored on your device when you visit a website. They are widely used to make websites work, to improve their efficiency, and to provide reporting and personalization. Some cookies are set by us, others by third-party services embedded in our pages.')) . '</p>';

		$out .= '<h3>' . $s->entities1($this->_('Categories of cookies we use')) . '</h3>';

		foreach($this->getCategories() as $cat) {
			$out .= '<div class="' . $p . '-policy-cat">';
			$out .= '<h4>' . $s->entities1($cat['label']) .
				($cat['required'] ? ' <span class="' . $p . '-policy-badge">' . $s->entities1($this->_('Always active')) . '</span>' : '') .
				'</h4>';
			if($cat['desc']) $out .= '<p>' . $s->entities1($cat['desc']) . '</p>';

			if(count($cat['services'])) {
				$out .= '<table class="' . $p . '-policy-table"><thead><tr>';
				foreach([
					$this->_('Service'), $this->_('Provider'), $this->_('Purpose'),
					$this->_('Retention'), $this->_('Cookies'),
				] as $th) {
					$out .= '<th>' . $s->entities1($th) . '</th>';
				}
				$out .= '</tr></thead><tbody>';
				foreach($cat['services'] as $svc) {
					$cookies = count($svc['cookies'])
						? '<code>' . implode('</code>, <code>', array_map([$s, 'entities1'], $svc['cookies'])) . '</code>'
						: '<span class="' . $p . '-policy-muted">' . $s->entities1($this->_('none')) . '</span>';
					$out .= '<tr>' .
						'<th scope="row">' . $s->entities1($svc['name']) . '</th>' .
						'<td>' . $s->entities1($svc['provider']) . '</td>' .
						'<td>' . $s->entities1($svc['purpose']) . '</td>' .
						'<td>' . $s->entities1($svc['duration']) . '</td>' .
						'<td>' . $cookies . '</td>' .
						'</tr>';
				}
				$out .= '</tbody></table>';
			} else {
				$out .= '<p class="' . $p . '-policy-muted">' .
					$s->entities1($this->_('No specific services are declared for this category.')) . '</p>';
			}
			$out .= '</div>';
		}

		if($managePrefs) {
			$out .= '<h3>' . $s->entities1($this->_('Managing your consent')) . '</h3>';
			$out .= '<p>' . $s->entities1($this->_('You can review or change your choices at any time:')) . ' ' .
				$this->renderTrigger($this->_('Cookie settings')) . '</p>';
		}

		$out .= '</div>';
		return $out;
	}

	/* ==================================================================
	 * Consent logging
	 * ================================================================ */

	/**
	 * URL hook handler: store an anonymized consent record.
	 */
	public function hookLogConsent(HookEvent $event) {
		$input = $this->wire()->input;
		if($_SERVER['REQUEST_METHOD'] !== 'POST') return 'Cookie consent log endpoint';
		if(!$this->enable_logging) return 'disabled';

		$raw = file_get_contents('php://input');
		if(!$raw || strlen($raw) > 2048) return 'invalid';
		$data = json_decode($raw, true);
		if(!is_array($data) || !isset($data['v']) || !isset($data['g']) || !is_array($data['g'])) return 'invalid';

		$granted = [];
		foreach($data['g'] as $key => $value) {
			$key = $this->wire()->sanitizer->fieldName($key);
			if($key) $granted[$key] = (bool) $value;
		}

		$ip = $this->wire()->session->getIP();
		$ipHash = hash('sha256', $ip . (string) $this->log_salt);
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : '';

		try {
			$db = $this->wire()->database;
			$query = $db->prepare('INSERT INTO ' . self::LOG_TABLE . ' (created, version, consent, ip_hash, ua) VALUES (NOW(), :v, :c, :ip, :ua)');
			$query->execute([
				':v' => (int) $data['v'],
				':c' => json_encode($granted, JSON_UNESCAPED_UNICODE),
				':ip' => $ipHash,
				':ua' => $ua,
			]);
			// occasionally purge records older than retention period
			$retention = (int) $this->log_retention_days;
			if($retention > 0 && mt_rand(1, 100) === 1) {
				$db->exec('DELETE FROM ' . self::LOG_TABLE . ' WHERE created < DATE_SUB(NOW(), INTERVAL ' . $retention . ' DAY)');
			}
		} catch(\Exception $e) {
			$this->wire()->log->save('cookie', 'Consent log error: ' . $e->getMessage());
		}
		return 'ok';
	}

	/* ==================================================================
	 * Install / uninstall
	 * ================================================================ */

	public function ___install() {
		$db = $this->wire()->database;
		$engine = $this->wire()->config->dbEngine ?: 'InnoDB';
		$charset = $this->wire()->config->dbCharset ?: 'utf8mb4';
		$db->exec("
			CREATE TABLE IF NOT EXISTS " . self::LOG_TABLE . " (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				created DATETIME NOT NULL,
				version INT UNSIGNED NOT NULL DEFAULT 1,
				consent VARCHAR(1024) NOT NULL DEFAULT '',
				ip_hash CHAR(64) NOT NULL DEFAULT '',
				ua VARCHAR(255) NOT NULL DEFAULT '',
				PRIMARY KEY (id),
				KEY created (created)
			) ENGINE={$engine} DEFAULT CHARSET={$charset}
		");
		// unique salt for IP anonymization
		$configData = $this->wire()->modules->getConfig($this);
		if(empty($configData['log_salt'])) {
			$configData['log_salt'] = bin2hex(random_bytes(16));
			$this->wire()->modules->saveConfig($this, $configData);
		}
	}

	public function ___uninstall() {
		try {
			$this->wire()->database->exec('DROP TABLE IF EXISTS ' . self::LOG_TABLE);
		} catch(\Exception $e) {
			// table may not exist
		}
	}
}
