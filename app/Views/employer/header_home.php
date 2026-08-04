 <!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo $settings[0]->s_sitename; ?> | <?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/bootstrap.min.css') ; ?>" >
    <!-- Icon -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/fonts/line-icons.css') ; ?>">
    <!-- Owl carousel -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/owl.carousel.min.css') ; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/owl.theme.css') ; ?>">
    
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/magnific-popup.css') ; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/nivo-lightbox.css') ; ?>">
    <!-- Animate -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/animate.css') ; ?>">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/main.css') ; ?>">
    <!-- Responsive Style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/front/assets/css/responsive.css') ; ?>">
	
	<!-- DataTables -->
	<link rel="stylesheet" href="<?php echo base_url();?>assets/front/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="<?php echo base_url();?>assets/front/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="<?php echo base_url();?>assets/front/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

  </head>
  <body>

    <!-- Header Area wrapper Starts -->
    <header id="header-wrap">	

		

		<nav class="navbar navbar-expand navbar-dark p1-primary static-top">
			<div class="container">
			
				<ul class="navbar-nav mr-auto w-100 justify-content-start clearfix">
					  <li class="nav-item dropdown megamenu-li">
					   <a class="nav-link dropdown-toggle" href="" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Resources</a>
					   <div class="dropdown-menu megamenu" aria-labelledby="dropdown01">
						<?php 
						if($headermenu_parent) {
						?>
							<div class="row">
							<?php
							foreach($headermenu_parent as $category){
								$subcategories = custom()->get_where('headermenu',array('m_parentid'=>$category->m_id,'m_status'=>1));
						?>
							<div class="col-sm-6 col-lg-3 border-right">
								<h5 class="text-center p-2 mb-1 bg-light text-dark "><?php echo $category->m_name ; ?></h5>
							<?php 
									if($subcategories) {
									?>
									
									<?php
										foreach($subcategories as $sub){ 			
									?>
											<a class="dropdown-item" href="<?php echo $sub->m_link ; ?>"><?php echo $sub->m_name ; ?></a>
									<?php }
									?>
							<?php }
							?>
							</div>
							<?php }?>
							</div>
						<?php
						}?>
						
					   </div>
					  </li>
				
					
				</ul>
			</div>
			
			

		</nav>
		
		
      <!-- Navbar Start -->
      <nav class="navbar navbar-expand-md bg-inverse fixed-top scrolling-navbar">
        <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <a href="<?php echo base_url() ; ?>" class="navbar-brand"><img src="" alt="PICKASHIFT"></a>       
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
                <a class="nav-link" href="<?php echo base_url() ; ?>#services">
                  Services
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
				<li class="nav-item active"><a class="nav-link" href="<?php echo $myaccountLink;?>">My Account</a></li>
			  <?php }else{ ?>
				<li class="nav-item active"><a class="btn ticker-btn" href="<?php echo base_url('front/login');?>"><?php echo lang('content.btnsignuplogin');?></a></li>
			  <?php } ?>
            </ul>
          </div>
        </div>
      </nav>
      <!-- Navbar End -->
	  
	  

      

       <!-- Hero Area Start -->
      <div id="hero-area-inner" class="hero-area-bg">
        <div class="container">      
          <div class="row text-center ">
            <div class="col-lg-7 col-md-12 col-sm-12 col-xs-12">
              <div class="contents">
                <h2 class="head-title section-padding"><?php echo $pageTitle; ?></h2>                
              </div>
            </div>
            
          </div> 
        </div> 
      </div>
      <!-- Hero Area End -->

    </header>
    <!-- Header Area wrapper End -->

    </header>
    <!-- Header Area wrapper End -->