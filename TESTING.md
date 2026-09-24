# Testing Plan

## Scope

- Project: Cookie
- Classification: C — Process and user-facing module
- Test owner: module maintainer
- Supported ProcessWire versions: 3.0.244+
- Supported PHP versions: 8.2+
- Uninstall data policy: remove-generated-data (the consent-log table and module permission)

## Risk summary

Cookie controls whether optional scripts and embeds may execute, persists a
visitor's consent decision, exposes two public no-session endpoints, and can
store an optional consent log. Consent model, cookie scope, gating rules,
Google Consent Mode ordering, permissions, logging retention and uninstall
behavior are the highest-risk areas.

## Test commands

```bash
npm install
npm test
```

`npm test` runs JavaScript syntax checks, PHP source-level regressions and the
Chromium E2E suite. The browser test starts a loopback-only HTTP server, maps
`main.example.test` and `agenda.example.test` to loopback inside Chromium, and
makes no external calls. A second browser scenario emulates ProcessWire's
light, dark and automatic admin color schemes and verifies that the builder,
form fields and statistics follow them while frontend previews stay independent.
If Chromium is not present yet, install it once with
`npx playwright install chromium`.

## Test environment

- Development site: disposable static browser harness for frontend tests; a disposable ProcessWire installation is required for full release testing
- Database isolation: no database in the static harness; use a disposable database for ProcessWire boundary tests
- Administrator account: dedicated disposable-site administrator
- Member/editor accounts: not applicable to the public consent widget
- External service fakes: Google Consent Mode is verified through the local `dataLayer`; no Google requests are sent
- Fixture prefix: E2E

## ProcessWire boundary coverage

- [ ] Module discovery and metadata
- [ ] Fresh install
- [x] Configuration defaults represented in automated source-level checks
- [ ] Dependencies
- [ ] Public API and documented hooks
- [ ] Permissions and roles
- [ ] Consent-log data save and reload
- [ ] Upgrade from supported prior version
- [ ] Uninstall according to declared policy
- [ ] Reinstall

The unchecked checks require a disposable ProcessWire installation and are not
represented by the static frontend harness.

## Critical automated journeys

### Journey 1: Shared consent across trusted subdomains

- Role: anonymous visitor
- Starting state: no consent cookie
- Actions: accept on `main.example.test`, then visit `agenda.example.test`
- Expected UI result: the sibling application recognizes the saved choice
- Expected stored result: one `pwcm_consent` cookie scoped to `example.test`
- Access/security assertion: host-only mode does not expose the choice to the sibling
- Cleanup: browser context deletion

### Journey 2: Reset and migration

- Role: anonymous visitor
- Starting state: domain-scoped consent plus an optional legacy host-only cookie
- Actions: reset consent or save a new choice
- Expected UI result: reset returns the widget to an undecided state
- Expected stored result: reset removes both scopes; save replaces the legacy host-only cookie with one parent-domain cookie
- Access/security assertion: no duplicate same-name cookie remains on save
- Cleanup: browser context deletion

### Journey 3: Selective consent and gated execution

- Role: anonymous visitor
- Starting state: no consent cookie and a statistics script neutralized with `type="text/plain"`
- Actions: open preferences, grant statistics only, reload, then revoke statistics
- Expected UI result: the gated script runs only after consent and the banner stays hidden after reload
- Expected stored result: statistics is granted while marketing remains denied; a configured statistics cookie is removed on revoke
- Integration assertion: Consent Mode reports analytics granted and ad storage denied; `pwcm:save` is dispatched
- Cleanup: browser context deletion

### Journey 4: GPC and consent-record identity

- Role: anonymous visitor
- Starting state: no consent cookie; either `Sec-GPC: 1` or `navigator.globalPrivacyControl` is active
- Actions: load the widget, inspect the confirmation, then attempt to accept every category
- Expected UI result: the normal banner remains hidden and the GPC confirmation is announced
- Expected stored result: marketing remains refused and no override cookie or log record is written for the privacy signal
- Logging assertion: a normal explicit decision receives a UUID consent ID, stores it in the cookie, sends the same ID to the log endpoint and can display it in preferences without transmitting an IP
- Cleanup: browser context deletion

## Agent-led release scenarios

- Administrator: save an empty, valid parent-domain and invalid cookie-domain setting on a disposable ProcessWire site
- Anonymous: accept, customize, reject and reset on the main host and a sibling host
- Presentation: desktop and mobile banner smoke, keyboard navigation, dark mode, console and failed-network inspection
- Administrator presentation: Design Studio, consent log, statistics and policy screens in ProcessWire light, dark and automatic admin themes; the simulated frontend preview remains independent
- Upgrade: back up a legacy consent-log table, upgrade from 1.2.x, verify IDs are assigned to old rows and confirm the `ip_hash` column and `log_salt` setting are removed

## Failure paths

- Invalid, unrelated, localhost and IP-host cookie domains are covered by `tests/cookie-domain.php`.
- Full CSRF, permissions, install/upgrade/uninstall and database behavior require the disposable ProcessWire environment.
- Tests make no real analytics, email, payment, webhook or storage calls.

## Cleanup

The automated browser test closes every browser context, Chromium itself and
the loopback HTTP server in `finally` blocks. It creates no persistent records.
