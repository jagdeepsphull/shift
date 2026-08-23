<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Generic data-access model, ported 1:1 from CI3 `application/models/Custom.php`.
 *
 * The method names and return shapes are deliberately unchanged (objects from
 * `result()`, arrays from `row_array()`, ...) so that the controllers and views
 * carried over from CodeIgniter 3 behave exactly as before.
 */
class CustomModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * The underlying connection, for the few places that used `$this->db`
     * directly inside a controller.
     */
    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function getSettings()
    {
        return $this->db->table('settings')->select('*')->get()->getResult();
    }

    public function insertData($table, $data)
    {
        return $this->db->table($table)->insert($data);
    }

    public function updateData($table, $data, $where)
    {
        $this->db->table($table)->where($where)->update($data);

        return $this->db->affectedRows() > 0;
    }

    /**
     * Flip a status column between 0 and `$onValue` for one row.
     *
     * Replaces the hand-built UPDATE statements the admin "change status"
     * actions used to run, which pasted a value straight from the URL into SQL.
     * The table and column names come from the controller, never from a request;
     * the id is bound.
     *
     * @param int|string $id
     */
    public function toggleStatus(string $table, string $column, string $pkColumn, $id, int $onValue = 1): bool
    {
        $this->db->table($table)
            ->set($column, "IF({$column} = {$onValue}, 0, {$onValue})", false)
            ->where($pkColumn, (int) $id)
            ->update();

        return $this->db->affectedRows() > 0;
    }

    public function get_data($table)
    {
        return $this->db->table($table)->get()->getResult();
    }

    /**
     * @param bool|null $escape false when `$order_by` is an expression rather
     *                          than a plain column name
     */
    public function get_data_order($table, $order_by, $order = 'asc', $escape = null)
    {
        return $this->db->table($table)->orderBy($order_by, $order, $escape)->get()->getResult();
    }

    public function get_where($table, $where)
    {
        return $this->db->table($table)->getWhere($where)->getResult();
    }

    public function get_where_row($table, $where)
    {
        return $this->db->table($table)->getWhere($where)->getRowArray();
    }

    public function get_where_count($table, $where)
    {
        return $this->db->table($table)->getWhere($where)->getNumRows();
    }

    /**
     * @param string    $order_by column, or a comma separated list of columns
     * @param string    $order    'asc' or 'desc' (CI3 always sorted descending)
     * @param bool|null $escape   false when `$order_by` is an expression rather
     *                            than a plain column name
     */
    public function get_where_order($table, $where, $order_by, $order = 'desc', $escape = null)
    {
        return $this->db->table($table)->orderBy($order_by, $order, $escape)->getWhere($where)->getResult();
    }

    /**
     * Run a query. Pass values as `$binds` with `?` placeholders rather than
     * concatenating them into the SQL.
     *
     * @param array|null $binds
     */
    public function query($query, $binds = null)
    {
        return $this->db->query($query, $binds)->getResult();
    }

    public function delete_where($tablename, $where)
    {
        $builder = $this->db->table($tablename);

        if ($where) {
            $builder->where($where);
        }

        return (bool) $builder->delete();
    }

    /**
     * Flexible fetch used by the login/registration flows.
     *
     * @param array $params conditions / returnType / limit / start
     */
    public function getRows($table, $params = [], $primarykey = '', $primaryval = '')
    {
        $builder = $this->db->table($table)->select('*');

        if (array_key_exists('conditions', $params)) {
            foreach ($params['conditions'] as $key => $val) {
                $builder->where($key, $val);
            }
        }

        if (array_key_exists('returnType', $params) && $params['returnType'] === 'count') {
            return $builder->countAllResults();
        }

        if (! empty($primarykey) || (isset($params['returnType']) && $params['returnType'] === 'single')) {
            if (! empty($primaryval)) {
                $builder->where($primarykey, $primaryval);
            }

            return $builder->get()->getRowArray();
        }

        if ($primarykey !== '') {
            $builder->orderBy($primarykey, 'desc');
        }

        if (array_key_exists('start', $params) && array_key_exists('limit', $params)) {
            $builder->limit($params['limit'], $params['start']);
        } elseif (! array_key_exists('start', $params) && array_key_exists('limit', $params)) {
            $builder->limit($params['limit']);
        }

        $query = $builder->get();

        return $query->getNumRows() > 0 ? $query->getResultArray() : false;
    }

    /**
     * Insert a row, stamping created/modified when the caller did not.
     *
     * @return false|int insert id, or false
     */
    public function insert($table, $data = [])
    {
        if (! empty($data)) {
            if (! array_key_exists('created', $data)) {
                $data['created'] = date('Y-m-d H:i:s');
            }
            if (! array_key_exists('modified', $data)) {
                $data['modified'] = date('Y-m-d H:i:s');
            }

            $insert = $this->db->table($table)->insert($data);

            return $insert ? $this->db->insertID() : false;
        }

        return false;
    }

    public function insert_data($tablename, $insert_data)
    {
        if ($this->db->table($tablename)->insert($insert_data)) {
            return $this->db->insertID();
        }

        return false;
    }

    /**
     * @param array $tables [['table' => 'users', 'column' => 'city_id'], ...]
     */
    public function check_dependencies($id, $tables)
    {
        foreach ($tables as $table) {
            $query = $this->db->table($table['table'])->where($table['column'], $id)->get();

            if ($query->getNumRows() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hash a password for storage. Accounts created before August 2026 hold an
     * MD5 digest; those are upgraded in place the next time the owner signs in
     * (see `passwordMatches()`), so both forms coexist during the changeover.
     */
    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Is `$plain` this user's password?
     *
     * Accepts either storage format and quietly re-saves the old ones as a
     * modern hash, so nobody has to reset anything.
     *
     * @param array|object $user a `users` row, needing at least u_id and u_pass
     */
    public function passwordMatches(string $plain, $user): bool
    {
        $stored = (string) (is_array($user) ? ($user['u_pass'] ?? '') : ($user->u_pass ?? ''));
        $userId = is_array($user) ? ($user['u_id'] ?? null) : ($user->u_id ?? null);

        if ($stored === '') {
            return false;
        }

        // Legacy MD5: 32 hex characters and nothing else.
        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            if (! hash_equals($stored, md5($plain))) {
                return false;
            }

            if ($userId !== null) {
                $this->update_password($userId, $this->hashPassword($plain));
            }

            return true;
        }

        if (! password_verify($plain, $stored)) {
            return false;
        }

        if ($userId !== null && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $this->update_password($userId, $this->hashPassword($plain));
        }

        return true;
    }

    /**
     * How many seconds this account is locked out for, or 0 if it is not.
     *
     * `users.u_login_attempt` and `u_login_attempt_dt` have been on the table
     * since the CI3 site and nothing has ever read them. They hold a run of
     * consecutive failures and when the last one was: once the run reaches
     * `loginMaxAttempts`, the account stops answering passwords at all until
     * `loginLockoutMinutes` have gone by since that last attempt.
     *
     * It counts failures per account rather than per address, which is the
     * right way round for this site - the same pharmacy signs in from a dozen
     * different places, and an attacker works through one list of passwords
     * against one address. The verification image already stops the crude
     * scripts; this is for the patient ones.
     *
     * @param array $user a `users` row
     */
    public function loginLockRemaining(array $user): int
    {
        $settings = config('AppSettings');
        $max      = (int) ($settings->loginMaxAttempts ?? 0);
        $minutes  = (int) ($settings->loginLockoutMinutes ?? 0);

        if ($max <= 0 || $minutes <= 0) {
            return 0;
        }

        if ((int) ($user['u_login_attempt'] ?? 0) < $max) {
            return 0;
        }

        $last = strtotime((string) ($user['u_login_attempt_dt'] ?? ''));

        // No usable timestamp means the counter cannot be aged out, and an
        // account nobody can ever sign into again is worse than one more guess.
        if ($last === false || $last <= 0) {
            return 0;
        }

        $window    = $minutes * 60;
        $remaining = ($last + $window) - time();

        if ($remaining <= 0) {
            return 0;
        }

        // Never more than one window, however far in the future the stored time
        // reads. `recordFailedLogin()` writes it with PHP's clock, on
        // `Config\App::$appTimezone`; a row stamped by anything using the
        // database server's `NOW()` - an old CI3 write, a hand-edit, a fixture -
        // is out by whatever the two clocks disagree by, and on a machine set
        // to another country that is hours. Uncapped, one such row locks the
        // account for most of a day and the message says so.
        return min($remaining, $window);
    }

    /**
     * Count one wrong password against the account.
     *
     * @param int|string $userId
     */
    public function recordFailedLogin($userId): void
    {
        $this->db->table('users')
            ->set('u_login_attempt', 'u_login_attempt + 1', false)
            ->set('u_login_attempt_dt', date('Y-m-d H:i:s'))
            ->where('u_id', (int) $userId)
            ->update();
    }

    /**
     * Clear the run of failures. Called on every successful sign-in, so a user
     * who mistypes twice and then gets it right starts from nothing again.
     *
     * @param int|string $userId
     */
    public function clearLoginAttempts($userId): void
    {
        $this->db->table('users')
            ->where('u_id', (int) $userId)
            ->where('u_login_attempt >', 0)
            ->update(['u_login_attempt' => 0]);
    }

    /**
     * Find an account by its login id (the e-mail address), case-insensitively.
     *
     * @param array $extra further conditions, e.g. ['u_usertype' => 0]
     *
     * @return array|null
     */
    public function findUserForLogin(string $userid, array $extra = [])
    {
        $builder = $this->db->table('users')
            ->select('*')
            ->where('LOWER(u_userid)', strtolower(trim($userid)));

        foreach ($extra as $key => $value) {
            $builder->where($key, $value);
        }

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }

    public function verify_password($user_id, $current_password)
    {
        $query = $this->db->table('users')->select('u_id, u_pass')->where('u_id', $user_id)->get();

        if ($query->getNumRows() === 1) {
            return $this->passwordMatches((string) $current_password, $query->getRow());
        }

        return false;
    }

    public function update_password($user_id, $new_password)
    {
        $this->db->table('users')->where('u_id', $user_id)->update(['u_pass' => $new_password]);
    }

    public function get_user_by_email($email)
    {
        return $this->db->table('users')->getWhere(['u_userid' => $email])->getRow();
    }

    public function update_reset_token($email, $token, $expiry)
    {
        $this->db->table('users')
            ->where('u_userid', $email)
            ->update(['reset_token' => $token, 'token_expiry' => $expiry]);
    }

    public function validate_reset_token($token)
    {
        return $this->db->table('users')
            ->where('reset_token', $token)
            ->where('token_expiry >=', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();
    }
}
