


<!-- Contact Section Start -->
    <section id="contact1" class="section-padding">    
      <div class="container  mt-3">
       
		
			<div class="row g-4 justify-content-center">
					<div class="col-md-10 border border-light p-5 bg-gray shadow rounded ">
			
						<h3 class="text-center mb-5 wow fadeInUp " data-wow-delay="0.1s">Access Your Account</h3>
						<div class="clearfix"></div>
						<!-- Title Header End -->
						<?php  if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');} ?>
						<!-- Tab Section Start -->

						<?php  $show_registration = isset($show_registration) && $show_registration; ?>
						
						<div class="tab-class text-center wow fadeInUp" data-wow-delay="0.3s">
							<ul class="nav nav-pills wz-authtabs d-inline-flex justify-content-center mb-5">
								<li class="nav-item">
									<a class="btn wz-auth-tab <?= $show_registration ? '' : 'active'; ?>" data-bs-toggle="pill" href="#login">
										<h6 class="mt-n1 mb-0">Login</h6>
									</a>
								</li>
								<li class="nav-item">
									<a class="btn wz-auth-tab <?= $show_registration ? 'active' : ''; ?>" data-bs-toggle="pill" href="#register">
										<h6 class="mt-n1 mb-0">Register</h6>
									</a>
								</li>
							</ul>
							
							<div class="tab-content text-left">
								<div id="login" class="tab-pane fade <?= $show_registration ? '' : ' show p-0 active'; ?>">
									<form id="login-form" class="form" action="" method="post">
									<div class="row justify-content-center">
										<div class="col-md-6" >
											
												<div class="form-group">
													<label>User Name(Email ID)</label>
													<div class="input-with-icon">
														<input type="text" class="form-control" placeholder="Enter Your User name"
															name="username" id="username" required>
														<i class="theme-cl ti-user"></i>
													</div>
												</div>


												<div class="form-group">
													<label>Password</label>
													<div class="input-with-icon">
														<input type="password" class="form-control" placeholder="Enter Your Password"
															name="password" id="password" required>
														<i class="theme-cl ti-lock"></i>
													</div>
												</div>
												
											
												<div class="form-group">
													<label for="captcha">Verification Code</label><img class="ml-2 mb-3" src="<?php echo site_url('front/test_cap');  ?>" />
													<input type="text" value="" class="form-control" id="captcha" size="6" name="captcha">
												
												</div>


												

											
										</div>
									</div>
									
									<div class="row justify-content-center">
										<div class="col-sm-6">										

											<div class="form-groups">
												<input type="submit" name="loginSubmit" class="btn btn-common theme-bg full-width"
													value="Login">
											</div>
											
											<div class="register-account text-left mt-3">
												<span class="font-weight-bold text-dark" >Forgot Password?  </span>
												<a class="theme-cl" href="<?php echo base_url('front/forgot_password') ; ?>" target="_blank" > Click Here</a> 
													
											</div>
										</div>
									</div>
									</form>
								</div>


								<div id="register" class="tab-pane fade <?= $show_registration ? ' show p-0 active' : ''; ?>">
									<form id="register-form" class="form" action="<?php echo base_url('front/register');?>" method="post" novalidate>
										<div class="row">
											<div class="col-sm-6">
												<div class="form-group">
													<label>Select User Type</label>
													<?php /* The first three are all `users.u_usertype` 1 - they differ
													   by how many stores the login owns and whether it answers to a
													   pharmacy group, which register() turns into `u_emp_role` and
													   `u_parent_id` (change request B4). */ ?>
													<select id="usrtpe"  name="reg_type" class="form-control">
														<option value="" >-- Select User Type --</option>
														<option value="manager" <?php echo (($reg_type ?? '') === 'manager') ? 'selected' : ''; ?>>Manager</option>
														<option value="owner_multi" <?php echo (($reg_type ?? '') === 'owner_multi') ? 'selected' : ''; ?>>Owner (Multi Store)</option>
														<option value="owner_individual" <?php echo (($reg_type ?? '') === 'owner_individual') ? 'selected' : ''; ?>>Owner (Individual Store)</option>
														<option value="applicant" <?php echo (($reg_type ?? '') === 'applicant') ? 'selected' : ''; ?>>Applicant</option>
													</select>
												</div>
											</div>
											<div class="col-sm-6 usrsubtpe">
												<div class="form-group">
													<label>Select Applicant Type</label>											
													<select  name="u_usersubtype" id="u_usersubtype" class="form-control">
														<option value="" >-- Select Applicant Type --</option>
														<?php if($shift_for){
																foreach($shift_for as $shifts) {?>
															<option value="<?php echo $shifts->sf_id; ?>" <?php echo ($u_usersubtype == $shifts->sf_id) ? 'Selected' :'' ?>><?php echo $shifts->sf_name?></option>
														<?php 	}
														}
														?>
													</select>	
												</div>
											</div>
										</div>
												
										<?php /* "Store Name" for a single-store owner, "Pharmacy Name" for
										   a multi-store one - the same column either way, relabelled by the
										   script in front/footer.php. */ ?>
										<div class="row agncy d-none">
											<div class="col-sm-6">
												<div class="form-group">
													<label id="compnamelbl">Store Name</label>
													<input type="text" class="form-control" placeholder="Enter Your Store Name" name="u_comp_name" id="u_comp_name"  value="<?php echo $u_comp_name; ?>">
												</div>
											</div>

											<?php /* A manager runs a store on behalf of a multi-store owner,
											   so the group is who they answer to and is required. An owner
											   of an individual store answers to nobody and is never asked. */ ?>
											<?php if (! empty($pharmacy_groups)) { ?>
											<div class="col-sm-6 grouponly">
												<div class="form-group">
													<label>Pharmacy Group</label>
													<select required name="u_parent_id" id="u_parent_id" class="form-control">
														<option value="">-- None --</option>
														<?php foreach ($pharmacy_groups as $group) { ?>
														<option value="<?php echo $group->u_id; ?>" <?php echo (($u_parent_id ?? '') == $group->u_id) ? 'selected' : ''; ?>>
															<?php echo esc($group->u_comp_name); ?></option>
														<?php } ?>
													</select>
												</div>
											</div>
											<?php } ?>
										</div>

										<div class="row">
											<div class="col-sm-6">
												<div class="form-group">
													<label>First Name</label>
													<input type="text"     onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}"   class="form-control" placeholder="Enter First Name" id="u_fname" name="u_fname" value="<?php echo $u_fname; ?>">
												</div>
											</div>
										
											<div class="col-sm-6">
												<div class="form-group">
													<label><label>Last Name</label></label>
													<input type="text"     onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}"   class="form-control" placeholder="Enter Last Name" id="u_lname" name="u_lname" value="<?php echo $u_lname; ?>">
												</div>
											</div>
										</div>
										
										<?php /* Store-specific: a manager registers the person, not a
										   location, so these are hidden for them and their stores carry
										   the licence and address instead (change request B4). */ ?>
										<div class="row storeonly">
											<div class="col-sm-6">
												<div class="form-group">
													<label id="liprov">License Province</label>
													<select  class="form-control " name="u_l_provice" id="u_l_provice" >
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
												
											<div class="col-sm-6">
												<div class="form-group">
													<label id="lireg">Licence No.</label> 
													<input  type="text" class="form-control" placeholder="Enter Licence No." id="u_licence_no" name="u_licence_no" value="<?php echo $u_licence_no; ?>">
												</div>
											</div>
										</div>
										
										<div class="row">
											<div class="col-sm-6">
												<div class="form-group">
													<label>Email (As your userid)</label>
													<input  type="email" class="form-control" placeholder="Enter Email Id" id="u_email" name="u_email" value="<?php echo $u_email; ?>">
												</div>
											</div>
										
											<div class="col-sm-6">
												<div class="form-group">
													<label>Mobile No.</label>
													<div class="input-with-icon">
														<input  type="text" class="form-control" placeholder="Enter Mobile No." id="u_phone" name="u_phone" value="<?php echo $u_phone; ?>">
													</div>
												</div>
											</div>
										</div>
										
										<div class="row storeonly">

											<div class="col-sm-3">
												<!-- text input -->
												<div class="form-group">
													<label>Address</label>
													<textarea required class="form-control" placeholder="Enter Address" name="u_address1"><?php echo $u_address1; ?></textarea>
												</div>
											</div>
											
											<div class="col-sm-3">
												<div class="form-group">
													<label>Province</label><input type="hidden" id="hprovince" value="<?php echo $u_provice; ?>" >
													<select  required class="form-control " name="u_provice" id="provincelist" onChange="getpcities(this.value);">
														<option value="">Select Province</option>
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
													<select  required name="u_city" id="city" class="form-control">
														<option value="">Select City</option>
													</select>
												</div>
											</div>
											
											<div class="col-sm-3">
												<!-- text input -->
												<div class="form-group">
													<label>Postal Code</label><span class="text-danger font-weight-bold ml-1" style="font-size:12px;">(e.g. M5A 1A1)</span>
													<input required class="form-control" placeholder="Enter Postal Code" name="u_pincode" value="<?php echo $u_pincode; ?>" >
													
												</div>
											</div>
										</div>

										<div class="row">
											<div class="col-sm-6">
										
												<div class="form-group">
													<label>Password</label>
													<input type="password" class="form-control" placeholder="Enter Your Password" name="password" id="mainpassword" value="">
												</div>
											</div>

											<div class="col-sm-6">
												<div class="form-group">
													<label>Confirm Password</label>
													<input type="password" class="form-control" placeholder="Enter Your Confirm Password" name="conf_password" id="conf_password" >
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
												<div class="form-group">
													<label for="captcha">Verification Code</label><img class="ml-2 mb-3" src="<?php echo site_url('front/test_cap');  ?>" />
													<input type="text" value="" class="form-control" id="captcha" size="6" name="captcha">
												
												</div>
											</div>
										</div>
											
										<?php 
										
										/* <div class="row">
											<div class="col-sm-6">
												<label for="captcha">CAPTCHA</label>
												<div> <?php echo $captcha; ?> <!-- Display CAPTCHA image -->
												</div>
												<input type="text" name="captcha" placeholder="Enter CAPTCHA" required>
											</div>
										</div> */
										
										?>
										
										<div class="row justify-content-center">
											<div class="col-sm-12">
												<div class="register-account text-center mb-3">
													By hitting the <span class="theme-cl">"Register"</span> button, you agree to the 
													<a class="theme-cl" href="<?php echo base_url('terms') ; ?>" target="_blank" >Terms conditions</a> 
														and 
													<a class="theme-cl" href="<?php echo base_url('policy') ; ?>" target="_blank" >Privacy Policy</a>
												</div>

												<div class="form-groups">
													<input type="submit" name="signupSubmit" class="btn btn-common theme-bg full-width"
														value="Register yourself">
												</div>
											</div>
										</div>


									</form>
								</div>

                </div>
							
						</div>
					</div>
				</div>
			
		
      </div> 
    </section>
    <!-- Contact Section End -->

