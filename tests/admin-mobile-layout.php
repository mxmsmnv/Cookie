<?php

$css = file_get_contents(__DIR__ . '/../assets/admin/builder.css');

$checks = [
	'mobile breakpoint' => '@media (max-width: 600px)',
	'preview controls wrap' => ".pwb-tabs-row {\n\t\talign-items: stretch;\n\t\tflex-wrap: wrap;",
	'tabs occupy their own row' => ".pwb-tabs {\n\t\tflex: 1 1 100%;",
	'device toggles occupy their own row' => ".pwb-view-toggles {\n\t\tflex: 1 1 100%;",
	'builder descendants are width-bounded' => ".pwb,\n\t.pwb-controls,\n\t.pwb-panel,\n\t.pwb-stage,\n\t.pwb-browser,\n\t.pwb-viewport",
];

foreach($checks as $label => $needle) {
	if(strpos($css, $needle) === false) {
		fwrite(STDERR, "FAIL: {$label}\n");
		exit(1);
	}
}

echo "Cookie admin mobile layout contract passed\n";
