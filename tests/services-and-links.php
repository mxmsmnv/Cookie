<?php namespace ProcessWire;

interface Module {}

class WireData {}

require dirname(__DIR__) . '/Cookie.module.php';

function failTest(string $message): void {
	fwrite(STDERR, $message . "\n");
	exit(1);
}

$module = new Cookie();
$reflection = new \ReflectionClass($module);

$normalizeCookies = $reflection->getMethod('normalizeServiceCookies');
$legacy = $normalizeCookies->invoke($module, ['_ga', ' _gid ', '_ga', ''], '2 years');
if($legacy !== [
	'names' => ['_ga', '_gid'],
	'details' => [
		['name' => '_ga', 'duration' => '2 years'],
		['name' => '_gid', 'duration' => '2 years'],
	],
	'explicit' => false,
]) failTest('Legacy service cookie normalization changed unexpectedly.');

$detailed = $normalizeCookies->invoke($module, [
	['name' => '_ga', 'duration' => '2 years'],
	['name' => '_gid', 'duration' => '24 hours'],
	['name' => '_gat'],
	['name' => ''],
	['name' => '_ga', 'duration' => 'wrong duplicate'],
], '1 year');
if($detailed !== [
	'names' => ['_ga', '_gid', '_gat'],
	'details' => [
		['name' => '_ga', 'duration' => '2 years'],
		['name' => '_gid', 'duration' => '24 hours'],
		['name' => '_gat', 'duration' => '1 year'],
	],
	'explicit' => true,
]) failTest('Per-cookie retention normalization failed.');

$parseLinks = $reflection->getMethod('parseExtraLinksJson');
$links = $parseLinks->invoke($module, json_encode([
	['label' => 'Accessibility', 'url' => '/accessibility/'],
	['label' => 'Section', 'url' => '#privacy'],
	['label' => 'External', 'url' => 'https://example.com/legal'],
	['label' => 'Protocol relative', 'url' => '//evil.example/'],
	['label' => 'Script', 'url' => 'javascript:alert(1)'],
	['label' => 'Control', 'url' => "/legal\nattack"],
	['label' => '', 'url' => '/empty-label/'],
]));
if($links !== [
	['label' => 'Accessibility', 'url' => '/accessibility/'],
	['label' => 'Section', 'url' => '#privacy'],
	['label' => 'External', 'url' => 'https://example.com/legal'],
]) failTest('Additional-link validation accepted an unsafe URL or rejected a safe URL.');

if($parseLinks->invoke($module, '{bad json') !== []) {
	failTest('Invalid additional-links JSON must produce no frontend links.');
}
$normalizeUrl = $reflection->getMethod('normalizeExtraLinkUrl');
if($normalizeUrl->invoke($module, "/legal/\xFF") !== '') {
	failTest('Invalid UTF-8 must not be accepted in an additional-link URL.');
}

$moduleSource = file_get_contents(dirname(__DIR__) . '/Cookie.module.php');
$templateSource = file_get_contents(dirname(__DIR__) . '/templates/banner.php');
if(!str_contains($moduleSource, "'showConsentId' => (bool) \$this->show_consent_id,")) {
	failTest('Frontend consent ID is still coupled to consent logging.');
}
if(str_contains($templateSource, '$module->show_consent_id && $module->enable_logging')) {
	failTest('Consent ID markup is still coupled to consent logging.');
}
foreach(['extra_links', 'cookie_details', 'has_cookie_durations'] as $marker) {
	if(!str_contains($templateSource, $marker)) failTest("Banner template is missing {$marker} rendering.");
}

echo "services, per-cookie retention, additional links and consent ID: ok\n";
