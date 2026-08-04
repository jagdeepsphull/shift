	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="ti-briefcase"></i>Apply Job</h4>
	            </div>
            <?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}		
			
			
		?>
		
			<section class="post-area">
				<div class="container">
					<div class="row justify-content-center d-flex">
						<div class="col-lg-12 post-list">
							<div class="single-post d-flex flex-row">
								<div class="thumb">
									<?php if(!empty($jobdetail[0]->u_a_company_logo)){?>    
									<div class="freelance-image"><a href="#"><img src="<?php echo base_url('uploads/agency/'.$jobdetail[0]->u_a_company_logo); ?>"
											class="img-responsive" alt=""></a></div>
									<?php } else {?>
									<img src="<?php echo base_url()?>assets/front/img/post.png" alt="">
									<?php } ?>
									
								</div>
								
								<div class="brows-job-position ">
									<h3><a href="<?php echo base_url('front/job_detail/'.$jobdetail[0]->p_id); ?>"><?php echo $jobdetail[0]->p_job_title; ?></a>
									</h3>
									<p class="pb-2">
										<span><?php //echo getPharmacyName($jobdetail[0]-> u_id ); ?></span>
										<h6><span class="font-weight-bold mr-2">Shift Requested For: </span><?php echo getShiftForName($jobdetail[0]->p_shift_for); ?></h6>
										<h6><span class="font-weight-bold mr-2">Softwares: </span><?php echo getSoftwareSkills($jobdetail[0]->p_skills); ?></h6>
										<h6><span class="font-weight-bold mr-2">Details: </span><?php echo getStoreServices($jobdetail[0]->p_services); ?></h6>
										<p class="icon"><i class="lni lni-map-marker"></i> <span class="font-weight-bold ml-2 text-dark"><?php echo getProvinceName($jobdetail[0]->p_province). ', '. getCityName($jobdetail[0]->p_city);$jobdetail[0]->c_name; ?></span></p> 
										<p class="icon"><i class="lni lni-wallet"></i> <span class="font-weight-bold ml-2 text-dark"><?php echo 'CAD$ '. $jobdetail[0]->p_ac_hourly_rate.'/Hour' ; ?></span></p>
										<p class="icon"><i class="lni lni-calendar"></i> <span class="font-weight-bold ml-2 text-dark"><?php echo dateFormat($jobdetail[0]->p_dates); ?></span></p>
										<p class="icon"><i class="lni lni-alarm-clock"></i> <span class="font-weight-bold ml-2 text-dark"><?php echo $jobdetail[0]->p_shift_time; ?></span></p>
										
									</p>
								
									<form name="applyjob" action="" method="post" class="pt-4">
										<h4 class="detail-title pt-2 ml-3">Additional Remarks</h4>
										<div class="col-md-12 col-sm-12">
											<textarea rows="4"  cols="50" title="This field is required. Please Enter Hourly Rate and other Remarks." class="form-control textarea" placeholder=" Additional Remarks" name="sj_applied_desc"><?php echo $sj_applied_desc;?></textarea>
        <br><br>
										</div>
										<div class="col-md-12 col-sm-12">
											<input type="submit" class="btn btn-common small-btn" value="Apply Job"
												name="applyjob" />
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>	
			</section>
			<!-- End post Area -->
            
        </div>
        
        <!-- Basic Full Detail Form End -->
		</div>
	    </div>
	</div>