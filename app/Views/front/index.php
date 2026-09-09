<?php
/**
 * Home page: the shift list, in the card layout.
 *
 * Ordering is done in the controller (soonest shift first). The tabs, the type
 * filter, the hero's search box and shift-date range, and "Load More" are all
 * client side in assets/front/assets/js/theme.js - they only ever hide rows that
 * are already on the page, so the order the server produced is never disturbed.
 */
$wz_today = date('Y-m-d');
$wz_recent_cutoff = date('Y-m-d', strtotime('-14 days'));

// The "Shift Requested For" types worth offering: those the back office has
// active AND that at least one shift on this page is posted for. Names, the
// active flag and the order all come from the `shift_for` list, so a type the
// admin renames, retires or moves follows along; the shifts decide which of
// them appear, so the filter never offers a choice that would empty the list.
//
// Left in the order the controller read them in, which is the order the agency
// put them in - `SHIFT_FOR_ORDER`. This was sorted by name here, which put
// "Dental Assistant" above "Pharmacist (R Ph)" on the one screen the public
// sees, whatever the back office had chosen. Keying by name only removes a
// duplicate; PHP keeps the insertion order.
$wz_used = [];
foreach ($jobs ?: [] as $wz_job) {
    $wz_used[(int) $wz_job->p_shift_for] = true;
}

$wz_types = [];
foreach (($shift_for ?: []) as $wz_row) {
    $wz_name = trim((string) $wz_row->sf_name);
    if ($wz_name !== '' && isset($wz_used[(int) $wz_row->sf_id])) {
        $wz_types[$wz_name] = $wz_name;
    }
}
?>

<section id="browsejobs" class="section-padding">
  <div class="wz-shell">

    <div class="wz-toolbar">
      <div class="wz-tabs" role="tablist">
        <button type="button" class="wz-tab is-active" data-filter="all">All Shifts</button>
        <button type="button" class="wz-tab" data-filter="upcoming">Upcoming</button>
        <button type="button" class="wz-tab" data-filter="recent">Recently Posted</button>
      </div>

      <div class="wz-select">
        <label class="visually-hidden" for="wz-job-type">Filter by shift type</label>
        <?php /* data-no-select2: theme.js drives this filter with a native
           addEventListener('change'), and select2 announces a pick through
           jQuery's trigger - a simulation that native listeners never hear.
           Dressed up, the filter goes silently dead. */ ?>
        <select id="wz-job-type" data-no-select2>
          <option value="">Shift Types</option>
          <?php foreach ($wz_types as $wz_type) { ?>
            <option value="<?php echo esc($wz_type, 'attr'); ?>"><?php echo esc($wz_type); ?></option>
          <?php } ?>
        </select>
      </div>
    </div>

    <div class="wz-jobs" id="wz-jobs">
      <?php if ($jobs) { ?>
        <?php foreach ($jobs as $job) {
            $wz_type = getShiftForName($job->p_shift_for);
            $wz_city = getCityName($job->p_city);
            $wz_province = getProvinceName($job->p_province);
            $wz_date = shiftDateSortValue($job);
            $wz_posted = substr((string) ($job->created ?? ''), 0, 10);

            $wz_haystack = strtolower(implode(' ', array_filter([
                $job->p_job_title, $wz_type, $wz_city, $wz_province, $job->p_shift_time,
            ])));
        ?>
          <article class="wz-job"
                   data-search="<?php echo esc($wz_haystack, 'attr'); ?>"
                   <?php /* Empty when the date could not be read, so the range filter skips the card. */ ?>
                   data-date="<?php echo esc($wz_date === '9999-12-31' ? '' : $wz_date, 'attr'); ?>"
                   data-type="<?php echo esc($wz_type, 'attr'); ?>"
                   data-upcoming="<?php echo ($wz_date >= $wz_today && $wz_date !== '9999-12-31') ? '1' : '0'; ?>"
                   data-recent="<?php echo ($wz_posted !== '' && $wz_posted >= $wz_recent_cutoff) ? '1' : '0'; ?>">

            <span class="wz-job-icon" aria-hidden="true"><i class="lni-briefcase"></i></span>

            <div class="wz-job-body">
              <a class="wz-job-title" href="<?php echo base_url('front/job_detail/' . $job->p_id); ?>">
                <?php echo esc($job->p_job_title); ?>
              </a>
              <p class="wz-job-meta">
                <span><?php echo esc(trim($wz_city . ($wz_city && $wz_province ? ', ' : '') . $wz_province)); ?></span>
                <?php if ($wz_type !== '') { ?>
                  <span class="sep">/</span><span><?php echo esc($wz_type); ?></span>
                <?php } ?>
                <?php if (! empty($job->p_shift_time)) { ?>
                  <span class="sep">/</span><span class="label">Shift Time</span><span><?php echo esc($job->p_shift_time); ?></span>
                <?php } ?>
              </p>
            </div>

            <span class="wz-job-date"><?php echo dateFormat($job->p_dates); ?></span>
          </article>
        <?php } ?>
      <?php } ?>
    </div>

    <p class="wz-empty" id="wz-jobs-empty" <?php echo $jobs ? 'hidden' : ''; ?>>
      No shifts match your search just yet. Try a different term or clear the filters.
    </p>

    <div class="wz-more">
      <button type="button" class="wz-btn" id="wz-load-more" hidden>Load More</button>
    </div>

  </div>
</section>

<!-- What makes us stand out -->
<section id="services" class="section-padding">
  <div class="wz-shell">
    <div class="text-center mb-5">
      <h2 class="wz-section-title">What Makes Us Stand Out</h2>
      <p class="wz-section-lead mx-auto">
        With over 20 years of experience in retail pharmacy patient care, we provide solution-oriented
        care for better patient outcomes, with qualified professionals who keep their clinical knowledge
        and communication skills current.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="services-item">
          <div class="icon"><i class="lni-cog"></i></div>
          <div class="services-content">
            <h3><a href="<?php echo base_url('contact'); ?>">Easily accessible, always ready</a></h3>
            <p>Reach us by phone, e-mail or through the portal. Once you contact us we will understand your
               specific situation and recommend the best possible staffing solution.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="services-item">
          <div class="icon"><i class="lni-stats-up"></i></div>
          <div class="services-content">
            <h3><a href="<?php echo base_url('front/signup'); ?>">Our process</a></h3>
            <p>Every account request, from an applicant or an employer, is verified before activation and
               approval. New graduates are welcome, and gain experience through our training programme.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="services-item">
          <div class="icon"><i class="lni-users"></i></div>
          <div class="services-content">
            <h3><a href="<?php echo base_url('resources'); ?>">Our mission</a></h3>
            <p>To become the one-stop shop for the staffing needs of the healthcare industry, with
               complementary resources that enhance your workflow and time management.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (! empty($testimonials)): ?>
<?php
  // Three to a slide, so the row matches the three tiles above it. Bootstrap
  // moves one slide at a time, so the grouping is done here rather than by
  // sliding a wider track - a fourth testimonial starts the next slide.
  $slides = array_chunk($testimonials, 3);

  // Cards take one of three accent tints in turn - the quote badge and the ring
  // round the photo. Counted across the whole set rather than per slide, so the
  // colours keep marching in order as the carousel advances instead of
  // restarting on every slide and putting the same tint under the same column.
  $wz_t_seq = 0;
?>
<!-- What people say about us. Hidden entirely when the admin has added none. -->
<section id="testimonials" class="section-padding">
  <div class="wz-shell">
    <div class="wz-testimonials-head">
      <span class="wz-pill">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true">
          <path d="M9 12a4 4 0 100-8 4 4 0 000 8zm7.5 1a3 3 0 100-6 3 3 0 000 6zM9 14c-3.9 0-7 2-7 4.5V21h14v-2.5C16 16 12.9 14 9 14zm7.5.5c-.9 0-1.7.1-2.4.3 1.2 1 1.9 2.3 1.9 3.7V21h6v-2c0-2.2-2.5-4.5-5.5-4.5z"/>
        </svg>
        Real stories. Real impact.
      </span>
      <h2 class="wz-section-title">What People <span>Say</span></h2>
      <p class="wz-testimonials-sub">
        Hear from job seekers and employers who have found success with
        <?php echo esc($settings[0]->s_sitename); ?>.
      </p>
    </div>

    <?php /* Hand-lettered asides, in the script face the hero uses. Decoration
       only - aria-hidden so a screen reader is not read three fragments that
       belong to no card, and hidden outright below the desktop breakpoint,
       where there is no margin for them to sit in. */ ?>
    <div class="wz-testimonials-stage">
      <span class="wz-doodle wz-doodle-tl" aria-hidden="true">
        People<br>Make It<br>Happen &#9825;
        <svg viewBox="0 0 40 46" width="34" height="40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
          <path d="M6 3c9 8 20 20 24 34"/><path d="M18 36l12 3 1-12"/>
        </svg>
      </span>
      <span class="wz-doodle wz-doodle-tr" aria-hidden="true">Great<br>People<br>Great Shifts</span>
      <span class="wz-doodle wz-doodle-br" aria-hidden="true">Stronger<br>Communities &#9825;</span>

    <div id="wz-testimonials" class="carousel slide wz-testimonials" data-bs-ride="carousel" data-bs-interval="6000">
      <div class="carousel-inner">
        <?php foreach ($slides as $i => $slide): ?>
          <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
            <div class="row g-4">
              <?php foreach ($slide as $testimonial): ?>
                <?php
                  $wz_stars    = testimonialRating($testimonial->t_rating ?? 5);
                  $wz_name     = trim((string) ($testimonial->t_name ?? ''));
                  $wz_role     = trim((string) ($testimonial->t_role ?? ''));
                  $wz_place    = trim((string) ($testimonial->t_location ?? ''));
                  $wz_accent   = 'wz-testimonial--a' . (($wz_t_seq++ % 3) + 1);
                ?>
                <div class="col-md-6 col-lg-4">
                  <figure class="wz-testimonial <?php echo $wz_accent; ?>">
                    <div class="wz-testimonial-head">
                      <?php /* Always an image: `testimonialPhoto()` hands back the
                         placeholder thumb when the row has no photo of its own,
                         so the circle is never an empty hole in the card. */ ?>
                      <img class="wz-testimonial-photo" src="<?php echo esc(testimonialPhoto($testimonial->t_image ?? ''), 'attr'); ?>"
                           alt="<?php echo $wz_name !== '' ? esc($wz_name, 'attr') : ''; ?>" loading="lazy" width="76" height="76">
                      <span class="wz-testimonial-mark" aria-hidden="true">&rdquo;</span>
                    </div>

                    <div class="wz-testimonial-stars" role="img"
                         aria-label="Rated <?php echo $wz_stars; ?> out of 5">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <svg viewBox="0 0 20 20" width="17" height="17" aria-hidden="true"
                             class="<?php echo $s <= $wz_stars ? 'is-on' : ''; ?>">
                          <path d="M10 1.6l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L1.6 7.7l5.8-.8z"/>
                        </svg>
                      <?php endfor; ?>
                    </div>

                    <blockquote>
                      <h3><?php echo esc($testimonial->t_title); ?></h3>
                      <p><?php echo nl2br(esc($testimonial->t_description)); ?></p>
                    </blockquote>

                    <?php /* The footer only appears once there is something to put
                       in it. An older quote with no name attached keeps the card
                       it always had rather than gaining an empty ruled-off strip. */ ?>
                    <?php if ($wz_name !== '' || $wz_role !== '' || $wz_place !== ''): ?>
                      <figcaption class="wz-testimonial-by">
                        <span class="wz-testimonial-who">
                          <?php if ($wz_name !== ''): ?>
                            <span class="wz-testimonial-name"><?php echo esc($wz_name); ?></span>
                          <?php endif; ?>
                          <?php if ($wz_role !== ''): ?>
                            <span class="wz-testimonial-role"><?php echo esc($wz_role); ?></span>
                          <?php endif; ?>
                        </span>
                        <?php if ($wz_place !== ''): ?>
                          <span class="wz-testimonial-place">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor" aria-hidden="true">
                              <path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/>
                            </svg>
                            <?php echo esc($wz_place); ?>
                          </span>
                        <?php endif; ?>
                      </figcaption>
                    <?php endif; ?>
                  </figure>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php /* Arrows and dots sit together in one strip under the cards rather
         than the arrows floating over them, which is what Bootstrap does by
         default: at three cards wide an arrow over the edge card lands on its
         text. They keep their Bootstrap classes and data attributes, so the
         carousel is still driven entirely by the framework. */ ?>
      <?php if (count($slides) > 1): ?>
        <div class="wz-testimonials-nav">
          <button class="carousel-control-prev" type="button" data-bs-target="#wz-testimonials" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>

          <div class="carousel-indicators">
            <?php foreach ($slides as $i => $slide): ?>
              <button type="button" data-bs-target="#wz-testimonials" data-bs-slide-to="<?php echo $i; ?>"
                      class="<?php echo $i === 0 ? 'active' : ''; ?>"
                      <?php echo $i === 0 ? 'aria-current="true"' : ''; ?>
                      aria-label="Testimonials <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
          </div>

          <button class="carousel-control-next" type="button" data-bs-target="#wz-testimonials" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      <?php endif; ?>
    </div>
    </div><!-- /.wz-testimonials-stage -->
  </div>
</section>
<?php endif; ?>
