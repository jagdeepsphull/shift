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
      <?php /* The breadcrumb is gone. It read "Home / " followed by the page
         name, and on every page that sets no `pageTitle` - which is most of
         them - by nothing at all. Without the link back it can only repeat the
         heading directly above it, which is the same duplicate the body
         headings were. The navbar carries Home. */ ?>
    </header>
