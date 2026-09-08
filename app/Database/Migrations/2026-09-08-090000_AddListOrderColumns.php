<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The order the other seven agency-kept lists are shown in, chosen by the
 * agency - the same column `shift_for` got in AddShiftForOrder, for every other
 * list in the Masters block of the back-office sidebar.
 *
 * Same reasoning as that migration. Alphabetical, or "whatever order the rows
 * were typed in", is an accident rather than an order anybody wanted, and these
 * lists are read on both sides of the login: the registration dropdowns, the
 * store and shift forms, the Resources menu, the testimonials on the home page.
 * One column per table, set with the arrows on the back-office list, read
 * through the `*_ORDER` constants everywhere else.
 *
 * Two of them are positions within a group rather than down one list, because
 * that is how they are offered - a city is only ever picked after a province,
 * and a resources link belongs to one menu. Both are numbered from 1 inside
 * each group.
 *
 * INT rather than TINYINT throughout: 1,092 cities and 184 resources links are
 * already past what a TINYINT holds, and a positions column that silently stops
 * at 127 is a trap for whoever grows the shorter lists.
 *
 * The backfill is what each list already looks like where it is offered, so
 * nothing moves on the day this runs. Numbering from 1 leaves 0 meaning "never
 * placed", which is what a row inserted by hand carries; every `*_ORDER`
 * constant sorts on the name after the position, so such a row sits in its
 * alphabetical place rather than in an order nobody set.
 */
class AddListOrderColumns extends Migration
{
    /**
     * table => [column, what the rows are sorted by to number them, the column
     * they are numbered within (null when the list is numbered end to end)].
     */
    private const LISTS = [
        'province'           => ['p_order', 'p_id', 'p_name', null],
        'city'               => ['c_order', 'c_id', 'c_name', 'c_province'],
        'software_skills'    => ['ss_order', 'ss_id', 'ss_name', null],
        'store_service'      => ['st_order', 'st_id', 'st_service_name', null],
        'additional_details' => ['ad_order', 'ad_id', 'ad_name', null],
        'headermenu'         => ['m_order', 'm_id', 'm_name', 'm_parentid'],
        'testimonial'        => ['t_order', 't_id', 't_id', null],
    ];

    public function up()
    {
        foreach (self::LISTS as $table => [$column, $key, $sortBy, $groupBy]) {
            $this->forge->addColumn($table, [
                $column => [
                    'type'    => 'INT',
                    'null'    => false,
                    'default' => 0,
                    'comment' => 'Where this sits in the list. Set by the arrows in the back office.',
                ],
            ]);

            $this->backfill($table, $column, $key, $sortBy, $groupBy);
        }
    }

    public function down()
    {
        foreach (self::LISTS as $table => [$column]) {
            $this->forge->dropColumn($table, $column);
        }
    }

    /**
     * Number one table's rows from 1, in the order that list is shown in.
     *
     * Deactivated rows are numbered too. Status says whether a row may be
     * picked, not where it sits, and one switched back on later should return
     * to its place rather than to the top.
     */
    private function backfill(string $table, string $column, string $key, string $sortBy, ?string $groupBy): void
    {
        $builder = $this->db->table($table)->select($key);

        if ($groupBy !== null) {
            $builder->select($groupBy)->orderBy($groupBy, 'asc');
        }

        $rows = $builder->orderBy($sortBy, 'asc')->get()->getResult();

        // One counter per group, so each province's cities and each menu's
        // children start again at 1.
        $positions = [];

        foreach ($rows as $row) {
            $group = $groupBy === null ? 0 : (int) $row->{$groupBy};

            $positions[$group] = ($positions[$group] ?? 0) + 1;

            $this->db->table($table)
                ->where($key, $row->{$key})
                ->update([$column => $positions[$group]]);
        }
    }
}
