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

    <!-- Page banner: the hero band, shorter, on every inner page -->
    <header class="wz-pagehead">
      <h1><?php echo esc($pageTitle); ?></h1>
      <?php if (! empty($pageLead)) { ?>
        <p><?php echo esc($pageLead); ?></p>
      <?php } ?>
      <nav class="wz-crumbs" aria-label="Breadcrumb">
        <a href="<?php echo base_url(); ?>">Home</a> <span aria-hidden="true">/</span> <?php echo esc($pageTitle); ?>
      </nav>
    </header>
