	
	
	<!-- Start post Area -->
			<section class="post-area section-gap">
				<div class="container">
					<div class="row justify-content-center d-flex">
						<div class="col-lg-8 post-list">
							<div class="single-post d-flex flex-row">
								<div class="thumb">
									<?php if(!empty($jobdetail[0]->u_a_company_logo)){?>    
									<div class="freelance-image"><a href="#"><img src="<?php echo base_url('uploads/agency/'.$jobdetail[0]->u_a_company_logo); ?>"
											class="img-responsive" alt=""></a></div>
									<?php } else {?>
									<img src="<?php echo base_url()?>assets/front/img/post.png" alt="">
									<?php } ?>
									
								</div>
								<div class="details w-100 ">
									<div class="title d-flex flex-row justify-content-between">
										<div class="titles">
											<a href="#"><h4><?php echo $jobdetail[0]->p_job_title; ?></h4></a>
														
										</div>
										
										<ul class="">
										<?php if($applied==1){ ?>
											<li><a class="p-3 mb-2 bg-warning text-dark">Already Applied</a></li>
										<?php }else{ ?>
											<li><a class="font-weight-bold btn btn-common" href="<?php echo $applylink; ?>">Apply</a></li>
										<?php } ?>
											
										</ul>
									</div>
									<p >
										<?php //echo $jobdetail[0]->p_jobinfo; ?>
									</p>
									<h6><span class="font-weight-bold mr-2">Shift Requested For:</span> <?php echo getShiftForName($jobdetail[0]->p_shift_for); ?></h6>
									
									<?php if ($shift_store && trim((string) $shift_store->s_name) !== '') { ?>
									<h6><span class="font-weight-bold mr-2">Store:</span> <?php echo esc($shift_store->s_name . ($shift_store->s_number !== '' ? ' (' . $shift_store->s_number . ')' : '')); ?></h6>
									<?php } ?>
									<p class="icon"><i class="lni lni-map-marker"></i> <?php
										// The address of the store the shift is at, then city and province.
										$location = ($shift_store && trim((string) $shift_store->s_address) !== '') ? esc($shift_store->s_address) . ', ' : '';
										echo $location . $jobdetail[0]->c_name . ', ' . $jobdetail[0]->p_name;
									?></p>
									<?php if ($is_booked_viewer && $shift_store && trim((string) $shift_store->s_phone) !== '') { ?>
									<p class="icon"><i class="lni lni-phone-handset"></i> <?php echo esc($shift_store->s_phone); ?></p>
									<?php } ?>
									<p class="icon"><i class="lni lni-wallet"></i> To be disclosed</p>
									<p class="icon"><i class="lni lni-calendar"></i> <?php echo dateFormat($jobdetail[0]->p_dates); ?></p>
									<p class="icon"><i class="lni lni-alarm-clock"></i> <?php echo $jobdetail[0]->p_shift_time; ?></p>
									<?php /*<div><a  data-toggle="modal" data-target="#myModalReview" class="review" id="review_<?php echo $jobdetail[0]->p_id; ?>" style="cursor:pointer;">Review(s) <?php echo $totreviews[0]->tot; ?></a></div> */  ?>
								</div>
							</div>	
							<div class="single-post job-details">
								<h4 class="single-title mt-2">Job Details</h4>
								<h6><span class="font-weight-bold mr-2">Softwares: </span><?php echo getSoftwareSkills($jobdetail[0]->p_skills); ?></h6>
								<h6><span class="font-weight-bold mr-2">Details: </span><?php echo getStoreServices($jobdetail[0]->p_services); ?></h6>
								<?php /* Saved on every shift form but shown nowhere until now, so anything
								   the pharmacy wrote about the shift never reached the applicant. */ ?>
								<?php if (trim(strip_tags((string) $jobdetail[0]->p_jobinfo)) !== '') { ?>
									<h6 class="font-weight-bold mt-3 mb-2">Additional details</h6>
									<div class="additional-details"><?php echo $jobdetail[0]->p_jobinfo; ?></div>
								<?php } ?>
							</div>
								
						</div>
						<div class="col-lg-4 sidebar">
							<div class="single-slidebar">
							<h4>Related job posts</h4>
								  <div id="rel-jobs" class="owl-carousel wow fadeInUp" data-wow-delay="1.2s">
								  <?php if($relatedjobs) { ?>
										<?php foreach($relatedjobs as $relatedjob) { ?>
									  <div class="item">
										<div class="testimonial-item">
										  <div class="img-thumb">
											<img src="assets/img/testimonial/img1.jpg" alt="">
										  </div>
										  <div class="info">
											<h2><a href="<?php echo base_url('front/job_detail/'.$relatedjob->p_id)?>"><?php echo $relatedjob->p_job_title; ?></a></h2>
											<h3><?php //echo $relatedjob->u_comp_name; ?></h3>
										  </div>
										  <div class="content">
											<p class="description"><?php //echo substr($relatedjob->p_jobinfo, 0,75).'....'; ?></p>
											<h6>Shift Requested For: <?php echo getShiftForName($relatedjob->p_shift_for); ?></h6>
											<p class="icon"><i class="lni lni-map-marker"></i> <?php echo $relatedjob->p_name. ', '. $relatedjob->c_name; ?></p>
											<p class="icon"><i class="lni lni-wallet"></i> To be disclosed</p>
										  </div>
										</div>
									  </div>
									  <?php } ?>
									<?php } ?>
              
									</div>
							</div>

						</div>
					</div>
				</div>	
			</section>
			<!-- End post Area -->
			

        