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
  
  <link href="<?php echo base_url();?>/assets/admin/plugins/select2/css/select2.min.css" rel="stylesheet">
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
    <span class="brand-text font-weight-light"><a href="<?php echo base_url($adminpath.'/dashboard');?>" class="brand-link">
      <img src="<?php echo base_url('/assets/front/assets/img/logo.png');?>" alt="<?php echo $settings[0]->s_sitename;?> Logo" class="brand-image">
      <?php //echo $settings[0]->s_sitename;?> </span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        
        <div class="info">
          <a href="#" class="d-block">Logged in as <?php echo $userdet[0]->u_userid;?></a>
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
                <a href="<?php echo base_url('sadmin/resources/index');?>" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Resources Menu</p>
                </a>
              </li>
			  
            </ul>
          </li>
		  <li class="nav-item">
			<a href="<?php echo base_url('sadmin/applications');?>" class="nav-link">
			  <i class="nav-icon fas fa-th"></i>
			  <p>Applications</p>
			</a>
		  </li>
		  
		  
		  <?php if($menuarr) {?>
			  <?php foreach($menuarr as $ky=>$vl) {?>
			  <li class="nav-item">
				<a href="<?php echo base_url($adminpath.'/'.$vl['mlink']);?>" class="nav-link <?php echo($link==$vl['mlink'])?"active":"";?>">
				  <i class="nav-icon fas fa-th"></i>
				  <p><?php echo $vl['mname'];?></p>
				</a>
			  </li>
			  <?php } ?>
		  <?php } ?>
          
        
         
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>