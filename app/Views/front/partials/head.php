<?php
/**
 * Everything the front-end pages load into <head>.
 *
 * Bootstrap 5 replaced the 4.1 build the site shipped with. theme.css is loaded
 * last so it can restyle both Bootstrap and the older main.css rules that the
 * page markup still depends on.
 */
?>
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/bootstrap5.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/fonts/line-icons.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/owl.carousel.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/magnific-popup.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/animate.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/main.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/responsive.css'); ?>">

<!-- DataTables (Bootstrap 5 build) -->
<link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/datatables-responsive/css/responsive.bootstrap5.min.css'); ?>">

<!-- daterangepicker: the shift-date range in the hero search -->
<link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/daterangepicker/daterangepicker.css'); ?>">

<!-- select2: every dropdown on the site wears it -->
<link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/select2/css/select2.min.css'); ?>">

<!-- Theme: loaded last, deliberately -->
<link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/theme.css'); ?>">
