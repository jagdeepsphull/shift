<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Who "your shift is live" is sent to.
 *
 * The shift form asks which side of the store to tell - owner, manager, both or
 * neither - and this is the logic that turns that answer into addresses, plus
 * the configured address, which is on all of them. It is tested here rather
 * than through the back office because the cases that matter are the quiet
 * ones: a store with nobody managing it, a recipient who has opted out, and
 * neither box ticked. None of the three shows on a screen, and all three end
 * the same way if they are wrong - a shift goes live and nobody hears about it.
 *
 * @internal
 */
final class ShiftEmailRecipientsTest extends CIUnitTestCase
{
    private const FALLBACK = 'fallback@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        helper('common');
    }

    /** A `users` row, as the send site holds one. */
    private function user(string $email, string $blocked = ''): object
    {
        return (object) [
            'u_id'            => 1,
            'u_email'         => $email,
            'u_email_blocked' => $blocked,
        ];
    }

    public function testBothTickedReachesBothSides(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            $this->user('manager@example.com'),
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame(
            ['owner@example.com', 'manager@example.com', self::FALLBACK],
            $audience['to']
        );
        $this->assertFalse($audience['fellBack']);
        $this->assertSame([], $audience['missing']);
    }

    public function testOneTickedReachesOnlyThatSide(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            $this->user('manager@example.com'),
            'manager',
            self::FALLBACK
        );

        $this->assertSame(['manager@example.com', self::FALLBACK], $audience['to']);
        $this->assertFalse($audience['fellBack']);
    }

    public function testNeitherTickedGoesToTheConfiguredAddressAlone(): void
    {
        // The case the column exists for. Empty is an answer - "do not write to
        // the pharmacy" - and the answer to it is not silence.
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            $this->user('manager@example.com'),
            '',
            self::FALLBACK
        );

        $this->assertSame([self::FALLBACK], $audience['to']);
        $this->assertTrue($audience['fellBack']);
    }

    public function testAskingForAManagerAStoreDoesNotHaveFallsBack(): void
    {
        // Most stores have no manager account on them, so this is the ordinary
        // case rather than an edge one.
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            null,
            'manager',
            self::FALLBACK
        );

        $this->assertSame([self::FALLBACK], $audience['to']);
        $this->assertTrue($audience['fellBack']);
        $this->assertSame(['manager'], $audience['missing']);
    }

    public function testTheOtherSideStillGetsItWhenOneIsMissing(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            null,
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame(['owner@example.com', self::FALLBACK], $audience['to']);
        $this->assertFalse($audience['fellBack']);
        $this->assertSame(['manager'], $audience['missing']);
    }

    public function testAnOptedOutRecipientIsNotWrittenTo(): void
    {
        // 3 is shift-posted in AppSettings::$emailTypes. Manage Email switching
        // it off has to hold here as it does at every other send site.
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com', '3'),
            $this->user('manager@example.com'),
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame(['manager@example.com', self::FALLBACK], $audience['to']);
        $this->assertSame(['owner'], $audience['missing']);
    }

    public function testEverybodyOptedOutStillLeavesARecord(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com', '3'),
            $this->user('manager@example.com', '3'),
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame([self::FALLBACK], $audience['to']);
        $this->assertTrue($audience['fellBack']);
        $this->assertSame(['owner', 'manager'], $audience['missing']);
    }

    public function testOneLoginOnBothSidesIsWrittenToOnce(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('both@example.com'),
            $this->user('both@example.com'),
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame(['both@example.com', self::FALLBACK], $audience['to']);
    }

    public function testTheConfiguredAddressIsNotWrittenToTwice(): void
    {
        // A small chain can run the site's own address as the store's login.
        $audience = shiftPostedRecipients(
            $this->user(self::FALLBACK),
            null,
            'owner',
            self::FALLBACK
        );

        $this->assertSame([self::FALLBACK], $audience['to']);
        $this->assertFalse($audience['fellBack']);
    }

    public function testTheChoiceIsReadTheSameFromAFormAndFromTheColumn(): void
    {
        $this->assertSame(['owner', 'manager'], shiftEmailChoice(['owner', 'manager']));
        $this->assertSame(['owner', 'manager'], shiftEmailChoice('owner,manager'));
        $this->assertSame(['owner'], shiftEmailChoice(' OWNER '));
        $this->assertSame([], shiftEmailChoice(''));

        // Anything else posted by hand decides nothing.
        $this->assertSame(['owner'], shiftEmailChoice(['owner', 'applicant', 'admin@example.com']));

        // Always in the same order, whichever order the boxes arrive in, so the
        // stored string of a given choice is always the same string.
        $this->assertSame(['owner', 'manager'], shiftEmailChoice('manager,owner'));
    }
}
