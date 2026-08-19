	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="ti-briefcase"></i>Edit Shift</h4>
	            </div>

	            <?php  echo session()->getFlashdata('error_msg'); ?>
				
	            <form name="post-job" action="" method="post">
	                <div class="dashboard-caption-wrap">

	                    

	                    <div class="row">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
	                                <h4>Shift Detail</h4>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="row">
	                        <?php /* <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
	                                <div class="form-group">
										<label>Shift Title</label>
										<input type="text" required class="form-control" name="p_job_title"  placeholder="Enter Shift Title" value="<?php echo $p_job_title; ?>">
									</div>
	                            </div>
	                        </div> */ ?>
							<div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Store (Location)</label>
									<?php /* See post_job.php: moving the shift to another
									   store re-ticks from that store's defaults. Nothing
									   fires on load, so the shift's own saved boxes stand. */ ?>
									<select required class="form-control" name="p_store_id" id="p_store_id">
										<option value="">-- Choose Store --</option>
										<?php if($stores) {
											foreach($stores as $store){
										?>
											<option value="<?php echo $store->s_id ?>" <?php echo ($p_store_id==$store->s_id)?"selected":""; ?> ><?php echo esc($store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : '')) ?></option>
										<?php }
										}
										?>
									</select>
								</div>
	                        </div>
							<div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Shift Requested For</label>
									<select required class="form-control" name="p_shift_for">
										<option value="" selected>-- Choose Shift Requested For --</option>
										<?php if($shift_for) {
											foreach($shift_for as $shifts){
										?>
											<option value="<?php echo $shifts->sf_id ?>" <?php echo ($p_shift_for==$shifts->sf_id)?"selected":""; ?> ><?php echo $shifts->sf_name ?></option>
										<?php }
										}
										?>
									</select>
								</div>
	                        </div>
							<?php /* What the shift pays is the group's decision, not the
							   branch's, so a manager does not see it here - and leaving the
							   field out means their save carries no rate to write over the
							   one the group set. Left out rather than hidden: it is
							   `required`, and a required control the browser cannot show
							   blocks the submit silently. See Employer::setup(). */ ?>
							<?php if ($can_set_rate) { ?>
								<div class="col-lg-4 col-md-4 col-sm-2">
									<div class="form-group">
										<label>Hourly Rate</label>
										<input type="number" required min="10" max="200" class="form-control" name="p_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo $p_hourly_rate; ?>">
									</div>
								</div>
							<?php } ?>
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Select Date</label>
									<input type="text" required class="form-control date" name="p_dates" placeholder="Pick date" value="<?php echo $p_dates; ?>">
								</div>
	                        </div>

	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Shift Time</label>
									<input required type="text" class="form-control timePicker" name="p_shift_time" placeholder="Shift Time" value="<?php echo $p_shift_time; ?>">
								</div>
	                        </div>
						</div>
						
	                    <div class="row">
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<?= view('partials/checkbox_grid', ['name' => 'p_skills', 'label' => 'Software', 'items' => $software_skills, 'idKey' => 'ss_id', 'labelKey' => 'ss_name', 'selected' => $p_skills, 'required' => true,]) ?>
								</div>
	                        </div>

	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<?= view('partials/checkbox_grid', ['name' => 'p_services', 'label' => 'Details', 'items' => $store_service, 'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $p_services, 'required' => false,]) ?>
								</div>
	                        </div>

	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<?php /* Not required, unlike the two beside it: this master starts
									   empty, and a required group with nothing to tick would make
									   editing a shift impossible until somebody filled it in. */ ?>
									<?= view('partials/checkbox_grid', ['name' => 'p_additional_details', 'label' => 'Additional Details', 'items' => $additional_details, 'idKey' => 'ad_id', 'labelKey' => 'ad_name', 'selected' => $p_additional_details, 'required' => false,]) ?>
								</div>
	                        </div>

	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
									<?php /* Named apart from the "Additional Details" tick-box group
									   above it, which is a different field on a different table. */ ?>
									<label>Additional details for agency</label>
									<textarea class="form-control summernote" name="p_jobinfo" id="p_jobinfo" ><?php echo $p_jobinfo;?></textarea>
								</div>
	                        </div>



	                    </div>

	                    

	                    <div class="row mrg-top-30">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group text-center">
	                                <input type="hidden" id="base" value="<?php echo base_url(); ?>">
	                                <input type="submit" class="btn btn-common" name="savepostjob" value="Edit Shift" />
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
