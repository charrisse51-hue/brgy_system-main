// Run after exporting fixtures with ResponsiveNavigationTest and building assets.
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { pathToFileURL } from 'node:url';

const { chromium } = await import(process.env.UI_PLAYWRIGHT_MODULE ? pathToFileURL(process.env.UI_PLAYWRIGHT_MODULE).href : 'playwright');
const fixtureDirectory = process.env.UI_FIXTURE_DIR || path.join(os.tmpdir(), 'barangay-ui-fixtures');
const publicDirectory = path.resolve('public');
const pages = ['admin/dashboard', 'admin/residents', 'admin/emergency', 'admin/incidents', 'admin/activity-logs', 'admin/summary-reports', 'admin/settings', 'admin/users', 'staff/staffdashboard', 'staff/disaster', 'staff/emergency', 'staff/residents', 'staff/summary-reports', 'resident/dashboard', 'resident/profile', 'resident/alerts'];
const forms = ['login', 'admin/residents/create', 'admin/incidents/create', 'admin/emergency/create', 'staff/residents/create', 'staff/disaster/create', 'staff/emergency/create', 'resident/emergency/create'];
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const serveFixture = async route => {
    const url = new URL(route.request().url());
    if (url.hostname !== 'barangay.test' && url.hostname !== 'localhost') return route.abort();
    const pathname = decodeURIComponent(url.pathname).replace(/^\//, '');
    const fixture = pages.includes(pathname) || forms.includes(pathname);
    const filename = fixture ? path.join(fixtureDirectory, pathname.replaceAll('/', '_') + '.html') : path.resolve(publicDirectory, pathname);
    if (!fixture && !filename.startsWith(publicDirectory + path.sep)) return route.abort();
    try {
        const bytes = await fs.readFile(filename);
        const body = fixture ? bytes.toString('utf8').replaceAll('http://127.0.0.1:8000', 'http://barangay.test').replaceAll('http://localhost', 'http://barangay.test') : bytes;
        const contentType = fixture ? 'text/html' : filename.endsWith('.css') ? 'text/css' : filename.endsWith('.js') ? 'application/javascript' : filename.endsWith('.png') ? 'image/png' : 'application/octet-stream';
        await route.fulfill({ body, contentType });
    } catch { await route.abort(); }
};
await page.route('**/*', serveFixture);

try {
    const assertGreenTheme = async () => {
        const colors = await page.evaluate(() => {
            const body = getComputedStyle(document.body);
            const active = document.querySelector('.sidebar nav a.active,.sidqbar nav a.sidebar-item-active');
            const icon = document.querySelector('.sidebar nav .lucide');
            return {primary:body.getPropertyValue('--ui-primary').trim(), secondary:body.getPropertyValue('--ui-secondary').trim(), dark:body.getPropertyValue('--ui-dark').trim(), light:body.getPropertyValue('--ui-light').trim(), pale:body.getPropertyValue('--ui-pale').trim(), background:body.getPropertyValue('--ui-background').trim(), text:body.getPropertyValue('--ui-text').trim(), active:active ? getComputedStyle(active).backgroundColor : null, icon:icon ? getComputedStyle(icon).color : null};
        });
        assert.equal(colors.primary, '#15803d'); assert.equal(colors.secondary, '#16a34a');
        assert.equal(colors.dark, '#166534'); assert.equal(colors.light, '#dcfce7');
        assert.equal(colors.pale, '#f0fdf4'); assert.equal(colors.background, '#f8faf9'); assert.equal(colors.text, '#172b22');
        if (colors.active) assert.equal(colors.active, 'rgb(220, 252, 231)');
        if (colors.icon) assert.equal(colors.icon, 'rgb(21, 128, 61)');
    };
    async function assertRecordTables(module, width) {
        const results = await page.locator('.ui-record-table').evaluateAll(tables => tables.map(table => {
            const region = table.parentElement;
            const rows = [...table.tBodies].flatMap(body => [...body.rows]);
            const cells = rows.flatMap(row => [...row.cells]);
            return { details:cells.map(cell => ({label:cell.dataset.label,display:getComputedStyle(cell).display,h:cell.clientHeight,scroll:cell.scrollHeight,w:cell.clientWidth,sw:cell.scrollWidth})), fits:region.scrollWidth <= region.clientWidth + 1, cards:region.clientWidth <= 760,
                rowDisplay:rows.length ? getComputedStyle(rows[0]).display : null,
                labeled:cells.every(cell => cell.colSpan > 1 || !!cell.dataset.label),
                bounded:cells.every(cell => getComputedStyle(cell).display !== 'none' && cell.getBoundingClientRect().right <= region.getBoundingClientRect().right + 1 && cell.scrollHeight <= cell.clientHeight + 1) };
        }));
        for (const result of results) {
            assert.ok(result.fits && result.bounded, `${module} at ${width}: no table scrolling or overflowing cells ${JSON.stringify(result)}`);
            assert.ok(result.labeled, `${module}: record fields labeled`);
            if (result.rowDisplay) assert.equal(result.rowDisplay, result.cards ? 'grid' : 'table-row', `${module}: container-aware record layout`);
        }
    }
    for (const module of (process.env.UI_FORMS_ONLY ? [] : process.env.UI_TABLE_PAGE ? [process.env.UI_TABLE_PAGE] : pages)) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('http://barangay.test/' + module);
        await page.waitForSelector('.sidebar .lucide');
        await assertGreenTheme();
        assert.equal(await page.locator('.ui-mobile-menu').count(), 1, module);
        for (const width of [1440, 1024, 1023, 768, 767, 390]) {
            await page.mouse.move(600, 880);
            await page.evaluate(() => document.activeElement?.blur());
            await page.setViewportSize({ width, height: 900 });
            // Let the intentional width/transform transition finish before measuring.
            await page.waitForTimeout(300);
            const result = await page.evaluate(() => {
                const sidebar = document.querySelector('.sidebar');
                const label = sidebar.querySelector('nav a span:not(.material-icons-round):not(.material-symbols-outlined)');
                const menu = document.querySelector('.ui-mobile-menu');
                const rect = sidebar.getBoundingClientRect();
                const header = document.querySelector('.topbar,.dashboard-header,.page-header');
                return { width:rect.width, right:rect.right, labelVisible:getComputedStyle(label).visibility !== 'hidden', menuVisible:menu.getClientRects().length > 0, inert:sidebar.inert, headerFits:header.getBoundingClientRect().right <= innerWidth + 1, overflow:document.documentElement.scrollWidth > innerWidth, chartsFit:[...document.querySelectorAll('.ui-chart')].every(el => el.getBoundingClientRect().right <= innerWidth + 1) };
            });
            assert.equal(result.width, width < 768 ? 250 : 80, `${module} at ${width}: sidebar width`);
            assert.equal(result.menuVisible, width < 768, `${module} at ${width}: mobile menu`);
            assert.equal(result.labelVisible, width < 768, `${module} at ${width}: labels`);
            assert.equal(result.inert, width < 768, `${module} at ${width}: focus access`);
            assert.ok(result.headerFits && !result.overflow, `${module} at ${width}: content fits`);
            await assertRecordTables(module, width);
            assert.ok(result.chartsFit, `${module} at ${width}: charts fit`);
            if (width < 768) assert.ok(result.right <= 1, `${module}: drawer hidden`);
            else {
                const content = page.locator('main,.main-area').first();
                const before = await content.boundingBox();
                await page.locator('.sidebar').hover({ position:{x:30,y:150} });
                await page.waitForTimeout(300);
                assert.equal((await page.locator('.sidebar').boundingBox()).width, 280, `${module}: hover expansion`);
                await assertRecordTables(module, width);
                const after = await content.boundingBox();
                assert.equal(Math.round(after.x - before.x), 200, `${module}: content shifts with sidebar`);
                assert.equal(Math.round(before.width - after.width), 200, `${module}: content resizes`);
                assert.ok(after.x >= (await page.locator('.sidebar').boundingBox()).width, `${module}: no overlap`);
                assert.equal(await page.locator('.sidebar nav a[title]').count(), 0, `${module}: no navigation tooltip`);
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth && [...document.querySelectorAll('.ui-chart')].every(el => el.getBoundingClientRect().right <= innerWidth + 1)), `${module}: expanded content fits`);
                assert.equal(await page.locator('.sidebar nav a span').last().evaluate(el => getComputedStyle(el).visibility), 'visible');
                if (module === 'admin/incidents') await page.screenshot({path:path.join(fixtureDirectory, `disaster-expanded-${width}.png`)});
                if (module === 'admin/dashboard' && width === 1440) await page.screenshot({ path:path.join(fixtureDirectory, 'dashboard-hover.png') });
                await page.mouse.move(width - 10, 880);
                await page.waitForTimeout(300);
                assert.equal((await page.locator('.sidebar').boundingBox()).width, 80, `${module}: hover collapse`);
            }
            if (module === 'admin/incidents' && width === 390) await page.screenshot({path:path.join(fixtureDirectory, 'disaster-mobile.png')});
            if (module === 'admin/dashboard' && [1440, 768, 390].includes(width)) {
                await page.screenshot({ path:path.join(fixtureDirectory, `dashboard-${width}.png`) });
            }
        }
        await page.locator('.ui-mobile-menu').click();
        await page.waitForTimeout(250);
        assert.equal(await page.locator('.ui-mobile-menu').getAttribute('aria-expanded'), 'true');
        assert.equal(await page.locator('.sidebar').evaluate(el => el.inert), false);
        if (module === 'admin/dashboard') await page.screenshot({ path:path.join(fixtureDirectory, 'dashboard-mobile-drawer.png') });
        await page.locator('.ui-mobile-menu').click();
        assert.equal(await page.locator('.ui-mobile-menu').getAttribute('aria-expanded'), 'false');
        await page.waitForTimeout(300);
        await page.locator('.ui-mobile-menu').click();
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('.ui-mobile-menu').getAttribute('aria-expanded'), 'false');
        await page.locator('.ui-mobile-menu').click();
        await page.setViewportSize({ width: 900, height: 900 });
        await page.waitForTimeout(250);
        assert.equal(await page.locator('body').evaluate(el => el.classList.contains('ui-nav-open')), false);
        await page.setViewportSize({ width: 390, height: 900 });
        await page.waitForTimeout(250);
        assert.equal(await page.locator('.sidebar').evaluate(el => el.inert), true);
        // Keyboard focus also reveals labels without requiring a toggle.
        await page.setViewportSize({ width:1440, height:900 });
        await page.locator('.sidebar nav a').first().focus();
        await page.waitForTimeout(300);
        assert.equal((await page.locator('.sidebar').boundingBox()).width, 280);
        await page.locator('main,.main-area').first().focus();
        console.log(`PASS ${module}: hover reflow, six widths, keyboard, drawer, resize reset`);
    }
    for (const form of forms) {
        await page.setViewportSize({width:1440,height:900});
        await page.goto('http://barangay.test/' + form);
        await page.waitForFunction(() => typeof window.uiConfirm === 'function');
        await assertGreenTheme();
        const primary = page.locator('.btn-primary,.btn-login,[type=submit].bg-primary').first();
        if (await primary.count()) assert.equal(await primary.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(21, 128, 61)');
        const field = page.locator('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):visible').filter({ visible: true }).last();
        if (await field.count() && await field.isVisible()) {
            await field.focus();
            await page.waitForTimeout(350);
            assert.equal(await field.evaluate(el => getComputedStyle(el).outlineColor), 'rgb(22, 163, 74)');
        }
        if (form === 'login') await page.screenshot({path:path.join(fixtureDirectory,'login-green.png')});
        console.log(`PASS ${form}: green palette, primary action, focus ring`);
    }
    const touch = await browser.newContext({ hasTouch:true, viewport:{width:1440,height:900} });
    const touchPage = await touch.newPage();
    await touchPage.route('**/*', serveFixture);
    await touchPage.goto('http://barangay.test/admin/dashboard');
    await touchPage.waitForSelector('.ui-mobile-menu', {state:'visible'});
    await touchPage.locator('.ui-mobile-menu').tap();
    await touchPage.waitForTimeout(300);
    assert.equal(await touchPage.locator('.sidebar').evaluate(el => el.inert), false);
    await touchPage.locator('.ui-backdrop').tap({position:{x:1300,y:500}});
    assert.equal(await touchPage.locator('.ui-mobile-menu').getAttribute('aria-expanded'), 'false');
    await touch.close();
    console.log('PASS wide touchscreen: drawer opens and closes without hover');
    assert.deepEqual(errors, [], 'No browser JavaScript errors');
} finally { await browser.close(); }
