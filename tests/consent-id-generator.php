<?php

declare(strict_types=1);

namespace ProcessWire {
    class WireData {}
    interface Module {}

    require_once dirname(__DIR__) . '/Cookie.module.php';

    final class CookieIdProbe extends Cookie {
        public function generateId(): string {
            return $this->newConsentId();
        }
    }

    $probe = new CookieIdProbe();
    $ids = [];
    for($i = 0; $i < 100; $i++) {
        $id = $probe->generateId();
        if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id)) {
            fwrite(STDERR, "Invalid RFC 4122 version 4 consent ID: {$id}\n");
            exit(1);
        }
        $ids[$id] = true;
    }

    if(count($ids) !== 100) {
        fwrite(STDERR, "Consent ID generator produced a duplicate.\n");
        exit(1);
    }

    echo "consent ID generator checks passed.\n";
}
