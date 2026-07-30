<?php namespace ProcessWire;

/**
 * ProcessCookie — interactive widget builder for the Cookie module.
 *
 * Two-pane admin UI (Setup > Cookie): controls on the left, live preview on
 * the right. Every change is applied instantly to the preview (CSS custom
 * properties + data attributes) and persisted to the Cookie module config
 * on save. Also provides the consent log viewer with CSV export.
 */
class ProcessCookie extends Process {

	public static function getModuleInfo() {
		return [
			'title' => 'Cookie: Design Studio',
			'summary' => 'Interactive visual builder for the Cookie consent widget (colors, fonts, layout, icon) + consent log.',
			'version' => '1.1.2',
			'icon' => 'paint-brush',
			'requires' => ['Cookie'],
			'permission' => 'cookie-admin',
			'permissions' => [
				'cookie-admin' => 'Manage Cookie consent widget and view consent log',
			],
			'page' => [
				'name' => 'cookie',
				'parent' => 'setup',
				'title' => 'Cookie',
			],
			'nav' => [
				['url' => '', 'label' => 'Builder', 'icon' => 'paint-brush'],
				['url' => 'policy/', 'label' => 'Policy', 'icon' => 'file-text-o'],
				['url' => 'stats/', 'label' => 'Statistics', 'icon' => 'bar-chart'],
				['url' => 'log/', 'label' => 'Consent log', 'icon' => 'list-alt'],
				['url' => 'transfer/', 'label' => 'Import / Export', 'icon' => 'exchange'],
			],
		];
	}

	/** @var Cookie */
	protected $cookie;

	public function init() {
		parent::init();
		$this->cookie = $this->wire()->modules->get('Cookie');
		$url = $this->wire()->config->urls->siteModules . 'Cookie/';
		$version = self::getModuleInfo()['version'];
		$this->wire()->config->styles->add($url . 'assets/cookie.css?v=' . $version);
		$this->wire()->config->styles->add($url . 'assets/admin/builder.css?v=' . $version);
		$this->wire()->config->scripts->add($url . 'assets/admin/builder.js?v=' . $version);
	}

	/* ==================================================================
	 * Builder
	 * ================================================================ */

	public function ___execute() {
		$this->headline($this->_('Cookie — Design Studio'));
		$this->browserTitle($this->_('Cookie Design Studio'));

		$design = $this->cookie->getDesign();
		$session = $this->wire()->session;

		$bootData = [
			'prefix' => $this->cookie->cssPrefix(),
			'saveUrl' => './save/',
			'csrfName' => $session->CSRF->getTokenName(),
			'csrfValue' => $session->CSRF->getTokenValue(),
			'icons' => [
				'cookie' => $this->cookie->getIconSvg('cookie'),
				'shield' => $this->cookie->getIconSvg('shield'),
				'sliders' => $this->cookie->getIconSvg('sliders'),
				'gear' => $this->cookie->getIconSvg('gear'),
				'banana' => $this->cookie->getIconSvg('banana'),
				'fingerprint' => $this->cookie->getIconSvg('fingerprint'),
				'pw' => $this->cookie->getIconSvg('pw'),
			],
			'design' => $design,
			'defaults' => $this->getDesignDefaults(),
			'i18n' => [
				'saved' => $this->_('Saved'),
				'saving' => $this->_('Saving…'),
				'error' => $this->_('Save failed'),
				'unsaved' => $this->_('Unsaved changes'),
			],
		];

		$out = '<script>window.pwcmBuilder = ' . json_encode($bootData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
		$out .= "<style>{$this->cookie->buildCssVars()}</style>";

		$out .= "<div class='pwb' id='pwb'>";
		$out .= "<div class='pwb-controls'>" . $this->renderControls($design) . '</div>';
		$out .= "<div class='pwb-stage'>" . $this->renderPreview() . '</div>';
		$out .= '</div>';

		return $out;
	}

	protected function getDesignDefaults() {
		$configClass = new CookieConfig();
		$all = $configClass->getDefaults();
		$defaults = [];
		foreach(array_keys($this->getSchema()) as $key) {
			if(array_key_exists($key, $all)) $defaults[$key] = $all[$key];
		}
		return $defaults;
	}

	/**
	 * Inline SVG icon (clean line style, in the spirit of Remix Icon) used
	 * throughout the builder UI instead of the admin theme's icon font, so the
	 * builder chrome stays visually consistent regardless of the active
	 * ProcessWire admin theme.
	 * @param string $name
	 * @param int $size
	 * @return string
	 */
	protected function icon($name, $size = 16) {
		// filled Bootstrap Icons (MIT), 16x16 — used for the builder's group-tab icons
		$filled = [
			'moon' => '<path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/><path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.73 1.73 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.73 1.73 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.73 1.73 0 0 0 1.097-1.097zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>',
			'layout' => '<path d="M0 1a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm9 0a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1zm0 9a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1z"/>',
			'palette' => '<path d="M12.433 10.07C14.133 10.585 16 11.15 16 8a8 8 0 1 0-8 8c1.996 0 1.826-1.504 1.649-3.08-.124-1.101-.252-2.237.351-2.92.465-.527 1.42-.237 2.433.07M8 5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m4.5 3a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3M5 6.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m.5 6.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3"/>',
			'text' => '<path d="M10.5 15a.5.5 0 0 1-.5-.5V2H9v12.5a.5.5 0 0 1-1 0V9H7a4 4 0 1 1 0-8h5.5a.5.5 0 0 1 0 1H11v12.5a.5.5 0 0 1-.5.5"/>',
			'smile' => '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M12.331 9.5a1 1 0 0 1 0 1A5 5 0 0 1 8 13a5 5 0 0 1-4.33-2.5A1 1 0 0 1 4.535 9h6.93a1 1 0 0 1 .866.5M7 6.5c0 .828-.448 0-1 0s-1 .828-1 0S5.448 5 6 5s1 .672 1 1.5m4 0c0 .828-.448 0-1 0s-1 .828-1 0S9.448 5 10 5s1 .672 1 1.5"/>',
			'frame' => '<path d="M12.5 3a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 3a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm.5 3.5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 .5-.5m-.5 2.5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/><path d="M16 2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2zM4 1v14H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zm1 0h9a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5z"/>',
		];
		if(isset($filled[$name])) {
			return "<svg class=\"pwb-icon\" width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 16 16\" fill=\"currentColor\" aria-hidden=\"true\">{$filled[$name]}</svg>";
		}

		// custom line icons, 24x24 — action buttons (save/reset/downloads/etc.)
		$paths = [
			'desktop' => '<rect x="3" y="4.5" width="18" height="12" rx="2"/><path d="M8.5 20h7M12 16.5V20"/>',
			'mobile' => '<rect x="7.5" y="2.5" width="9" height="19" rx="2"/><path d="M11 18.2h2"/>',
			'check' => '<path d="M20 6 9 17l-5-5"/>',
			'refresh' => '<path d="M4 12a8 8 0 0 1 14.1-5.1M20 12a8 8 0 0 1-14.1 5.1"/><path d="M18.5 3.5v4h-4M5.5 20.5v-4h4"/>',
			'arrow-right' => '<path d="M4.5 12h15M13.5 6l6 6-6 6"/>',
			'plus' => '<path d="M12 4.5v15M4.5 12h15"/>',
			'download' => '<path d="M12 3.5v11"/><path d="M7 10l5 5 5-5"/><path d="M5 20.5h14"/>',
			'upload' => '<path d="M12 20.5v-11"/><path d="M7 14l5-5 5 5"/><path d="M5 20.5h14"/>',
			'copy' => '<rect x="8.5" y="8.5" width="11" height="11" rx="2"/><path d="M15.5 8.5V6a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5"/>',
			'list' => '<path d="M4 6.5h.01M4 12h.01M4 17.5h.01"/><path d="M9 6.5h11M9 12h11M9 17.5h11"/>',
			'chart' => '<rect x="4" y="11" width="3.4" height="9" rx="1" fill="currentColor" stroke="none"/><rect x="10.3" y="6.5" width="3.4" height="13.5" rx="1" fill="currentColor" stroke="none"/><rect x="16.6" y="14" width="3.4" height="6" rx="1" fill="currentColor" stroke="none"/>',
			'file-text' => '<path d="M14 3.5H7.5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V8z"/><path d="M14 3.5V8h4.5"/><path d="M9 13h6M9 16.5h6"/>',
			'exchange' => '<path d="M4 7.5h13M17 7.5l-3-3M17 7.5l-3 3"/><path d="M20 16.5H7M7 16.5l3-3M7 16.5l3 3"/>',
		];
		$inner = isset($paths[$name]) ? $paths[$name] : '';
		return "<svg class=\"pwb-icon\" width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.75\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">{$inner}</svg>";
	}

	/* ---------------- controls ---------------- */

	protected function renderControls(array $d) {
		$groups = [];

		/* Layout */
		$html = $this->segmented('design_layout', $this->_('Banner type'), [
			'box' => $this->_('Box'),
			'bar' => $this->_('Bar'),
			'modal' => $this->_('Modal'),
		], $d['design_layout']);
		$html .= "<div class='pwb-row' data-show-layout='box'>";
		$html .= $this->quadrant9('design_position', $this->_('Position'), $d['design_position']);
		$html .= '</div>';
		$html .= "<div class='pwb-row' data-show-layout='bar'>";
		$html .= $this->segmented('design_position_bar', $this->_('Position'), [
			'top' => $this->_('Top'),
			'bottom' => $this->_('Bottom'),
		], in_array($d['design_position'], ['top', 'bottom']) ? $d['design_position'] : 'bottom');
		$html .= '</div>';
		$html .= $this->toggle('design_overlay', $this->_('Dim page behind the banner'), (bool) $d['design_overlay']);
		$groups['layout'] = ['layout', $this->_('Layout'), $html];

		/* Colors */
		$html = "<div class='pwb-colors'>";
		foreach([
			'design_color_bg' => $this->_('Background'),
			'design_color_text' => $this->_('Text'),
			'design_color_primary' => $this->_('Primary button'),
			'design_color_primary_text' => $this->_('Primary text'),
			'design_color_secondary' => $this->_('Secondary button'),
			'design_color_secondary_text' => $this->_('Secondary text'),
			'design_color_link' => $this->_('Links'),
			'design_color_accent' => $this->_('Toggles / accent'),
		] as $key => $label) {
			$html .= $this->color($key, $label, $d[$key]);
		}
		$html .= '</div>';
		$html .= $this->palettes();
		$groups['colors'] = ['palette', $this->_('Colors'), $html];

		/* Typography */
		$html = $this->select('design_font_family', $this->_('Font family'), [
			'inherit' => $this->_('Inherit from site'),
			'system' => $this->_('System UI'),
			'serif' => $this->_('Serif (Georgia)'),
			'mono' => $this->_('Monospace'),
			'custom' => $this->_('Custom…'),
		], $d['design_font_family']);
		$html .= "<div class='pwb-row' data-show-font='custom'>";
		$html .= $this->text('design_font_custom', $this->_('Custom font-family'), $d['design_font_custom'], 'e.g. "Inter", sans-serif');
		$html .= '</div>';
		$html .= $this->range('design_font_size', $this->_('Base size'), $d['design_font_size'], 10, 24, 'px');
		$html .= $this->range('design_font_size_title', $this->_('Title size'), $d['design_font_size_title'], 12, 32, 'px');
		$html .= $this->range('design_font_size_button', $this->_('Button size'), $d['design_font_size_button'], 10, 24, 'px');
		$groups['type'] = ['text', $this->_('Text'), $html];

		/* Icon */
		$html = $this->toggle('icon_show', $this->_('Show icon after consent is given'), (bool) $d['icon_show']);
		$html .= $this->toggle('icon_transparent', $this->_('No background (icon only)'), (bool) $d['icon_transparent']);
		$html .= $this->segmented('icon_shadow', $this->_('Shadow'), [
			'none' => $this->_('None'),
			'soft' => $this->_('Soft'),
			'strong' => $this->_('Strong'),
		], $d['icon_shadow'], "data-show-icon-shadow='1'");
		$html .= $this->iconPicker('icon_type', $this->_('Icon'), $d['icon_type']);
		$html .= $this->segmented('icon_shape', $this->_('Shape'), [
			'round' => $this->_('Round'),
			'rounded' => $this->_('Rounded'),
			'square' => $this->_('Square'),
		], $d['icon_shape']);
		$html .= "<div class='pwb-cols'>";
		$html .= $this->quadrant('icon_position', $this->_('Position'), $d['icon_position']);
		$html .= "<div class='pwb-cols-rest'>";
		$html .= $this->range('icon_size', $this->_('Size'), $d['icon_size'], 36, 80, 'px');
		$html .= $this->range('icon_offset_x', $this->_('Offset X'), $d['icon_offset_x'], 0, 80, 'px');
		$html .= $this->range('icon_offset_y', $this->_('Offset Y'), $d['icon_offset_y'], 0, 80, 'px');
		$html .= '</div></div>';
		$html .= "<div class='pwb-colors'>";
		$html .= $this->color('icon_color_bg', $this->_('Background'), $d['icon_color_bg'], "data-show-icon-bg='1'");
		$html .= $this->color('icon_color_fg', $this->_('Icon color'), $d['icon_color_fg']);
		$html .= '</div>';
		$html .= $this->iconPalettes();
		$groups['icon'] = ['smile', $this->_('Icon'), $html];

		/* Frame */
		$html = $this->range('design_radius', $this->_('Corner radius'), $d['design_radius'], 0, 32, 'px');
		$html .= $this->segmented('design_shadow', $this->_('Shadow'), [
			'none' => $this->_('None'),
			'soft' => $this->_('Soft'),
			'strong' => $this->_('Strong'),
		], $d['design_shadow']);
		$html .= $this->range('design_max_width', $this->_('Max width'), $d['design_max_width'], 300, 640, 'px');
		$html .= $this->range('design_spacing', $this->_('Padding'), $d['design_spacing'], 8, 40, 'px');
		$groups['frame'] = ['frame', $this->_('Frame'), $html];

		/* Dark theme */
		$html = $this->toggle('dark_enable', $this->_('Enable dark theme (prefers-color-scheme: dark)'), (bool) $d['dark_enable']);
		$html .= "<div class='pwb-colors' data-show-dark='1'>";
		foreach([
			'dark_color_bg' => $this->_('Background'),
			'dark_color_text' => $this->_('Text'),
			'dark_color_primary' => $this->_('Primary button'),
			'dark_color_primary_text' => $this->_('Primary text'),
			'dark_color_secondary' => $this->_('Secondary button'),
			'dark_color_secondary_text' => $this->_('Secondary text'),
			'dark_color_link' => $this->_('Links'),
			'dark_color_accent' => $this->_('Toggles / accent'),
		] as $key => $label) {
			$html .= $this->color($key, $label, $d[$key]);
		}
		$html .= '</div>';
		$html .= $this->darkPalettes();
		$groups['dark'] = ['moon', $this->_('Dark'), $html];

		/* assemble: vertical tab nav + one visible group */
		$s = $this->wire()->sanitizer;
		$nav = "<div class='pwb-nav' role='tablist'>";
		$panels = "<div class='pwb-groups'>";
		$first = true;
		foreach($groups as $name => $group) {
			$active = $first ? ' is-active' : '';
			$nav .= "<button type='button' class='pwb-nav-btn{$active}' data-group='{$name}' role='tab'>" .
				$this->icon($group[0], 18) . "<span>{$s->entities1($group[1])}</span></button>";
			$panels .= "<div class='pwb-group{$active}' data-group='{$name}'>{$group[2]}</div>";
			$first = false;
		}
		$nav .= '</div>';
		$panels .= '</div>';

		/* Save bar: status line on top, all actions in a single row below */
		$configUrl = $this->wire()->config->urls->admin . 'module/edit?name=Cookie&collapse_info=1';
		$savebar = "<div class='pwb-savebar'>";
		$savebar .= "<span class='pwb-status' id='pwb-status'></span>";
		$savebar .= "<div class='pwb-savebar-row'>";
		$savebar .= "<button type='button' class='pwb-save' id='pwb-save'>" . $this->icon('check', 16) . '<span>' . $this->_('Save design') . '</span></button>';
		$savebar .= "<button type='button' class='pwb-reset' id='pwb-reset'>" . $this->icon('refresh', 14) . '<span>' . $this->_('Reset to defaults') . '</span></button>';
		$savebar .= "<a class='pwb-config-link' href='{$configUrl}'>" . $this->_('Texts & behavior') . ' ' . $this->icon('arrow-right', 13) . '</a>';
		$savebar .= '</div>';
		$savebar .= '</div>';

		return "<div class='pwb-panel'>{$nav}{$panels}{$savebar}</div>";
	}

	protected function segmented($key, $label, array $options, $value, $dataAttr = '') {
		$s = $this->wire()->sanitizer;
		$out = "<div class='pwb-field' {$dataAttr}><label class='pwb-label'>{$s->entities1($label)}</label><div class='pwb-segmented' role='radiogroup'>";
		foreach($options as $optValue => $optLabel) {
			$checked = ((string) $value === (string) $optValue) ? 'checked' : '';
			$id = "pwb_{$key}_{$optValue}";
			$out .= "<label class='pwb-seg'><input type='radio' name='{$key}' id='{$id}' value='{$s->entities1($optValue)}' data-key='{$key}' {$checked}><span>{$s->entities1($optLabel)}</span></label>";
		}
		return $out . '</div></div>';
	}

	protected function quadrant($key, $label, $value) {
		$s = $this->wire()->sanitizer;
		$out = "<div class='pwb-field'><label class='pwb-label'>{$s->entities1($label)}</label><div class='pwb-quadrant'>";
		foreach(['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $pos) {
			$checked = ($value === $pos) ? 'checked' : '';
			$out .= "<label class='pwb-quad pwb-quad-{$pos}' title='{$pos}'><input type='radio' name='{$key}' value='{$pos}' data-key='{$key}' {$checked}><span></span></label>";
		}
		return $out . '</div></div>';
	}

	/**
	 * 8-point position picker (corners + edge midpoints, no true center — that's
	 * what the "modal" layout is for). Used for the banner's box position.
	 * @return string
	 */
	protected function quadrant9($key, $label, $value) {
		$s = $this->wire()->sanitizer;
		$cells = [
			'top-left', 'top-center', 'top-right',
			'left-center', '', 'right-center',
			'bottom-left', 'bottom-center', 'bottom-right',
		];
		$out = "<div class='pwb-field'><label class='pwb-label'>{$s->entities1($label)}</label><div class='pwb-quadrant pwb-quadrant-9'>";
		foreach($cells as $pos) {
			if($pos === '') {
				$out .= "<span class='pwb-quad-empty'></span>";
				continue;
			}
			$checked = ($value === $pos) ? 'checked' : '';
			$out .= "<label class='pwb-quad pwb-quad-{$pos}' title='{$pos}'><input type='radio' name='{$key}' value='{$pos}' data-key='{$key}' {$checked}><span></span></label>";
		}
		return $out . '</div></div>';
	}

	protected function toggle($key, $label, $checked) {
		$s = $this->wire()->sanitizer;
		$on = $checked ? 'checked' : '';
		return "<div class='pwb-field pwb-field-toggle'><label><input type='checkbox' data-key='{$key}' value='1' {$on}> {$s->entities1($label)}</label></div>";
	}

	protected function color($key, $label, $value, $dataAttr = '') {
		$s = $this->wire()->sanitizer;
		$value = $s->entities1($value);
		return "<div class='pwb-color' {$dataAttr}><input type='color' data-key='{$key}' value='{$value}' id='pwb_{$key}'><label for='pwb_{$key}'>{$s->entities1($label)}</label></div>";
	}

	protected function palettes() {
		$palettes = [
			// bg, text, primary, primary_text, secondary, secondary_text, link, accent
			'indigo' => ['#ffffff', '#1f2937', '#4f46e5', '#ffffff', '#eef0f4', '#111827', '#4f46e5', '#4f46e5'],
			'dark' => ['#1f2430', '#e7eaf0', '#6366f1', '#ffffff', '#343b4c', '#e7eaf0', '#93a5fd', '#6366f1'],
			'emerald' => ['#ffffff', '#1c2b27', '#059669', '#ffffff', '#e8f2ee', '#123f33', '#047857', '#059669'],
			'amber' => ['#fffdf7', '#33302a', '#d97706', '#ffffff', '#f4ecdd', '#4a4133', '#b45309', '#d97706'],
			'mono' => ['#ffffff', '#18181b', '#18181b', '#ffffff', '#f0f0f1', '#18181b', '#18181b', '#18181b'],
			'rose' => ['#ffffff', '#3f1728', '#e11d48', '#ffffff', '#fde8ef', '#881337', '#e11d48', '#e11d48'],
			'sky' => ['#ffffff', '#0c2d3f', '#0284c7', '#ffffff', '#e0f2fe', '#075985', '#0284c7', '#0284c7'],
			'violet' => ['#ffffff', '#2e1065', '#7c3aed', '#ffffff', '#ede9fe', '#4c1d95', '#7c3aed', '#7c3aed'],
			'teal' => ['#ffffff', '#0f2e2b', '#0d9488', '#ffffff', '#ccfbf1', '#115e59', '#0d9488', '#0d9488'],
			'orange' => ['#ffffff', '#431407', '#ea580c', '#ffffff', '#ffedd5', '#7c2d12', '#ea580c', '#ea580c'],
			'red' => ['#ffffff', '#450a0a', '#dc2626', '#ffffff', '#fee2e2', '#7f1d1d', '#dc2626', '#dc2626'],
			'lime' => ['#ffffff', '#1a2e05', '#65a30d', '#ffffff', '#ecfccb', '#3f6212', '#65a30d', '#65a30d'],
			'cyan' => ['#ffffff', '#083344', '#0891b2', '#ffffff', '#cffafe', '#155e75', '#0891b2', '#0891b2'],
			'fuchsia' => ['#ffffff', '#4a044e', '#c026d3', '#ffffff', '#fae8ff', '#86198f', '#c026d3', '#c026d3'],
			'midnight' => ['#0b1120', '#dbeafe', '#3b82f6', '#ffffff', '#16213b', '#dbeafe', '#93c5fd', '#3b82f6'],
			'forest' => ['#14251d', '#d7ecdf', '#34d399', '#04140d', '#1f3a2c', '#d7ecdf', '#6ee7b7', '#34d399'],
			'coffee' => ['#fbf3ea', '#3b2a1e', '#92400e', '#ffffff', '#f0e2d0', '#5b3a20', '#92400e', '#92400e'],
			'blush' => ['#fff5f7', '#4a2130', '#db2777', '#ffffff', '#ffe4ec', '#831843', '#db2777', '#db2777'],
			'gold' => ['#fffaf0', '#2b2110', '#ca8a04', '#1a1300', '#fef9c3', '#713f12', '#a16207', '#ca8a04'],
			'charcoal' => ['#202023', '#e4e4e7', '#f4f4f5', '#18181b', '#2d2d31', '#e4e4e7', '#d4d4d8', '#a1a1aa'],
			// football (soccer) club colors
			'barcelona' => ['#ffffff', '#1a2340', '#a50044', '#ffffff', '#e6ecf5', '#08284d', '#004d98', '#004d98'],
			'real-madrid' => ['#ffffff', '#1c1c2b', '#1a2a6c', '#ffffff', '#fdf3d9', '#6b4e00', '#febe10', '#febe10'],
			'man-utd' => ['#ffffff', '#241010', '#da291c', '#ffffff', '#fde8e6', '#7a1710', '#da291c', '#da291c'],
			'liverpool' => ['#ffffff', '#241012', '#c8102e', '#ffffff', '#fbe3e7', '#7a0a1d', '#c8102e', '#c8102e'],
			'bayern' => ['#ffffff', '#101d33', '#dc052d', '#ffffff', '#e3edf7', '#0a2c56', '#0066b2', '#0066b2'],
			'juventus' => ['#ffffff', '#1a1a1a', '#000000', '#ffffff', '#f2f2f2', '#1a1a1a', '#000000', '#000000'],
			'dortmund' => ['#fffbe6', '#1a1a12', '#f9c700', '#1a1a12', '#efefef', '#1a1a1a', '#1a1a1a', '#000000'],
			'psg' => ['#ffffff', '#0d1a2b', '#004170', '#ffffff', '#fbe4e2', '#7a1710', '#da291c', '#da291c'],
			'chelsea' => ['#ffffff', '#0b1f38', '#034694', '#ffffff', '#e3ecf7', '#062a52', '#034694', '#034694'],
			'arsenal' => ['#ffffff', '#241012', '#ef0107', '#ffffff', '#fde7e7', '#7a0d0f', '#ef0107', '#ef0107'],
			// hockey (NHL) club colors
			'canadiens' => ['#ffffff', '#10142b', '#af1e2d', '#ffffff', '#e5e8f5', '#14205c', '#192168', '#192168'],
			'maple-leafs' => ['#ffffff', '#0b1530', '#00205b', '#ffffff', '#e3e8f2', '#041233', '#00205b', '#00205b'],
			'bruins' => ['#ffffff', '#1a1a1a', '#000000', '#ffb81c', '#fff6e0', '#6b4e00', '#ffb81c', '#ffb81c'],
			'blackhawks' => ['#ffffff', '#1a1010', '#cf0a2c', '#ffffff', '#f0f0f0', '#1a1a1a', '#000000', '#000000'],
			'rangers' => ['#ffffff', '#0a1530', '#0038a8', '#ffffff', '#fbe4e6', '#7a0a1d', '#ce1126', '#ce1126'],
			'oilers' => ['#ffffff', '#0a1530', '#ff4c00', '#ffffff', '#e3e8f2', '#041233', '#041e42', '#041e42'],
			'capitals' => ['#ffffff', '#0a1530', '#c8102e', '#ffffff', '#e3e8f2', '#041233', '#041e42', '#041e42'],
			'penguins' => ['#ffffff', '#1a1a1a', '#000000', '#fcb514', '#fff8e1', '#6b4e00', '#fcb514', '#fcb514'],
			'red-wings' => ['#ffffff', '#241012', '#ce1126', '#ffffff', '#fbe4e6', '#7a0a1d', '#ce1126', '#ce1126'],
			'golden-knights' => ['#ffffff', '#1c1e20', '#b4975a', '#1c1e20', '#eceeef', '#333f42', '#333f42', '#333f42'],
			// basketball (NBA) club colors
			'lakers' => ['#ffffff', '#241033', '#552583', '#ffffff', '#fef3d9', '#6b4e00', '#fdb927', '#fdb927'],
			'celtics' => ['#ffffff', '#0a2e1c', '#007a33', '#ffffff', '#e3f5ea', '#04381c', '#007a33', '#007a33'],
			'bulls' => ['#ffffff', '#1a1010', '#ce1141', '#ffffff', '#f0f0f0', '#1a1a1a', '#000000', '#000000'],
			'warriors' => ['#ffffff', '#0a1a3a', '#1d428a', '#ffffff', '#fff6da', '#6b4e00', '#ffc72c', '#ffc72c'],
			'heat' => ['#ffffff', '#240a12', '#98002e', '#ffffff', '#fff3e0', '#6b4200', '#f9a01b', '#f9a01b'],
			'knicks' => ['#ffffff', '#0a1c30', '#006bb6', '#ffffff', '#fef0e3', '#7a3300', '#f58426', '#f58426'],
			'nets' => ['#ffffff', '#1a1a1a', '#000000', '#ffffff', '#f2f2f2', '#1a1a1a', '#000000', '#000000'],
			'spurs' => ['#ffffff', '#1a1a1a', '#000000', '#ffffff', '#f0f1f2', '#3a4046', '#626f76', '#626f76'],
			'mavericks' => ['#ffffff', '#0a1c30', '#00538c', '#ffffff', '#e3eef5', '#063050', '#00538c', '#00538c'],
			'suns' => ['#ffffff', '#2a1040', '#e56020', '#ffffff', '#ece7f7', '#1d1160', '#1d1160', '#1d1160'],
		];
		$out = "<div class='pwb-field'><label class='pwb-label'>" . $this->_('Presets') . '</label>';
		$out .= "<p class='pwb-palette-legend'>" . $this->_('Each swatch previews three colors, left to right: primary button, background, secondary button.') . '</p>';
		$out .= "<div class='pwb-palettes'>";
		foreach($palettes as $name => $colors) {
			$json = $this->wire()->sanitizer->entities1(json_encode($colors));
			$out .= "<button type='button' class='pwb-palette' data-palette='{$json}' title='{$name}'>";
			$out .= "<i style='background:{$colors[2]}'></i><i style='background:{$colors[0]};border:1px solid #ddd'></i><i style='background:{$colors[4]}'></i>";
			$out .= '</button>';
		}
		return $out . '</div></div>';
	}

	/**
	 * One-click color presets for the floating icon button (background + icon color).
	 * @return string
	 */
	protected function iconPalettes() {
		$presets = [
			// bg, fg
			'indigo' => ['#4f46e5', '#ffffff'],
			'dark' => ['#18181b', '#ffffff'],
			'white' => ['#ffffff', '#18181b'],
			'emerald' => ['#059669', '#ffffff'],
			'rose' => ['#e11d48', '#ffffff'],
			'sky' => ['#0284c7', '#ffffff'],
			'amber' => ['#d97706', '#ffffff'],
			'violet' => ['#7c3aed', '#ffffff'],
			'gold' => ['#ca8a04', '#1a1300'],
			'charcoal' => ['#27272a', '#f4f4f5'],
			'barcelona' => ['#a50044', '#ffffff'],
			'real-madrid' => ['#1a2a6c', '#ffffff'],
			'man-utd' => ['#da291c', '#ffffff'],
			'bayern' => ['#dc052d', '#ffffff'],
			'juventus' => ['#000000', '#ffffff'],
			'canadiens' => ['#af1e2d', '#ffffff'],
			'bruins' => ['#000000', '#ffb81c'],
			'blackhawks' => ['#cf0a2c', '#ffffff'],
			'oilers' => ['#ff4c00', '#ffffff'],
			'golden-knights' => ['#b4975a', '#1c1e20'],
			'lakers' => ['#552583', '#fdb927'],
			'celtics' => ['#007a33', '#ffffff'],
			'bulls' => ['#ce1141', '#ffffff'],
			'warriors' => ['#1d428a', '#ffc72c'],
			'heat' => ['#98002e', '#f9a01b'],
		];
		$out = "<div class='pwb-field'><label class='pwb-label'>" . $this->_('Presets') . "</label><div class='pwb-icon-swatches'>";
		foreach($presets as $name => $colors) {
			$json = $this->wire()->sanitizer->entities1(json_encode($colors));
			$out .= "<button type='button' class='pwb-icon-swatch' data-icon-palette='{$json}' title='{$name}' style='background:{$colors[0]}'>";
			$out .= "<i style='background:{$colors[1]}'></i>";
			$out .= '</button>';
		}
		return $out . '</div></div>';
	}

	/**
	 * One-click color presets for the dark theme (dark_color_* keys).
	 * @return string
	 */
	protected function darkPalettes() {
		$palettes = [
			// bg, text, primary, primary_text, secondary, secondary_text, link, accent
			'slate' => ['#1f2430', '#e7eaf0', '#6366f1', '#ffffff', '#343b4c', '#e7eaf0', '#93a5fd', '#6366f1'],
			'midnight' => ['#0b1120', '#dbeafe', '#3b82f6', '#ffffff', '#16213b', '#dbeafe', '#93c5fd', '#3b82f6'],
			'forest' => ['#14251d', '#d7ecdf', '#34d399', '#04140d', '#1f3a2c', '#d7ecdf', '#6ee7b7', '#34d399'],
			'charcoal' => ['#202023', '#e4e4e7', '#f4f4f5', '#18181b', '#2d2d31', '#e4e4e7', '#d4d4d8', '#a1a1aa'],
			'plum' => ['#1e1b2e', '#ede9fe', '#a78bfa', '#1e1b2e', '#2f2a47', '#ede9fe', '#c4b5fd', '#a78bfa'],
			'wine' => ['#240f13', '#fbe4e6', '#e11d48', '#ffffff', '#3a191e', '#fbe4e6', '#fb7185', '#e11d48'],
			'ocean' => ['#06222b', '#d7f0f5', '#22d3ee', '#06222b', '#0d3540', '#d7f0f5', '#67e8f9', '#22d3ee'],
			'amber' => ['#221708', '#fdf0dd', '#f59e0b', '#221708', '#3a2a12', '#fdf0dd', '#fbbf24', '#f59e0b'],
			// sports club colors, brightened/adapted for a dark background
			'barcelona' => ['#12182a', '#e8ecf7', '#e0005f', '#ffffff', '#1c2742', '#dbe6fb', '#5b93e0', '#5b93e0'],
			'real-madrid' => ['#14162a', '#eef0fb', '#4a5fc7', '#ffffff', '#2a2410', '#ffe9a8', '#ffce3d', '#ffce3d'],
			'bayern' => ['#12151f', '#e7ecf7', '#ff3355', '#ffffff', '#142c47', '#cfe3fb', '#3f9eef', '#3f9eef'],
			'juventus' => ['#17181a', '#eaeaea', '#f2f2f2', '#17181a', '#26272a', '#eaeaea', '#f2f2f2', '#f2f2f2'],
			'canadiens' => ['#14172a', '#eceffb', '#ff4d5e', '#ffffff', '#1c234a', '#dbe1fb', '#6b7fe0', '#6b7fe0'],
			'bruins' => ['#17171a', '#f3f0e6', '#ffb81c', '#17171a', '#262620', '#ffe9b0', '#ffcf5c', '#ffcf5c'],
			'oilers' => ['#12172a', '#eef1fb', '#ff7a3d', '#17171a', '#16233e', '#cfe0fb', '#5b93e0', '#5b93e0'],
			'lakers' => ['#1c1330', '#f1ebfb', '#b17ee0', '#1c1330', '#3a2410', '#ffe9a8', '#ffce3d', '#ffce3d'],
			'celtics' => ['#0f1f18', '#e6f5ec', '#24c96a', '#0f1f18', '#163527', '#cdf0dc', '#3fdc8a', '#3fdc8a'],
			'warriors' => ['#10152a', '#e9edfb', '#4d78d6', '#ffffff', '#2e2610', '#ffe9a8', '#ffce3d', '#ffce3d'],
		];
		$out = "<div class='pwb-field'><label class='pwb-label'>" . $this->_('Presets') . '</label>';
		$out .= "<p class='pwb-palette-legend'>" . $this->_('Same order as above: primary button, background, secondary button.') . '</p>';
		$out .= "<div class='pwb-palettes'>";
		foreach($palettes as $name => $colors) {
			$json = $this->wire()->sanitizer->entities1(json_encode($colors));
			$out .= "<button type='button' class='pwb-dark-palette' data-dark-palette='{$json}' title='{$name}'>";
			$out .= "<i style='background:{$colors[2]}'></i><i style='background:{$colors[0]};border:1px solid #444'></i><i style='background:{$colors[4]}'></i>";
			$out .= '</button>';
		}
		return $out . '</div></div>';
	}

	protected function select($key, $label, array $options, $value) {
		$s = $this->wire()->sanitizer;
		$out = "<div class='pwb-field'><label class='pwb-label' for='pwb_{$key}'>{$s->entities1($label)}</label><select data-key='{$key}' id='pwb_{$key}'>";
		foreach($options as $optValue => $optLabel) {
			$selected = ((string) $value === (string) $optValue) ? 'selected' : '';
			$out .= "<option value='{$s->entities1($optValue)}' {$selected}>{$s->entities1($optLabel)}</option>";
		}
		return $out . '</select></div>';
	}

	protected function text($key, $label, $value, $placeholder = '') {
		$s = $this->wire()->sanitizer;
		return "<div class='pwb-field'><label class='pwb-label' for='pwb_{$key}'>{$s->entities1($label)}</label>" .
			"<input type='text' data-key='{$key}' id='pwb_{$key}' value='{$s->entities1($value)}' placeholder='{$s->entities1($placeholder)}'></div>";
	}

	protected function range($key, $label, $value, $min, $max, $unit) {
		$s = $this->wire()->sanitizer;
		$value = (int) $value;
		$label = $s->entities1($label);
		$unit = $s->entities1($unit);
		return "<div class='pwb-field pwb-field-range'><label class='pwb-label' for='pwb_{$key}'>{$label}" .
			"<span class='pwb-range-value'><input type='number' class='pwb-range-num' inputmode='numeric' value='{$value}' min='{$min}' max='{$max}' aria-label='{$label}'>{$unit}</span></label>" .
			"<input type='range' data-key='{$key}' id='pwb_{$key}' value='{$value}' min='{$min}' max='{$max}'></div>";
	}

	protected function iconPicker($key, $label, $value) {
		$s = $this->wire()->sanitizer;
		$out = "<div class='pwb-field'><label class='pwb-label'>{$s->entities1($label)}</label><div class='pwb-icons'>";
		foreach(['cookie', 'shield', 'sliders', 'gear', 'banana', 'fingerprint', 'pw'] as $type) {
			$checked = ($value === $type) ? 'checked' : '';
			$svg = $this->cookie->getIconSvg($type);
			$out .= "<label class='pwb-icon-choice' title='{$type}'><input type='radio' name='{$key}' value='{$type}' data-key='{$key}' {$checked}><span>{$svg}</span></label>";
		}
		return $out . '</div></div>';
	}

	/* ---------------- preview ---------------- */

	protected function renderPreview() {
		$p = $this->cookie->cssPrefix();
		$t = $this->cookie->getTexts();
		$s = $this->wire()->sanitizer;

		$tabs = [
			'banner' => $this->_('Banner'),
			'prefs' => $this->_('Preferences'),
			'fab' => $this->_('Icon'),
			'placeholder' => $this->_('Placeholder'),
		];
		$out = "<div class='pwb-tabs-row'>";
		$out .= "<div class='pwb-tabs' role='tablist'>";
		foreach($tabs as $tab => $tabLabel) {
			$active = $tab === 'banner' ? ' is-active' : '';
			$out .= "<button type='button' class='pwb-tab{$active}' data-tab='{$tab}'>{$s->entities1($tabLabel)}</button>";
		}
		$out .= '</div>';

		// device + theme toggles for the preview
		$out .= "<div class='pwb-view-toggles'>";
		$out .= "<div class='pwb-devicetoggle'>";
		$out .= "<button type='button' class='pwb-device is-active' data-device='desktop' title='" . $s->entities1($this->_('Desktop')) . "'>" . $this->icon('desktop', 15) . '</button>';
		$out .= "<button type='button' class='pwb-device' data-device='mobile' title='" . $s->entities1($this->_('Mobile')) . "'>" . $this->icon('mobile', 15) . '</button>';
		$out .= '</div>';
		$out .= "<button type='button' class='pwb-themetoggle' id='pwb-themetoggle' title='" . $s->entities1($this->_('Toggle dark preview')) . "'>" . $this->icon('moon', 15) . '</button>';
		$out .= '</div></div>';

		// fake browser window
		$out .= "<div class='pwb-browser' id='pwb-browser'>";
		$out .= "<div class='pwb-browser-bar'><i></i><i></i><i></i><span>example.com</span></div>";
		$out .= "<div class='pwb-viewport' id='pwb-viewport'>";

		// greeked page content
		$out .= "<div class='pwb-page'>";
		$out .= "<div class='pwb-sk pwb-sk-nav'></div>";
		$out .= "<div class='pwb-sk pwb-sk-h1'></div>";
		$out .= "<div class='pwb-sk pwb-sk-line'></div><div class='pwb-sk pwb-sk-line'></div><div class='pwb-sk pwb-sk-line short'></div>";

		// sample placeholder (visible on the Placeholder tab only, toggled by builder.js)
		$out .= "<div class='pwb-ph-demo' id='pwb-ph-demo' hidden>";
		$out .= "<div class='{$p}-root'><div class='{$p}-ph'><div class='{$p}-ph-inner'>";
		$msg = str_replace('{category}', $s->entities1($this->cookie->txt('label_external_media')), $s->entities1($t['txt_ph_message']));
		$out .= "<p class='{$p}-ph-msg'>{$msg}</p>";
		$out .= "<div class='{$p}-ph-actions'>";
		$out .= "<button type='button' class='{$p}-btn {$p}-btn-secondary'>{$s->entities1($t['txt_ph_load'])}</button>";
		$out .= "<button type='button' class='{$p}-btn {$p}-btn-primary'>{$s->entities1($t['txt_ph_always'])}</button>";
		$out .= '</div></div></div></div>';
		$out .= '</div>';

		$out .= "<div class='pwb-sk pwb-sk-line'></div><div class='pwb-sk pwb-sk-line short'></div>";
		$out .= '</div>'; // .pwb-page

		// the real widget markup in preview mode
		$out .= $this->cookie->renderBanner(true);

		$out .= '</div></div>'; // viewport, browser
		return $out;
	}

	/* ==================================================================
	 * Save endpoint
	 * ================================================================ */

	protected function getSchema() {
		$positions = [
			'bottom-right', 'bottom-left', 'top-right', 'top-left',
			'top-center', 'bottom-center', 'left-center', 'right-center',
			'top', 'bottom', 'center',
		];
		$colorKeys = [
			'design_color_bg', 'design_color_text', 'design_color_primary', 'design_color_primary_text',
			'design_color_secondary', 'design_color_secondary_text', 'design_color_link', 'design_color_accent',
			'icon_color_bg', 'icon_color_fg',
			'dark_color_bg', 'dark_color_text', 'dark_color_primary', 'dark_color_primary_text',
			'dark_color_secondary', 'dark_color_secondary_text', 'dark_color_link', 'dark_color_accent',
		];
		$schema = [
			'design_layout' => ['enum', ['box', 'bar', 'modal']],
			'design_position' => ['enum', $positions],
			'design_overlay' => ['bool'],
			'design_font_family' => ['enum', ['inherit', 'system', 'serif', 'mono', 'custom']],
			'design_font_custom' => ['text', 200],
			'design_font_size' => ['int', 10, 24],
			'design_font_size_title' => ['int', 12, 32],
			'design_font_size_button' => ['int', 10, 24],
			'design_radius' => ['int', 0, 32],
			'design_shadow' => ['enum', ['none', 'soft', 'strong']],
			'design_max_width' => ['int', 300, 640],
			'design_spacing' => ['int', 8, 40],
			'icon_show' => ['bool'],
			'icon_transparent' => ['bool'],
			'icon_shadow' => ['enum', ['none', 'soft', 'strong']],
			'icon_type' => ['enum', ['cookie', 'shield', 'sliders', 'gear', 'banana', 'fingerprint', 'pw']],
			'icon_position' => ['enum', ['bottom-right', 'bottom-left', 'top-right', 'top-left']],
			'icon_offset_x' => ['int', 0, 80],
			'icon_offset_y' => ['int', 0, 80],
			'icon_size' => ['int', 36, 80],
			'icon_shape' => ['enum', ['round', 'rounded', 'square']],
			'dark_enable' => ['bool'],
		];
		foreach($colorKeys as $key) $schema[$key] = ['color'];
		return $schema;
	}

	public function ___executeSave() {
		$input = $this->wire()->input;
		$modules = $this->wire()->modules;
		if(!$input->requestMethod('POST')) throw new WireException('POST only');
		$this->wire()->session->CSRF->validate();

		header('Content-Type: application/json');

		// reset to defaults
		if($input->post('reset')) {
			$configData = $modules->getConfig('Cookie');
			$configData = array_merge($configData, $this->getDesignDefaults());
			$modules->saveConfig('Cookie', $configData);
			return json_encode(['ok' => true, 'reset' => true]);
		}

		$clean = [];
		$errors = [];
		foreach($this->getSchema() as $key => $rule) {
			$value = $input->post($key);
			if($value === null) continue;
			switch($rule[0]) {
				case 'enum':
					if(in_array($value, $rule[1], true)) $clean[$key] = $value;
					else $errors[] = $key;
					break;
				case 'bool':
					$clean[$key] = (int) ((string) $value === '1');
					break;
				case 'int':
					$int = (int) $value;
					if($int < $rule[1]) $int = $rule[1];
					if($int > $rule[2]) $int = $rule[2];
					$clean[$key] = $int;
					break;
				case 'color':
					if(preg_match('/^#[0-9a-f]{3,8}$/i', (string) $value)) $clean[$key] = strtolower($value);
					else $errors[] = $key;
					break;
				case 'text':
					$clean[$key] = $this->wire()->sanitizer->text((string) $value, ['maxLength' => $rule[1]]);
					break;
			}
		}

		if(count($errors)) {
			return json_encode(['ok' => false, 'errors' => $errors]);
		}

		$configData = $modules->getConfig('Cookie');
		$configData = array_merge(is_array($configData) ? $configData : [], $clean);
		$modules->saveConfig('Cookie', $configData);
		$this->wire()->log->save('cookie', 'Widget design updated by ' . $this->wire()->user->name);

		return json_encode(['ok' => true]);
	}

	/* ==================================================================
	 * Consent log
	 * ================================================================ */

	public function ___executeLog() {
		$this->headline($this->_('Cookie — Consent log'));
		$this->breadcrumb('../', $this->_('Cookie'));
		$database = $this->wire()->database;
		$input = $this->wire()->input;
		$s = $this->wire()->sanitizer;

		$out = '';
		if(!$this->cookie->enable_logging) {
			$configUrl = $this->wire()->config->urls->admin . 'module/edit?name=Cookie&collapse_info=1';
			$out .= "<p class='ui-state-highlight' style='padding:1em'>" .
				sprintf($this->_('Consent logging is disabled. Enable it in the %s.'), "<a href='{$configUrl}'>" . $this->_('module settings') . '</a>') .
				'</p>';
		}

		$perPage = 50;
		$pageNum = max(1, (int) $input->get('pg'));
		$offset = ($pageNum - 1) * $perPage;

		$total = (int) $database->query('SELECT COUNT(*) FROM ' . Cookie::LOG_TABLE)->fetchColumn();
		$query = $database->prepare('SELECT * FROM ' . Cookie::LOG_TABLE . ' ORDER BY id DESC LIMIT :offset, :limit');
		$query->bindValue(':offset', $offset, \PDO::PARAM_INT);
		$query->bindValue(':limit', $perPage, \PDO::PARAM_INT);
		$query->execute();
		$rows = $query->fetchAll(\PDO::FETCH_ASSOC);

		/** @var MarkupAdminDataTable $table */
		$table = $this->wire()->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->headerRow([
			$this->_('ID'),
			$this->_('Date'),
			$this->_('Version'),
			$this->_('Consent'),
			$this->_('IP (hashed)'),
			$this->_('User agent'),
		]);
		foreach($rows as $row) {
			$consent = json_decode($row['consent'], true);
			$badges = '';
			if(is_array($consent)) {
				foreach($consent as $category => $granted) {
					$class = $granted ? 'pwb-badge pwb-badge-on' : 'pwb-badge';
					$badges .= "<span class='{$class}'>" . $s->entities1($category) . '</span> ';
				}
			}
			$table->row([
				(int) $row['id'],
				$s->entities1($row['created']),
				(int) $row['version'],
				$badges,
				'<code>' . substr($s->entities1($row['ip_hash']), 0, 12) . '…</code>',
				$s->entities1($s->truncate((string) $row['ua'], 60)),
			]);
		}

		$out .= "<p>" . sprintf($this->_('%d consent records'), $total) . '</p>';
		$out .= $table->render();

		// pagination
		$pages = (int) ceil($total / $perPage);
		if($pages > 1) {
			$out .= "<p class='pwb-pager'>";
			if($pageNum > 1) $out .= "<a class='ui-button ui-state-default' href='./?pg=" . ($pageNum - 1) . "'>&laquo; " . $this->_('Newer') . '</a> ';
			$out .= sprintf($this->_('Page %1$d of %2$d'), $pageNum, $pages);
			if($pageNum < $pages) $out .= " <a class='ui-button ui-state-default' href='./?pg=" . ($pageNum + 1) . "'>" . $this->_('Older') . ' &raquo;</a>';
			$out .= '</p>';
		}

		$out .= "<p><a class='ui-button ui-state-default' href='../export/'><span class='ui-button-text'>" . $this->icon('download', 14) . ' ' . $this->_('Export CSV') . '</span></a></p>';

		return $out;
	}

	/* ==================================================================
	 * Cookie policy generator
	 * ================================================================ */

	public function ___executePolicy() {
		$s = $this->wire()->sanitizer;
		$policy = $this->cookie->renderPolicy();

		// download branch (before any admin chrome is rendered)
		if($this->wire()->input->get('download')) {
			$doc = "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Cookie Policy</title></head><body>{$policy}</body></html>";
			header('Content-Type: text/html; charset=utf-8');
			header('Content-Disposition: attachment; filename="cookie-policy.html"');
			echo $doc;
			exit;
		}

		$this->headline($this->_('Cookie — Policy generator'));
		$this->breadcrumb('../', $this->_('Cookie'));

		$out = "<div class='pwb-policy'>";
		$out .= "<p>" . $this->_('This page previews the cookie policy generated from your categories and services. Output it in a template, or copy the HTML into a page.') . '</p>';

		// usage snippet
		$out .= "<pre class='pwb-policy-code'>&lt;?php echo \$modules-&gt;get('Cookie')-&gt;renderPolicy(); ?&gt;</pre>";

		$out .= "<p><button type='button' class='ui-button ui-state-default' id='pwb-policy-copy'><span class='ui-button-text'>" . $this->icon('copy', 14) . ' ' . $this->_('Copy HTML') . "</span></button> ";
		$out .= "<a class='ui-button ui-state-default' href='./?download=1'><span class='ui-button-text'>" . $this->icon('download', 14) . ' ' . $this->_('Download .html') . "</span></a> ";
		$out .= "<span class='pwb-policy-note' id='pwb-policy-note'></span></p>";

		// preview (load the frontend widget CSS so triggers/policy tables look right)
		$out .= "<h2>" . $this->_('Preview') . "</h2>";
		$out .= "<div class='pwb-policy-preview'>{$policy}</div>";

		// raw HTML for copy
		$out .= "<textarea id='pwb-policy-html' style='position:absolute;left:-9999px' readonly>" . $s->entities1($policy) . "</textarea>";
		$out .= "<script>(function(){var b=document.getElementById('pwb-policy-copy'),t=document.getElementById('pwb-policy-html'),n=document.getElementById('pwb-policy-note');if(!b)return;b.addEventListener('click',function(){t.select();try{document.execCommand('copy');n.textContent='" . $this->_('Copied to clipboard.') . "';}catch(e){navigator.clipboard&&navigator.clipboard.writeText(t.value);n.textContent='" . $this->_('Copied.') . "';}});})();</script>";

		$out .= '</div>';
		return $out;
	}

	/* ==================================================================
	 * Statistics dashboard
	 * ================================================================ */

	public function ___executeStats() {
		$this->headline($this->_('Cookie — Statistics'));
		$this->breadcrumb('../', $this->_('Cookie'));
		$s = $this->wire()->sanitizer;

		if(!$this->cookie->enable_logging) {
			$configUrl = $this->wire()->config->urls->admin . 'module/edit?name=Cookie&collapse_info=1';
			return "<p class='ui-state-highlight' style='padding:1em'>" .
				sprintf($this->_('Consent logging is disabled — there is nothing to chart yet. Enable it in the %s.'),
					"<a href='{$configUrl}'>" . $this->_('module settings') . '</a>') . '</p>';
		}

		$days = 30;
		$data = $this->collectStats($days);

		if($data['total'] === 0) {
			return "<p class='ui-state-highlight' style='padding:1em'>" .
				$this->_('No consent records in the selected period yet. Statistics will appear once visitors start making choices.') . '</p>';
		}

		$out = "<div class='pwb-stats'>";

		// summary cards
		$acceptAllPct = round($data['acceptAll'] / $data['total'] * 100);
		$rejectAllPct = round($data['rejectAll'] / $data['total'] * 100);
		$customPct = max(0, 100 - $acceptAllPct - $rejectAllPct);
		$cards = [
			[$this->_('Total decisions'), number_format($data['total']), sprintf($this->_('last %d days'), $days), ''],
			[$this->_('Accept all'), $acceptAllPct . '%', $data['acceptAll'] . ' ' . $this->_('visitors'), 'on'],
			[$this->_('Only necessary'), $rejectAllPct . '%', $data['rejectAll'] . ' ' . $this->_('visitors'), 'off'],
			[$this->_('Custom choice'), $customPct . '%', ($data['total'] - $data['acceptAll'] - $data['rejectAll']) . ' ' . $this->_('visitors'), 'mid'],
		];
		$out .= "<div class='pwb-stat-cards'>";
		foreach($cards as $card) {
			$out .= "<div class='pwb-stat-card pwb-stat-{$card[3]}'>";
			$out .= "<span class='pwb-stat-num'>{$s->entities1($card[1])}</span>";
			$out .= "<span class='pwb-stat-label'>{$s->entities1($card[0])}</span>";
			$out .= "<span class='pwb-stat-sub'>{$s->entities1($card[2])}</span>";
			$out .= '</div>';
		}
		$out .= '</div>';

		// per-category acceptance bars
		$out .= "<h2>" . $this->_('Category acceptance') . '</h2>';
		$out .= "<div class='pwb-stat-bars'>";
		foreach($data['categories'] as $key => $granted) {
			$pct = $data['total'] ? round($granted / $data['total'] * 100) : 0;
			$label = $this->cookie->txt("label_{$key}");
			if(!$label) $label = $key;
			$out .= "<div class='pwb-stat-bar'>";
			$out .= "<span class='pwb-stat-bar-label'>{$s->entities1($label)}</span>";
			$out .= "<span class='pwb-stat-bar-track'><span class='pwb-stat-bar-fill' style='width:{$pct}%'></span></span>";
			$out .= "<span class='pwb-stat-bar-val'>{$pct}%</span>";
			$out .= '</div>';
		}
		$out .= '</div>';

		// daily volume chart (accept-all vs only-necessary vs custom)
		$out .= "<h2>" . sprintf($this->_('Daily volume (last %d days)'), $days) . '</h2>';
		$out .= $this->renderDailyChart($data['daily']);

		// export shortcut
		$out .= "<p style='margin-top:1.5em'><a class='ui-button ui-state-default' href='../log/'><span class='ui-button-text'>" . $this->icon('list', 14) . ' ' . $this->_('View raw log') . "</span></a> ";
		$out .= "<a class='ui-button ui-state-default' href='../export/'><span class='ui-button-text'>" . $this->icon('download', 14) . ' ' . $this->_('Export CSV') . '</span></a></p>';

		$out .= '</div>';
		return $out;
	}

	/**
	 * Aggregate the consent log over the last $days days.
	 * Aggregation is done in PHP to stay portable across MySQL versions.
	 * @param int $days
	 * @return array
	 */
	protected function collectStats($days) {
		$database = $this->wire()->database;
		$optional = [];
		foreach($this->cookie->getCategories() as $cat) {
			if(!$cat['required']) $optional[] = $cat['key'];
		}

		$stats = [
			'total' => 0,
			'acceptAll' => 0,
			'rejectAll' => 0,
			'categories' => [],
			'daily' => [],
		];
		foreach($optional as $key) $stats['categories'][$key] = 0;

		// pre-seed daily buckets so gaps show as zero
		for($i = $days - 1; $i >= 0; $i--) {
			$date = date('Y-m-d', strtotime("-{$i} days"));
			$stats['daily'][$date] = ['all' => 0, 'necessary' => 0, 'custom' => 0];
		}

		$query = $database->prepare(
			'SELECT created, consent FROM ' . Cookie::LOG_TABLE .
			' WHERE created >= :since ORDER BY id ASC'
		);
		$query->bindValue(':since', date('Y-m-d 00:00:00', strtotime("-" . ($days - 1) . " days")));
		$query->execute();

		while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
			$consent = json_decode($row['consent'], true);
			if(!is_array($consent)) continue;
			$stats['total']++;

			$grantedOptional = 0;
			foreach($optional as $key) {
				if(!empty($consent[$key])) {
					$stats['categories'][$key]++;
					$grantedOptional++;
				}
			}

			$date = substr($row['created'], 0, 10);
			if(!isset($stats['daily'][$date])) continue;
			if(count($optional) && $grantedOptional === count($optional)) {
				$stats['acceptAll']++;
				$stats['daily'][$date]['all']++;
			} elseif($grantedOptional === 0) {
				$stats['rejectAll']++;
				$stats['daily'][$date]['necessary']++;
			} else {
				$stats['daily'][$date]['custom']++;
			}
		}
		return $stats;
	}

	/**
	 * Stacked-bar SVG chart of daily consent volume.
	 * @param array $daily date => ['all','necessary','custom']
	 * @return string
	 */
	protected function renderDailyChart(array $daily) {
		$s = $this->wire()->sanitizer;
		$max = 1;
		foreach($daily as $d) {
			$sum = $d['all'] + $d['necessary'] + $d['custom'];
			if($sum > $max) $max = $sum;
		}
		$count = count($daily);
		$w = 760;
		$h = 200;
		$pad = 24;
		$barGap = 3;
		$barW = ($w - $pad * 2) / max(1, $count) - $barGap;
		$colors = ['all' => '#4f46e5', 'custom' => '#a5b4fc', 'necessary' => '#d1d5db'];

		$svg = "<svg viewBox='0 0 {$w} {$h}' class='pwb-stat-chart' role='img' aria-label='" . $s->entities1($this->_('Daily consent volume')) . "'>";
		// y gridline at max
		$svg .= "<line x1='{$pad}' y1='" . ($pad) . "' x2='" . ($w - $pad) . "' y2='{$pad}' stroke='#eee'/>";
		$svg .= "<text x='" . ($pad - 4) . "' y='" . ($pad + 4) . "' text-anchor='end' font-size='10' fill='#9ca3af'>{$max}</text>";

		$i = 0;
		$chartH = $h - $pad * 2;
		foreach($daily as $date => $d) {
			$x = $pad + $i * ($barW + $barGap);
			$y = $h - $pad;
			foreach(['necessary', 'custom', 'all'] as $seg) {
				$val = $d[$seg];
				if($val <= 0) continue;
				$segH = ($val / $max) * $chartH;
				$y -= $segH;
				$svg .= "<rect x='" . round($x, 1) . "' y='" . round($y, 1) . "' width='" . round($barW, 1) . "' height='" . round($segH, 1) .
					"' fill='{$colors[$seg]}'><title>" . $s->entities1($date) . ': ' . ($d['all'] + $d['necessary'] + $d['custom']) . '</title></rect>';
			}
			// x label every ~5 days
			if($i % 5 === 0 || $i === $count - 1) {
				$svg .= "<text x='" . round($x + $barW / 2, 1) . "' y='" . ($h - 6) . "' text-anchor='middle' font-size='9' fill='#9ca3af'>" . $s->entities1(substr($date, 5)) . '</text>';
			}
			$i++;
		}
		$svg .= '</svg>';

		// legend
		$legend = "<div class='pwb-stat-legend'>";
		foreach([
			'all' => $this->_('Accept all'),
			'custom' => $this->_('Custom'),
			'necessary' => $this->_('Only necessary'),
		] as $key => $label) {
			$legend .= "<span><i style='background:{$colors[$key]}'></i>{$s->entities1($label)}</span>";
		}
		$legend .= '</div>';

		return "<div class='pwb-stat-chart-wrap'>{$svg}{$legend}</div>";
	}

	public function ___executeExport() {
		$database = $this->wire()->database;
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="consent-log-' . date('Y-m-d') . '.csv"');
		$fp = fopen('php://output', 'w');
		fputcsv($fp, ['id', 'created', 'version', 'consent', 'ip_hash', 'user_agent'], ',', '"', '\\');
		$query = $database->query('SELECT * FROM ' . Cookie::LOG_TABLE . ' ORDER BY id DESC');
		while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
			fputcsv($fp, [$row['id'], $row['created'], $row['version'], $row['consent'], $row['ip_hash'], $row['ua']], ',', '"', '\\');
		}
		fclose($fp);
		exit;
	}

	/* ==================================================================
	 * Settings transfer (export / import)
	 * ================================================================ */

	public function ___executeTransfer() {
		$input = $this->wire()->input;
		$session = $this->wire()->session;
		$s = $this->wire()->sanitizer;

		// download branch
		if($input->get('download')) {
			$json = json_encode($this->cookie->exportSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Disposition: attachment; filename="cookie-settings-' . $this->wire()->config->httpHost . '-' . date('Y-m-d') . '.json"');
			echo $json;
			exit;
		}

		// import branch
		if($input->requestMethod('POST')) {
			$session->CSRF->validate();
			$raw = '';
			if(!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
				if($_FILES['import_file']['size'] <= 262144) $raw = file_get_contents($_FILES['import_file']['tmp_name']);
			}
			if(!$raw) $raw = trim((string) $input->post('import_json'));

			if(!$raw) {
				$this->error($this->_('Nothing to import — choose a file or paste JSON.'));
			} else {
				$data = json_decode($raw, true);
				if(!is_array($data)) {
					$this->error($this->_('Invalid JSON.'));
				} elseif(isset($data['_module']) && $data['_module'] !== 'Cookie') {
					$this->error($this->_('This file is not a Cookie settings export.'));
				} else {
					$result = $this->cookie->importSettings($data);
					$this->message(sprintf($this->_('Imported %1$d settings (%2$d ignored).'), $result['applied'], $result['skipped']));
					$session->redirect('./');
				}
			}
		}

		$this->headline($this->_('Cookie — Import / Export settings'));
		$this->breadcrumb('../', $this->_('Cookie'));

		$export = json_encode($this->cookie->exportSettings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$out = "<div class='pwb-transfer'>";

		// export
		$out .= "<h2>" . $this->_('Export') . '</h2>';
		$out .= "<p>" . $this->_('Copy this JSON or download it, then import it on another site. Texts, categories, behavior, integrations, geo and design are included; the IP-hash salt and consent log are not.') . '</p>';
		$out .= "<p><a class='ui-button ui-state-default' href='./?download=1'><span class='ui-button-text'>" . $this->icon('download', 14) . ' ' . $this->_('Download settings.json') . '</span></a></p>';
		$out .= "<textarea readonly rows='8' style='width:100%;font-family:monospace;font-size:12px' onclick='this.select()'>" . $s->entities1($export) . '</textarea>';

		// import
		$out .= "<h2 style='margin-top:1.5em'>" . $this->_('Import') . '</h2>';
		$out .= "<p class='ui-state-highlight' style='padding:.6em 1em'>" . $this->_('Importing overwrites the current settings. Consider exporting a backup first.') . '</p>';
		$out .= "<form method='post' enctype='multipart/form-data' class='pwb-transfer-form'>";
		$out .= $session->CSRF->renderInput();
		$out .= "<p><label>" . $this->_('Settings file (.json):') . " <input type='file' name='import_file' accept='.json,application/json'></label></p>";
		$out .= "<p>" . $this->_('…or paste JSON:') . "<br><textarea name='import_json' rows='6' style='width:100%;font-family:monospace;font-size:12px'></textarea></p>";
		$out .= "<p><button type='submit' class='ui-button ui-state-default'><span class='ui-button-text'>" . $this->icon('upload', 14) . ' ' . $this->_('Import settings') . '</span></button></p>';
		$out .= '</form>';

		$out .= '</div>';
		return $out;
	}
}
