<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Give the managers created from the back office the store details they run on.
 *
 * A manager picks one of their corporate group's stores rather than describing
 * one of their own, and the store's name, number and address are copied onto
 * their `users` row - a dozen screens, exports and e-mails read those columns
 * straight off the login rather than through the store: the employer listing,
 * the employer dropdown on both shift forms, the booking e-mail, the profile
 * page's required address.
 *
 * Public registration always did this. The back-office employer form did not,
 * so a manager added there was saved with no company name and no address at
 * all, and read as an empty row everywhere an employer is named. Both forms
 * apply `storeSnapshotForManager()` now; this repairs the accounts made before
 * they did.
 *
 * Only blank columns are filled, and only from the store the account is already
 * attached to - a manager whose details were corrected by hand keeps them, and
 * one with no store is left alone because there is nothing to copy.
 */
class BackfillManagerStoreSnapshot extends Migration
{
    public function up()
    {
        $this->db->query("
            UPDATE users u
              JOIN store s
                ON s.s_id = u.u_store_id
               SET u.u_comp_name  = CASE WHEN COALESCE(u.u_comp_name, '')  = '' THEN s.s_name     ELSE u.u_comp_name END,
                   u.u_licence_no = CASE WHEN COALESCE(u.u_licence_no, '') = '' THEN s.s_number   ELSE u.u_licence_no END,
                   u.u_address1   = CASE WHEN COALESCE(u.u_address1, '')   = '' THEN s.s_address  ELSE u.u_address1 END,
                   u.u_pincode    = CASE WHEN COALESCE(u.u_pincode, '')    = '' THEN s.s_pincode  ELSE u.u_pincode END,
                   u.u_l_provice  = CASE WHEN COALESCE(u.u_l_provice, 0)   = 0  THEN s.s_province ELSE u.u_l_provice END,
                   u.u_provice    = CASE WHEN COALESCE(u.u_provice, 0)     = 0  THEN s.s_province ELSE u.u_provice END,
                   u.u_city       = CASE WHEN COALESCE(u.u_city, 0)        = 0  THEN s.s_city     ELSE u.u_city END
             WHERE u.u_usertype = 1
               AND u.u_emp_role = 2
               AND COALESCE(u.u_store_id, 0) > 0
        ");
    }

    public function down()
    {
        // Blanking them again would only restore accounts that showed as empty
        // rows on every screen that names an employer, so this is deliberately
        // not reversible.
    }
}
