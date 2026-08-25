<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Every property here is overridable from `.env` with an `email.` prefix
 * (`email.SMTPHost`, `email.SMTPUser`, ...), which is where the credentials
 * belong — this file is in version control and they are not.
 */
class Email extends BaseConfig
{
    public string $fromEmail  = 'donotreply@pickashift.ca';
    public string $fromName   = 'PickAShift';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'PickAShift';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     *
     * SMTP, always. Bare `mail()` sends straight from the web server, which
     * fails SPF/DKIM for pickashift.ca — booking mail then lands in spam or
     * is dropped with no bounce, and nobody finds out until an applicant says
     * they never heard back.
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port. 587 with `SMTPCrypto = 'tls'` is the submission port nearly
     * every provider wants; 465 means implicit SSL and `SMTPCrypto = ''`.
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds). 5s is optimistic for a remote relay on a
     * first connection; a timeout here shows the admin a failed send.
     */
    public int $SMTPTimeout = 30;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'. Every template under
     * `app/Views/emails/` is HTML.
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;
}
