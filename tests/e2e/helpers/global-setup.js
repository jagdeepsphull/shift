// @ts-check
/**
 * Seeds the throwaway administrator the suite logs in with, and removes any
 * leftovers from an interrupted previous run.
 */
const { query, count } = require('./db');
const { ADMIN } = require('./admin');

module.exports = async () => {
  // Clean up anything a previous run left behind.
  query("DELETE FROM city WHERE c_name LIKE 'E2E %';");
  query(`DELETE FROM users WHERE u_userid = '${ADMIN.user}';`);

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (0, 0, '${ADMIN.user}', 'E2E', 'Admin', MD5('${ADMIN.pass}'), '',
       0, '', '', '', 0, 0,
       '', '', '0000000000', '${ADMIN.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1',
       '', '1970-01-01 00:00:00');
  `);

  if (count('users', `u_userid = '${ADMIN.user}'`) !== 1) {
    throw new Error('Could not seed the e2e administrator account.');
  }
};
