	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="ti-briefcase"></i>Edit Profile</h4>
	            </div>

	            <?php  
				if(session()->getFlashdata('error_msg')){
						echo session()->getFlashdata('error_msg');
				}
					?>
	            <form name="post-job" action="" method="post" enctype="multipart/form-data">
	                <div class="dashboard-caption-wrap">

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
	                                <h4>Employer Information</h4>
	                            </div>
	                        </div>
	                    </div>

	                    <!-- row -->
	                    <div class="row">
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>First Name</label>
									<input required type="text" class="form-control" placeholder="Enter First Name" name="u_fname" value="<?php echo esc($u_fname); ?>">
								</div>
	                        </div>
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <!-- text input -->
								<div class="form-group">
									<label>Last Name</label>
									<input required type="text" class="form-control" placeholder="Enter Last Name" name="u_lname" value="<?php echo esc($u_lname); ?>">
								</div>
	                        </div>
							<div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Store Name</label>
									<input required readonly disabled type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Name" name="u_comp_name" value="<?php echo esc($u_comp_name); ?>">
								</div>
	                        </div>
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Store Registration Province</label>
									<select required readonly disabled class="form-control " name="u_l_provice" id="province_L_list" >
										<option value="">Select Province</option>
										<?php if($province){ ?>
										<?php foreach($province as $record){ ?>
										<option value="<?php echo $record->p_id; ?>"
											<?php echo ($u_l_provice==$record->p_id)?"selected":""; ?>>
											<?php echo $record->p_name; ?></option>
										<?php } ?>
										<?php } ?>
									</select>
								</div>
	                        </div>
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Store No.</label>
									<input required readonly disabled type="text" class="form-control" placeholder="Enter Store No." name="u_licence_no" value="<?php echo esc($u_licence_no); ?>">
								</div>
	                        </div>
						</div>
						
						<div class="row">
                                    
							<div class="col-sm-4">
								<div class="form-group">
									<label>Email Id</label>
									<input required type="email" readonly disabled class="form-control" placeholder="Enter Email Id" name="u_email" value="<?php echo esc($u_email); ?>">
								</div>
							</div>
							<div class="col-sm-4">
								<!-- text input -->
								<div class="form-group">
									<label>Mobile No.</label>
									<input required type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Mobile No." name="u_phone" value="<?php echo esc($u_phone); ?>" maxlength="<?= PHONE_LENGTH ?>" inputmode="numeric" pattern="[0-9]{<?= PHONE_LENGTH ?>}" data-phone-input>
								</div>
							</div>
							

						</div>
						
						
						<div class="row">
							<div class="col-sm-3">
								<!-- text input -->
								<div class="form-group">
									<label>Address</label>
									<textarea required class="form-control" placeholder="Enter Address" name="u_address1"><?php echo esc($u_address1); ?></textarea>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label>Province</label><input type="hidden" id="hprovince" value="<?php echo $u_provice; ?>" >
									<select required class="form-control " name="u_provice" onChange="getpcities(this.value);">>
										<?php if($province){ ?>
										<?php foreach($province as $record){ ?>
										<option value="<?php echo $record->p_id; ?>"
											<?php echo ($u_provice==$record->p_id)?"selected":""; ?>>
											<?php echo $record->p_name; ?></option>
										<?php } ?>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label>City</label><input type="hidden" id="hcity" value="<?php echo $u_city; ?>" >
									<select required name="u_city" id="city" class="form-control">
										<option value="">Select City</option>
									</select>
								</div>
							</div>
							<div class="col-sm-3">
								<!-- text input -->
								<div class="form-group">
									<label>Postal Code</label><span class="text-danger font-weight-bold ml-1" style="font-size:12px;"> e.g., M5A 1A1</span>
									<input required class="form-control" placeholder="Enter Postal Code" name="u_pincode" value="<?php echo esc($u_pincode); ?>" >
								</div>
							</div>
						</div>


	                    <div class="row mrg-top-30">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group text-center">
	                                <input type="hidden" id="base" value="<?php echo base_url(); ?>">
	                                <input type="submit" class="btn btn-common" name="updateprofile" value="Update Profile" />
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
