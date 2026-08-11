<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?php echo base_url('assets/images/favicon.png');?>" type="image/png">

    <title><?php echo $settings[0]->s_sitename; ?> | <?php echo $pageTitle; ?></title>

    <?php echo view('front/partials/head'); ?>
  </head>
  <body>

    <?php echo view('front/partials/navbar'); ?>

    <!-- Hero -->
    <header class="wz-hero">
      <h1>Your Valuable Partner in<br>Excellent Patient Care</h1>
      <p>Empowering Pharmacies With Skilled Professionals To Provide Enhanced Quality</p>

      <form class="wz-searchbar" role="search" onsubmit="return false;">
        <span class="wz-search-icon" aria-hidden="true"><i class="lni-search"></i></span>
        <label class="visually-hidden" for="wz-job-search">Search shifts</label>
        <input type="search" id="wz-job-search" name="q" placeholder="Type to search ..." autocomplete="off">

        <span class="wz-search-divider" aria-hidden="true"></span>

        <!-- Shift date range. The calendar is attached in theme.js; the field
             stays plain text so it degrades to an inert box without it. -->
        <div class="wz-search-dates">
          <span class="wz-search-icon" aria-hidden="true"><i class="lni-calendar"></i></span>
          <label class="visually-hidden" for="wz-job-dates">Shift date range</label>
          <input type="text" id="wz-job-dates" name="dates" placeholder="Any shift date" autocomplete="off">
          <button type="button" class="wz-date-clear" id="wz-job-dates-clear" aria-label="Clear the date range" hidden>&times;</button>
        </div>

        <button type="submit" class="wz-btn">Search</button>
      </form>
    </header>
