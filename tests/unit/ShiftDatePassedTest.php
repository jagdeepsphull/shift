<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Whether a shift's date has gone by, which is what keeps it off the public
 * site.
 *
 * The home page, the shift page and the related-shifts sidebar all ask this,
 * two of them through the SQL twin. It is tested here rather than through the
 * browser because the case that matters is the boundary: a shift is worked on
 * a date, and the whole of that day it is still a shift somebody can turn up
 * for. Off by one day in either direction is either a shift hidden from the
 * pharmacist due that morning, or last month's shifts sitting on the front
 * page - and neither shows up on a screen until the day it happens.
 *
 * @internal
 */
final class ShiftDatePassedTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('common');
    }

    /** A `post_job` row, as the public queries return one. */
    private function shift(?string $dateStart): object
    {
        return (object) [
            'p_id'         => 1,
            'p_dates'      => $dateStart === null ? '' : date('d-m-Y', strtotime($dateStart)),
            'p_date_start' => $dateStart,
        ];
    }

    public function testYesterdayHasPassed(): void
    {
        $this->assertTrue(shiftDatePassed($this->shift(date('Y-m-d', strtotime('-1 day')))));
    }

    /**
     * The boundary. A shift is worked on its own date, so it stays readable
     * all day and drops off the site at midnight - the same line
     * `expire_past_shifts()` draws with `p_date_start < today`.
     */
    public function testTodayHasNotPassed(): void
    {
        $this->assertFalse(shiftDatePassed($this->shift(date('Y-m-d'))));
    }

    public function testTomorrowHasNotPassed(): void
    {
        $this->assertFalse(shiftDatePassed($this->shift(date('Y-m-d', strtotime('+1 day')))));
    }

    /**
     * A date the backfill could not read leaves the column NULL. Those rows
     * are left showing rather than silently dropped: there is no date to have
     * passed, and hiding them would take a shift off the site for a typo.
     */
    public function testAnUnreadableDateHasNotPassed(): void
    {
        $this->assertFalse(shiftDatePassed($this->shift(null)));
        $this->assertFalse(shiftDatePassed($this->shift('0000-00-00')));
    }

    /** A DATETIME rather than a DATE still compares on the day part alone. */
    public function testATimeOnTheDateIsIgnored(): void
    {
        $this->assertFalse(shiftDatePassed($this->shift(date('Y-m-d') . ' 23:59:59')));
    }

    public function testItReadsAnArrayRowToo(): void
    {
        $this->assertTrue(shiftDatePassed(['p_date_start' => date('Y-m-d', strtotime('-1 day'))]));
        $this->assertFalse(shiftDatePassed(['p_date_start' => date('Y-m-d')]));
    }

    /**
     * The SQL twin has to draw the line in the same place, or the shift page
     * and the list it was reached from disagree about one row.
     */
    public function testTheSqlTwinBindsPhpsDayAndAllowsNull(): void
    {
        [$sql, $binds] = shiftNotPassedSql();

        $this->assertSame('(p_date_start IS NULL OR p_date_start >= ?)', $sql);
        $this->assertSame([date('Y-m-d')], $binds);
    }

    public function testTheSqlTwinQualifiesTheColumnWithAnAlias(): void
    {
        [$sql] = shiftNotPassedSql('pj');

        $this->assertSame('(pj.p_date_start IS NULL OR pj.p_date_start >= ?)', $sql);
    }
}
