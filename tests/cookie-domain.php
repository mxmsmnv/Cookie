<?php namespace ProcessWire;

interface Module {}

class WireData {
	public $cookie_domain = '';
	private $wireApi;

	public function setWireApi($wireApi) {
		$this->wireApi = $wireApi;
	}

	public function wire() {
		return $this->wireApi;
	}
}

require dirname(__DIR__) . '/Cookie.module.php';

function checkDomain($configured, $host, $expected) {
	$module = new Cookie();
	$module->cookie_domain = $configured;
	$module->setWireApi((object) ['config' => (object) ['httpHost' => $host]]);
	$actual = $module->cookieDomain();
	if($actual !== $expected) {
		fwrite(STDERR, "cookieDomain({$configured}, {$host}): expected '{$expected}', got '{$actual}'\n");
		exit(1);
	}
}

checkDomain('', 'www.example.com', '');
checkDomain('.example.com', 'www.example.com', 'example.com');
checkDomain('EXAMPLE.COM', 'agenda.example.com', 'example.com');
checkDomain('www.example.com', 'www.example.com:8443', 'www.example.com');
checkDomain('other.example', 'www.example.com', '');
checkDomain('example.com; Secure', 'www.example.com', '');
checkDomain('localhost', 'localhost:8080', '');
checkDomain('example.com', '127.0.0.1:8080', '');

echo "cookie domain validation: ok\n";
