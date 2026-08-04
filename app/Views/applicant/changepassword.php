	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="ti-briefcase"></i>Change Password</h4>
	            </div>

	            <?php  
				if(session()->getFlashdata('error_msg')){
						echo session()->getFlashdata('error_msg');
				}
					?>
	            <form id="change-pass" name="change-pass" action="" method="post" enctype="multipart/form-data">
	                <div class="dashboard-caption-wrap">

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
	                                <h4>Change Password</h4>
	                            </div>
	                        </div>
	                    </div>

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-lg-6 col-md-6 col-sm-2">
	                            <div class="form-group">
									<label>Current Password</label>
									<input required type="password" class="form-control" placeholder="Enter Current Password" name="current_password" id="current_password" value="">
								</div>
	                        </div>
						</div>

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-lg-6 col-md-6 col-sm-2">
	                            <!-- text input -->
								<div class="form-group">
									<label>New Password</label>
									<input required type="password" class="form-control" placeholder="Enter New password" name="new_password" id="mainpassword" value="">
								</div>
	                        </div>
						</div>

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-lg-6 col-md-6 col-sm-2">
	                            <!-- text input -->
								<div class="form-group">
									<label>Confirm New Password</label>
									<input required type="password" class="form-control" placeholder="Enter Confirm password" name="confirm_password" id="confirm_password" value="">
								</div>
	                        </div>
	                    </div>
						


	                    <div class="row mrg-top-30">
	                        <div class="col-md-6 col-sm-12">
	                            <div class="form-group text-center">
	                                <input type="hidden" id="base" value="<?php echo base_url(); ?>">
	                                <input type="submit" class="btn btn-common" name="updateprofile" value="Change Password" />
	                            </div>
	                        </div>
	                    </div>


	                </div>
	            </form>

	        </div>
	    </div>
	</div>

	</div>
	</div>
	</section>
	<!-- General Detail End -->
