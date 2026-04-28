const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: false,
        slowMo: 200,
    });
    const page = await browser.newPage();
    page.setDefaultTimeout(20000);

    const waitForPage = async () => {
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(2000);
    };

    console.log('Navigating to login page...');
    await page.goto('http://localhost:8000/login');
    await waitForPage();

    await page.fill('input[name="email"]', 'mohamed@wajenziprofessional.co.tz');
    await page.fill('input[name="password"]', '123456789');
    await page.click('button[type="submit"]');
    await waitForPage();
    console.log('Logged in. Current URL:', page.url());
    await page.screenshot({ path: '/tmp/00-after-login.png' });

    // ----- Test 1: WhatsApp Marketing Campaign Modal -----
    console.log('\n=== TEST 1: WhatsApp Marketing — Campaign Modal ===');
    await page.goto('http://localhost:8000/whatsapp-marketing');
    await waitForPage();
    await page.screenshot({ path: '/tmp/wa-01-index.png' });

    // Activate campaigns tab
    const campTabLink = page.locator('a[href="#campaigns-tab"], a[data-target="#campaigns-tab"], a[href*="campaigns"]').first();
    if (await campTabLink.count()) {
        await campTabLink.click();
        await page.waitForTimeout(600);
    }
    await page.screenshot({ path: '/tmp/wa-02-campaigns-tab.png' });

    const newCampBtn = page.locator('button[data-target="#addCampaignModal"]').first();
    if (await newCampBtn.count()) {
        await newCampBtn.click();
        await page.waitForTimeout(800);
        await page.screenshot({ path: '/tmp/wa-03-campaign-modal.png' });
        console.log('Campaign modal opened — screenshot: wa-03-campaign-modal.png');

        const startInput = page.locator('.modal.show input.datepicker, .modal.show input[name="start_date"]').first();
        if (await startInput.count()) {
            await startInput.click();
            await page.waitForTimeout(1000);
            await page.screenshot({ path: '/tmp/wa-04-campaign-datepicker.png' });

            const dpDropdown = page.locator('.datepicker-dropdown');
            const dpVisible = await dpDropdown.isVisible().catch(() => false);
            console.log('Campaign Start Date datepicker visible:', dpVisible, dpVisible ? '✅ PASS' : '❌ FAIL');
        } else {
            console.log('Start date input not found in modal');
        }
        // Close modal
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
    } else {
        console.log('New Campaign button not found');
    }

    // ----- Test 2: WhatsApp Contact Add Modal -----
    console.log('\n=== TEST 2: WhatsApp Marketing — Contact Modal ===');
    // Navigate fresh and ensure contacts tab is active
    await page.goto('http://localhost:8000/whatsapp-marketing');
    await waitForPage();
    const addContactBtn = page.locator('button[data-target="#addContactModal"]').first();
    if (await addContactBtn.count()) {
        await addContactBtn.click();
        await page.waitForTimeout(800);
        await page.screenshot({ path: '/tmp/wa-05-contact-modal.png' });

        const followupInput = page.locator('.modal.show input.datepicker').first();
        if (await followupInput.count()) {
            await followupInput.click();
            await page.waitForTimeout(1000);
            await page.screenshot({ path: '/tmp/wa-06-contact-datepicker.png' });
            const dpVisible = await page.locator('.datepicker-dropdown').isVisible().catch(() => false);
            console.log('Contact Follow-up datepicker visible:', dpVisible, dpVisible ? '✅ PASS' : '❌ FAIL');
        }
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
    }

    // ----- Test 3: Field Marketing Session Modal -----
    console.log('\n=== TEST 3: Field Marketing — New Session Modal ===');
    await page.goto('http://localhost:8000/field-marketing');
    await waitForPage();
    await page.screenshot({ path: '/tmp/fm-01-index.png' });

    const newSessionBtn = page.locator('button[data-target="#newSessionModal"]').first();
    if (await newSessionBtn.count()) {
        await newSessionBtn.click();
        await page.waitForTimeout(800);
        await page.screenshot({ path: '/tmp/fm-02-session-modal.png' });

        const dateInput = page.locator('.modal.show input.datepicker').first();
        if (await dateInput.count()) {
            await dateInput.click();
            await page.waitForTimeout(1000);
            await page.screenshot({ path: '/tmp/fm-03-session-datepicker.png' });
            const dpVisible = await page.locator('.datepicker-dropdown').isVisible().catch(() => false);
            console.log('Session Date datepicker visible:', dpVisible, dpVisible ? '✅ PASS' : '❌ FAIL');
        }
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
    }

    console.log('\nAll screenshots saved to /tmp/');
    await browser.close();
})().catch(err => {
    console.error('Test error:', err.message);
    process.exit(1);
});
