<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Ported from CI3 `application/models/Usersapi.php` (class `Userapi`).
 *
 * It was never autoloaded by the CI3 application; it is kept here so the REST
 * side of the site can be brought back without rewriting it.
 */
class UsersapiModel
{
    protected BaseConnection $db;
    protected string $userTbl = 'users';

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get rows from the users table.
     *
     * @param array $params conditions / id / start / limit / returnType
     */
    public function getRows($params = [])
    {
        $builder = $this->db->table($this->userTbl)->select('*');

        if (array_key_exists('conditions', $params)) {
            foreach ($params['conditions'] as $key => $value) {
                $builder->where($key, $value);
            }
        }

        if (array_key_exists('id', $params)) {
            return $builder->where('id', $params['id'])->get()->getRowArray();
        }

        if (array_key_exists('start', $params) && array_key_exists('limit', $params)) {
            $builder->limit($params['limit'], $params['start']);
        } elseif (! array_key_exists('start', $params) && array_key_exists('limit', $params)) {
            $builder->limit($params['limit']);
        }

        if (array_key_exists('returnType', $params) && $params['returnType'] === 'count') {
            return $builder->countAllResults();
        }

        $query = $builder->get();

        if (array_key_exists('returnType', $params) && $params['returnType'] === 'single') {
            return $query->getNumRows() > 0 ? $query->getRowArray() : false;
        }

        return $query->getNumRows() > 0 ? $query->getResultArray() : false;
    }

    /**
     * Insert user data.
     */
    public function insert($data)
    {
        if (! array_key_exists('created', $data)) {
            $data['created'] = date('Y-m-d H:i:s');
        }
        if (! array_key_exists('modified', $data)) {
            $data['modified'] = date('Y-m-d H:i:s');
        }

        $insert = $this->db->table($this->userTbl)->insert($data);

        return $insert ? $this->db->insertID() : false;
    }

    /**
     * Update user data.
     */
    public function update($data, $id)
    {
        if (! array_key_exists('modified', $data)) {
            $data['modified'] = date('Y-m-d H:i:s');
        }

        return (bool) $this->db->table($this->userTbl)->where('id', $id)->update($data);
    }

    /**
     * Delete user data.
     */
    public function delete($id)
    {
        return (bool) $this->db->table($this->userTbl)->where('id', $id)->delete();
    }
}
