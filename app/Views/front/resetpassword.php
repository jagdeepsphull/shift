


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

					
					<div class="tab-class text-center wow fadeInUp" data-wow-delay="0.3s">
						<ul class="nav nav-pills d-inline-flex justify-content-end mb-5 bg-light py-2">
							<li class="nav-item me-2 ms-2 rounded">									
								<h6 class=" btn btn-primary py-2 mt-n1 mb-0">Forgot Password</h6>									
							</li>
						</ul>
						
						<div class="row justify-content-center  text-left">
							<div class="col-md-6" >
								<form id="reset-form" class="form" action="" method="post">
									<div class="form-group">
										<input type="hidden" name="token" value="<?= $token ?>">
										<label>New Password</label>
										<div class="input-with-icon">
											<input type="password" class="form-control" placeholder="Enter new password"
												name="password" id="password">
											<i class="theme-cl ti-user"></i>
										</div>
									</div>

									<div class="form-groups">
										<input type="submit" name="resetSubmit" class="btn btn-common theme-bg full-width"
											value="Reset Password">
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

