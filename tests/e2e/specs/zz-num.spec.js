// @ts-check
const fs = require('fs');
const { test } = require('@playwright/test');
const { loginAsAdmin } = require('../helpers/admin');

test('numcheck', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/applicant');
  const [d] = await Promise.all([
    page.waitForEvent('download'),
    page.locator('.dt-buttons button', { hasText: 'Excel' }).click(),
  ]);
  const buf = fs.readFileSync(String(await d.path()));
  const xml = await page.evaluate(async (b64) => {
    const zip = await JSZip.loadAsync(b64, { base64: true });
    return zip.file('xl/worksheets/sheet1.xml').async('string');
  }, buf.toString('base64'));
  const row = xml.split('<row ').find((r) => r.includes('DiNardo')) || '';
  fs.writeFileSync('row.txt', row.slice(0, 1200));
  console.log('ROW>>', row.slice(0, 1200));
});
