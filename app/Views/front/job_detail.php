<?php
/**
 * Shift detail.
 *
 * The layout is the one the rest of the front end uses - cards on the page
 * surface, a facts grid inside them - rather than the legacy theme's panel,
 * which spent a screen and a half on eight short lines.
 *
 * The page is read by three kinds of visitor, and each is told a different
 * amount about the pharmacy:
 *
 *   signed out   role, town, date, time, software and the details of the work.
 *                No rate.
 *   signed in    the same, plus the hourly rate the applicant is paid and
 *                whatever the pharmacy wrote about the shift.
 *   booked       the same, plus which pharmacy it is: name, street address,
 *                directions, the note on finding it, its site and its phone.
 *
 * Which branch it is is the one thing worth withholding - it is what lets
 * somebody arrange the shift around the platform - so it waits for a confirmed
 * booking rather than for an account. The town is always shown: without it
 * nobody can judge whether the shift is worth applying for at all.
 */
$wz_shift = $jobdetail[0];
$wz_role  = getShiftForName($wz_shift->p_shift_for);

$wz_signedIn = ! empty($isUserLoggedIn);

// `$is_booked_viewer` is set by the controller from this reader's own
// application, approved - so it is false for everybody else, signed in or not.
$wz_booked = ! empty($is_booked_viewer);

// Street address of the store the shift is at, then city and province. Without
// the address the line still names the town, as it always has.
$wz_address = ($wz_booked && $shift_store && trim((string) $shift_store->s_address) !== '')
    ? $shift_store->s_address . ', '
    : '';
$wz_place = $wz_address . $wz_shift->c_name . ', ' . $wz_shift->p_name;

// The pasted pin where the store has one, otherwise a search for the address
// just printed - so it travels with that address.
$wz_map = ($wz_booked && $shift_store) ? storeMapLink($shift_store) : '';

// The store's own page where it has one, else the employer's. Either one names
// the pharmacy as plainly as the store row does, so it keeps the same company.
$wz_web = $wz_booked
    ? safeUrl(trim((string) ($shift_store->s_website ?? '')) !== ''
        ? $shift_store->s_website
        : (string) ($wz_shift->u_website ?? ''))
    : '';

$wz_store_name = ($wz_booked && $shift_store && trim((string) $shift_store->s_name) !== '')
    ? $shift_store->s_name . ($shift_store->s_number !== '' ? ' (' . $shift_store->s_number . ')' : '')
    : '';

// Only useful next to the address, and it locates the branch just as exactly.
$wz_where = ($wz_booked && $shift_store) ? trim((string) ($shift_store->s_location_label ?? '')) : '';

$wz_phone = ($wz_booked && $shift_store) ? trim((string) $shift_store->s_phone) : '';

// `p_ac_hourly_rate` is what the applicant is paid; `p_hourly_rate` is what the
// employer is billed and has no business on this page. Shifts posted from the
// employer's own form carry no applicant rate at all - that form asks for one
// number - so those keep reading "To be disclosed" rather than "CAD$ 0/hour".
$wz_rate = (float) ($wz_shift->p_ac_hourly_rate ?? 0);
$wz_rate = ($wz_signedIn && $wz_rate > 0)
    ? 'CAD$ ' . rtrim(rtrim(number_format($wz_rate, 2, '.', ''), '0'), '.') . '/hour'
    : 'To be disclosed';

$wz_extra = $wz_signedIn ? trim(strip_tags((string) $wz_shift->p_jobinfo)) : '';
?>

<section class="section-padding wz-detail-wrap">
  <div class="wz-shell">
    <div class="wz-detail">

      <div class="wz-detail-main">

        <article class="wz-card">
          <div class="wz-detail-top">
            <div>
              <h1 class="wz-detail-title"><?php echo esc($wz_shift->p_job_title); ?></h1>
              <?php if ($wz_role !== '') { ?>
                <p class="wz-detail-role"><span class="wz-chip"><?php echo esc($wz_role); ?></span></p>
              <?php } ?>
            </div>
            <?php if ($applied == 1) { ?>
              <span class="wz-applied">Already applied</span>
            <?php } else { ?>
              <a class="btn btn-common" href="<?php echo $applylink; ?>">Apply</a>
            <?php } ?>
          </div>

          <dl class="wz-facts">
            <div class="wz-fact">
              <dt><i class="lni lni-calendar" aria-hidden="true"></i>Shift date</dt>
              <dd><?php echo dateFormat($wz_shift->p_dates); ?></dd>
            </div>

            <div class="wz-fact">
              <dt><i class="lni lni-alarm-clock" aria-hidden="true"></i>Shift time</dt>
              <dd><?php echo esc($wz_shift->p_shift_time); ?></dd>
            </div>

            <div class="wz-fact">
              <dt><i class="lni lni-wallet" aria-hidden="true"></i>Rate</dt>
              <dd><?php echo esc($wz_rate); ?></dd>
            </div>

            <?php if ($wz_store_name !== '') { ?>
              <div class="wz-fact">
                <dt><i class="lni lni-home" aria-hidden="true"></i>Store</dt>
                <dd><?php echo esc($wz_store_name); ?></dd>
              </div>
            <?php } ?>

            <div class="wz-fact is-wide">
              <dt><i class="lni lni-map-marker" aria-hidden="true"></i>Location</dt>
              <dd>
                <?php echo esc($wz_place); ?>
                <?php if ($wz_map !== '') { ?>
                  <a class="wz-directions" href="<?php echo esc($wz_map); ?>" target="_blank" rel="noopener noreferrer">Get directions</a>
                <?php } ?>
              </dd>
            </div>

            <?php if ($wz_where !== '') { ?>
              <?php /* Where the place actually is, when the street address on its
                 own will not find it - a unit in a plaza, a counter inside a
                 supermarket. */ ?>
              <div class="wz-fact is-wide">
                <dt><i class="lni lni-direction" aria-hidden="true"></i>Where to find it</dt>
                <dd><?php echo esc($wz_where); ?></dd>
              </div>
            <?php } ?>

            <?php if ($wz_phone !== '') { ?>
              <div class="wz-fact">
                <dt><i class="lni lni-phone-handset" aria-hidden="true"></i>Store phone</dt>
                <dd><a href="tel:<?php echo esc(preg_replace('/[^0-9+]/', '', $wz_phone), 'attr'); ?>"><?php echo esc($wz_phone); ?></a></dd>
              </div>
            <?php } ?>

            <?php if ($wz_web !== '') { ?>
              <div class="wz-fact">
                <dt><i class="lni lni-world" aria-hidden="true"></i>Website</dt>
                <dd><a href="<?php echo esc($wz_web); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc(parse_url($wz_web, PHP_URL_HOST)); ?></a></dd>
              </div>
            <?php } ?>
          </dl>

          <?php /* So what is missing reads as held back rather than absent, and
             whoever is reading knows what would unlock it. Each tier is told
             only about the next one. */ ?>
          <?php if (! $wz_signedIn) { ?>
            <p class="wz-detail-note">
              <i class="lni lni-lock" aria-hidden="true"></i>
              <span><a href="<?php echo base_url('front/login'); ?>">Sign in</a> to see the hourly rate and the full details of this shift.</span>
            </p>
          <?php } elseif (! $wz_booked) { ?>
            <p class="wz-detail-note">
              <i class="lni lni-lock" aria-hidden="true"></i>
              <span>The pharmacy's name, address and phone number are shared once your booking for this shift is confirmed.</span>
            </p>
          <?php } ?>
        </article>

        <article class="wz-card">
          <h2 class="wz-card-title">Job details</h2>
          <dl class="wz-deflist">
            <div class="wz-deflist-row">
              <dt>Softwares</dt>
              <dd><?php echo getSoftwareSkills($wz_shift->p_skills); ?></dd>
            </div>
            <div class="wz-deflist-row">
              <dt>Details</dt>
              <dd><?php echo getStoreServices($wz_shift->p_services); ?></dd>
            </div>
          </dl>

          <?php /* Saved on every shift form but shown nowhere until now, so anything
             the pharmacy wrote about the shift never reached the applicant. */ ?>
          <?php if ($wz_extra !== '') { ?>
            <h3 class="wz-detail-subhead">Additional details</h3>
            <div class="wz-richtext"><?php echo $wz_shift->p_jobinfo; ?></div>
          <?php } ?>
        </article>

      </div>

      <aside class="wz-detail-side">
        <div class="wz-card">
          <h2 class="wz-card-title">Related shifts</h2>
          <?php /* A plain list, not the old owl carousel: nothing on the site
             initialises owl any more, and its stylesheet keeps an uninitialised
             carousel at display:none - so this box had been rendering empty. */ ?>
          <?php if ($relatedjobs) { ?>
            <ul class="wz-related">
              <?php foreach ($relatedjobs as $relatedjob) { ?>
                <li class="wz-related-item">
                  <a class="wz-related-title" href="<?php echo base_url('front/job_detail/' . $relatedjob->p_id); ?>"><?php echo esc($relatedjob->p_job_title); ?></a>
                  <span class="wz-related-meta"><?php echo esc(getShiftForName($relatedjob->p_shift_for)); ?></span>
                  <span class="wz-related-meta"><?php echo esc($relatedjob->c_name . ', ' . $relatedjob->p_name); ?> <span class="sep">/</span> <?php echo dateFormat($relatedjob->p_dates); ?></span>
                </li>
              <?php } ?>
            </ul>
          <?php } else { ?>
            <p class="wz-related-empty">No other shifts are open right now.</p>
          <?php } ?>
        </div>
      </aside>

    </div>
  </div>
</section>
