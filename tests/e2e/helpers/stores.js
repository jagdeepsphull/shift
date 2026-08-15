// @ts-check
/**
 * Fixtures for change request B4 - "one login for multiple stores".
 *
 * B4 is not built yet. Today one employer login *is* one store: the store name,
 * licence number and address are columns on the `users` row, and a shift copies
 * the province and city straight off that row when it is saved
 * (app/Controllers/Employer.php:145-146 on create, :207-208 on edit). There is
 * no location field on the shift form at all.
 *
 * The spec that uses this file is written against the shape B4 describes, so it
 * turns green the day the feature lands rather than having to be written then.
 * Until the schema exists every test skips with a message naming what is
 * missing - it does not fail, so the suite stays honest about what is built.
 *
 * ## The contract this file assumes
 *
 * | Thing                   | Expected                                   |
 * | ----------------------- | ------------------------------------------ |
 * | store table             | `store`                                    |
 * | owning login            | `store.u_id` -> `users.u_id`               |
 * | store columns           | `s_name`, `s_number`, `s_address`, `s_phone` |
 * | shift -> store          | `post_job.p_store_id`                      |
 * | manager -> store        | `users.u_store_id`                         |
 * | employer store screens  | `employer/stores`, `employer/add_store`    |
 * | store picker on a shift | `select[name="p_store_id"]`                |
 *
 * If B4 ships with different names, change the constants below - that is the
 * only edit the spec should need.
 */
const { query, scalar } = require('./db');

/** The store table and the columns the spec reads back. */
const STORE_TABLE = 'store';
const STORE_OWNER_COLUMN = 'u_id';
const STORE_COLUMNS = {
  name: 's_name',
  number: 's_number',
  address: 's_address',
  phone: 's_phone',
};

/** The store reference on a shift. */
const SHIFT_STORE_COLUMN = 'p_store_id';

/** Which of the corporate group's stores a manager runs. */
const MANAGER_STORE_COLUMN = 'u_store_id';

/** The store picker on public registration, and the endpoint that fills it. */
const REG_STORE_SELECT = '#u_store_id';
const REG_STORE_ENDPOINT = 'front/ajax_getstorelist';

/** Employer-facing screens and the store picker on the shift form. */
const STORE_LIST_URL = 'employer/stores';
const STORE_ADD_URL = 'employer/add_store';
const STORE_SELECT = `select[name="${SHIFT_STORE_COLUMN}"]`;

/**
 * Three stores under one login, with deliberately different addresses and
 * phone numbers so a screen that shows the wrong one is caught rather than
 * accidentally matching.
 */
const STORES = [
  {
    name: 'E2E Store North',
    number: 'E2E-101',
    address: '101 North Road, Unit A',
    phone: '4160000101',
  },
  {
    name: 'E2E Store Central',
    number: 'E2E-202',
    address: '202 Central Avenue, Suite 5',
    phone: '4160000202',
  },
  {
    name: 'E2E Store South',
    number: 'E2E-303',
    address: '303 South Street',
    phone: '4160000303',
  },
];

/**
 * Whether a table exists in the database under test.
 *
 * @param {string} table
 * @returns {boolean}
 */
function tableExists(table) {
  return scalar(`SHOW TABLES LIKE '${table}';`) !== '';
}

/**
 * Whether a column exists on a table (false when the table itself is missing).
 *
 * @param {string} table
 * @param {string} column
 * @returns {boolean}
 */
function columnExists(table, column) {
  if (!tableExists(table)) return false;
  return scalar(`SHOW COLUMNS FROM \`${table}\` LIKE '${column}';`) !== '';
}

/**
 * What is missing before the multi-store specs can run, as a sentence, or null
 * when the schema is there.
 *
 * Checked once and remembered: every test in the file asks, and each answer
 * costs a mysql process.
 *
 * @returns {string|null}
 */
let missingCache;
function multiStoreMissing() {
  if (missingCache !== undefined) return missingCache;

  const missing = [];

  if (!tableExists(STORE_TABLE)) {
    missing.push(`the \`${STORE_TABLE}\` table`);
  } else {
    for (const column of [STORE_OWNER_COLUMN, ...Object.values(STORE_COLUMNS)]) {
      if (!columnExists(STORE_TABLE, column)) missing.push(`\`${STORE_TABLE}.${column}\``);
    }
  }

  if (!columnExists('post_job', SHIFT_STORE_COLUMN)) {
    missing.push(`\`post_job.${SHIFT_STORE_COLUMN}\``);
  }

  // A manager picks one of the group's stores instead of describing their own,
  // and this column records which. Without it the registration specs would
  // fail on a form that has not been built rather than skipping.
  if (!columnExists('users', MANAGER_STORE_COLUMN)) {
    missing.push(`\`users.${MANAGER_STORE_COLUMN}\``);
  }

  missingCache =
    missing.length === 0
      ? null
      : `B4 (one login, multiple stores) is not built: ${missing.join(', ')} ${
          missing.length === 1 ? 'is' : 'are'
        } missing. See plan/change-requests.html#B4.`;

  return missingCache;
}

/**
 * Insert the three stores against one login, replacing any left behind.
 *
 * @param {number} employerId  `users.u_id` of the employer login
 * @returns {Array<{name: string, number: string, address: string, phone: string, id: number}>}
 */
function seedStores(employerId) {
  removeStores();

  // A real store sits in a province and a city, and the shift detail page
  // joins on both - a store with neither would make the shift invisible.
  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  const city = scalar('SELECT c_id FROM city WHERE c_status = 1 LIMIT 1;');

  return STORES.map((store) => {
    query(`
      INSERT INTO \`${STORE_TABLE}\`
        (\`${STORE_OWNER_COLUMN}\`, \`${STORE_COLUMNS.name}\`, \`${STORE_COLUMNS.number}\`,
         s_province, s_city,
         \`${STORE_COLUMNS.address}\`, \`${STORE_COLUMNS.phone}\`)
      VALUES
        (${employerId}, '${store.name}', '${store.number}', ${province}, ${city},
         '${store.address}', '${store.phone}');
    `);

    return {
      ...store,
      id: Number(
        scalar(
          `SELECT MAX(s_id) FROM \`${STORE_TABLE}\` WHERE \`${STORE_COLUMNS.name}\` = '${store.name}';`,
        ),
      ),
    };
  });
}

/** Remove the seeded stores. Safe to call when the table does not exist yet. */
function removeStores() {
  if (!tableExists(STORE_TABLE)) return;
  query(`DELETE FROM \`${STORE_TABLE}\` WHERE \`${STORE_COLUMNS.name}\` LIKE 'E2E Store %';`);
}

/** The approved multi-store owner the "pharmacy group" dropdown must offer. */
const GROUP = {
  user: 'e2e.group@example.com',
  name: 'E2E Pharmacy Group',
};

/**
 * Seed an approved multi-store owner, so the group dropdown on registration
 * has something to list. Without one the field is not rendered at all and a
 * test of it would pass by doing nothing.
 *
 * @returns {number} users.u_id
 */
function seedPharmacyGroup() {
  removePharmacyGroup();

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (1, 0, 1, '${GROUP.user}', 'Group', 'E2E', MD5('E2eTest@12345'), '${GROUP.name}',
       0, '', '', '', 0, 0,
       '', '', '0000000000', '${GROUP.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1',
       '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${GROUP.user}';`));
}

/** Remove the seeded group. Safe to call when it does not exist. */
function removePharmacyGroup() {
  query(`DELETE FROM users WHERE u_userid = '${GROUP.user}';`);
}

/**
 * The group's own locations, which a registering manager picks from.
 *
 * A separate name prefix from STORES on purpose: removeStores() is a blanket
 * `LIKE 'E2E Store %'` and seedStores() calls it, so sharing the prefix would
 * make whichever spec ran second delete the other's rows.
 */
const GROUP_STORES = [
  { name: 'E2E Group Store North', number: 'E2E-G01', address: '11 Group Road', phone: '4160000901' },
  { name: 'E2E Group Store Central', number: 'E2E-G02', address: '22 Group Avenue', phone: '4160000902' },
];

/**
 * Insert the group's stores, replacing any left behind.
 *
 * A manager can only register once their group has somewhere to work, so
 * without this the store dropdown is empty and the group is not even offered.
 *
 * @param {number} groupId `users.u_id` of the multi-store owner
 * @returns {Array<{name: string, number: string, address: string, phone: string, id: number}>}
 */
function seedGroupStores(groupId) {
  removeGroupStores();

  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  const city = scalar('SELECT c_id FROM city WHERE c_status = 1 LIMIT 1;');

  return GROUP_STORES.map((store) => {
    query(`
      INSERT INTO \`${STORE_TABLE}\`
        (\`${STORE_OWNER_COLUMN}\`, \`${STORE_COLUMNS.name}\`, \`${STORE_COLUMNS.number}\`,
         s_province, s_city,
         \`${STORE_COLUMNS.address}\`, s_pincode, \`${STORE_COLUMNS.phone}\`, s_status)
      VALUES
        (${groupId}, '${store.name}', '${store.number}', ${province}, ${city},
         '${store.address}', 'M5A 1A1', '${store.phone}', 1);
    `);

    return {
      ...store,
      id: Number(
        scalar(
          `SELECT MAX(s_id) FROM \`${STORE_TABLE}\` WHERE \`${STORE_COLUMNS.name}\` = '${store.name}';`,
        ),
      ),
    };
  });
}

/** Remove the seeded group stores. Safe to call when the table does not exist. */
function removeGroupStores() {
  if (!tableExists(STORE_TABLE)) return;
  query(`DELETE FROM \`${STORE_TABLE}\` WHERE \`${STORE_COLUMNS.name}\` LIKE 'E2E Group Store %';`);
}

/**
 * The store a shift is posted against, read back from the database.
 *
 * @param {string} shiftTitle
 * @returns {number}
 */
function storeIdOfShift(shiftTitle) {
  return Number(
    scalar(
      `SELECT \`${SHIFT_STORE_COLUMN}\` FROM post_job WHERE p_job_title = '${shiftTitle}' LIMIT 1;`,
    ) || 0,
  );
}

module.exports = {
  STORES,
  GROUP,
  GROUP_STORES,
  seedPharmacyGroup,
  removePharmacyGroup,
  seedGroupStores,
  removeGroupStores,
  STORE_TABLE,
  STORE_COLUMNS,
  SHIFT_STORE_COLUMN,
  MANAGER_STORE_COLUMN,
  REG_STORE_SELECT,
  REG_STORE_ENDPOINT,
  STORE_LIST_URL,
  STORE_ADD_URL,
  STORE_SELECT,
  multiStoreMissing,
  seedStores,
  removeStores,
  storeIdOfShift,
};
