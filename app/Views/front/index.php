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

// Shift types present in the list, for the dropdown.
$wz_types = [];
foreach ($jobs as $wz_job) {
    $wz_name = getShiftForName($wz_job->p_shift_for);
    if ($wz_name !== '') {
        $wz_types[$wz_name] = $wz_name;
    }
}
asort($wz_types);
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
<!-- What people say about us. Hidden entirely when the admin has added none. -->
<section id="testimonials" class="section-padding">
  <div class="wz-shell">
    <div class="text-center mb-5">
      <h2 class="wz-section-title">What People Say</h2>
    </div>

    <?php
      // Three to a slide, so the row matches the three tiles above it. Bootstrap
      // moves one slide at a time, so the grouping is done here rather than by
      // sliding a wider track - a fourth testimonial starts the next slide.
      $slides = array_chunk($testimonials, 3);
    ?>
    <div id="wz-testimonials" class="carousel slide wz-testimonials" data-bs-ride="carousel" data-bs-interval="6000">
      <div class="carousel-inner">
        <?php foreach ($slides as $i => $slide): ?>
          <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
            <div class="row g-4">
              <?php foreach ($slide as $testimonial): ?>
                <div class="col-md-6 col-lg-4">
                  <figure class="wz-testimonial">
                    <div class="wz-testimonial-mark" aria-hidden="true">&ldquo;</div>
                    <blockquote>
                      <h3><?php echo esc($testimonial->t_title); ?></h3>
                      <p><?php echo nl2br(esc($testimonial->t_description)); ?></p>
                    </blockquote>
                  </figure>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($slides) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#wz-testimonials" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#wz-testimonials" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>

        <div class="carousel-indicators">
          <?php foreach ($slides as $i => $slide): ?>
            <button type="button" data-bs-target="#wz-testimonials" data-bs-slide-to="<?php echo $i; ?>"
                    class="<?php echo $i === 0 ? 'active' : ''; ?>"
                    <?php echo $i === 0 ? 'aria-current="true"' : ''; ?>
                    aria-label="Testimonials <?php echo $i + 1; ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
