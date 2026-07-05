<?php namespace ProcessWire;

/**
 * Configuration for the Cookie module.
 *
 * Texts, categories, behavior and integrations live here (module config screen).
 * Visual design settings (design_* / icon_*) are managed by the interactive
 * widget builder (ProcessCookie, Setup > Cookie) and are intentionally not
 * rendered as fields here — ProcessWire preserves them on config save.
 */
class CookieConfig extends ModuleConfig {

	public function getDefaults() {
		return [
			// status & behavior
			'is_active' => 1,
			'consent_first' => 1,
			'consent_model' => 'optin',
			'version' => 1,
			'consent_expire_days' => 180,
			'cookie_name' => 'pwcm_consent',
			'css_prefix' => 'pwcm',
			'respect_gpc' => 1,
			'respect_dnt' => 0,
			// geo mode
			'geo_mode' => 0,
			'geo_header' => 'CF-IPCountry',
			'geo_default_model' => 'optin',
			// EU/EEA + UK + Switzerland (GDPR/ePrivacy, UK GDPR/PECR, nFADP) + Brazil (LGPD)
			'geo_optin_countries' => 'AT BE BG HR CY CZ DK EE FI FR DE GR HU IE IT LV LT LU MT NL PL PT RO SK SI ES SE IS LI NO GB CH BR',
			// California + other US state privacy laws
			'geo_optout_countries' => 'US',
			'detect_bots' => 1,
			'message_timeout' => 1500,
			'hide_when_only_necessary' => 1,
			'auto_exclude_legal' => 1,
			'excluded_pages' => [],
			'render_manually' => 0,
			'output_js' => 'file',
			'output_css' => 'inline',
			'body_classes' => 1,
			'observe_dom' => 1,
			'reload_on_revoke' => 1,
			'custom_function' => '',
			'custom_css' => '',

			// integrations
			'consent_mode' => 0,
			'consent_mode_map' => '',
			'enable_logging' => 0,
			'log_retention_days' => 365,
			'log_salt' => '',
			'embed_map' => "youtube.com=external_media\nyoutube-nocookie.com=external_media\nyoutu.be=external_media\nvimeo.com=external_media\nplayer.vimeo.com=external_media\ngoogle.com/maps=external_media\nmaps.google.com=external_media\nopenstreetmap.org=external_media",
			'tracker_map' => "# statistics\ngoogletagmanager.com=statistics\ngoogle-analytics.com=statistics\nmc.yandex.ru=statistics\ncdn.matomo.cloud=statistics\nstatic.hotjar.com=statistics\nclarity.ms=statistics\nplausible.io/js=statistics\n# marketing\nconnect.facebook.net=marketing\ngoogleadservices.com=marketing\ngooglesyndication.com=marketing\ndoubleclick.net=marketing\nanalytics.tiktok.com=marketing\nstatic.ads-twitter.com=marketing\nsnap.licdn.com=marketing\nads.vk.com=marketing",

			// categories
			'cat_functional' => 1,
			'cat_statistics' => 1,
			'cat_marketing' => 0,
			'cat_external_media' => 1,
			'custom_categories' => '',
			'services_json' => '',

			'label_necessary' => $this->_('Necessary'),
			'desc_necessary' => $this->_('Required for basic site functionality such as security and session handling. Always active.'),
			'label_functional' => $this->_('Functional'),
			'desc_functional' => $this->_('Enable enhanced functionality such as remembering your preferences.'),
			'label_statistics' => $this->_('Statistics'),
			'desc_statistics' => $this->_('Help us understand how visitors interact with the website.'),
			'label_marketing' => $this->_('Marketing'),
			'desc_marketing' => $this->_('Used to deliver relevant advertising and measure its effectiveness.'),
			'label_external_media' => $this->_('External media'),
			'desc_external_media' => $this->_('Content from video platforms and map services is blocked until you consent.'),

			// texts
			'txt_banner_title' => $this->_('We value your privacy'),
			'txt_banner_text' => $this->_('We use cookies to enhance your browsing experience and analyze our traffic. You can accept all cookies, keep only the necessary ones, or fine-tune your preferences.'),
			'txt_btn_accept_all' => $this->_('Accept all'),
			'txt_btn_reject' => $this->_('Only necessary'),
			'txt_btn_prefs' => $this->_('Preferences'),
			'txt_prefs_title' => $this->_('Privacy preferences'),
			'txt_prefs_text' => $this->_('Choose which categories of cookies you allow. You can change your decision at any time.'),
			'txt_btn_save' => $this->_('Save preferences'),
			'txt_close' => $this->_('Close'),
			'txt_msg_saved' => $this->_('Your preferences have been saved.'),
			'txt_details' => $this->_('Details'),
			'txt_ph_message' => $this->_('This content is provided by an external service. It loads only after you allow the “{category}” category.'),
			'txt_ph_load' => $this->_('Load once'),
			'txt_ph_always' => $this->_('Always allow'),
			'txt_icon_aria' => $this->_('Cookie settings'),
			'txt_privacy' => $this->_('Privacy policy'),
			'txt_imprint' => $this->_('Imprint'),
			'link_privacy' => '',
			'link_imprint' => '',
			'link_privacy_page' => 0,
			'link_imprint_page' => 0,
			'policy_intro' => $this->_('This Cookie Policy explains what cookies are, which cookies this website uses, why we use them, and how you can control them. It should be read together with our Privacy Policy.'),

			// design (managed via ProcessCookie builder)
			'design_layout' => 'box',
			'design_position' => 'bottom-right',
			'design_overlay' => 0,
			'design_color_bg' => '#ffffff',
			'design_color_text' => '#1f2937',
			'design_color_primary' => '#4f46e5',
			'design_color_primary_text' => '#ffffff',
			'design_color_secondary' => '#eef0f4',
			'design_color_secondary_text' => '#111827',
			'design_color_link' => '#4f46e5',
			'design_color_accent' => '#4f46e5',
			'design_font_family' => 'inherit',
			'design_font_custom' => '',
			'design_font_size' => 15,
			'design_font_size_title' => 18,
			'design_font_size_button' => 14,
			'design_radius' => 14,
			'design_shadow' => 'soft',
			'design_max_width' => 420,
			'design_spacing' => 20,
			'icon_show' => 1,
			'icon_transparent' => 0,
			'icon_shadow' => 'soft',
			'icon_type' => 'cookie',
			'icon_position' => 'bottom-right',
			'icon_offset_x' => 20,
			'icon_offset_y' => 20,
			'icon_size' => 52,
			'icon_shape' => 'round',
			'icon_color_bg' => '#4f46e5',
			'icon_color_fg' => '#ffffff',
			// dark theme (managed via ProcessCookie builder)
			'dark_enable' => 0,
			'dark_color_bg' => '#1f2430',
			'dark_color_text' => '#e7eaf0',
			'dark_color_primary' => '#6366f1',
			'dark_color_primary_text' => '#ffffff',
			'dark_color_secondary' => '#343b4c',
			'dark_color_secondary_text' => '#e7eaf0',
			'dark_color_link' => '#93a5fd',
			'dark_color_accent' => '#6366f1',
			// video preview posters in placeholders
			'ph_video_posters' => 1,
		];
	}

	public function getInputfields() {
		$inputfields = parent::getInputfields();
		$modules = $this->wire()->modules;

		$builderUrl = $this->wire()->config->urls->admin . 'setup/cookie/';
		$note = $modules->get('InputfieldMarkup');
		$note->label = $this->_('Widget design');
		$note->icon = 'paint-brush';
		$note->value = '<p>' . sprintf(
			$this->_('Colors, fonts, layout, icon and positioning are configured in the interactive %s.'),
			"<a href='{$builderUrl}'><strong>" . $this->_('widget builder (Setup > Cookie)') . '</strong></a>'
		) . '</p>';
		$inputfields->add($note);

		/* ---------- Status ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Status & consent');
		$fs->icon = 'toggle-on';

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'is_active';
		$f->label = $this->_('Cookie banner active');
		$f->description = $this->_('Disable to turn off all frontend output without losing your settings.');
		$f->columnWidth = 25;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'consent_first';
		$f->label = $this->_('Consent-first auto-blocking');
		$f->description = $this->_('Neutralize known trackers and embeds (GTM, GA, Metrika, Facebook, YouTube, …) in the rendered page until consent — even when they are not marked with data-consent. Opt out per element with data-consent-ignore.');
		$f->columnWidth = 25;
		$fs->add($f);

		$f = $modules->get('InputfieldInteger');
		$f->name = 'version';
		$f->label = $this->_('Consent version');
		$f->description = $this->_('Increase to re-ask all visitors for consent (e.g. after adding new services).');
		$f->min = 1;
		$f->columnWidth = 25;
		$fs->add($f);

		$f = $modules->get('InputfieldInteger');
		$f->name = 'consent_expire_days';
		$f->label = $this->_('Consent lifetime (days)');
		$f->description = $this->_('After this period visitors are asked again. GDPR guidance: 90–180 days.');
		$f->min = 1;
		$f->columnWidth = 25;
		$fs->add($f);

		$f = $modules->get('InputfieldRadios');
		$f->name = 'consent_model';
		$f->label = $this->_('Consent model');
		$f->description = $this->_('Opt-in: nothing optional runs before explicit consent — GDPR/ePrivacy (EU), UK GDPR/PECR, LGPD (Brazil), Quebec Law 25, POPIA, KVKK. Opt-out: optional categories run by default, visitors can refuse — CCPA/CPRA and other US state laws.');
		$f->addOptions([
			'optin' => $this->_('Opt-in — prior consent required (GDPR-style)'),
			'optout' => $this->_('Opt-out — allowed until refused (CCPA-style)'),
		]);
		$f->optionColumns = 1;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'respect_gpc';
		$f->label = $this->_('Respect Global Privacy Control (GPC)');
		$f->description = $this->_('Browsers may send a GPC signal, a legally binding opt-out under CCPA/CPRA and Colorado CPA. Opt-in model: treated like Do Not Track. Opt-out model: the marketing category is refused automatically.');
		$f->columnWidth = 50;
		$fs->add($f);

		$inputfields->add($fs);

		/* ---------- Geo mode ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Geo mode (regional consent model)');
		$fs->icon = 'globe';
		$fs->collapsed = Inputfield::collapsedYes;

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'geo_mode';
		$f->label = $this->_('Choose the consent model by visitor country');
		$f->description = $this->_('Overrides the fixed “Consent model” above: opt-in for GDPR regions, opt-out for US-style regions, and a default for the rest. Detected from the geo header below.');
		$f->notes = $this->_('With full-page caching (ProCache) the emitted config is shared between visitors — vary the cache by country or exclude the Cookie config script, otherwise everyone gets the first visitor’s region.');
		$fs->add($f);

		$f = $modules->get('InputfieldText');
		$f->name = 'geo_header';
		$f->label = $this->_('Country header');
		$f->description = $this->_('HTTP header carrying the ISO country code. Cloudflare and many CDNs set CF-IPCountry. You can also set $config->geoCountry or hook Cookie::detectCountry for a GeoIP library.');
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldSelect');
		$f->name = 'geo_default_model';
		$f->label = $this->_('Default for other countries');
		$f->addOptions([
			'optin' => $this->_('Opt-in (safest)'),
			'optout' => $this->_('Opt-out'),
			'none' => $this->_('No banner'),
		]);
		$f->columnWidth = 33;
		$fs->add($f);

		$note = $modules->get('InputfieldMarkup');
		$note->label = $this->_('Detected now');
		$note->columnWidth = 33;
		$detected = $this->wire()->modules->get('Cookie')->detectCountry();
		$note->value = '<p style="padding-top:6px"><strong>' .
			($detected ? $this->wire()->sanitizer->entities1($detected) : $this->_('(unknown)')) .
			'</strong></p>';
		$fs->add($note);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'geo_optin_countries';
		$f->label = $this->_('Opt-in countries');
		$f->description = $this->_('ISO 3166 two-letter codes, separated by spaces or commas. Visitors from these countries get the GDPR-style opt-in model.');
		$f->rows = 2;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'geo_optout_countries';
		$f->label = $this->_('Opt-out countries');
		$f->description = $this->_('These get the CCPA-style opt-out model. Takes priority over the opt-in list on overlap.');
		$f->rows = 2;
		$f->columnWidth = 50;
		$fs->add($f);

		$inputfields->add($fs);

		/* ---------- Categories ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Categories & services');
		$fs->icon = 'tags';

		foreach([
			'functional' => $this->_('Functional'),
			'statistics' => $this->_('Statistics'),
			'marketing' => $this->_('Marketing'),
			'external_media' => $this->_('External media'),
		] as $key => $label) {
			$f = $modules->get('InputfieldCheckbox');
			$f->name = "cat_{$key}";
			$f->label = sprintf($this->_('Enable category “%s”'), $label);
			$f->columnWidth = 25;
			$fs->add($f);
		}

		foreach(['necessary', 'functional', 'statistics', 'marketing', 'external_media'] as $key) {
			$f = $modules->get('InputfieldText');
			$f->name = "label_{$key}";
			$f->label = sprintf($this->_('Label: %s'), $key);
			$f->useLanguages = true;
			$f->columnWidth = 30;
			$f->collapsed = Inputfield::collapsedNever;
			$fs->add($f);

			$f = $modules->get('InputfieldTextarea');
			$f->name = "desc_{$key}";
			$f->label = sprintf($this->_('Description: %s'), $key);
			$f->useLanguages = true;
			$f->rows = 2;
			$f->columnWidth = 70;
			$fs->add($f);
		}

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'custom_categories';
		$f->label = $this->_('Custom categories');
		$f->description = $this->_('One per line: `key=Label|Optional description`. Example: `chat=Live chat|Cookies of the support chat widget`');
		$f->rows = 3;
		$f->collapsed = Inputfield::collapsedBlank;
		$fs->add($f);

		// known-services picker: fills services_json from a curated database
		$catalog = $this->wire()->modules->get('Cookie')->getServiceCatalog();
		if(count($catalog)) {
			$options = '';
			$groups = [];
			foreach($catalog as $slug => $svc) {
				$g = isset($svc['_group']) ? $svc['_group'] : $this->_('Other');
				$groups[$g][$slug] = $svc['name'];
			}
			foreach($groups as $group => $items) {
				$options .= "<optgroup label='" . $this->wire()->sanitizer->entities1($group) . "'>";
				foreach($items as $slug => $name) {
					$options .= "<option value='" . $this->wire()->sanitizer->entities1($slug) . "'>" . $this->wire()->sanitizer->entities1($name) . '</option>';
				}
				$options .= '</optgroup>';
			}
			// strip the _group key from the data payload
			$payload = [];
			foreach($catalog as $slug => $svc) {
				unset($svc['_group']);
				$payload[$slug] = $svc;
			}
			$json = $this->wire()->sanitizer->entities1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

			$m = $modules->get('InputfieldMarkup');
			$m->name = 'service_picker';
			$m->label = $this->_('Add a known service');
			$m->description = $this->_('Pick a service to append it to the list below, pre-filled with provider, purpose, duration and cookie names. Edit afterwards as needed.');
			$m->value =
				"<div class='pwcm-svc-picker' data-catalog=\"{$json}\">" .
				"<select class='pwcm-svc-select uk-select' style='max-width:280px;display:inline-block'>{$options}</select> " .
				"<button type='button' class='ui-button ui-state-default pwcm-svc-add'><span class='ui-button-text'><i class='fa fa-plus'></i> " . $this->_('Add service') . '</span></button>' .
				"<span class='pwcm-svc-note' style='margin-left:10px'></span>" .
				'</div>' . $this->servicePickerScript();
			$fs->add($m);
		}

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'services_json';
		$f->label = $this->_('Services (JSON)');
		$f->description = $this->_('List of services shown in the “Details” section of each category. Cookie names listed here are deleted when consent is revoked. Use the picker above or edit the JSON directly.');
		$f->notes = $this->_('Format: [{"name":"Google Analytics","category":"statistics","provider":"Google LLC","purpose":"Traffic measurement","duration":"2 years","cookies":["_ga","_gid"]}]');
		$f->rows = 8;
		$f->collapsed = Inputfield::collapsedNo;
		$saved = $modules->getConfig('Cookie');
		$raw = isset($saved['services_json']) ? trim((string) $saved['services_json']) : '';
		if($raw && json_decode($raw, true) === null) $f->error($this->_('services_json contains invalid JSON.'));
		$fs->add($f);

		$inputfields->add($fs);

		/* ---------- Texts ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Texts');
		$fs->icon = 'language';
		$fs->collapsed = Inputfield::collapsedYes;

		// language presets: one-click fill of all text fields in a chosen language,
		// optionally targeting a specific ProcessWire language (multi-language sites)
		$presets = $this->getTextPresets();
		if(count($presets)) {
			$s = $this->wire()->sanitizer;
			$languages = $this->wire()->languages;
			$multiLang = $languages && $languages->count() > 1;

			$langSelect = '';
			if($multiLang) {
				$langSelect = "<p><label>" . $this->_('Apply presets to language:') . ' ' .
					"<select class='pwcm-preset-lang'>";
				foreach($languages as $lang) {
					$id = $lang->isDefault() ? '' : (string) $lang->id;
					$title = $s->entities1($lang->get('title|name'));
					$langSelect .= "<option value='{$id}'>{$title}</option>";
				}
				$langSelect .= '</select></label></p>';
			}

			$buttons = '';
			foreach($presets as $code => $preset) {
				$buttons .= "<button type='button' class='ui-button ui-state-default pwcm-preset-btn' data-preset='" .
					$s->entities1($code) . "'><span class='ui-button-text'>" .
					$s->entities1($preset['_label']) . '</span></button> ';
			}
			$buttons .= "<button type='button' class='ui-button ui-state-default pwcm-preset-custom-toggle'>" .
				"<span class='ui-button-text'>" . $this->_('Custom…') . '</span></button>';

			$customBox = "<div class='pwcm-preset-custom' hidden style='margin-top:10px'>" .
				"<textarea class='pwcm-preset-custom-json' rows='5' style='width:100%;max-width:640px;font-family:monospace;font-size:12px' placeholder='" .
				$s->entities1('{"txt_banner_title": "...", "txt_banner_text": "...", ...}') . "'></textarea><br>" .
				"<button type='button' class='ui-button ui-state-default pwcm-preset-custom-apply' style='margin-top:6px'>" .
				"<span class='ui-button-text'>" . $this->_('Apply custom text') . '</span></button>' .
				"<p class='description'>" . $this->_('Paste a JSON object using the same field keys as the presets — handy for a language not listed above, or for house-style wording.') . '</p>' .
				'</div>';

			$json = $s->entities1(json_encode($presets, JSON_UNESCAPED_UNICODE));
			$m = $modules->get('InputfieldMarkup');
			$m->name = 'text_presets';
			$m->label = $this->_('Language presets');
			$desc = $this->_('Fill every text field with a ready-made translation (edit afterwards as needed).');
			if($multiLang) {
				$desc .= ' ' . $this->_('Multi-language site detected: pick the target language above before clicking a preset. For countries with several official languages (e.g. Switzerland: German/French/Italian, or Belgium: Dutch/French/German), apply a different preset to each language in turn — visitors then see the banner in ProcessWire’s active language automatically.');
			}
			$m->description = $desc;
			$m->value = "<div class='pwcm-presets' data-presets=\"{$json}\">{$langSelect}{$buttons}{$customBox}</div>" . $this->presetScript();
			$fs->add($m);
		}

		$texts = [
			'txt_banner_title' => [$this->_('Banner title'), 'text', 50],
			'txt_prefs_title' => [$this->_('Preferences window title'), 'text', 50],
			'txt_banner_text' => [$this->_('Banner text'), 'textarea', 50],
			'txt_prefs_text' => [$this->_('Preferences window text'), 'textarea', 50],
			'txt_btn_accept_all' => [$this->_('Button: accept all'), 'text', 25],
			'txt_btn_reject' => [$this->_('Button: only necessary'), 'text', 25],
			'txt_btn_prefs' => [$this->_('Button: preferences'), 'text', 25],
			'txt_btn_save' => [$this->_('Button: save'), 'text', 25],
			'txt_msg_saved' => [$this->_('Confirmation message'), 'text', 34],
			'txt_details' => [$this->_('Label: details'), 'text', 33],
			'txt_close' => [$this->_('Label: close'), 'text', 33],
			'txt_ph_message' => [$this->_('Placeholder message ({category} = category label)'), 'textarea', 50],
			'txt_ph_load' => [$this->_('Placeholder button: load once'), 'text', 25],
			'txt_ph_always' => [$this->_('Placeholder button: always allow'), 'text', 25],
			'txt_icon_aria' => [$this->_('Floating icon label (accessibility)'), 'text', 34],
			'txt_privacy' => [$this->_('Privacy link label'), 'text', 33],
			'txt_imprint' => [$this->_('Imprint link label'), 'text', 33],
			'policy_intro' => [$this->_('Cookie policy intro (Setup > Cookie > Policy)'), 'textarea', 100],
		];
		foreach($texts as $name => $def) {
			$f = $modules->get($def[1] === 'textarea' ? 'InputfieldTextarea' : 'InputfieldText');
			if($def[1] === 'textarea') $f->rows = 3;
			$f->name = $name;
			$f->label = $def[0];
			$f->useLanguages = true;
			$f->columnWidth = $def[2];
			$fs->add($f);
		}

		// Privacy / Imprint targets: pick a page (follows that page's URL in the
		// visitor's active language automatically) or fall back to a plain URL
		foreach([
			['link_privacy_page', 'link_privacy', $this->_('Privacy policy page'), $this->_('Privacy policy URL (used if no page is selected above)')],
			['link_imprint_page', 'link_imprint', $this->_('Imprint page'), $this->_('Imprint URL (used if no page is selected above)')],
		] as $pair) {
			list($pageKey, $urlKey, $pageLabel, $urlLabel) = $pair;

			$f = $modules->get('InputfieldPageListSelect');
			$f->name = $pageKey;
			$f->label = $pageLabel;
			$f->columnWidth = 50;
			$fs->add($f);

			$f = $modules->get('InputfieldText');
			$f->name = $urlKey;
			$f->label = $urlLabel;
			$f->columnWidth = 50;
			$fs->add($f);
		}

		$inputfields->add($fs);

		/* ---------- Behavior ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Behavior');
		$fs->icon = 'sliders';
		$fs->collapsed = Inputfield::collapsedYes;

		$f = $modules->get('InputfieldInteger');
		$f->name = 'message_timeout';
		$f->label = $this->_('Confirmation message duration (ms)');
		$f->description = $this->_('0 disables the confirmation message entirely.');
		$f->min = 0;
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'hide_when_only_necessary';
		$f->label = $this->_('Skip banner when only necessary categories are enabled');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'auto_exclude_legal';
		$f->label = $this->_('Do not auto-show banner on privacy/imprint pages');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'respect_dnt';
		$f->label = $this->_('Respect “Do Not Track”');
		$f->description = $this->_('Visitors with DNT get necessary-only consent without seeing the banner.');
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'detect_bots';
		$f->label = $this->_('Hide banner for bots/crawlers');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'body_classes';
		$f->label = $this->_('Add body classes (consent-<category>)');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'observe_dom';
		$f->label = $this->_('Watch for dynamically added content (AJAX)');
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'reload_on_revoke';
		$f->label = $this->_('Reload page when consent is revoked');
		$f->description = $this->_('Also deletes first-party cookies listed in services.');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldText');
		$f->name = 'custom_function';
		$f->label = $this->_('Custom JS callback on save');
		$f->description = $this->_('Global function name, called as fn(consent) after saving.');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'ph_video_posters';
		$f->label = $this->_('Video preview thumbnails in placeholders');
		$f->description = $this->_('Show the YouTube/Vimeo poster (with a play glyph) inside the consent placeholder. The image is cached on your server, so the visitor never contacts the video host before consent.');
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldPageListSelectMultiple');
		$f->name = 'excluded_pages';
		$f->label = $this->_('Pages without auto-shown banner');
		$f->description = $this->_('The banner will not open automatically on these pages (widget stays available via icon/trigger).');
		$fs->add($f);

		$inputfields->add($fs);

		/* ---------- Integrations ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Integrations');
		$fs->icon = 'plug';
		$fs->collapsed = Inputfield::collapsedYes;

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'consent_mode';
		$f->label = $this->_('Google Consent Mode v2');
		$f->description = $this->_('Emits gtag consent defaults (all denied) before your GTM/gtag snippet and sends updates when consent changes.');
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'consent_mode_map';
		$f->label = $this->_('Consent Mode mapping override (JSON)');
		$f->notes = $this->_('Default: {"statistics":["analytics_storage"],"marketing":["ad_storage","ad_user_data","ad_personalization"],"functional":["functionality_storage","personalization_storage"]}');
		$f->rows = 3;
		$f->columnWidth = 50;
		$f->collapsed = Inputfield::collapsedBlank;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'enable_logging';
		$f->label = $this->_('Consent logging');
		$f->description = $this->_('Store anonymized consent records (hashed IP) for GDPR documentation. View & export in Setup > Cookie > Consent log.');
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldInteger');
		$f->name = 'log_retention_days';
		$f->label = $this->_('Log retention (days)');
		$f->min = 0;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'tracker_map';
		$f->label = $this->_('Known trackers (consent-first auto-blocking)');
		$f->description = $this->_('One per line: `domain=category`, lines starting with # are comments. Scripts/iframes from these domains are neutralized server-side until consent when consent-first mode is on. Edit or remove lines freely.');
		$f->rows = 8;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'embed_map';
		$f->label = $this->_('Embed domain map');
		$f->description = $this->_('Same format. Applies always (also via TextformatterCookie inside formatted fields) and overrides the tracker list on conflicts.');
		$f->rows = 8;
		$f->columnWidth = 50;
		$fs->add($f);

		$inputfields->add($fs);

		/* ---------- Advanced ---------- */
		$fs = $modules->get('InputfieldFieldset');
		$fs->label = $this->_('Advanced');
		$fs->icon = 'cogs';
		$fs->collapsed = Inputfield::collapsedYes;

		$f = $modules->get('InputfieldText');
		$f->name = 'cookie_name';
		$f->label = $this->_('Consent cookie name');
		$f->columnWidth = 34;
		$fs->add($f);

		$f = $modules->get('InputfieldText');
		$f->name = 'css_prefix';
		$f->label = $this->_('CSS class prefix');
		$f->description = $this->_('Change if ad blockers (uBlock/Brave filter lists) hide the widget.');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldCheckbox');
		$f->name = 'render_manually';
		$f->label = $this->_('Manual render mode');
		$f->description = $this->_('Disable auto-injection; call renderHead() and renderBanner() in your templates.');
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $modules->get('InputfieldRadios');
		$f->name = 'output_js';
		$f->label = $this->_('JavaScript output');
		$f->addOptions(['file' => $this->_('External file (cacheable)'), 'inline' => $this->_('Inline (single request)')]);
		$f->optionColumns = 1;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldRadios');
		$f->name = 'output_css';
		$f->label = $this->_('CSS output');
		$f->addOptions(['inline' => $this->_('Inline (default)'), 'file' => $this->_('External file'), 'none' => $this->_('None (bring your own styles)')]);
		$f->optionColumns = 1;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $modules->get('InputfieldTextarea');
		$f->name = 'custom_css';
		$f->label = $this->_('Custom CSS');
		$f->rows = 5;
		$f->collapsed = Inputfield::collapsedBlank;
		$fs->add($f);

		$inputfields->add($fs);

		return $inputfields;
	}

	/**
	 * Ready-made text translations for one-click filling of all text fields.
	 * Keys match the config field names; `_label` is the button caption.
	 * @return array
	 */
	protected function getTextPresets() {
		return [
			'en' => [
				// mirrors the module's built-in English defaults exactly, so you can
				// always get back to a known-good state after experimenting
				'_label' => 'English',
				'label_necessary' => 'Necessary', 'desc_necessary' => 'Required for basic site functionality such as security and session handling. Always active.',
				'label_functional' => 'Functional', 'desc_functional' => 'Enable enhanced functionality such as remembering your preferences.',
				'label_statistics' => 'Statistics', 'desc_statistics' => 'Help us understand how visitors interact with the website.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Used to deliver relevant advertising and measure its effectiveness.',
				'label_external_media' => 'External media', 'desc_external_media' => 'Content from video platforms and map services is blocked until you consent.',
				'txt_banner_title' => 'We value your privacy',
				'txt_banner_text' => 'We use cookies to enhance your browsing experience and analyze our traffic. You can accept all cookies, keep only the necessary ones, or fine-tune your preferences.',
				'txt_btn_accept_all' => 'Accept all', 'txt_btn_reject' => 'Only necessary', 'txt_btn_prefs' => 'Preferences',
				'txt_prefs_title' => 'Privacy preferences', 'txt_prefs_text' => 'Choose which categories of cookies you allow. You can change your decision at any time.',
				'txt_btn_save' => 'Save preferences', 'txt_close' => 'Close', 'txt_msg_saved' => 'Your preferences have been saved.',
				'txt_details' => 'Details',
				'txt_ph_message' => 'This content is provided by an external service. It loads only after you allow the “{category}” category.',
				'txt_ph_load' => 'Load once', 'txt_ph_always' => 'Always allow',
				'txt_icon_aria' => 'Cookie settings', 'txt_privacy' => 'Privacy policy', 'txt_imprint' => 'Imprint',
			],
			'de' => [
				'_label' => 'Deutsch',
				'label_necessary' => 'Notwendig', 'desc_necessary' => 'Erforderlich für grundlegende Funktionen wie Sicherheit und Sitzungsverwaltung. Immer aktiv.',
				'label_functional' => 'Funktional', 'desc_functional' => 'Ermöglichen erweiterte Funktionen wie das Speichern Ihrer Einstellungen.',
				'label_statistics' => 'Statistik', 'desc_statistics' => 'Helfen uns zu verstehen, wie Besucher die Website nutzen.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Werden verwendet, um relevante Werbung anzuzeigen und deren Wirkung zu messen.',
				'label_external_media' => 'Externe Medien', 'desc_external_media' => 'Inhalte von Videoplattformen und Kartendiensten werden bis zu Ihrer Einwilligung blockiert.',
				'txt_banner_title' => 'Wir schätzen Ihre Privatsphäre',
				'txt_banner_text' => 'Wir verwenden Cookies, um Ihr Surferlebnis zu verbessern und unseren Traffic zu analysieren. Sie können alle Cookies akzeptieren, nur die notwendigen behalten oder Ihre Auswahl anpassen.',
				'txt_btn_accept_all' => 'Alle akzeptieren', 'txt_btn_reject' => 'Nur notwendige', 'txt_btn_prefs' => 'Einstellungen',
				'txt_prefs_title' => 'Datenschutzeinstellungen', 'txt_prefs_text' => 'Wählen Sie, welche Cookie-Kategorien Sie zulassen. Sie können Ihre Entscheidung jederzeit ändern.',
				'txt_btn_save' => 'Einstellungen speichern', 'txt_close' => 'Schließen', 'txt_msg_saved' => 'Ihre Einstellungen wurden gespeichert.',
				'txt_details' => 'Details',
				'txt_ph_message' => 'Dieser Inhalt wird von einem externen Dienst bereitgestellt. Er wird erst geladen, wenn Sie die Kategorie „{category}“ zulassen.',
				'txt_ph_load' => 'Einmal laden', 'txt_ph_always' => 'Immer zulassen',
				'txt_icon_aria' => 'Cookie-Einstellungen', 'txt_privacy' => 'Datenschutzerklärung', 'txt_imprint' => 'Impressum',
			],
			'fr' => [
				'_label' => 'Français',
				'label_necessary' => 'Nécessaires', 'desc_necessary' => 'Requis pour les fonctions de base comme la sécurité et la gestion de session. Toujours actifs.',
				'label_functional' => 'Fonctionnels', 'desc_functional' => 'Activent des fonctionnalités avancées comme la mémorisation de vos préférences.',
				'label_statistics' => 'Statistiques', 'desc_statistics' => 'Nous aident à comprendre comment les visiteurs utilisent le site.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Utilisés pour diffuser des publicités pertinentes et mesurer leur efficacité.',
				'label_external_media' => 'Médias externes', 'desc_external_media' => 'Le contenu des plateformes vidéo et des services de cartographie est bloqué jusqu’à votre consentement.',
				'txt_banner_title' => 'Nous respectons votre vie privée',
				'txt_banner_text' => 'Nous utilisons des cookies pour améliorer votre navigation et analyser notre trafic. Vous pouvez accepter tous les cookies, ne garder que les nécessaires ou ajuster vos préférences.',
				'txt_btn_accept_all' => 'Tout accepter', 'txt_btn_reject' => 'Seulement nécessaires', 'txt_btn_prefs' => 'Préférences',
				'txt_prefs_title' => 'Préférences de confidentialité', 'txt_prefs_text' => 'Choisissez les catégories de cookies que vous autorisez. Vous pouvez changer d’avis à tout moment.',
				'txt_btn_save' => 'Enregistrer les préférences', 'txt_close' => 'Fermer', 'txt_msg_saved' => 'Vos préférences ont été enregistrées.',
				'txt_details' => 'Détails',
				'txt_ph_message' => 'Ce contenu est fourni par un service externe. Il se charge uniquement après que vous ayez autorisé la catégorie « {category} ».',
				'txt_ph_load' => 'Charger une fois', 'txt_ph_always' => 'Toujours autoriser',
				'txt_icon_aria' => 'Paramètres des cookies', 'txt_privacy' => 'Politique de confidentialité', 'txt_imprint' => 'Mentions légales',
			],
			'es' => [
				'_label' => 'Español',
				'label_necessary' => 'Necesarias', 'desc_necessary' => 'Requeridas para funciones básicas como seguridad y gestión de sesión. Siempre activas.',
				'label_functional' => 'Funcionales', 'desc_functional' => 'Habilitan funciones avanzadas como recordar tus preferencias.',
				'label_statistics' => 'Estadísticas', 'desc_statistics' => 'Nos ayudan a entender cómo los visitantes usan el sitio web.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Se usan para mostrar publicidad relevante y medir su eficacia.',
				'label_external_media' => 'Medios externos', 'desc_external_media' => 'El contenido de plataformas de vídeo y servicios de mapas se bloquea hasta tu consentimiento.',
				'txt_banner_title' => 'Valoramos tu privacidad',
				'txt_banner_text' => 'Usamos cookies para mejorar tu experiencia de navegación y analizar nuestro tráfico. Puedes aceptar todas las cookies, mantener solo las necesarias o ajustar tus preferencias.',
				'txt_btn_accept_all' => 'Aceptar todas', 'txt_btn_reject' => 'Solo necesarias', 'txt_btn_prefs' => 'Preferencias',
				'txt_prefs_title' => 'Preferencias de privacidad', 'txt_prefs_text' => 'Elige qué categorías de cookies permites. Puedes cambiar tu decisión en cualquier momento.',
				'txt_btn_save' => 'Guardar preferencias', 'txt_close' => 'Cerrar', 'txt_msg_saved' => 'Tus preferencias se han guardado.',
				'txt_details' => 'Detalles',
				'txt_ph_message' => 'Este contenido lo proporciona un servicio externo. Se carga solo después de que permitas la categoría «{category}».',
				'txt_ph_load' => 'Cargar una vez', 'txt_ph_always' => 'Permitir siempre',
				'txt_icon_aria' => 'Configuración de cookies', 'txt_privacy' => 'Política de privacidad', 'txt_imprint' => 'Aviso legal',
			],
			'it' => [
				'_label' => 'Italiano',
				'label_necessary' => 'Necessari', 'desc_necessary' => 'Necessari per il funzionamento di base del sito, come sicurezza e gestione delle sessioni. Sempre attivi.',
				'label_functional' => 'Funzionali', 'desc_functional' => 'Abilitano funzionalità avanzate, come ricordare le tue preferenze.',
				'label_statistics' => 'Statistiche', 'desc_statistics' => 'Ci aiutano a capire come i visitatori utilizzano il sito web.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Utilizzati per mostrare pubblicità pertinente e misurarne l’efficacia.',
				'label_external_media' => 'Media esterni', 'desc_external_media' => 'I contenuti di piattaforme video e servizi di mappe vengono bloccati fino al tuo consenso.',
				'txt_banner_title' => 'Rispettiamo la tua privacy',
				'txt_banner_text' => 'Utilizziamo i cookie per migliorare la tua esperienza di navigazione e analizzare il nostro traffico. Puoi accettare tutti i cookie, mantenere solo quelli necessari o personalizzare le tue preferenze.',
				'txt_btn_accept_all' => 'Accetta tutti', 'txt_btn_reject' => 'Solo necessari', 'txt_btn_prefs' => 'Preferenze',
				'txt_prefs_title' => 'Preferenze sulla privacy', 'txt_prefs_text' => 'Scegli quali categorie di cookie consentire. Puoi cambiare la tua decisione in qualsiasi momento.',
				'txt_btn_save' => 'Salva preferenze', 'txt_close' => 'Chiudi', 'txt_msg_saved' => 'Le tue preferenze sono state salvate.',
				'txt_details' => 'Dettagli',
				'txt_ph_message' => 'Questo contenuto è fornito da un servizio esterno. Viene caricato solo dopo aver consentito la categoria «{category}».',
				'txt_ph_load' => 'Carica una volta', 'txt_ph_always' => 'Consenti sempre',
				'txt_icon_aria' => 'Impostazioni cookie', 'txt_privacy' => 'Informativa sulla privacy', 'txt_imprint' => 'Note legali',
			],
			'nl' => [
				'_label' => 'Nederlands',
				'label_necessary' => 'Noodzakelijk', 'desc_necessary' => 'Vereist voor basisfunctionaliteit van de website, zoals beveiliging en sessiebeheer. Altijd actief.',
				'label_functional' => 'Functioneel', 'desc_functional' => 'Maken verbeterde functionaliteit mogelijk, zoals het onthouden van je voorkeuren.',
				'label_statistics' => 'Statistieken', 'desc_statistics' => 'Helpen ons begrijpen hoe bezoekers de website gebruiken.',
				'label_marketing' => 'Marketing', 'desc_marketing' => 'Worden gebruikt om relevante advertenties te tonen en de effectiviteit ervan te meten.',
				'label_external_media' => 'Externe media', 'desc_external_media' => 'Inhoud van videoplatforms en kaartdiensten wordt geblokkeerd totdat u toestemming geeft.',
				'txt_banner_title' => 'Wij hechten waarde aan uw privacy',
				'txt_banner_text' => 'Wij gebruiken cookies om uw surfervaring te verbeteren en ons verkeer te analyseren. U kunt alle cookies accepteren, alleen de noodzakelijke behouden of uw voorkeuren aanpassen.',
				'txt_btn_accept_all' => 'Alles accepteren', 'txt_btn_reject' => 'Alleen noodzakelijke', 'txt_btn_prefs' => 'Voorkeuren',
				'txt_prefs_title' => 'Privacyvoorkeuren', 'txt_prefs_text' => 'Kies welke cookiecategorieën u toestaat. U kunt uw keuze op elk moment wijzigen.',
				'txt_btn_save' => 'Voorkeuren opslaan', 'txt_close' => 'Sluiten', 'txt_msg_saved' => 'Uw voorkeuren zijn opgeslagen.',
				'txt_details' => 'Details',
				'txt_ph_message' => 'Deze inhoud wordt geleverd door een externe dienst. Deze wordt pas geladen nadat u de categorie «{category}» toestaat.',
				'txt_ph_load' => 'Eenmalig laden', 'txt_ph_always' => 'Altijd toestaan',
				'txt_icon_aria' => 'Cookie-instellingen', 'txt_privacy' => 'Privacybeleid', 'txt_imprint' => 'Colofon',
			],
			'pl' => [
				'_label' => 'Polski',
				'label_necessary' => 'Niezbędne', 'desc_necessary' => 'Wymagane do podstawowego działania witryny, takiego jak bezpieczeństwo i obsługa sesji. Zawsze aktywne.',
				'label_functional' => 'Funkcjonalne', 'desc_functional' => 'Umożliwiają rozszerzoną funkcjonalność, np. zapamiętywanie Twoich preferencji.',
				'label_statistics' => 'Statystyczne', 'desc_statistics' => 'Pomagają nam zrozumieć, jak odwiedzający korzystają ze strony.',
				'label_marketing' => 'Marketingowe', 'desc_marketing' => 'Służą do wyświetlania trafnych reklam i mierzenia ich skuteczności.',
				'label_external_media' => 'Media zewnętrzne', 'desc_external_media' => 'Treści z platform wideo i usług mapowych są blokowane do czasu wyrażenia zgody.',
				'txt_banner_title' => 'Cenimy Twoją prywatność',
				'txt_banner_text' => 'Używamy plików cookie, aby poprawić komfort przeglądania i analizować nasz ruch. Możesz zaakceptować wszystkie pliki cookie, zachować tylko niezbędne lub dostosować swoje preferencje.',
				'txt_btn_accept_all' => 'Zaakceptuj wszystkie', 'txt_btn_reject' => 'Tylko niezbędne', 'txt_btn_prefs' => 'Preferencje',
				'txt_prefs_title' => 'Preferencje prywatności', 'txt_prefs_text' => 'Wybierz, na które kategorie plików cookie zezwalasz. Możesz zmienić decyzję w dowolnym momencie.',
				'txt_btn_save' => 'Zapisz preferencje', 'txt_close' => 'Zamknij', 'txt_msg_saved' => 'Twoje preferencje zostały zapisane.',
				'txt_details' => 'Szczegóły',
				'txt_ph_message' => 'Ta zawartość jest dostarczana przez usługę zewnętrzną. Zostanie załadowana dopiero po wyrażeniu zgody na kategorię „{category}”.',
				'txt_ph_load' => 'Załaduj raz', 'txt_ph_always' => 'Zawsze zezwalaj',
				'txt_icon_aria' => 'Ustawienia plików cookie', 'txt_privacy' => 'Polityka prywatności', 'txt_imprint' => 'Nota prawna',
			],
			'ru' => [
				'_label' => 'Русский',
				'label_necessary' => 'Необходимые', 'desc_necessary' => 'Требуются для базовой работы сайта: безопасность и сессии. Всегда активны.',
				'label_functional' => 'Функциональные', 'desc_functional' => 'Включают расширенные возможности, например запоминание ваших настроек.',
				'label_statistics' => 'Статистика', 'desc_statistics' => 'Помогают понять, как посетители пользуются сайтом.',
				'label_marketing' => 'Маркетинг', 'desc_marketing' => 'Используются для показа релевантной рекламы и оценки её эффективности.',
				'label_external_media' => 'Внешние медиа', 'desc_external_media' => 'Контент с видеоплатформ и картографических сервисов блокируется до вашего согласия.',
				'txt_banner_title' => 'Мы ценим вашу конфиденциальность',
				'txt_banner_text' => 'Мы используем файлы cookie, чтобы улучшить работу сайта и проанализировать трафик. Вы можете принять все cookie, оставить только необходимые или настроить выбор.',
				'txt_btn_accept_all' => 'Принять все', 'txt_btn_reject' => 'Только необходимые', 'txt_btn_prefs' => 'Настройки',
				'txt_prefs_title' => 'Настройки конфиденциальности', 'txt_prefs_text' => 'Выберите, какие категории cookie разрешить. Решение можно изменить в любой момент.',
				'txt_btn_save' => 'Сохранить настройки', 'txt_close' => 'Закрыть', 'txt_msg_saved' => 'Ваши настройки сохранены.',
				'txt_details' => 'Подробнее',
				'txt_ph_message' => 'Этот контент предоставляется внешним сервисом. Он загрузится только после того, как вы разрешите категорию «{category}».',
				'txt_ph_load' => 'Загрузить один раз', 'txt_ph_always' => 'Всегда разрешать',
				'txt_icon_aria' => 'Настройки cookie', 'txt_privacy' => 'Политика конфиденциальности', 'txt_imprint' => 'Выходные данные',
			],
		];
	}

	/**
	 * Inline script that wires the preset buttons to fill the config text inputs.
	 * @return string
	 */
	protected function presetScript() {
		return <<<'JS'
<script>
(function(){
	var wrap = document.querySelector('.pwcm-presets');
	if(!wrap || wrap.dataset.bound) return;
	wrap.dataset.bound = '1';
	var presets = JSON.parse(wrap.getAttribute('data-presets'));
	var langSelect = wrap.querySelector('.pwcm-preset-lang');

	// bare field name for the default language, "key__id" for any other
	// installed ProcessWire language — matches core useLanguages naming
	function fieldName(key){
		var langId = langSelect ? langSelect.value : '';
		return langId ? key + '__' + langId : key;
	}

	function note(text, isError){
		var n = wrap.querySelector('.pwcm-preset-note');
		if(!n){ n = document.createElement('div'); n.className = 'pwcm-preset-note'; n.style.marginTop = '8px'; wrap.appendChild(n); }
		n.textContent = text;
		n.style.color = isError ? '#dc2626' : '#059669';
	}

	function applyData(data){
		var n = 0;
		for(var key in data){
			if(key === '_label') continue;
			var name = fieldName(key);
			var input = document.querySelector('#Inputfield_' + name) || document.querySelector('[name="' + name + '"]');
			if(input && 'value' in input){ input.value = data[key]; n++; }
		}
		return n;
	}

	wrap.addEventListener('click', function(e){
		var btn = e.target.closest('.pwcm-preset-btn');
		if(btn){
			e.preventDefault();
			var data = presets[btn.getAttribute('data-preset')];
			if(!data) return;
			var n = applyData(data);
			btn.blur();
			note(n + ' fields filled — remember to Submit.');
			return;
		}

		var toggle = e.target.closest('.pwcm-preset-custom-toggle');
		if(toggle){
			e.preventDefault();
			var box = wrap.querySelector('.pwcm-preset-custom');
			if(box) box.hidden = !box.hidden;
			return;
		}

		var applyBtn = e.target.closest('.pwcm-preset-custom-apply');
		if(applyBtn){
			e.preventDefault();
			var ta = wrap.querySelector('.pwcm-preset-custom-json');
			var custom;
			try { custom = JSON.parse(ta.value); } catch(err) { note('Invalid JSON.', true); return; }
			if(!custom || typeof custom !== 'object'){ note('Invalid JSON.', true); return; }
			var count = applyData(custom);
			note(count + ' fields filled — remember to Submit.');
		}
	});
})();
</script>
JS;
	}

	/**
	 * Inline script wiring the "Add service" picker to append the chosen
	 * catalog entry to the services_json textarea (kept as valid, pretty JSON).
	 * @return string
	 */
	protected function servicePickerScript() {
		return <<<'JS'
<script>
(function(){
	var wrap = document.querySelector('.pwcm-svc-picker');
	if(!wrap || wrap.dataset.bound) return;
	wrap.dataset.bound = '1';
	var catalog = JSON.parse(wrap.getAttribute('data-catalog'));
	var addBtn = wrap.querySelector('.pwcm-svc-add');
	var select = wrap.querySelector('.pwcm-svc-select');
	var note = wrap.querySelector('.pwcm-svc-note');

	function textarea(){ return document.querySelector('[name="services_json"]'); }

	addBtn.addEventListener('click', function(e){
		e.preventDefault();
		var svc = catalog[select.value];
		if(!svc) return;
		var ta = textarea();
		if(!ta) return;
		var list = [];
		var raw = (ta.value || '').trim();
		if(raw){
			try { list = JSON.parse(raw); } catch(err){
				note.textContent = 'Fix the invalid JSON below first.';
				note.style.color = '#dc2626';
				return;
			}
			if(!Array.isArray(list)) list = [];
		}
		// skip if a service with the same name already exists
		var exists = list.some(function(it){ return it && it.name === svc.name; });
		if(exists){
			note.textContent = svc.name + ' is already in the list.';
			note.style.color = '#d97706';
			return;
		}
		list.push(svc);
		ta.value = JSON.stringify(list, null, 2);
		note.textContent = 'Added ' + svc.name + ' — remember to Submit.';
		note.style.color = '#059669';
		// make sure the (possibly collapsed) field is open
		var wrapper = ta.closest('.Inputfield');
		if(wrapper) wrapper.classList.remove('InputfieldStateCollapsed');
	});
})();
</script>
JS;
	}
}
