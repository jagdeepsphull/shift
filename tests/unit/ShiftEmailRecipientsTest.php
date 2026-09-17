<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Who a store is told about one of its shifts - both "your shift is live" and
 * the booking confirmation, which read the same choice.
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

    /**
     * A `users` row, as the send site holds one.
     *
     * `u_unsubscribed_at` is on it even when nobody has unsubscribed, and that
     * is load-bearing: a row that does not carry the column makes
     * `userHasUnsubscribed()` go and look the account up by id, which would
     * quietly turn these into database tests whose result depends on whatever
     * u_id 1 happens to be on the machine running them.
     */
    private function user(string $email, string $blocked = '', ?string $unsubscribedAt = null, string $fname = 'Given', string $lname = 'Family'): object
    {
        return (object) [
            'u_id'              => 1,
            'u_email'           => $email,
            'u_fname'           => $fname,
            'u_lname'           => $lname,
            'u_email_blocked'   => $blocked,
            'u_unsubscribed_at' => $unsubscribedAt,
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

    public function testSomebodyWhoUnsubscribedIsNotWrittenToThoughTheirBoxIsTicked(): void
    {
        // The whole point of the Unsubscribe link. The administrator ticked
        // Owner on the shift form - which is them saying who at the store to
        // tell, not the owner agreeing to be told - and the owner had already
        // taken themselves off the list from their inbox. The tick does not
        // outrank that.
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com', '', '2026-08-24 09:00:00'),
            $this->user('manager@example.com'),
            'owner,manager',
            self::FALLBACK
        );

        $this->assertSame(['manager@example.com', self::FALLBACK], $audience['to']);
        $this->assertSame(['owner'], $audience['missing']);
    }

    public function testUnsubscribingCoversEveryTypeNotJustTheOnesBlocked(): void
    {
        // `u_email_blocked` is empty on both of these - nothing was switched
        // off on Manage Email - so the only thing keeping the e-mail from them
        // is the opt-out itself.
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com', '', '2026-08-24 09:00:00'),
            $this->user('manager@example.com', '', '2026-08-24 09:30:00'),
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

    /**
     * The booking half of a shift's mail obeys the same tick boxes.
     *
     * This is the QC report: an owner unticked on the shift form was still
     * being sent "an applicant has been approved for your shift", because that
     * send site went straight to the employer's address without asking. No
     * fallback is passed for that message - the agency's copy is its
     * always-sent recipient - so an unticked store really is told nothing.
     */
    public function testUntickedSidesAreNotToldABookingEither(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com'),
            $this->user('manager@example.com'),
            '',
            '',
            'booking-employer'
        );

        $this->assertSame([], $audience['to']);
        $this->assertTrue($audience['fellBack']);
    }

    /**
     * Which e-mail is being sent decides which opt-out is read. An employer who
     * switched "your shift is live" off in Manage Email has not thereby asked
     * to stop hearing that their shift has been filled.
     */
    public function testTheOptOutCheckedIsTheOneForTheMessageBeingSent(): void
    {
        // 3 is shift-posted, 5 is booking-employer, per AppSettings::$emailTypes.
        $owner = $this->user('owner@example.com', '3');

        $posted = shiftPostedRecipients($owner, null, 'owner', '', 'shift-posted');
        $this->assertSame([], $posted['to']);
        $this->assertSame(['owner'], $posted['missing']);

        $booking = shiftPostedRecipients($owner, null, 'owner', '', 'booking-employer');
        $this->assertSame(['owner@example.com'], $booking['to']);
        $this->assertSame([], $booking['missing']);
    }

    /**
     * Each address comes back with the account behind it, which is what lets
     * the send sites greet a manager by their own name.
     *
     * QC found the three store e-mails building one body from the owner and
     * sending it to everyone, so a store's manager opened "Hello, <the owner>".
     * The fallback is the agency's own address and belongs to no account, so it
     * is deliberately absent - the callers fall back to the owner for it.
     */
    public function testEachAddressCarriesTheAccountBehindIt(): void
    {
        $owner   = $this->user('owner@example.com', '', null, 'Ben', 'Limbong');
        $manager = $this->user('manager@example.com', '', null, 'Arun', 'Mehta');

        $audience = shiftPostedRecipients($owner, $manager, 'owner,manager', self::FALLBACK);

        $this->assertSame('Ben', $audience['people']['owner@example.com']->u_fname);
        $this->assertSame('Arun', $audience['people']['manager@example.com']->u_fname);
        $this->assertArrayNotHasKey(self::FALLBACK, $audience['people']);
    }

    /**
     * A side that was not ticked is nobody's greeting either: the manager is
     * not in `people` when the shift says owner only, so nothing can address a
     * message to somebody it was never sent to.
     */
    public function testAnUntickedSideIsNotGreeted(): void
    {
        $audience = shiftPostedRecipients(
            $this->user('owner@example.com', '', null, 'Ben', 'Limbong'),
            $this->user('manager@example.com', '', null, 'Arun', 'Mehta'),
            'owner',
            self::FALLBACK
        );

        $this->assertSame(['owner@example.com', self::FALLBACK], $audience['to']);
        $this->assertArrayNotHasKey('manager@example.com', $audience['people']);
    }

    /**
     * One login on both sides of a small chain is one address, and so one
     * greeting - the name has to be the one belonging to the address that
     * survived the de-duplication, not a second entry shadowing it.
     */
    public function testOneLoginOnBothSidesIsGreetedOnce(): void
    {
        $both = $this->user('both@example.com', '', null, 'Sam', 'Okafor');

        $audience = shiftPostedRecipients($both, $both, 'owner,manager', self::FALLBACK);

        $this->assertSame(['both@example.com', self::FALLBACK], $audience['to']);
        $this->assertCount(1, $audience['people']);
        $this->assertSame('Sam', $audience['people']['both@example.com']->u_fname);
    }
}
