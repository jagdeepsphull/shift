<?php
/**
 * The page the Unsubscribe link in an e-mail lands on.
 *
 * Five states, one view: the link is bad, we are asking, they were already off
 * the list, they have just come off it, or they have just gone back on. Kept in
 * one file because they are one conversation, and splitting them puts the
 * wording of the same sentence in five places.
 *
 * Every button here is a POST. The opt-out must not be something a mail
 * client's link prefetch can perform by fetching the page - see the note on
 * Front::unsubscribe.
 *
 * @var string      $state    invalid|confirm|already|done|resubscribed
 * @var string|null $account  the address the token belongs to
 * @var string      $token
 */
$account = $account ?? '';
?>
<!-- Unsubscribe Section Start -->
<section id="unsubscribe" class="section-padding">
    <div class="container mt-3">
        <div class="row g-4 justify-content-center">
            <div class="col-md-8 border border-light p-5 bg-gray shadow rounded text-center">

                <?php if ($state === 'invalid') { ?>

                    <h3 class="mb-3">This link has expired</h3>
                    <p class="mb-4">We could not match this unsubscribe link to an account. It may have been
                        cut short by your e-mail program when the message was forwarded.</p>
                    <p class="mb-0">Write to us and we will take you off the list by hand:
                        <a href="mailto:<?= esc($settings[0]->s_email ?? 'team@pickashift.ca') ?>" class="theme-cl">
                            <?= esc($settings[0]->s_email ?? 'team@pickashift.ca') ?></a>
                    </p>

                <?php } elseif ($state === 'confirm') { ?>

                    <h3 class="mb-3">Unsubscribe from e-mails</h3>
                    <p class="mb-4">Stop sending e-mails to <strong><?= esc($account) ?></strong>?</p>

                    <form action="" method="post" class="mb-4">
                        <button type="submit" name="unsubscribeSubmit" value="1" class="btn btn-common theme-bg">
                            Yes, unsubscribe me
                        </button>
                    </form>

                    <p class="text-muted mb-0" style="font-size: 14px;">
                        You will still be sent the things you ask for directly &mdash; a password reset, and
                        notice that a shift you were booked on has been cancelled. Everything else stops.
                    </p>

                <?php } elseif ($state === 'already') { ?>

                    <h3 class="mb-3">You are already unsubscribed</h3>
                    <p class="mb-4"><strong><?= esc($account) ?></strong> is not being sent our e-mails.</p>

                    <form action="" method="post">
                        <button type="submit" name="resubscribeSubmit" value="1" class="btn btn-common theme-bg">
                            Start sending them again
                        </button>
                    </form>

                <?php } elseif ($state === 'done') { ?>

                    <h3 class="mb-3">You have been unsubscribed</h3>
                    <p class="mb-4">We will stop e-mailing <strong><?= esc($account) ?></strong>. Nothing else
                        about your account changes, and you can still sign in as usual.</p>

                    <form action="" method="post">
                        <button type="submit" name="resubscribeSubmit" value="1" class="wz-btn wz-btn-light">
                            Changed your mind? Re-subscribe
                        </button>
                    </form>

                <?php } else { ?>

                    <h3 class="mb-3">You are back on the list</h3>
                    <p class="mb-0">We will start e-mailing <strong><?= esc($account) ?></strong> again.</p>

                <?php } ?>

                <p class="mt-4 mb-0">
                    <a href="<?= base_url() ?>" class="theme-cl">Back to <?= esc($settings[0]->s_sitename ?? 'PickAShift') ?></a>
                </p>

            </div>
        </div>
    </div>
</section>
<!-- Unsubscribe Section End -->
