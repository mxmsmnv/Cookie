<?php namespace ProcessWire;

interface Module {}

#[\AllowDynamicProperties]
class WireData {
	private $wireApi;

	public function setWireApi($wireApi) { $this->wireApi = $wireApi; }
	public function wire() { return $this->wireApi; }
	public function get($key) { return $this->$key ?? null; }
	public function __call($name, $arguments) {
		$hookable = '___' . $name;
		if(method_exists($this, $hookable)) return $this->$hookable(...$arguments);
		throw new \BadMethodCallException($name);
	}
}

require dirname(__DIR__) . '/Cookie.module.php';

function moduleFor(string $country, string $region): Cookie {
	$module = new Cookie();
	$module->geo_mode = 1;
	$module->consent_model = 'optin';
	$module->geo_default_model = 'none';
	$module->geo_optin_countries = '';
	$module->geo_optout_countries = 'US';
	$module->geo_optin_regions = 'CA-QC US-CA';
	$module->geo_optout_regions = 'CA-ON';
	$module->geo_header = 'CF-IPCountry';
	$module->geo_region_header = 'CF-Region-Code';
	$module->setWireApi((object) [
		'config' => (object) ['geoCountry' => $country, 'geoRegion' => $region],
	]);
	return $module;
}

$cases = [
	['CA', 'QC', 'CA-QC', 'optin'],
	['CA', 'ca_on', 'CA-ON', 'optout'],
	['US', 'CA', 'US-CA', 'optin'],
	['US', 'NY', 'US-NY', 'optout'],
	['MX', '', '', 'none'],
];

foreach($cases as [$country, $region, $expectedRegion, $expectedModel]) {
	$module = moduleFor($country, $region);
	$actualRegion = $module->___detectRegion($country);
	$actualModel = $module->___resolveConsentModel();
	if($actualRegion !== $expectedRegion || $actualModel !== $expectedModel) {
		fwrite(STDERR, "Geo case {$country}/{$region}: expected {$expectedRegion}/{$expectedModel}, got {$actualRegion}/{$actualModel}\n");
		exit(1);
	}
}

$_SERVER['HTTP_SEC_GPC'] = '1';
if(!moduleFor('', '')->___detectGpc()) {
	fwrite(STDERR, "Sec-GPC: 1 was not detected\n");
	exit(1);
}
$_SERVER['HTTP_SEC_GPC'] = '0';
if(moduleFor('', '')->___detectGpc()) {
	fwrite(STDERR, "Invalid Sec-GPC value was accepted\n");
	exit(1);
}
unset($_SERVER['HTTP_SEC_GPC']);

echo "geo region and Sec-GPC validation: ok\n";
