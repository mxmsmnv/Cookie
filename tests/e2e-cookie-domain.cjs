const assert = require("node:assert/strict");
const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");
const { chromium } = require("playwright");

const moduleRoot = path.resolve(__dirname, "..");
const cookieJs = fs.readFileSync(path.join(moduleRoot, "assets/cookie.js"));

function html(shared) {
	const config = {
		prefix: "pwcm",
		cookieName: "pwcm_consent",
		cookieDomain: shared ? "example.test" : "",
		version: 1,
		expireDays: 180,
		model: "optin",
		gpc: false,
		dnt: false,
		bots: false,
		autoShow: true,
		bodyClasses: true,
		observe: false,
		reloadOnRevoke: false,
		consentMode: true,
		consentModeMap: {
			statistics: ["analytics_storage"],
			marketing: ["ad_storage", "ad_user_data", "ad_personalization"]
		},
		logEndpoint: "",
		categories: [
			{ key: "necessary", label: "Necessary", required: true },
			{ key: "statistics", label: "Statistics", required: false },
			{ key: "marketing", label: "Marketing", required: false }
		],
		cookiesToClear: { statistics: ["_ga"] }
	};

	return `<!doctype html>
<html><body>
<div id="pwcm-root" class="pwcm-root" data-overlay="0">
	<div class="pwcm-overlay" hidden></div>
	<section class="pwcm-banner" hidden>
		<button data-action="accept-all">Accept all</button>
		<button data-action="reject">Reject all</button>
		<button data-action="prefs">Preferences</button>
	</section>
	<section class="pwcm-prefs" hidden>
		<button data-action="close">Close</button>
		<input type="checkbox" data-consent-cat="necessary" checked disabled>
		<input type="checkbox" data-consent-cat="statistics">
		<input type="checkbox" data-consent-cat="marketing">
		<button data-action="save">Save</button>
	</section>
	<div class="pwcm-toast" hidden></div>
	<button class="pwcm-fab" data-action="prefs" hidden>Preferences</button>
	<template id="pwcm-ph-tpl"><div class="pwcm-ph"><p class="pwcm-ph-msg"></p></div></template>
</div>
<script type="text/plain" data-consent="statistics">window.__statisticsRuns = (window.__statisticsRuns || 0) + 1;</script>
<script>window.pwcmConfig=${JSON.stringify(config)};</script>
<script src="/cookie.js"></script>
</body></html>`;
}

async function main() {
	const server = http.createServer((request, response) => {
		if(request.url === "/cookie.js") {
			response.writeHead(200, { "content-type": "text/javascript; charset=utf-8" });
			response.end(cookieJs);
			return;
		}
		const url = new URL(request.url, "http://example.test");
		response.writeHead(200, { "content-type": "text/html; charset=utf-8" });
		response.end(html(url.searchParams.get("shared") === "1"));
	});

	await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
	const port = server.address().port;
	const mainUrl = `http://main.example.test:${port}/?shared=1`;
	const siblingUrl = `http://agenda.example.test:${port}/?shared=1`;
	const hostOnlyMainUrl = `http://main.example.test:${port}/`;
	const hostOnlySiblingUrl = `http://agenda.example.test:${port}/`;
	let browser;

	try {
		browser = await chromium.launch({
			headless: true,
			args: [
				"--host-resolver-rules=MAP main.example.test 127.0.0.1, MAP agenda.example.test 127.0.0.1",
				"--no-proxy-server"
			]
		});

		const shared = await browser.newContext();
		const page = await shared.newPage();
		await page.goto(mainUrl);
		assert.equal(await page.evaluate(() => window.__statisticsRuns || 0), 0);
		assert.equal(await page.locator(".pwcm-banner").isVisible(), true);
		await page.locator('[data-action="accept-all"]').click();
		assert.equal(await page.evaluate(() => window.__statisticsRuns), 1);
		let cookies = await shared.cookies();
		let consentCookies = cookies.filter((cookie) => cookie.name === "pwcm_consent");
		assert.equal(consentCookies.length, 1);
		assert.equal(consentCookies[0].domain.replace(/^\./, ""), "example.test");
		assert.equal(consentCookies[0].sameSite, "Lax");

		await page.goto(siblingUrl);
		assert.equal(await page.evaluate(() => window.pwCookie.hasConsent("statistics")), true);
		assert.equal(await page.evaluate(() => document.body.classList.contains("consent-statistics")), true);
		assert.equal(await page.evaluate(() => window.__statisticsRuns), 1);
		assert.equal(await page.locator(".pwcm-banner").isHidden(), true);
		assert.equal(await page.evaluate(() => window.dataLayer.some((entry) =>
			entry[0] === "consent" && entry[1] === "update" && entry[2].analytics_storage === "granted"
		)), true);

		// A stale host-only value and the shared value must both be removed by reset.
		await shared.addCookies([{
			name: "pwcm_consent",
			value: encodeURIComponent(JSON.stringify({ v: 1, t: Date.now(), g: { necessary: true } })),
			domain: "agenda.example.test",
			path: "/",
			sameSite: "Lax"
		}]);
		assert.equal((await shared.cookies()).filter((cookie) => cookie.name === "pwcm_consent").length, 2);
		await page.evaluate(() => window.pwCookie.reset());
		assert.equal((await shared.cookies()).filter((cookie) => cookie.name === "pwcm_consent").length, 0);
		await shared.close();

		const isolated = await browser.newContext();
		const isolatedPage = await isolated.newPage();
		await isolatedPage.goto(hostOnlyMainUrl);
		await isolatedPage.locator('[data-action="accept-all"]').click();
		cookies = await isolated.cookies();
		consentCookies = cookies.filter((cookie) => cookie.name === "pwcm_consent");
		assert.equal(consentCookies.length, 1);
		assert.equal(consentCookies[0].domain, "main.example.test");
		await isolatedPage.goto(hostOnlySiblingUrl);
		assert.equal(await isolatedPage.evaluate(() => window.pwCookie.hasConsent("statistics")), false);
		assert.equal(await isolatedPage.locator(".pwcm-banner").isVisible(), true);
		await isolated.close();

		const rejected = await browser.newContext();
		const rejectedPage = await rejected.newPage();
		await rejectedPage.goto(hostOnlyMainUrl);
		await rejectedPage.locator('[data-action="reject"]').click();
		assert.equal(await rejectedPage.evaluate(() => window.pwCookie.hasConsent("necessary")), true);
		assert.equal(await rejectedPage.evaluate(() => window.pwCookie.hasConsent("statistics")), false);
		assert.equal(await rejectedPage.evaluate(() => window.pwCookie.hasConsent("marketing")), false);
		assert.equal(await rejectedPage.evaluate(() => window.__statisticsRuns || 0), 0);
		await rejectedPage.locator(".pwcm-banner").waitFor({ state: "hidden" });
		assert.equal(await rejectedPage.evaluate(() => window.dataLayer.some((entry) =>
			entry[0] === "consent" && entry[1] === "update" &&
			entry[2].analytics_storage === "denied" && entry[2].ad_storage === "denied"
		)), true);
		await rejected.close();

		const preferences = await browser.newContext();
		const preferencesPage = await preferences.newPage();
		await preferencesPage.goto(hostOnlyMainUrl);
		await preferencesPage.evaluate(() => {
			window.__saveEvents = [];
			document.addEventListener("pwcm:save", (event) => window.__saveEvents.push(event.detail));
			document.cookie = "_ga=test;path=/;SameSite=Lax";
		});
		await preferencesPage.locator('.pwcm-banner [data-action="prefs"]').click();
		await preferencesPage.locator('[data-consent-cat="statistics"]').check();
		await preferencesPage.locator('.pwcm-prefs [data-action="save"]').click();
		assert.equal(await preferencesPage.evaluate(() => window.pwCookie.hasConsent("statistics")), true);
		assert.equal(await preferencesPage.evaluate(() => window.pwCookie.hasConsent("marketing")), false);
		assert.equal(await preferencesPage.evaluate(() => window.__statisticsRuns), 1);
		assert.equal(await preferencesPage.evaluate(() => window.__saveEvents.length), 1);
		assert.equal(await preferencesPage.evaluate(() => window.dataLayer.some((entry) =>
			entry[0] === "consent" && entry[1] === "update" &&
			entry[2].analytics_storage === "granted" && entry[2].ad_storage === "denied"
		)), true);
		await preferencesPage.reload();
		assert.equal(await preferencesPage.evaluate(() => window.pwCookie.hasConsent("statistics")), true);
		assert.equal(await preferencesPage.locator(".pwcm-banner").isHidden(), true);
		await preferencesPage.evaluate(() => window.pwCookie.revoke("statistics"));
		assert.equal(await preferencesPage.evaluate(() => window.pwCookie.hasConsent("statistics")), false);
		assert.equal(await preferencesPage.evaluate(() => document.cookie.includes("_ga=")), false);
		await preferences.close();

		const migration = await browser.newContext();
		await migration.addCookies([{
			name: "pwcm_consent",
			value: encodeURIComponent(JSON.stringify({
				v: 1,
				t: Date.now(),
				g: { necessary: true, statistics: false, marketing: false }
			})),
			domain: "main.example.test",
			path: "/",
			sameSite: "Lax"
		}]);
		const migrationPage = await migration.newPage();
		await migrationPage.goto(mainUrl);
		await migrationPage.evaluate(() => window.pwCookie.allow("statistics"));
		consentCookies = (await migration.cookies()).filter((cookie) => cookie.name === "pwcm_consent");
		assert.equal(consentCookies.length, 1);
		assert.equal(consentCookies[0].domain.replace(/^\./, ""), "example.test");
		await migration.close();

		console.log("cookie consent browser E2E: ok");
	} finally {
		if(browser) await browser.close();
		await new Promise((resolve) => server.close(resolve));
	}
}

main().catch((error) => {
	console.error(error);
	process.exitCode = 1;
});
