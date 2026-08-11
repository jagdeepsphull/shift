<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Update <?php echo $pageinfo['title']; ?> </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a
                                href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">Update</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <?php echo session()->getFlashdata('error_msg'); ?>
	
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
		<?php endif; ?>
    <!-- Main content -->
    <form name="editform" action="" method="post">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Company Information</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

                                <div class="row">
									<div class="col-sm-4">
										<!-- text input -->
										<div class="form-group">
											<label>Choose Employer</label>
											<select class="form-control " name="u_id" id="u_id" required>
												<option value="">-- Select Employer -- </option>
												<?php if($agencies){?>
												  <?php foreach($agencies as $agency){?>
												  <option value="<?php echo $agency->u_id;?>" <?php echo ($u_id==$agency->u_id)?"selected":""; ?> >
													  <?php echo $agency->u_comp_name;?></option>
												  <?php } ?>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-sm-4">
										<!-- text input -->
										<div class="form-group">
											<label>Store (Location)</label>
											<select class="form-control" name="p_store_id" id="p_store_id" required>
												<option value="">-- Select Store --</option>
												<?php if($agency_stores){?>
												  <?php foreach($agency_stores as $store){?>
												  <option value="<?php echo $store->s_id;?>" <?php echo ($p_store_id==$store->s_id)?"selected":""; ?> >
													  <?php echo esc($store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : ''));?></option>
												  <?php } ?>
												<?php } ?>
											</select>
										</div>
									</div>
                                <!-- /.card-body -->

								</div>
							</div>
						</div>
					</div>

                        <!-- left column -->
					<div class="col-md-12">

						<div class="card card-info">
							<div class="card-header">
								<h3 class="card-title">Shift Detail</h3>
							</div>
							<!-- /.card-header -->
							<div class="card-body">


								<div class="row">
									<?php /* <div class="col-sm-4">
										<!-- text input -->
										<div class="form-group">
											<label>Job Title</label>
											<input type="text" required class="form-control" name="p_job_title"  placeholder="Enter Job Title" value="<?php echo $p_job_title; ?>">
										</div>
									</div> */ ?>
									<div class="col-sm-4">
										<!-- text input -->
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
								
									<div class="col-sm-4">
										<div class="form-group">
											<label>Hourly Rate</label>
											<input type="number" required min="10" max="200" class="form-control" name="p_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo $p_hourly_rate; ?>">
											
										   
										</div>
									  </div>
									<div class="col-sm-4">
										<div class="form-group">
											<label>Actual Hourly Rate (to be visible on the website)</label>
											<input type="number" required min="10" max="200" class="form-control" name="p_ac_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo $p_ac_hourly_rate; ?>">
											
										  
										</div>
									  </div>
								</div>
								<div class="row">	
									<div class="col-sm-4">
										<!-- text input -->
										<label>Select Date</label>
										<div class="form-group">
											<input required type="text" class="form-control date" name="p_dates" placeholder="Pick date" value="<?php echo $p_dates; ?>">
										</div>
									</div>
									<div class="col-sm-4">
										<div class="form-group">
											<label>Shift Time</label>
											<input required type="text" class="form-control timePicker" name="p_shift_time" placeholder="Shift Time" value="<?php echo $p_shift_time; ?>">
										</div>
									</div>
								   
								</div>
								
								
								<div class="row">	
									
									<div class="col-sm-3">
										<!-- text input -->
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 'p_skills', 'label' => 'Software', 'items' => $software_skills, 'idKey' => 'ss_id', 'labelKey' => 'ss_name', 'selected' => $p_skills, 'required' => true,]) ?>
										</div>
									</div>
									<div class="col-sm-3">
										<!-- text input -->
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 'p_services', 'label' => 'Details', 'items' => $store_service, 'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $p_services, 'required' => true,]) ?>
										</div>
									</div>
									
									<div class="col-sm-12">
										<div class="form-group">
											<label>Additional details</label>
											<textarea class="form-control" name="p_jobinfo" id="p_jobinfo" ><?php echo $p_jobinfo;?></textarea>
										</div>
									</div>
								</div>




							</div>
							<!-- /.card-body -->

						</div>



					</div>
                    

					<!-- left column -->
					<div class="col-md-12">

						<div class="card card-info">
							
							<div class="card-header">
                                <h3 class="card-title">Shift Status</h3>
                            </div>
							
							<!-- /.card-header -->
							<div class="card-body">
								<div class="row">
                                    
                                    
									
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Shift Approval</label>
                                            <select   class="form-control " name="p_approved" id="p_approved">
                                                <?php /* Only the statuses an agency may set by hand. "Inactive
                                                   (Expired)" is set by the nightly job; if this shift already
                                                   carries it, it is shown so that saving does not silently
                                                   revive the shift as Pending. */ ?>
                                                <?php $options = $approvedSelectable;
                                                      if (!array_key_exists($p_approved, $options)) {
                                                          $options = [$p_approved => $approved[$p_approved] ?? 'Unknown'] + $options;
                                                      } ?>
                                                <?php foreach($options as $ky=>$vl){ ?>
                                                <option value="<?php echo $ky; ?>"
                                                    <?php echo ($p_approved==$ky)?"selected":""; ?>>
                                                    <?php echo $vl; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
									
                                </div>
								


							</div>
							<!-- /.card-body -->

							

						</div>



					</div>
					
                    <!-- left column -->
					<div class="col-md-12">

						<div class="card card-info">
							
							<!-- /.card-header -->
							<div class="card-body">
								


							</div>
							<!-- /.card-body -->

							<!-- /.card-body -->
							<div class="card-footer">
								<input type="submit" name="savedata" class="btn btn-primary"
									value="Update <?php echo $pageinfo['title']; ?>" />
							</div>

						</div>



					</div>
                    

                 

                </div>
            </div>
        </section>
		<form>
</div>