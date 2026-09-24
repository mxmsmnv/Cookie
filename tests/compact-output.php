<?php namespace ProcessWire;

interface Module {}

class WireData {}

require dirname(__DIR__) . '/Cookie.module.php';

$module = new Cookie();
$reflection = new \ReflectionClass($module);

$compactHtml = $reflection->getMethod('compactHtmlFragment');
$html = "<div>\n\t<span data-label=\"A  B\">Text  stays</span>\n</div>";
$expectedHtml = '<div><span data-label="A  B">Text  stays</span></div>';
$actualHtml = $compactHtml->invoke($module, $html);
if($actualHtml !== $expectedHtml) {
	fwrite(STDERR, "Unexpected compact HTML:\n{$actualHtml}\n");
	exit(1);
}

$sourceCss = file_get_contents(dirname(__DIR__) . '/assets/cookie.css');
$moduleCss = file_get_contents(dirname(__DIR__) . '/assets/cookie.min.css');
if(str_contains($moduleCss, '/*') || str_contains($moduleCss, "\n")) {
	fwrite(STDERR, "Minified module CSS contains comments or line breaks\n");
	exit(1);
}
if(strlen($moduleCss) >= strlen($sourceCss) * 0.8) {
	fwrite(STDERR, "Minified module CSS did not shrink by at least 20%\n");
	exit(1);
}

printf("compact output: ok (%d -> %d CSS bytes)\n", strlen($sourceCss), strlen($moduleCss));
