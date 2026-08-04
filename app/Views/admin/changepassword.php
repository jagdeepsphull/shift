<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Change Password</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        
                        <li class="breadcrumb-item ">Change Password</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
  

    <!-- Main content -->
    <form name="addform" action="" method="post"  enctype="multipart/form-data">
        <section class="content">
            <div class="container-fluid">
			<?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}		
					
		?>
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
		<?php endif; ?>
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Change Password</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

                                <div class="row">
                                   
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
											<label>Current Password</label>
											<input required type="password" class="form-control" placeholder="Enter Current Password" name="current_password" id="current_password" value="">
										</div>
                                    </div>
                                </div>
								

                                <div class="row">
                                    
                                    <div class="col-sm-4">
                                        <div class="form-group">
											<label>New Password</label>
											<input required minlength="5" maxlength="15" type="password" class="form-control" placeholder="Enter New password" name="new_password" id="mainpassword" value="">
										</div>
                                    </div>
									<div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
											<label>Confirm New Password</label>
											<input required type="password" class="form-control" placeholder="Enter Confirm password" name="confirm_password" id="confirm_password" value="">
										</div>
                                    </div>
                                    

                                </div>

                            </div>
                            <!-- /.card-body -->

                            <!-- /.card-body -->
                            <div class="card-footer">                                
                                    <input type="submit" class="btn btn-primary" name="updateprofile" value="Change Password" />
                            </div>

                        </div>



                    </div>
                </div>



            </div>
            </section>
      </form>
</div>
