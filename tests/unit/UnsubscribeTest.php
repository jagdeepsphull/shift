<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * The Unsubscribe link, and what an opt-out actually stops.
 *
 * Two halves, and they fail in opposite directions. The link half is the one
 * that goes wrong silently: a message sent with the placeholder still in it, or
 * with the block left in and no address behind it, reads as a working
 * unsubscribe and is not one - and the recipient's next move is the button that
 * reports the domain for spam. The opt-out half is the one that goes wrong
 * loudly, by suppressing a password reset somebody is standing at a login
 * screen waiting for.
 *
 * @internal
 */
final class UnsubscribeTest extends CIUnitTestCase
{
    private const URL = 'https://pickashift.ca/unsubscribe/deadbeef';

    protected function setUp(): void
    {
        parent::setUp();
        helper('common');
    }

    /** A `users` row carrying the columns the send path reads. */
    private function user(?string $unsubscribedAt = null, string $blocked = ''): object
    {
        return (object) [
            'u_id'              => 1,
            'u_email'           => 'someone@example.com',
            'u_email_blocked'   => $blocked,
            'u_unsubscribed_at' => $unsubscribedAt,
        ];
    }

    public function testTheLayoutLeavesABlockForTheSendToFillIn(): void
    {
        // Every body extends this one file, which is what makes the link
        // something a new e-mail template inherits rather than remembers.
        $html = view('emails/layout', ['title' => 'Anything', 'settings' => []]);

        $this->assertStringContainsString('<!--[unsubscribe]-->', $html);
        $this->assertStringContainsString('{{unsubscribe_url}}', $html);
        $this->assertStringContainsString('<!--[/unsubscribe]-->', $html);
    }

    public function testSendingFillsInTheAddressAndTakesTheMarkersOut(): void
    {
        $sent = apply_unsubscribe_link(
            view('emails/layout', ['title' => 'Anything', 'settings' => []]),
            self::URL
        );

        $this->assertStringContainsString(self::URL, $sent);

        // The markers are scaffolding for the send, not something to post to
        // somebody's inbox.
        $this->assertStringNotContainsString('<!--[unsubscribe]-->', $sent);
        $this->assertStringNotContainsString('<!--[/unsubscribe]-->', $sent);
        $this->assertStringNotContainsString('{{unsubscribe_url}}', $sent);
    }

    public function testAMessageToNobodyInParticularLosesTheBlockEntirely(): void
    {
        // The contact form landing on the administrator, the agency's copy of a
        // booking, `php spark email:test`. None is a list anybody is on, and an
        // Unsubscribe link on one would lead nowhere.
        $sent = apply_unsubscribe_link(
            view('emails/layout', ['title' => 'Anything', 'settings' => []]),
            ''
        );

        $this->assertStringNotContainsString('{{unsubscribe_url}}', $sent);
        $this->assertStringNotContainsString('Unsubscribe', $sent);
        $this->assertStringNotContainsString('<!--[unsubscribe]-->', $sent);

        // The rest of the footer is untouched - only the block goes.
        $this->assertStringContainsString('Terms', $sent);
    }

    public function testTheUrlIsEscapedForAnAttribute(): void
    {
        $sent = apply_unsubscribe_link('<a href="{{unsubscribe_url}}">x</a>', 'https://e.test/u/a"b');

        $this->assertStringNotContainsString('u/a"b', $sent);
        $this->assertStringContainsString('&quot;', $sent);
    }

    public function testAnUnsubscribedUserIsSentNoneOfTheOptionalEmails(): void
    {
        $user = $this->user('2026-08-24 09:00:00');

        foreach (config('AppSettings')->emailTypes as $type) {
            $this->assertFalse(
                userAllowsEmail($user, $type['template']),
                $type['template'] . ' was allowed to somebody who unsubscribed.'
            );
        }
    }

    public function testUnsubscribingDoesNotStopThePasswordResetTheyJustAskedFor(): void
    {
        // The reason the opt-out is scoped to `emailTypes` rather than to
        // everything. Front::forgot_password tells the visitor the link was
        // sent whether or not it was, so an account that could not be sent one
        // could never be recovered, against a screen insisting it had been.
        $user = $this->user('2026-08-24 09:00:00');

        $this->assertTrue(userAllowsEmail($user, 'reset-password'));

        // Same for the notice that a booking was cancelled: somebody who does
        // not get it turns up to work a shift that is not theirs.
        $this->assertTrue(userAllowsEmail($user, 'booking-cancelled'));
    }

    public function testASubscribedUserIsUnaffected(): void
    {
        $user = $this->user();

        $this->assertFalse(userHasUnsubscribed($user));
        $this->assertTrue(userAllowsEmail($user, 'welcome'));
    }

    public function testTheTwoOptOutsAreSeparate(): void
    {
        // Blocked on Manage Email but never unsubscribed: type 1 (welcome) is
        // off, and the rest still arrive.
        $blocked = $this->user(null, '1');

        $this->assertFalse(userHasUnsubscribed($blocked));
        $this->assertFalse(userAllowsEmail($blocked, 'welcome'));
        $this->assertTrue(userAllowsEmail($blocked, 'account-approved'));
    }
}
