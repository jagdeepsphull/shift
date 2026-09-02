<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/*
 | --------------------------------------------------------------------------
 | PickAShift application constants
 | --------------------------------------------------------------------------
 |
 | Flash-message markup carried over from the CodeIgniter 3
 | `application/config/constants.php`.
 */
defined('INSERTJOB') || define('INSERTJOB', '<div class="alert alert-success">Shift has been Applied successfully and pending for verification.</div>');
defined('INSERT')    || define('INSERT', '<div class="alert alert-success">Records has been Inserted successfully.</div>');
defined('UPDATE')    || define('UPDATE', '<div class="alert alert-success">Records has been Updated successfully.</div>');
defined('DELETE')    || define('DELETE', '<div class="alert alert-danger">Records has been Deleted successfully.</div>');
defined('WRONG')     || define('WRONG', '<div class="alert alert-warning">Somethig went wrong. Please try again.</div>');
defined('EMPTY_FORM') || define('EMPTY_FORM', '<div class="alert alert-danger">Please fill all the mandatory fields......</div>');

/*
 | Assets URL used by the `asset_url()` helper.
 */
defined('ASSETS_URL') || define('ASSETS_URL', 'assets/');

/*
 | Accepted characters in a personal name: letters in any alphabet, spaces, and
 | the punctuation real names contain - hyphens, apostrophes, brackets, periods.
 | Kept here so the browser rule and the server rule cannot drift apart.
 */
defined('NAME_PATTERN') || define('NAME_PATTERN', "/^[\p{L}\s'’\-().,]+$/u");

/*
 | A mobile number is exactly ten digits and nothing else - no spaces, brackets,
 | dashes or country code. Kept here, next to NAME_PATTERN and for the same
 | reason: the browser rule, the `maxlength` and the server rule are all driven
 | from this one length so they cannot drift apart.
 */
defined('PHONE_LENGTH')  || define('PHONE_LENGTH', 10);
defined('PHONE_PATTERN') || define('PHONE_PATTERN', '/^[0-9]{' . PHONE_LENGTH . '}$/');

/*
 | An hourly rate is dollars and cents: at least one digit, then at most two
 | after a single decimal point. That rejects the shapes a number box will
 | otherwise hand over or hold - ".334" with no dollars in front, "42.555" with
 | more cents than exist, and "3.4.3.4" with a point in every gap.
 |
 | The two decimals are the column: `post_job.p_hourly_rate` is DECIMAL(6,2), so
 | a third one is not refused by MySQL, it is rounded away silently - which is a
 | rate nobody typed being saved as if they had.
 |
 | The bounds are the ones the shift forms have always shown. They were only
 | ever `min` and `max` attributes, which is to say only ever a suggestion: the
 | server took whatever was posted. Kept here, next to PHONE_PATTERN and for the
 | same reason - the browser rule and the server rule are driven from the same
 | four values, so they cannot drift apart.
 */
defined('RATE_MIN')      || define('RATE_MIN', 10);
defined('RATE_MAX')      || define('RATE_MAX', 200);
defined('RATE_DECIMALS') || define('RATE_DECIMALS', 2);
defined('RATE_STEP')     || define('RATE_STEP', '0.01');
defined('RATE_PATTERN')  || define('RATE_PATTERN', '/^[0-9]{1,4}(\.[0-9]{1,' . RATE_DECIMALS . '})?$/');

/*
 | What the Shift Time box opens on when a shift is being added - a nine to six
 | day, which is what most of them are. Only a starting point: the picker is
 | still there, and whatever it is left showing is what gets posted.
 |
 | The shape matters as much as the hours. `p_shift_time` is free text, and the
 | daterangepicker on both add forms splits the box back apart on ' - ' in
 | 24-hour HH:mm to decide where to open - so anything else written here is a
 | value the picker cannot read, and it would quietly open on the current hour.
 |
 | Adding only. An edit form shows the hours the shift was saved with, and a
 | default there would stand in for hours somebody actually chose.
 */
defined('SHIFT_TIME_DEFAULT') || define('SHIFT_TIME_DEFAULT', '09:00 - 18:00');
