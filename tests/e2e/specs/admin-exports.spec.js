// @ts-check
/**
 * Every admin listing offers an Excel and a PDF download.
 *
 * The buttons come from DataTables Buttons, configured once for all of them in
 * app/Views/admin/footer.php. What is worth testing is not that the buttons
 * render but that the file which arrives is a real spreadsheet, holds the data
 * columns, and leaves out the two columns that mean nothing off-screen: the
 * hidden primary key and the column of Edit/Delete buttons.
 */
const fs = require('fs');
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { scalar } = require('../helpers/db');

/** Every screen whose table is wired to the shared DataTables init. */
const LISTINGS = [
  ['sadmin/applicant', 'Applicant'],
  ['sadmin/employer', 'Employer'],
  ['sadmin/applications', 'Application'],
  ['sadmin/postjobs', 'Shift'],
  ['sadmin/city', 'City'],
  ['sadmin/province', 'Province'],
  ['sadmin/hourly', 'Hourly'],
  ['sadmin/shift_for', 'Shift For'],
  ['sadmin/softwareskills', 'Software'],
  ['sadmin/storeservice', 'Service'],
  ['sadmin/resources', 'Resources'],
];

const excelButton = (page) => page.locator('.dt-buttons button', { hasText: 'Excel' });
const pdfButton = (page) => page.locator('.dt-buttons button', { hasText: 'PDF' });

/**
 * Click a button, wait for the download it triggers, and return its bytes.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} button
 * @returns {Promise<{name: string, buffer: Buffer}>}
 */
async function downloadFrom(page, button) {
  const [download] = await Promise.all([page.waitForEvent('download'), button.click()]);

  const path = await download.path();
  expect(path, 'the download should have been saved').toBeTruthy();

  return { name: download.suggestedFilename(), buffer: fs.readFileSync(String(path)) };
}

/**
 * The sheet XML inside an .xlsx, read with the JSZip the admin pages load.
 *
 * An .xlsx is a zip of XML. DataTables Buttons writes every cell as an
 * `inlineStr` straight into the worksheet rather than into a sharedStrings
 * part, so the whole visible text of the export is in this one entry.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Buffer} buffer
 * @returns {Promise<string>}
 */
async function zipEntry(page, buffer, name) {
  return page.evaluate(
    async ([base64, entryName]) => {
      // @ts-ignore - JSZip is loaded by the admin footer
      const zip = await JSZip.loadAsync(base64, { base64: true });
      const entry = zip.file(entryName);
      return entry ? entry.async('string') : '';
    },
    [buffer.toString('base64'), name],
  );
}

async function sheetXml(page, buffer) {
  const xml = await zipEntry(page, buffer, 'xl/worksheets/sheet1.xml');

  expect(xml, 'the spreadsheet should contain a worksheet').not.toBe('');

  return xml;
}

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

test('every listing offers both downloads', async ({ page }) => {
  for (const [path, label] of LISTINGS) {
    await page.goto(path);

    await expect(excelButton(page), `${label}: Excel button`).toHaveCount(1);
    await expect(pdfButton(page), `${label}: PDF button`).toHaveCount(1);

    await expectNoServerError(page);
  }
});

test('every listing downloads a real spreadsheet and a real PDF', async ({ page }) => {
  for (const [path, label] of LISTINGS) {
    await page.goto(path);

    const xlsx = await downloadFrom(page, excelButton(page));
    expect(xlsx.name, `${label}: spreadsheet extension`).toMatch(/\.xlsx$/);
    // An .xlsx is a zip, and every zip starts "PK".
    expect(xlsx.buffer.subarray(0, 2).toString('latin1'), `${label}: xlsx magic`).toBe('PK');

    const pdf = await downloadFrom(page, pdfButton(page));
    expect(pdf.name, `${label}: pdf extension`).toMatch(/\.pdf$/);
    expect(pdf.buffer.subarray(0, 5).toString('latin1'), `${label}: pdf magic`).toBe('%PDF-');
    expect(pdf.buffer.length, `${label}: pdf is not empty`).toBeGreaterThan(1000);
  }
});

test('the PDF is landscape, so a wide list fits across the page', async ({ page }) => {
  await page.goto('sadmin/employer');

  const { buffer } = await downloadFrom(page, pdfButton(page));

  // A4 landscape in PDF points is 841.89 x 595.28; portrait is the reverse.
  const box = buffer.toString('latin1').match(/MediaBox\s*\[\s*0\s+0\s+([\d.]+)\s+([\d.]+)\s*\]/);
  expect(box, 'the PDF should declare a page size').toBeTruthy();

  const [width, height] = [Number(box?.[1]), Number(box?.[2])];
  expect(width, 'landscape is wider than it is tall').toBeGreaterThan(height);
  expect(Math.round(width)).toBe(842);
});

test('a long screen name still makes a sheet Excel will open', async ({ page }) => {
  // The store list scoped to one employer reads "Manage Stores - <company>",
  // which is the longest heading the back office produces. It replaced the old
  // "Manage Owners (Individual Store)" here when that kind was removed; no
  // other screen still runs past the limit.
  const owner = scalar(`
    SELECT u.u_id FROM users u
     WHERE u.u_usertype = 1 AND CHAR_LENGTH(u.u_comp_name) > 20
       AND EXISTS (SELECT 1 FROM store s WHERE s.u_id = u.u_id)
     ORDER BY CHAR_LENGTH(u.u_comp_name) DESC LIMIT 1;
  `);
  test.skip(!owner, 'no employer with a long enough name and a store');

  await page.goto(`sadmin/stores?owner=${owner}`);

  const heading = (await page.locator('.content-header h1').first().innerText())
    .replace(/\s+/g, ' ').trim();

  expect(heading.length, 'this test is pointless unless the heading is over the limit')
    .toBeGreaterThan(31);

  const { buffer } = await downloadFrom(page, excelButton(page));
  const workbook = await zipEntry(page, buffer, 'xl/workbook.xml');

  const sheetName = workbook.match(/<sheet name="([^"]*)"/)?.[1] || '';

  // Past 31 characters Excel opens the file with a repair prompt instead of
  // the data. The characters Excel forbids outright go first, then the cut.
  expect(sheetName.length, 'Excel rejects a sheet name past 31 characters').toBeLessThanOrEqual(31);
  expect(sheetName).toBe(heading.replace(/[[\]*/\?:]/g, '').slice(0, 31));
});

test('the file is named after the screen, not the site', async ({ page }) => {
  await page.goto('sadmin/city');

  const { name } = await downloadFrom(page, excelButton(page));

  // "city List" -> "city-List-YYYY-MM-DD.xlsx". The site name would have made
  // every screen download the same file name.
  expect(name).toMatch(/^city-List-\d{4}-\d{2}-\d{2}\.xlsx$/);
});

test('the spreadsheet holds the data columns and drops the buttons column', async ({ page }) => {
  await page.goto('sadmin/applicant');

  const { buffer } = await downloadFrom(page, excelButton(page));
  const sheet = await sheetXml(page, buffer);

  for (const header of ['Applicant Name', 'Applicant Type', 'Licence No.', 'Email ID', 'Mobile No.', 'Status']) {
    expect(sheet, `spreadsheet keeps "${header}"`).toContain(header);
  }

  // The buttons column and the photo column are both empty off-screen.
  expect(sheet, 'spreadsheet drops the Action column').not.toContain('Action');
  expect(sheet, 'spreadsheet drops the photo column').not.toContain('Applicant Image');

  // Real rows, not just headings.
  expect(sheet).toContain('Pharmacist');

  // The headings must be row 1. Giving the Excel button a title would put a
  // merged screen-name cell above them, and Excel then stops treating the
  // sheet as a plain grid to sort, filter or re-import.
  const firstRow = sheet.split('<row ')[1] || '';
  expect(firstRow, 'row 1 is the heading row').toContain('Applicant Name');

  // The screen name rides on the sheet tab instead.
  const workbook = await zipEntry(page, buffer, 'xl/workbook.xml');
  expect(workbook).toContain('Manage Applicant');
});

test('a narrow window does not shrink the export', async ({ page }) => {
  // Responsive collapses columns by putting display:none on the <th>, which is
  // exactly what the ":visible" column selector would have keyed off. At 700px
  // several columns are collapsed on screen; the spreadsheet must be unchanged.
  await page.setViewportSize({ width: 700, height: 720 });
  await page.goto('sadmin/employer');

  // Confirm the premise: the browser really has collapsed columns away.
  await expect(page.locator('#example1 thead th').filter({ hasText: 'Email ID' })).toBeHidden();

  const { buffer } = await downloadFrom(page, excelButton(page));
  const sheet = await sheetXml(page, buffer);

  for (const header of ['Store Name', 'Store No.', 'Conatact Person', 'Email ID', 'Mobile No.', 'Status']) {
    expect(sheet, `narrow window keeps "${header}"`).toContain(header);
  }

  expect(sheet, 'narrow window still drops the Action column').not.toContain('Action');
});

test('the export covers every page of the list, not just the visible one', async ({ page }) => {
  await page.goto('sadmin/city');

  // The city list runs to a thousand-odd rows over a hundred pages.
  const total = Number(
    (await page.locator('#example1_info').textContent())?.replace(/,/g, '').match(/of (\d+) entries/)?.[1],
  );
  expect(total, 'the city list should be longer than one page').toBeGreaterThan(50);

  const { buffer } = await downloadFrom(page, excelButton(page));
  const sheet = await sheetXml(page, buffer);

  // One <row> per record plus the heading row, against the ten rows the
  // browser was showing.
  const rows = (sheet.match(/<row /g) || []).length;
  expect(rows, 'the spreadsheet should hold every page, not the visible ten').toBeGreaterThan(total - 5);
});
