<?php
/**
 * The public navigation bar, shared by the home page and every inner page.
 *
 * Bootstrap 5 collapse: the data attributes are `data-bs-*`, and the menu is a
 * real off-canvas-style panel below the medium breakpoint (see theme.css).
 */
$wz_segment = function ($name) {
    return uri_segment(1) === $name ? ' active' : '';
};
?>
<nav class="navbar navbar-expand-lg fixed-top scrolling-navbar" id="wz-nav">
  <div class="container">
    <a class="navbar-brand" href="<?php echo base_url(); ?>">
      <img src="<?php echo base_url('assets/front/assets/img/logo.png'); ?>" alt="<?php echo esc($settings[0]->s_sitename); ?>">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <i class="lni-menu"></i>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarCollapse">
      <ul class="navbar-nav mb-2 mb-lg-0">
        <li class="nav-item<?php echo uri_segment(1) === '' ? ' active' : ''; ?>">
          <a class="nav-link" href="<?php echo base_url(); ?>">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo base_url(); ?>#browsejobs">Browse Shifts</a>
        </li>
        <li class="nav-item<?php echo $wz_segment('resources'); ?>">
          <a class="nav-link" href="<?php echo base_url('resources'); ?>">Resources</a>
        </li>
        <li class="nav-item<?php echo $wz_segment('contact'); ?>">
          <a class="nav-link" href="<?php echo base_url('contact'); ?>">Contact</a>
        </li>

        <?php if (! empty($isUserLoggedIn)) { ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="wzAccount" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">My Account</a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="wzAccount">
              <li class="dropdown-item p-0"><a class="nav-link" href="<?php echo $myaccountLink; ?>">Dashboard</a></li>
              <li class="dropdown-item p-0"><a class="nav-link" href="<?php echo $logoutLink; ?>">Logout</a></li>
            </ul>
          </li>
        <?php } else { ?>
          <li class="nav-item">
            <a class="btn ticker-btn" href="<?php echo base_url('front/login'); ?>"><?php echo lang('content.btnsignuplogin'); ?></a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
