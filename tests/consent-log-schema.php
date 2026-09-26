<?php

declare(strict_types=1);

$module = file_get_contents(__DIR__ . '/../Cookie.module.php');
$admin = file_get_contents(__DIR__ . '/../ProcessCookie.module.php');
if($module === false || $admin === false) {
	fwrite(STDERR, "Unable to read consent-log sources\n");
	exit(1);
}

$required = [
	'new log schema' => 'consent_id CHAR(36)',
	'client ID insert' => '(created, version, consent, consent_id, ua)',
	'legacy row selection' => "WHERE consent_id = '' ORDER BY id",
	'portable ID assignment' => "SET consent_id = :consent_id WHERE id = :id AND consent_id = ''",
	'RFC 4122 generator' => 'protected function newConsentId(): string',
	'IP hash removal' => "DROP COLUMN ip_hash",
];
foreach($required as $label => $needle) {
	if(!str_contains($module, $needle)) {
		fwrite(STDERR, "Missing consent-log migration marker: {$label}\n");
		exit(1);
	}
}

if(str_contains($module, 'UUID()')) {
	fwrite(STDERR, "Consent-log migration still depends on the MySQL UUID() function\n");
	exit(1);
}

if(str_contains($module, 'session->getIP()') || str_contains($module, "':ip' =>")) {
	fwrite(STDERR, "Consent logging still reads or stores the visitor IP\n");
	exit(1);
}
if(!str_contains($admin, "'consent_id'")) {
	fwrite(STDERR, "Admin log/export does not use consent IDs\n");
	exit(1);
}

echo "consent ID schema migration checks passed.\n";
