const assert = require("node:assert/strict");
const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");
const { chromium } = require("playwright");

const cookieJs = fs.readFileSync(path.join(__dirname, "..", "assets", "cookie.js"));
const cookieCss = fs.readFileSync(path.join(__dirname, "..", "assets", "cookie.css"));
const logged = [];

function html({ logging = true } = {}) {
	const config = {
		prefix: "pwcm",
		cookieName: "pwcm_consent",
		cookieDomain: "",
		version: 1,
		expireDays: 180,
		model: "optin",
		gpc: true,
		gpcSignal: false,
		dnt: false,
		bots: false,
		messageTimeout: 5000,
		autoShow: true,
		geoConfigUrl: "/pwcm-geo/",
		showConsentId: true,
		bodyClasses: true,
		observe: false,
		reloadOnRevoke: false,
		consentMode: false,
		logEndpoint: logging ? "/pwcm-cl/" : "",
		categories: [
			{ key: "necessary", label: "Necessary", required: true },
			{ key: "statistics", label: "Statistics", required: false },
			{ key: "marketing", label: "Marketing", required: false }
		],
		cookiesToClear: {}
	};
	return `<!doctype html><html><head><link rel="stylesheet" href="/cookie.css"></head><body>
		<div id="pwcm-root" class="pwcm-root" data-layout="bar" data-position="bottom" data-overlay="1">
			<div class="pwcm-overlay" hidden></div>
			<section class="pwcm-banner" hidden>
				<button data-action="accept-all">Accept all</button>
				<button data-action="reject">Reject</button>
				<button data-action="prefs">Preferences</button>
			</section>
			<section class="pwcm-prefs" hidden>
				<input type="checkbox" data-consent-cat="necessary" checked disabled>
				<input type="checkbox" data-consent-cat="statistics">
				<input type="checkbox" data-consent-cat="marketing">
				<button data-action="save">Save</button>
				<p class="pwcm-consent-id" hidden><span>Consent ID:</span> <code data-consent-id></code></p>
			</section>
			<div class="pwcm-toast" role="status" data-saved-message="Saved" data-gpc-message="Opt out request honored via privacy signal" hidden>Saved</div>
			<button class="pwcm-fab" data-action="prefs" hidden>Preferences</button>
			<template id="pwcm-ph-tpl"><div class="pwcm-ph"></div></template>
		</div>
		<script>window.pwcmConfig=${JSON.stringify(config)};</script>
		<script src="/cookie.js"></script>
	</body></html>`;
}

async function main() {
	const server = http.createServer((request, response) => {
		if(request.url === "/cookie.css") {
			response.writeHead(200, { "content-type": "text/css" });
			response.end(cookieCss);
			return;
		}
		if(request.url === "/cookie.js") {
			response.writeHead(200, { "content-type": "text/javascript" });
			response.end(cookieJs);
			return;
		}
		if(request.url === "/pwcm-geo/") {
			response.writeHead(200, { "content-type": "application/json", "cache-control": "private, no-store" });
			response.end(JSON.stringify({ model: "optout", autoShow: true, gpc: request.headers["sec-gpc"] === "1" }));
			return;
		}
		if(request.url === "/pwcm-cl/" && request.method === "POST") {
			let body = "";
			request.on("data", chunk => { body += chunk; });
			request.on("end", () => {
				logged.push(JSON.parse(body));
				response.writeHead(200, { "content-type": "text/plain" });
				response.end("ok");
			});
			return;
		}
		if(request.url === "/logged-count") {
			response.writeHead(200, { "content-type": "application/json" });
			response.end(JSON.stringify({ count: logged.length }));
			return;
		}
		response.writeHead(200, { "content-type": "text/html" });
		const requestUrl = new URL(request.url, "http://127.0.0.1");
		response.end(html({ logging: requestUrl.searchParams.get("logging") !== "0" }));
	});

	await new Promise(resolve => server.listen(0, "127.0.0.1", resolve));
	const url = `http://127.0.0.1:${server.address().port}/`;
	let browser;
	try {
		browser = await chromium.launch({ headless: true });

		const normal = await browser.newContext({ viewport: { width: 834, height: 1112 } });
		const normalPage = await normal.newPage();
		await normalPage.goto(url);
		await normalPage.locator('.pwcm-banner.is-open').waitFor();
		const layers = await normalPage.evaluate(() => {
			const root = document.querySelector('.pwcm-root');
			const overlay = document.querySelector('.pwcm-overlay');
			const banner = document.querySelector('.pwcm-banner');
			const rect = banner.getBoundingClientRect();
			const stack = document.elementsFromPoint(rect.left + 10, rect.top + 10);
			return {
				rootPosition: getComputedStyle(root).position,
				rootIsolation: getComputedStyle(root).isolation,
				rootZIndex: getComputedStyle(root).zIndex,
				overlayZIndex: Number(getComputedStyle(overlay).zIndex),
				overlayBackdropFilter: getComputedStyle(overlay).backdropFilter,
				bannerZIndex: Number(getComputedStyle(banner).zIndex),
				bannerOpacity: getComputedStyle(banner).opacity,
				bannerAboveOverlay: stack.indexOf(banner) < stack.indexOf(overlay)
			};
		});
		assert.deepEqual(layers, {
			rootPosition: "relative",
			rootIsolation: "isolate",
			rootZIndex: "99990",
			overlayZIndex: 0,
			overlayBackdropFilter: "none",
			bannerZIndex: 2,
			bannerOpacity: "1",
			bannerAboveOverlay: true
		});
		await normalPage.locator('[data-action="accept-all"]').click();
		await normalPage.waitForFunction(() => window.pwCookie.getConsent().id !== null);
		const firstConsent = await normalPage.evaluate(() => window.pwCookie.getConsent());
		assert.match(firstConsent.id, /^[0-9a-f-]{36}$/);
		await normalPage.locator('.pwcm-fab').click();
		assert.equal(await normalPage.locator('[data-consent-id]').textContent(), firstConsent.id);
		assert.equal(await normalPage.locator('.pwcm-consent-id').isVisible(), true);
		await normalPage.waitForFunction(() => decodeURIComponent(document.cookie).includes('"i"'));
		await normalPage.waitForFunction(() => fetch('/logged-count').then(response => response.json()).then(data => data.count > 0));
		assert.equal(logged.at(-1).i, firstConsent.id);
		assert.equal(Object.hasOwn(logged.at(-1), "ip"), false);
		await normal.close();

		const withoutLogging = await browser.newContext();
		const withoutLoggingPage = await withoutLogging.newPage();
		const logCountBefore = logged.length;
		await withoutLoggingPage.goto(`${url}?logging=0`);
		await withoutLoggingPage.locator('[data-action="accept-all"]').click();
		await withoutLoggingPage.waitForFunction(() => window.pwCookie.getConsent().id !== null);
		const unloggedConsent = await withoutLoggingPage.evaluate(() => window.pwCookie.getConsent());
		assert.match(unloggedConsent.id, /^[0-9a-f-]{36}$/);
		await withoutLoggingPage.locator('.pwcm-fab').click();
		assert.equal(await withoutLoggingPage.locator('[data-consent-id]').textContent(), unloggedConsent.id);
		assert.equal(await withoutLoggingPage.locator('.pwcm-consent-id').isVisible(), true);
		await withoutLoggingPage.waitForTimeout(150);
		assert.equal(logged.length, logCountBefore);
		await withoutLogging.close();

		const headerGpc = await browser.newContext({ extraHTTPHeaders: { "Sec-GPC": "1" } });
		const headerPage = await headerGpc.newPage();
		await headerPage.goto(url);
		await headerPage.waitForFunction(() => window.pwCookie && window.pwCookie.getConsent().valid);
		assert.equal(await headerPage.locator('.pwcm-banner').isHidden(), true);
		assert.equal(await headerPage.locator('.pwcm-toast').textContent(), "Opt out request honored via privacy signal");
		assert.equal(await headerPage.evaluate(() => window.pwCookie.hasConsent("marketing")), false);
		await headerPage.evaluate(() => window.pwCookie.acceptAll());
		assert.equal(await headerPage.evaluate(() => window.pwCookie.hasConsent("marketing")), false);
		assert.equal((await headerGpc.cookies()).some(cookie => cookie.name === "pwcm_consent"), false);
		await headerGpc.close();

		const navigatorGpc = await browser.newContext();
		await navigatorGpc.addInitScript(() => {
			Object.defineProperty(navigator, "globalPrivacyControl", { configurable: true, value: true });
		});
		const navigatorPage = await navigatorGpc.newPage();
		await navigatorPage.goto(url);
		await navigatorPage.waitForFunction(() => window.pwCookie && window.pwCookie.getConsent().valid);
		assert.equal(await navigatorPage.evaluate(() => window.pwCookie.hasConsent("marketing")), false);
		assert.equal(await navigatorPage.locator('.pwcm-banner').isHidden(), true);
		assert.equal(await navigatorPage.locator('.pwcm-toast').textContent(), "Opt out request honored via privacy signal");
		await navigatorGpc.close();

		console.log("privacy features browser E2E: ok");
	} finally {
		if(browser) await browser.close();
		await new Promise(resolve => server.close(resolve));
	}
}

main().catch(error => {
	console.error(error);
	process.exitCode = 1;
});
