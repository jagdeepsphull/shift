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
	
	<!-- daterangepicker -->
    <link rel="stylesheet" href="<?php //echo base_url('assets/front/plugins/daterangepicker/daterangepicker.css') ; ?>">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

	
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
	<style>
        /* Hide the calendar portion to show only time pickers.

           Every picker in the portal but one: the shift-date filter on All
           Shifts is a date picker and needs both its calendar and its list of
           ranges, so it marks its own container and is excused here. The admin
           header excuses its own filter the same way. See
           partials/shift_list_filter_script.php. */
        .daterangepicker:not(.ps-shift-picker) .calendar-table,
        .daterangepicker:not(.ps-shift-picker) .ranges {
            display: none !important;
        }
        .daterangepicker:not(.ps-shift-picker) .drp-calendar {
            width: auto !important;
        }
		/* .daterangepicker {
			z-index: 1050 !important; 
		}
		.modal .daterangepicker {
			z-index: 1055 !important; 
		} */
    </style>
	 <script>
    /*to prevent Firefox FOUC, this must be here*/
    let FF_FOUC_FIX;
  </script>
    <?php /* The home page's palette and type, applied to this area - see the
       partial for why theme.css itself cannot simply be loaded here. */ ?>
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
                         * The employer's side menu.
                         *
                         * Every icon here used to be a `ti-` class. Themify is not
                         * loaded in this area - only line-icons is - so none of them
                         * drew anything and the menu was a column of bare words. The
                         * `lni-` equivalents below are all present in the font.
                         *
                         * Presentation is partials/portal_sidebar_styles.php.
                         */
                        $sideName    = trim($userinfo[0]->u_fname . ' ' . $userinfo[0]->u_lname);
                        $sideCompany = trim((string) $userinfo[0]->u_comp_name);
                        $sideLicence = trim((string) $userinfo[0]->u_licence_no);
                        $sidePhone   = trim((string) $userinfo[0]->u_phone);
                        $sideEmail   = trim((string) $userinfo[0]->u_email);

                        // Initials stand in for the photo nobody uploads. Falls back to
                        // the company's first letter, then to a dash, so the circle is
                        // never empty.
                        $sideInitials = trim(mb_substr((string) $userinfo[0]->u_fname, 0, 1) . mb_substr((string) $userinfo[0]->u_lname, 0, 1));

                        if ($sideInitials === '') {
                            $sideInitials = mb_substr($sideCompany !== '' ? $sideCompany : '-', 0, 1);
                        }
                    ?>
                    <div class="col-lg-3 col-md-3">
                        <div class="side-dashboard ps-side">

                            <div class="ps-who">
                                <span class="ps-avatar"><?php echo esc($sideInitials); ?></span>
                                <div class="ps-who-text">
                                    <h4 class="ps-name"><?php echo esc($sideName !== '' ? $sideName : 'Your account'); ?></h4>
                                    <?php if ($sideCompany !== '') { ?>
                                        <span class="ps-company"><?php echo esc($sideCompany . ($sideLicence !== '' ? ' (' . $sideLicence . ')' : '')); ?></span>
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
                                    <li class="<?php echo $pjcls; ?>"><a href="<?php echo base_url('employer/post_job'); ?>"><i class="lni-pencil-alt"></i>Post New Shift</a></li>
                                    <li class="<?php echo $ajcls; ?>"><a href="<?php echo base_url('employer/all_jobs'); ?>"><i class="lni-briefcase"></i>All Shifts</a></li>
                                    <li class="<?php echo $stcls; ?>"><a href="<?php echo base_url('employer/stores'); ?>"><i class="lni-apartment"></i>My Stores</a></li>
                                    <?php /* <li class="<?php echo $dashcls; ?>"><a href="<?php echo base_url('employer/dashboard')?>"><i class="lni-dashboard"></i>Dashboard</a></li>
                                    <li class="<?php echo $apcls; ?>"><a href="<?php echo base_url('employer/applications'); ?>"><i class="lni-user"></i>Applications</a></li>
                                    <li class="<?php echo $sccls; ?>"><a href="<?php echo base_url('employer/search_candidates'); ?>"><i class="lni-briefcase"></i>Search Candidates</a></li>
                                    <li class="<?php echo $iccls; ?>"><a href="<?php echo base_url('employer/invited_candidates'); ?>"><i class="lni-briefcase"></i>Invited Candidates</a></li> */ ?>
                                    <li class="<?php echo $epcls; ?>"><a href="<?php echo base_url('employer/edit_profile'); ?>"><i class="lni-user"></i>Edit Profile</a></li>
                                    <li class="<?php echo $cpcls; ?>"><a href="<?php echo base_url('employer/change_password'); ?>"><i class="lni-lock"></i>Change Password</a></li>
                                    <li class="ps-menu-out <?php echo $lgcls; ?>"><a href="<?php echo base_url('employer/logout')?>"><i class="lni-power-switch"></i>Logout</a></li>
                                </ul>
                            </div>

                            <?= view('partials/portal_support') ?>
                        </div>
                    </div>