const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');

const cssPath = path.join(__dirname, '..', 'assets', 'admin', 'builder.css');

(async () => {
	const browser = await chromium.launch({ headless: true });
	try {
		const page = await browser.newPage();
		await page.setContent(`<!doctype html>
			<style>
				:root {
					color-scheme: light dark;
					--pw-text-color: light-dark(#111111, #ffffff);
					--pw-muted-color: light-dark(rgba(0,0,0,.55), rgba(255,255,255,.6));
					--pw-border-color: light-dark(rgba(0,0,0,.16), #444444);
					--pw-inputs-background: light-dark(#f8f8f8, #161616);
					--pw-blocks-background: light-dark(#ffffff, #000000);
				}
				body.light-theme { color-scheme: light; }
				body.dark-theme { color-scheme: dark; }
			</style>
			<div class="pwb">
				<div class="pwb-panel">
					<label class="pwb-field"><input type="text" value="theme test"></label>
				</div>
				<div class="pwb-browser"><div class="pwb-viewport"></div></div>
			</div>
			<div class="pwb-stats"><div class="pwb-stat-card"><span class="pwb-stat-num">42</span></div></div>
			<div class="pwb-policy"><div class="pwb-policy-preview">Frontend preview</div></div>`);
		await page.addStyleTag({ path: cssPath });

		const colors = async () => page.evaluate(() => {
			const value = selector => getComputedStyle(document.querySelector(selector));
			return {
				panel: value('.pwb-panel').backgroundColor,
				input: value('.pwb-field input').backgroundColor,
				text: value('.pwb-field input').color,
				browser: value('.pwb-browser').backgroundColor,
				stats: value('.pwb-stat-card').backgroundColor,
				policyPreview: value('.pwb-policy-preview').backgroundColor,
			};
		});

		await page.emulateMedia({ colorScheme: 'light' });
		assert.deepEqual(await colors(), {
			panel: 'rgb(255, 255, 255)',
			input: 'rgb(255, 255, 255)',
			text: 'rgb(0, 0, 0)',
			browser: 'rgb(255, 255, 255)',
			stats: 'rgb(255, 255, 255)',
			policyPreview: 'rgb(255, 255, 255)',
		});

		await page.emulateMedia({ colorScheme: 'dark' });
		assert.deepEqual(await colors(), {
			panel: 'rgb(0, 0, 0)',
			input: 'rgb(0, 0, 0)',
			text: 'rgb(255, 255, 255)',
			browser: 'rgb(255, 255, 255)',
			stats: 'rgb(0, 0, 0)',
			policyPreview: 'rgb(255, 255, 255)',
		});

		await page.evaluate(() => document.body.className = 'light-theme');
		assert.equal((await colors()).panel, 'rgb(255, 255, 255)');
		await page.emulateMedia({ colorScheme: 'light' });
		await page.evaluate(() => document.body.className = 'dark-theme');
		assert.equal((await colors()).panel, 'rgb(0, 0, 0)');

		console.log('ProcessWire admin theme browser E2E: ok');
	} finally {
		await browser.close();
	}
})().catch(error => {
	console.error(error);
	process.exit(1);
});
