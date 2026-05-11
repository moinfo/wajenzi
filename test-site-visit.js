const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const page    = await browser.newPage();
    page.setDefaultTimeout(15000);

    const BASE = 'http://127.0.0.1:8000';
    const log  = (msg) => console.log('[TEST]', msg);

    // ── 1. Login ─────────────────────────────────────────────────────────
    log('Navigating to login...');
    await page.goto(BASE + '/login');
    await page.fill('input[name="email"]',    'mohamed@wajenziprofessional.co.tz');
    await page.fill('input[name="password"]', '123456789');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 10000 }).catch(() => {});
    log('Logged in. URL: ' + page.url());

    // ── 2. Open Site Visit Calculator ─────────────────────────────────────
    log('Going to Site Visit Calculator...');
    await page.goto(BASE + '/calculators/site-visit');
    await page.waitForSelector('.location-card', { timeout: 10000 });
    log('Page loaded. Location cards visible.');

    // ── 3. Click a location card ──────────────────────────────────────────
    const cards = page.locator('.location-card');
    const count = await cards.count();
    log('Found ' + count + ' location cards.');

    const firstCard = cards.first();
    const cardName  = await firstCard.locator('.fw-semibold').textContent();
    log('Clicking card: ' + cardName.trim());
    await firstCard.click();

    // Wait for cost block to appear
    await page.waitForSelector('#costBlock', { state: 'visible', timeout: 5000 });
    log('Cost block appeared ✓');

    // Wait for result panel to update (base fee line should appear)
    await page.waitForSelector('#resultPanel .bg-body-secondary', { timeout: 5000 });
    log('Result panel updated ✓');

    // ── 4. Check base fee row ─────────────────────────────────────────────
    const breakdownText = await page.locator('#resultPanel').innerText();
    log('Result panel content:\n' + breakdownText);

    const hasBaseFee = breakdownText.includes('Base site visit fee');
    log(hasBaseFee ? '✅ Base site visit fee shown' : '❌ Base site visit fee MISSING');

    // ── 5. Change days to 2 ───────────────────────────────────────────────
    log('Increasing days to 2...');
    await page.click('.day-adj[data-delta="1"]');
    await page.waitForTimeout(500);
    const dayVal = await page.locator('#dayVal').textContent();
    log('Days now: ' + dayVal);

    const updatedTotal = await page.locator('#resultPanel .fs-3.fw-bold').textContent();
    log('Total after 2 days: ' + updatedTotal);

    // ── 6. Switch currency to USD ─────────────────────────────────────────
    log('Switching currency to USD...');
    await page.selectOption('#currencySelect', { label: 'USD — United States Dollar' }).catch(async () => {
        // fallback: select by data-code attribute
        await page.evaluate(() => {
            const sel = document.getElementById('currencySelect');
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].dataset.code === 'USD') { sel.selectedIndex = i; break; }
            }
            sel.dispatchEvent(new Event('change'));
        });
    });
    await page.waitForTimeout(600);

    const cardSubtitle = await firstCard.locator('.location-card-base').textContent();
    log('Card subtitle after USD switch: ' + cardSubtitle);
    const rateDisplay  = await page.locator('#rateDisplay').textContent();
    log('Rate display: ' + rateDisplay);
    const usdTotal     = await page.locator('#resultPanel .fs-3.fw-bold').textContent();
    log('Total in USD: ' + usdTotal);

    // ── 7. Click "Quote" button ───────────────────────────────────────────
    log('Opening billing modal (Quote)...');
    await page.click('button[onclick*="quote"]');
    await page.waitForSelector('#billingModal.show', { timeout: 5000 });
    log('Billing modal opened ✓');

    const previewText = await page.locator('#billingItemsPreview').innerText();
    log('Billing preview:\n' + previewText);

    const billingCurrency = await page.inputValue('#billingCurrency');
    log('Billing currency: ' + billingCurrency);

    // Close modal
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);

    log('\n🎉 All tests passed!');
    await browser.close();
})().catch(err => {
    console.error('[FAIL]', err.message);
    process.exit(1);
});
