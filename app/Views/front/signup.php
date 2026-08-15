


<!-- Contact Section Start -->
    <section id="contact1" class="section-padding">    
      <div class="container  mt-3">
       
		
			<?php  $show_registration = isset($show_registration) && $show_registration; ?>

			<div class="row g-4 justify-content-center">
					<?php /* One card serves both tabs, and they want different
					   widths: Login is a single column of three fields, Register
					   is a two-column grid. The card sizes itself to whichever is
					   open - see `is-register` below, and the toggle at the foot
					   of this file. */ ?>
					<div class="wz-auth-card<?= $show_registration ? ' is-register' : ''; ?>">

						<h3 class="text-center wz-auth-title wow fadeInUp" data-wow-delay="0.1s">Access Your Account</h3>
						<div class="clearfix"></div>
						<!-- Title Header End -->
						<?php  if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');} ?>
						<!-- Tab Section Start -->

						<div class="tab-class text-center wow fadeInUp" data-wow-delay="0.3s">
							<ul class="nav nav-pills wz-authtabs d-inline-flex justify-content-center mb-4">
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
							
							<div class="tab-content text-start">
								<div id="login" class="tab-pane fade <?= $show_registration ? '' : ' show p-0 active'; ?>">
									<form id="login-form" class="form" action="" method="post">
										<div class="form-group">
											<label for="username">User Name (Email ID)</label>
											<div class="input-with-icon">
												<input type="text" class="form-control" placeholder="Enter Your User Name"
													name="username" id="username" autocomplete="username" required>
												<i class="theme-cl lni-user"></i>
											</div>
										</div>

										<div class="form-group">
											<label for="password">Password</label>
											<div class="input-with-icon">
												<input type="password" class="form-control" placeholder="Enter Your Password"
													name="password" id="password" autocomplete="current-password" required>
												<i class="theme-cl lni-lock"></i>
											</div>
										</div>

										<div class="form-group">
											<?php /* The image used to sit beside its own label on one line,
											   which left the field below it looking unlabelled. Image and
											   field now share a row, with a button for a fresh code so a
											   misread one does not mean reposting the form. */ ?>
											<label for="login_captcha">Verification Code</label>
											<div class="wz-captcha-row">
												<img class="wz-captcha-img" src="<?php echo site_url('front/test_cap');  ?>" alt="Verification code">
												<button type="button" class="wz-captcha-refresh" title="Get a new code"><i class="lni-reload"></i></button>
												<input type="text" value="" class="form-control" id="login_captcha" size="6" maxlength="6"
													inputmode="numeric" autocomplete="off" placeholder="Enter the code" name="captcha" required>
											</div>
										</div>

										<div class="form-groups">
											<input type="submit" name="loginSubmit" class="btn btn-common theme-bg full-width"
												value="Login">
										</div>

										<div class="register-account text-center mt-3">
											<span class="font-weight-bold text-dark" >Forgot Password?  </span>
											<a class="theme-cl" href="<?php echo base_url('front/forgot_password') ; ?>" > Click Here</a>
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
													   corporate group, which register() turns into `u_emp_role` and
													   `u_parent_id` (change request B4). */ ?>
													<select id="usrtpe"  name="reg_type" class="form-control">
														<option value="" >-- Select User Type --</option>
														<?php foreach (($registerTypes ?? []) as $typeCode => $typeDef) { ?>
														<option value="<?php echo (int) $typeCode; ?>"
															data-employer="<?php echo $typeDef['empRole'] === null ? '0' : '1'; ?>"
															<?php echo ((string) ($reg_type ?? '') === (string) $typeCode) ? 'selected' : ''; ?>>
															<?php echo esc($typeDef['label']); ?></option>
														<?php } ?>
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
												
										<?php /* A manager runs a store on behalf of a multi-store owner, so
										   the group is who they answer to and is required. It sits directly
										   under the type they just picked, because choosing Manager is the
										   only thing that raises the question. An owner of an individual
										   store answers to nobody and is never asked. */ ?>
										<?php if (! empty($pharmacy_groups)) { ?>
										<div class="row grouponly d-none">
											<div class="col-sm-6">
												<div class="form-group">
													<label>Corporate Group</label>
													<select required name="u_parent_id" id="u_parent_id" class="form-control">
														<option value="">-- None --</option>
														<?php foreach ($pharmacy_groups as $group) { ?>
														<option value="<?php echo $group->u_id; ?>" <?php echo (($u_parent_id ?? '') == $group->u_id) ? 'selected' : ''; ?>>
															<?php echo esc($group->u_comp_name); ?></option>
														<?php } ?>
													</select>
												</div>
											</div>
										</div>

										<?php /* Which of that group's stores they run. A manager joins a
										   location that already exists, so the name, number and address
										   this form used to ask them to retype come off the store itself.
										   Filled by the script in front/footer.php the moment a group is
										   chosen; the hidden field brings the choice back after a failed
										   submit, exactly as #hcity does for the city list. */ ?>
										<div class="row storepick d-none">
											<div class="col-sm-6">
												<div class="form-group">
													<label>Store</label><input type="hidden" id="hstoreid" value="<?php echo (int) ($u_store_id ?? 0); ?>">
													<select required name="u_store_id" id="u_store_id" class="form-control">
														<option value="">-- Select Store --</option>
													</select>
												</div>
											</div>
										</div>
										<?php } ?>

										<?php /* "Store Name" for a single-store owner, "Corporate Group Name"
										   for a multi-store one - the same column either way, relabelled by
										   the script in front/footer.php. A manager is asked for neither:
										   the store they picked above already has a name. */ ?>
										<div class="row agncy d-none">
											<div class="col-sm-6">
												<div class="form-group">
													<label id="compnamelbl">Store Name</label>
													<input type="text" class="form-control" placeholder="Enter Your Store Name" name="u_comp_name" id="u_comp_name"  value="<?php echo $u_comp_name; ?>">
												</div>
											</div>
										</div>

										<?php /* Optional, and asked of every employer kind: the
										   group's corporate site for a multi-store owner, the
										   store's own page for a single one. */ ?>
										<div class="row agncy d-none">
											<div class="col-sm-6">
												<div class="form-group">
													<label id="websitelbl">Store Website <span class="text-muted" style="font-size:12px;">(optional)</span></label>
													<input type="url" class="form-control" placeholder="https://example.com" name="u_website" id="u_website" value="<?php echo esc($u_website ?? ''); ?>">
												</div>
											</div>
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
													<label>Last Name</label>
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
													<label id="liprov">Licence Province</label>
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
													<label>Email (As your User ID)</label>
													<input  type="email" class="form-control" placeholder="Enter Email ID" id="u_email" name="u_email" value="<?php echo $u_email; ?>">
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
													<input type="password" class="form-control" placeholder="Confirm Your Password" name="conf_password" id="conf_password" >
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
												<div class="form-group">
													<?php /* Same row as the login tab. A distinct id, too: both
													   tabs are in the page at once, and they used to share one. */ ?>
													<label for="register_captcha">Verification Code</label>
													<div class="wz-captcha-row">
														<img class="wz-captcha-img" src="<?php echo site_url('front/test_cap');  ?>" alt="Verification code">
														<button type="button" class="wz-captcha-refresh" title="Get a new code"><i class="lni-reload"></i></button>
														<input type="text" value="" class="form-control" id="register_captcha" size="6" maxlength="6"
															inputmode="numeric" autocomplete="off" placeholder="Enter the code" name="captcha">
													</div>
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
													<a class="theme-cl" href="<?php echo base_url('terms') ; ?>" target="_blank" >Terms &amp; Conditions</a>
														and 
													<a class="theme-cl" href="<?php echo base_url('policy') ; ?>" target="_blank" >Privacy Policy</a>
												</div>

												<div class="form-groups">
													<input type="submit" name="signupSubmit" class="btn btn-common theme-bg full-width"
														value="Register">
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

<script>
/* The card is narrow for Login and wide for Register - see wz-auth-card in
   theme.css. Bootstrap tells us which tab opened; nothing else here changes. */
document.addEventListener('DOMContentLoaded', function () {
    var card = document.querySelector('.wz-auth-card');

    if (card) {
        card.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function (e) {
                card.classList.toggle('is-register', (e.target.getAttribute('href') || '') === '#register');
            });
        });
    }

    /* A fresh verification image, without reposting the form. The query string
       is what stops the browser handing back the cached one. */
    document.querySelectorAll('.wz-captcha-refresh').forEach(function (button) {
        button.addEventListener('click', function () {
            var row = button.closest('.wz-captcha-row');
            var img = row.querySelector('.wz-captcha-img');
            var field = row.querySelector('input');

            img.src = '<?php echo site_url('front/test_cap'); ?>?_=' + new Date().getTime();
            field.value = '';
            field.focus();
        });
    });
});
</script>

