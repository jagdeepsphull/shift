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
	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Hourly Rate</label>
									<input type="number" required min="10" max="200" class="form-control" name="p_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo $p_hourly_rate; ?>">
									
								  
								</div>
	                        </div>
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
									<label>Software</label>
									<select required class="form-control" name="p_skills[]" size="4" multiple>
										<option value="" >-- Choose Software --</option>
										<?php $p_skills = explode(',' , $p_skills);if($software_skills) {
											foreach($software_skills as $skills){
										?>
											<option value="<?php echo $skills->ss_id ?>" <?php echo in_array($skills->ss_id, $p_skills) ? 'selected' : ''; ?>><?php echo $skills->ss_name ?></option>
										<?php }
										}
										?>
									</select>
								</div>
	                        </div>

	                        <div class="col-lg-4 col-md-4 col-sm-2">
	                            <div class="form-group">
									<label>Details</label>
									<select required class="form-control" name="p_services[]" size="4" multiple>
										<option value="" >-- Choose Details --</option>
										<?php $p_services = explode(',' , $p_services); if($store_service) {
											foreach($store_service as $services){
										?>
											<option value="<?php echo $services->st_id ?>" <?php echo in_array($services->st_id, $p_services) ? 'selected' : ''; ?>><?php echo $services->st_service_name ?></option>
										<?php }
										}
										?>
									</select>
								</div>
	                        </div>
	                        
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
									<label>Enter Shift Detail</label>
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
