 <!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="icon" href="<?php echo base_url('assets/images/favicon.png');?>" type="image/png">

    <title><?php echo $settings[0]->s_sitename; ?> | <?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/bootstrap.min.css') ; ?>" >
    <!-- Icon -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/fonts/line-icons.css') ; ?>">
    <!-- Owl carousel -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/owl.carousel.min.css') ; ?>">
    <link rel="stylesheet" href="<?php //echo base_url('assets/front/assets/css/owl.theme.css') ; ?>">
    
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/magnific-popup.css') ; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/nivo-lightbox.css') ; ?>">
	
	
	
    <!-- Animate -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/animate.css') ; ?>">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/main.css') ; ?>">
	
	<!-- datepicker -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/bootstrap/css/bootstrap-datepicker.css') ; ?>">
	
	<!-- summernote -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/summernote/summernote-bs4.min.css') ; ?>">
    <!-- select2: every dropdown in this area wears it -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/select2/css/select2.min.css') ; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') ; ?>">


    <!-- datatables: the plugin's own JavaScript is loaded in the footer, its
         stylesheet never was. Without it a sortable heading draws no arrow, and
         on a phone the responsive extension's "+" control - the only way to
         reach the columns it folds away - is invisible. -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') ; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/front/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') ; ?>">

    <!-- Responsive Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/responsive.css') ; ?>">

    <?php /* The home page's palette and type, applied to this area - see the
       partial for why theme.css itself cannot simply be loaded here. It is the
       same file the owner's screens load, so both sides of the login look like
       one product. */ ?>
    <?= view('partials/portal_theme') ?>
  </head>
  <?php /* The class every rule in portal_theme.php hangs off, so the palette
     reaches this area and nothing else. */ ?>
  <body class="ps-portal">

    <!-- Header Area wrapper Starts -->
    <header id="header-wrap">	
		
      <!-- Navbar Start -->
      <nav class="navbar navbar-expand-md bg-inverse fixed-top scrolling-navbar shadow border-bottom rounded-bottom">
        <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <a href="<?php echo base_url() ; ?>" class="navbar-brand"><img src="<?php echo base_url('/assets/front/assets/img/logo.png');?>" alt="PICKASHIFT"></a>       
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <i class="lni-menu"></i>
          </button>
          <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav mr-auto w-100 justify-content-end clearfix">
              <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url() ; ?>">
                  Home
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('resources') ; ?>">
                  Resources
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url() ; ?>#browsejobs">
                  Browse Jobs
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('contact') ; ?>">
                  Contact
                </a>
              </li>
			  <?php if(!empty($isUserLoggedIn)){ ?>
				<li class="nav-item dropdown active">
					<a class="nav-link dropdown-toggle" id="dropdown2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">My Account</a>
					<ul class="dropdown-menu" aria-labelledby="dropdown2">
						<li class="dropdown-item" href="#"><a class="nav-link" href="<?php echo $myaccountLink;?>">Dashboard</a></li>
						<li class="dropdown-item" href="#"><a class="nav-link" href="<?php echo $logoutLink;?>">Logout</a></li>
					</ul>
				</li>
			  <?php }else{ ?>
				<li class="nav-item active"><a class="btn ticker-btn" href="<?php echo base_url('front/login');?>"><?php echo lang('content.btnsignuplogin');?></a></li>
			  <?php } ?>
            </ul>
          </div>
        </div>
      </nav>
      <!-- Navbar End -->
	  
	  

      

      <!-- Hero Area Start -->
      <?php /* <div id="hero-area-inner" class="hero-area-bg mt-5 ">
        <div class="container">      
          <div class="row text-center ">
            <div class="col-lg-7 col-md-12 col-sm-12 col-xs-12">
              <div class="contents">
                <h2 class="head-title section-padding"><?php echo $pageTitle; ?></h2>                
              </div>
            </div>
            
          </div> 
        </div> 
      </div> */ ?>
      <!-- Hero Area End -->

    </header>
    <!-- Header Area wrapper End -->


        <?= view('partials/portal_sidebar_styles') ?>

        <!-- General Detail Start -->
        <section class="dashboard-wrap section-padding" style="margin-top: 82px;">
            <div class="container-fluid">
                <div class="row">

                    <!-- Sidebar Wrap -->
                    <?php
                        /**
                         * The pharmacist's side menu.
                         *
                         * Every icon here used to be a `ti-` class. Themify is not
                         * loaded in this area - only line-icons is - so none of them
                         * drew anything and the menu was a column of bare words. The
                         * `lni-` equivalents below are all present in the font.
                         *
                         * The two lists it used to be - four links under a "Personal
                         * Info" heading that labelled two of them - are one list, the
                         * same shape as the owner's menu in employer/header_inner.php.
                         *
                         * Presentation is partials/portal_sidebar_styles.php.
                         */
                        $sideName  = trim($userinfo[0]->u_fname . ' ' . $userinfo[0]->u_lname);
                        $sideRole  = trim((string) getShiftForName($userinfo[0]->u_usersubtype));
                        $sidePhone = trim((string) $userinfo[0]->u_phone);
                        $sideEmail = trim((string) $userinfo[0]->u_email);
                        $sideLicen = trim((string) $userinfo[0]->u_licence_no);

                        // The photo is optional and mostly absent: the column used to
                        // point an <img> at uploads/profile/ whatever the column held,
                        // so an account without one drew a broken image. Shown only
                        // when the file is actually on disk.
                        $sidePhoto = trim((string) $userinfo[0]->u_photo);
                        $sidePhoto = ($sidePhoto !== '' && is_file(FCPATH . 'uploads/profile/' . $sidePhoto)) ? $sidePhoto : '';

                        // Initials stand in for it, as they do on the owner's side.
                        $sideInitials = trim(mb_substr((string) $userinfo[0]->u_fname, 0, 1) . mb_substr((string) $userinfo[0]->u_lname, 0, 1));

                        if ($sideInitials === '') {
                            $sideInitials = mb_substr($sideName !== '' ? $sideName : '-', 0, 1);
                        }
                    ?>
                    <div class="col-lg-3 col-md-4">
                        <div class="side-dashboard ps-side">

                            <div class="ps-who">
                                <?php if ($sidePhoto !== '') { ?>
                                    <span class="ps-avatar"><img src="<?php echo base_url('uploads/profile/' . $sidePhoto); ?>" alt=""></span>
                                <?php } else { ?>
                                    <span class="ps-avatar"><?php echo esc($sideInitials); ?></span>
                                <?php } ?>
                                <div class="ps-who-text">
                                    <h4 class="ps-name"><?php echo esc($sideName !== '' ? $sideName : 'Your account'); ?></h4>
                                    <?php if ($sideRole !== '') { ?>
                                        <span class="ps-company"><?php echo esc($sideRole . ($sideLicen !== '' ? ' (' . $sideLicen . ')' : '')); ?></span>
                                    <?php } ?>
                                </div>
                            </div>

                            <?php if ($sidePhone !== '' || $sideEmail !== '') { ?>
                                <div class="ps-contact">
                                    <?php if ($sidePhone !== '') { ?>
                                        <a href="tel:<?php echo esc($sidePhone, 'attr'); ?>"><i class="lni-phone-handset"></i><span><?php echo esc($sidePhone); ?></span></a>
                                    <?php } ?>
                                    <?php if ($sideEmail !== '') { ?>
                                        <a href="mailto:<?php echo esc($sideEmail, 'attr'); ?>"><i class="lni-envelope"></i><span><?php echo esc($sideEmail); ?></span></a>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <?php /* Collapsed on a phone, where this column stacks above
                               the page and would otherwise have to be scrolled past. */ ?>
                            <button class="ps-menu-toggle" type="button" data-toggle="collapse"
                                    data-target="#ps-menu-panel" aria-expanded="false" aria-controls="ps-menu-panel">
                                <span><i class="lni-menu mr-2"></i>Menu</span>
                                <i class="lni-chevron-down"></i>
                            </button>

                            <div class="dashboard-menu collapse ps-menu-panel" id="ps-menu-panel">
                                <ul>
                                    <li class="<?php echo $ajcls; ?>"><a href="<?php echo base_url('applicant/applied_jobs'); ?>"><i class="lni-briefcase"></i>My Shifts</a></li>
                                    <?php /* <li class="<?php echo $dashcls; ?>"><a href="<?php echo base_url('applicant/dashboard'); ?>"><i class="lni-dashboard"></i>Dashboard</a></li>
                                    <li class="<?php echo $sjcls; ?>"><a href="<?php echo base_url('applicant/saved_jobs'); ?>"><i class="lni-heart"></i>Saved Jobs</a></li>
                                    <li class="<?php echo $alcls; ?>"><a href="<?php echo base_url('applicant/alert_jobs'); ?>"><i class="lni-alarm"></i>Alert Jobs</a></li> */ ?>
                                    <li class="<?php echo $picls; ?>"><a href="<?php echo base_url('applicant/personal_info'); ?>"><i class="lni-user"></i>Edit Profile</a></li>
                                    <li class="<?php echo $cpcls; ?>"><a href="<?php echo base_url('applicant/change_password'); ?>"><i class="lni-lock"></i>Change Password</a></li>
                                    <li class="ps-menu-out <?php echo $lgcls; ?>"><a href="<?php echo base_url('applicant/logout')?>"><i class="lni-power-switch"></i>Logout</a></li>
                                </ul>
                            </div>

                            <?= view('partials/portal_support') ?>
                        </div>
                    </div>