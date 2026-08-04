// @ts-check
/**
 * Removes everything the suite created, so the database is left as it was found.
 */
const { query } = require('./db');
const { ADMIN } = require('./admin');

module.exports = async () => {
  query("DELETE FROM city WHERE c_name LIKE 'E2E %';");
  query(`DELETE FROM users WHERE u_userid = '${ADMIN.user}';`);
};
