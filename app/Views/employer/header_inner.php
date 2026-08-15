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


    <!-- Responsive Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/responsive.css') ; ?>">
	<style>
        /* Hide the calendar portion to show only time pickers */
        .daterangepicker .calendar-table,
        .daterangepicker .ranges {
            display: none !important;
        }
        .daterangepicker .drp-calendar {
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
  </head>
  <body>

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


        <!-- General Detail Start -->
        <section class="dashboard-wrap section-padding" style="margin-top: 82px;">
            <div class="container-fluid">
                <div class="row">

                    <!-- Sidebar Wrap -->
                    <div class="col-lg-3 col-md-3">
                        <div class="side-dashboard">

                            <div class="dashboard-avatar">

                                

                                <div class="dashboard-avatar-text text-left">
                                    <h4></i><?php echo $userinfo[0]->u_fname.' '.$userinfo[0]->u_lname;?>
                                    </h4>
                                    <span class="font-weight-bold font-italic text-info"><?php echo $userinfo[0]->u_comp_name.' ('.$userinfo[0]->u_licence_no.')';?></span><hr>
                                </div>
								<div class="dashboard-avatar-text text-left text-info font-weight-bold">
                                    <i class="lni-phone-handset mr-2"></i><?php echo $userinfo[0]->u_phone?><hr>
                                    <i class="lni-envelope mr-2"></i><?php echo $userinfo[0]->u_email?>
                                </div>

                            </div>

                            <div class="dashboard-menu">
                                <ul>
                                    <li class="<?php echo $pjcls; ?>"><a href="<?php echo base_url('employer/post_job'); ?>"><i class="ti-ruler-pencil"></i>Post New Shift </a></li>
                                    <li class="<?php echo $ajcls; ?>"><a href="<?php echo base_url('employer/all_jobs'); ?>"><i class="ti-briefcase"></i>All Shifts</a></li>
                                    <li class="<?php echo $stcls; ?>"><a href="<?php echo base_url('employer/stores'); ?>"><i class="ti-home"></i>My Stores</a></li>
                                    <?php /* <li class="<?php echo $dashcls; ?>"><a href="<?php echo base_url('employer/dashboard')?>"><i class="ti-dashboard"></i>Dashboard</a></li>
                                    <li class="<?php echo $apcls; ?>"><a href="<?php echo base_url('employer/applications'); ?>"><i class="ti-user"></i>Applications</a></li>
                                    <li class="<?php echo $sccls; ?>"><a href="<?php echo base_url('employer/search_candidates'); ?>"><i class="ti-briefcase"></i>Search Candidates</a></li>
                                    <li class="<?php echo $iccls; ?>"><a href="<?php echo base_url('employer/invited_candidates'); ?>"><i class="ti-briefcase"></i>Invited Candidates</a></li>
                                    <li><a href=""><i class="ti-flag-alt-2"></i>Viewed Resume</a></li> */ ?> 
                                    <li class="<?php echo $epcls; ?>"><a href="<?php echo base_url('employer/edit_profile'); ?>"><i class="ti-id-badge"></i>Edit Profile</a></li>
                                    <li class="<?php echo $epcls; ?>"><a href="<?php echo base_url('employer/change_password'); ?>"><i class="ti-id-badge"></i>Change Password</a></li>
                                    <li class="<?php echo $lgcls; ?>"><a href="<?php echo base_url('employer/logout')?>"><i class="ti-power-off"></i>Logout</a></li>

                                </ul>

                                </ul>
                            </div>
                        </div>
                    </div>