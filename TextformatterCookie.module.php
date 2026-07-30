<?php namespace ProcessWire;

/**
 * TextformatterCookie — blocks known third-party embeds inside formatted fields.
 *
 * Rewrites <iframe> and <script src> tags whose URL matches the domain map
 * configured in the Cookie module (Integrations > Embed domain map) so they
 * load only after consent, with a placeholder rendered in place of iframes.
 *
 * Apply AFTER TextformatterVideoEmbed (field > Details > Text formatters order).
 */
class TextformatterCookie extends Textformatter {

	public static function getModuleInfo() {
		return [
			'title' => 'Cookie: Embed Blocker',
			'summary' => 'Defers iframes/scripts from configured domains (YouTube, Vimeo, Google Maps, …) until cookie consent is given.',
			'version' => '1.1.1',
			'icon' => 'eye-slash',
			'requires' => ['Cookie'],
		];
	}

	public function format(&$str) {
		if(strpos($str, '<iframe') === false && strpos($str, '<script') === false) return;
		/** @var Cookie $cookie */
		$cookie = $this->wire()->modules->get('Cookie');
		$str = $cookie->gateHtml($str);
	}
}
