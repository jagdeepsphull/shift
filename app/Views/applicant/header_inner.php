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


    <!-- Responsive Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/responsive.css') ; ?>">

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
                    <div class="col-lg-3 col-md-4">
                        <div class="side-dashboard">

                            <div class="dashboard-avatar">

                                <div class="dashboard-avatar-thumb">
                                    <img src="<?php echo base_url('uploads/profile/'.$userinfo[0]->u_photo);?>" class="img-avater" alt="" />
                                </div>

                                <div class="dashboard-avatar-text">
                                    <h4><?php echo $userinfo[0]->u_fname.' '.$userinfo[0]->u_lname;?></h4>
                                    <h6><?php echo getShiftForName($userinfo[0]->u_usersubtype); ?></h6>
                                </div>

                            </div>

                            <div class="dashboard-menu">
                                <ul>
                                    <li class="<?php echo $ajcls; ?>"><a href="<?php echo base_url('applicant/applied_jobs')?>"><i class="ti-hand-point-right"></i>Applied Shifts</a></li>
                                    <?php /* <li class="<?php echo $dashcls; ?>"><a href="<?php echo base_url('applicant/dashboard')?>"><i class="ti-dashboard"></i>Dashboard</a></li>
									<li class="<?php echo $ajcls; ?>"><a href="<?php echo base_url('applicant/job_preference')?>"><i class="ti-hand-point-right"></i>Job Preference</a></li>
                                    <li class="<?php echo $sjcls; ?>"><a href="<?php echo base_url('applicant/saved_jobs')?>"><i class="ti-heart"></i>Saved Jobs</a></li>
                                    <li class="<?php echo $alcls; ?>"><a href="<?php echo base_url('applicant/alert_jobs')?>"><i class="ti-bell"></i>Alert Jobs</a></li>
                                    <li><a href="<?php echo base_url('applicant/dashboard')?>"><i class="ti-flag-alt-2"></i>Viewed Resume</a></li> */  ?>

                                </ul>
                                <h4>Personal Info</h4>
                                <ul>
                                    <li class="<?php echo $picls; ?>"><a
                                            href="<?php echo base_url('applicant/personal_info')?>"><i
                                                class="ti-id-badge"></i>Edit Profile</a></li>
                                    <?php /*   <li class="<?php echo $wecls; ?>"><a
                                            href="<?php echo base_url('applicant/work_experience')?>"><i
                                                class="ti-id-badge"></i>Work Experience</a></li>
                                    <li class="<?php echo $qacls; ?>"><a
                                            href="<?php echo base_url('applicant/qualification')?>"><i
                                                class="ti-id-badge"></i>Qualification</a></li>
                                    <li class="<?php echo $crcls; ?>"><a
                                            href="<?php echo base_url('applicant/certification')?>"><i
                                                class="ti-id-badge"></i>Certification</a></li>
                                    <li class="<?php echo $dccls; ?>"><a
                                            href="<?php echo base_url('applicant/documents')?>"><i
                                                class="ti-id-badge"></i>Documents</a></li>
                                    <li class="<?php echo $trcls; ?>"><a
                                            href="<?php echo base_url('applicant/tranings')?>"><i
                                                class="ti-id-badge"></i>Tranings</a></li> */ ?>
                                    <li class="<?php echo $lgcls; ?>"><a
                                            href="<?php echo base_url('applicant/change_password')?>"><i
                                                class="ti-power-off"></i>Change Password</a></li>
                                    <li class="<?php echo $lgcls; ?>"><a
                                            href="<?php echo base_url('applicant/logout')?>"><i
                                                class="ti-power-off"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>