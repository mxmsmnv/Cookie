<?php

declare(strict_types=1);

$css = file_get_contents(__DIR__ . '/../assets/admin/builder.css');
if($css === false) {
	fwrite(STDERR, "Unable to read builder.css\n");
	exit(1);
}

$checks = [
	'ProcessWire border variable' => '--pwb-border: var(--pw-border-color',
	'ProcessWire surface variable' => '--pwb-surface: var(--pw-blocks-background',
	'ProcessWire input surface variable' => '--pwb-surface-soft: var(--pw-inputs-background',
	'ProcessWire text variable' => '--pwb-text: var(--pw-text-color',
	'standalone admin pages use theme variables' => ".pwb-stats,\n.pwb-policy",
];

foreach($checks as $label => $needle) {
	if(strpos($css, $needle) === false) {
		fwrite(STDERR, "Missing dark-mode regression marker: {$label}\n");
		exit(1);
	}
}

$patterns = [
	'builder panel uses themed surface' => '/\.pwb-panel\s*\{[^}]*background:\s*var\(--pwb-surface\)/s',
	'admin inputs use themed surface' => '/\.pwb-field select,[^{]+\{[^}]*background:\s*var\(--pwb-surface\)/s',
	'statistics cards use themed surface' => '/\.pwb-stat-card\s*\{[^}]*background:\s*var\(--pwb-surface\)/s',
	'preview keeps its intentional light surface' => '/\.pwb-browser\s*\{[^}]*background:\s*#fff/s',
];

foreach($patterns as $label => $pattern) {
	if(!preg_match($pattern, $css)) {
		fwrite(STDERR, "Missing dark-mode regression rule: {$label}\n");
		exit(1);
	}
}

echo "Admin dark-mode CSS regression checks passed.\n";
