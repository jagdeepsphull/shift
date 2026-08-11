// @ts-check
/**
 * Minimal MySQL access for the e2e fixtures, shelling out to the mysql client
 * that ships with WAMP. Avoids adding a database driver dependency just to seed
 * and clean up a handful of rows.
 *
 * Override the binary with MYSQL_BIN, credentials with DB_USER / DB_PASS / DB_NAME.
 */
const { execFileSync } = require('child_process');

const MYSQL_BIN = process.env.MYSQL_BIN || 'C:/wamp64/bin/mysql/mysql9.1.0/bin/mysql.exe';
const DB_NAME = process.env.DB_NAME || 'pickashift';
const DB_USER = process.env.DB_USER || 'root';
const DB_PASS = process.env.DB_PASS || '';

function args() {
  const a = ['-u', DB_USER];
  if (DB_PASS !== '') a.push(`-p${DB_PASS}`);
  a.push('-N', '-B', DB_NAME);
  return a;
}

/**
 * Run SQL and return the raw stdout (tab separated, no column headers).
 *
 * @param {string} sql
 * @returns {string}
 */
function query(sql) {
  const out = execFileSync(MYSQL_BIN, [...args(), '-e', sql], {
    encoding: 'utf8',
    windowsHide: true,
  });

  // The client ends its lines with CRLF on Windows. Trimming the whole string
  // only clears the last one, so splitting a multi-row result would leave a
  // stray '\r' on the end of every value but the last.
  return out.replace(/\r\n/g, '\n').trim();
}

/**
 * Run SQL and return the first column of the first row ('' when empty).
 *
 * @param {string} sql
 * @returns {string}
 */
function scalar(sql) {
  const out = query(sql);
  if (out === '') return '';
  return out.split('\n')[0].split('\t')[0];
}

/**
 * Number of rows matching a condition.
 *
 * @param {string} table
 * @param {string} where
 * @returns {number}
 */
function count(table, where) {
  return Number(scalar(`SELECT COUNT(*) FROM ${table} WHERE ${where};`) || 0);
}

module.exports = { query, scalar, count, MYSQL_BIN, DB_NAME };
