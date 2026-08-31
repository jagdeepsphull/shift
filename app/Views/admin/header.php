<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="<?php echo base_url('assets/images/favicon.png');?>">
  <title><?php echo $settings[0]->s_sitename;?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/fontawesome-free/css/all.min.css">
  
  <!-- DataTables -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  
  <!-- datepicker -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/bootstrap/css/bootstrap-datepicker.css">
  
  <!-- daterangepicker -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/daterangepicker/daterangepicker.css">
  
  <link href="<?php echo base_url();?>assets/admin/plugins/select2/css/select2.min.css" rel="stylesheet">
  <?php /* The theme makes select2 wear AdminLTE's own form-control: same
     height, border and focus ring as the inputs beside it. Without it a
     dropdown is visibly a different control from the field above it. */ ?>
  <link href="<?php echo base_url();?>assets/admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css" rel="stylesheet">
  <!-- iCheck -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="<?php echo base_url();?>assets/admin/plugins/summernote/summernote-bs4.min.css">
  
  <style>
        /* The logo is a transparent PNG lettered in black and red, so against
           AdminLTE's dark sidebar the wordmark all but vanished. The strip it
           sits in gets its own white background - the rest of the sidebar stays
           the theme's. */
        .main-sidebar .brand-link {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            display: block;
            padding: 12px 16px;
            text-align: center;
        }

        /* AdminLTE floats the brand image left at a fixed height, which suits a
           square mark next to wordmark text. This one is the wordmark, 470x114,
           so it is centred and left to scale on its own ratio instead. */
        .main-sidebar .brand-link .brand-image {
            float: none;
            margin: 0;
            max-height: 46px;
            max-width: 100%;
            width: auto;
            opacity: 1;
        }

        /* Hide the calendar portion to show only time pickers */
        .daterangepicker .calendar-table,
        .daterangepicker .ranges {
            display: none !important;
        }
        .daterangepicker .drp-calendar {
            width: auto !important;
        }
    </style>
  
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="<?php echo base_url('/assets/front/assets/img/logo.png');?>" alt="<?php echo $settings[0]->s_sitename;?>" height="60" width="60">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo base_url($adminpath.'/dashboard');?>" class="nav-link">Home</a>
      </li>
	  <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo base_url('sadmin/logout');?>" class="nav-link">Logout</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo base_url($adminpath.'/dashboard');?>" class="brand-link">
      <img src="<?php echo base_url('/assets/front/assets/img/logo.png');?>" alt="<?php echo $settings[0]->s_sitename;?> Logo" class="brand-image">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        
        <div class="info">
          <a href="#" class="d-block">Logged in as <?php echo esc($userdet[0]->u_userid);?></a>
        </div>
      </div>

   
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item">
            <a href="<?php echo base_url($adminpath.'/dashboard');?>" class="nav-link <?php echo($link=='dashboard')?"active":"";?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
            
          </li>
		  <li class="nav-item">
            <a href="#" class="nav-link ">
              <i class="nav-icon fa fa-bars"></i>
              <p>
                Main Master
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
			<ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/province/index');?>" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Province</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/city/index');?>" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>City</p>
                </a>
              </li>
             <?php /*  <li class="nav-item">
                <a href="<?php echo base_url('sadmin/hourly/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Hourly Rate</p>
                </a>
              </li> */ ?>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/shift_for/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Shift For</p>
                </a>
              </li>
			  <li class="nav-item">
                <a href="<?php echo base_url('sadmin/softwareskills/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Softwares</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/storeservice/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Services</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/additionaldetails/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Additional Details</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/resources/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Resources Menu</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('sadmin/testimonials/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Testimonials</p>
                </a>
              </li>
			  
            </ul>
          </li>
		  <?php
			/* The sidebar badges: how much is sitting in each queue waiting on
			   the admin. Drawn only when there is something to draw, so a badge
			   appearing means there is work and no badge means none - a zero
			   would have to be read before it could be dismissed.

			   $pending counts accounts not yet activated, $review counts rows
			   not yet decided on. */
			$pending = $pendingUsers ?? [];
			$review  = $pendingReview ?? [];
			$badge   = function($count){
				if(!$count){ return ''; }
				return ' <span class="badge badge-warning right">'.(int)$count.'</span>';
			};
		  ?>
		  <li class="nav-item">
			<a href="<?php echo base_url('sadmin/applications');?>" class="nav-link <?php echo($link=='applications')?"active":"";?>">
			  <i class="nav-icon fas fa-th"></i>
			  <p>
				Applications
				<?php echo $badge($review['application'] ?? 0);?>
			  </p>
			</a>
		  </li>
		  <li class="nav-item">
			<a href="<?php echo base_url($adminpath.'/reports');?>" class="nav-link <?php echo($link=='reports')?"active":"";?>">
			  <i class="nav-icon fas fa-chart-bar"></i>
			  <p>Reports</p>
			</a>
		  </li>
		  <li class="nav-item">
			<a href="<?php echo base_url($adminpath.'/manageemail');?>" class="nav-link <?php echo($link=='manageemail')?"active":"";?>">
			  <i class="nav-icon fas fa-envelope"></i>
			  <p>Manage Email</p>
			</a>
		  </li>


		  <?php
			/* The employer entry is one row in the `menu` table but three kinds
			   of account behind it (change request B4), so it is drawn as a
			   treeview: All Employers plus one list per kind. The badge on each
			   counts the accounts of that kind still waiting to be activated. */
			$kindcode   = ($link=='employer') ? (int) ($kind ?? 0) : 0;
		  ?>
		  <?php if($menuarr) {?>
			  <?php foreach($menuarr as $ky=>$vl) {?>
				  <?php if($vl['mlink']=='employer' && !empty($employerKinds)) {?>
					  <?php /* Stores live inside this treeview, so being on that screen
					     has to keep it open too - otherwise the sidebar collapses the
					     branch the admin is standing in. */ ?>
					  <?php $employerBranch = in_array($link, ['employer','stores'], true); ?>
					  <li class="nav-item <?php echo $employerBranch?"menu-open":"";?>">
						<a href="#" class="nav-link <?php echo $employerBranch?"active":"";?>">
						  <i class="nav-icon fas fa-th"></i>
						  <p>
							<?php echo $vl['mname'];?>
							<i class="right fas fa-angle-left"></i>
							<?php echo $badge($pending['employer'] ?? 0);?>
						  </p>
						</a>
						<ul class="nav nav-treeview">
						  <li class="nav-item">
							<a href="<?php echo base_url($adminpath.'/employer');?>" class="nav-link <?php echo($link=='employer' && $kindslug=='')?"active":"";?>">
							  <i class="far fa-circle nav-icon"></i>
							  <p>All Employers</p>
							</a>
						  </li>
						  <?php foreach($employerKinds as $kindcodeitem=>$empkind) {?>
						  <li class="nav-item">
							<a href="<?php echo base_url($adminpath.'/employer/'.$empkind['slug']);?>" class="nav-link <?php echo($kindcode===$kindcodeitem)?"active":"";?>">
							  <i class="far fa-circle nav-icon"></i>
							  <p>
								<?php echo $empkind['label'];?>
								<?php echo $badge($pending[$kindcodeitem] ?? 0);?>
							  </p>
							</a>
						  </li>
						  <?php } ?>
						  <?php /* Stores belong under employers rather than beside them:
						     a location is only ever reached through the account that
						     owns it. Not a `menu` row, because the treeview it sits in
						     is not one either. */ ?>
						  <li class="nav-item">
							<a href="<?php echo base_url($adminpath.'/stores');?>" class="nav-link <?php echo($link=='stores')?"active":"";?>">
							  <i class="far fa-circle nav-icon"></i>
							  <p>Stores</p>
							</a>
						  </li>
						</ul>
					  </li>
				  <?php } else {?>
					  <li class="nav-item">
						<a href="<?php echo base_url($adminpath.'/'.$vl['mlink']);?>" class="nav-link <?php echo($link==$vl['mlink'])?"active":"";?>">
						  <i class="nav-icon fas fa-th"></i>
						  <p>
							<?php echo $vl['mname'];?>
							<?php echo ($vl['mlink']=='applicant') ? $badge($pending['applicant'] ?? 0) : '';?>
							<?php echo ($vl['mlink']=='postjobs')  ? $badge($review['postjobs'] ?? 0)   : '';?>
						  </p>
						</a>
					  </li>
				  <?php } ?>
			  <?php } ?>
		  <?php } ?>
          
        
         
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>