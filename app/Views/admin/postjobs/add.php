<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add <?php echo $pageinfo['title']; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a
                                href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">Add</li>
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
									<?= view('partials/shift_store_picker', [
									    'shift_stores' => $shift_stores,
									    'p_store_id'   => $p_store_id,
									]) ?>
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
											<input type="text" required class="form-control" name="p_job_title"  placeholder="Enter Job Title" value="<?php echo esc($p_job_title); ?>">
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
											<input type="number" required min="10" max="200" class="form-control" name="p_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo esc($p_hourly_rate); ?>">
											
										  
										</div>
									</div>
									<div class="col-sm-4">
										<div class="form-group">
											<label>Actual Hourly Rate (to be visible on the website)</label>
											<input type="number" required min="10" max="200" class="form-control" name="p_ac_hourly_rate"  placeholder="Enter Hourly Rate" value="<?php echo esc($p_ac_hourly_rate); ?>">
											
										  
										</div>
									</div>
								</div>
								<div class="row">	
									<div class="col-sm-4">
										<!-- text input -->
										<div class="form-group">
											<label>Select Date</label>
											<input required type="text" class="form-control date" name="p_dates" placeholder="Pick date" value="<?php echo esc($p_dates); ?>">
										</div>
									</div>
									<div class="col-sm-4">
										<div class="form-group">
											<label>Shift Time</label>
											<input required type="text" class="form-control timePicker" name="p_shift_time" placeholder="Shift Time" value="<?php echo esc($p_shift_time); ?>">
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
											<?= view('partials/checkbox_grid', ['name' => 'p_services', 'label' => 'Details', 'items' => $store_service, 'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $p_services, 'required' => false,]) ?>
										</div>
									</div>
									<div class="col-sm-3">
										<?php /* Not required, unlike the two beside it: this master starts
										   empty, and a required group with nothing to tick would make
										   posting a shift impossible until somebody filled it in. */ ?>
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 'p_additional_details', 'label' => 'Additional Details', 'items' => $additional_details, 'idKey' => 'ad_id', 'labelKey' => 'ad_name', 'selected' => $p_additional_details, 'required' => false,]) ?>
										</div>
									</div>

									<div class="col-sm-12">
										<div class="form-group">
											<?php /* Named apart from the "Additional Details" tick-box group
											   above it, which is a different field on a different table. */ ?>
											<label>Additional details for agency</label>
											<textarea class="form-control" name="p_jobinfo" id="p_jobinfo" ><?php echo $p_jobinfo;?></textarea>
										</div>
									</div>
								</div>




							</div>
							<!-- /.card-body -->

						</div>



					</div>
                    

					<?php /* Booking straight off this form, for a shift already
					   agreed with somebody over the phone: it saves posting the
					   shift, waiting for that person to apply and approving
					   them on the applications screen. Add only - once a shift
					   is booked the edit screen refuses to touch it at all. */ ?>
					<!-- left column -->
					<div class="col-md-12">

						<div class="card card-info">

							<div class="card-header">
								<h3 class="card-title">Book an Applicant (optional)</h3>
							</div>

							<!-- /.card-header -->
							<div class="card-body">
								<div class="row">
									<div class="col-sm-4">
										<div class="form-group">
											<label>Book Shift For</label>
											<select class="form-control" name="sj_applicant_id" id="sj_applicant_id">
												<option value="">-- Nobody yet --</option>
												<?php if($applicants){ ?>
												  <?php foreach($applicants as $person){
													  $person_type = getShiftForName($person->u_usersubtype);
													  $person_name = trim($person->u_lname . ', ' . $person->u_fname)
														  . ($person_type !== '' ? ' - ' . $person_type : '')
														  . ' (' . $person->u_email . ')';
												  ?>
												  <option value="<?php echo $person->u_id;?>" <?php echo ($sj_applicant_id==$person->u_id)?"selected":""; ?> >
													  <?php echo esc($person_name);?></option>
												  <?php } ?>
												<?php } ?>
											</select>
											<small class="form-text text-muted">Leave this alone to post the shift and take applications as usual.</small>
										</div>
									</div>
									<div class="col-sm-8">
										<div class="form-group">
											<label>Message to the applicant</label>
											<textarea class="form-control" name="sj_admin_comment" id="sj_admin_comment" rows="3" placeholder="Anything they should know about the booking"><?php echo esc($sj_admin_comment);?></textarea>
											<small class="form-text text-muted">Choosing somebody books them the moment this shift is saved: it is set to <strong>Booked</strong>, and the booking e-mail goes to both them and the employer. Whatever is written here is included in their copy.</small>
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
                                                <?php /* Hand-set statuses only - "Closed (Expired)" belongs
                                                   to the nightly job, never to a new shift. */ ?>
                                                <?php foreach($approvedSelectable as $ky=>$vl){ ?>
                                                <option value="<?php echo $ky; ?>"
                                                    <?php echo ($p_approved==$ky)?"selected":""; ?>>
                                                    <?php echo $vl; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-5">
                                        <?= view('partials/shift_email_recipients', ['selected' => $p_email_to]) ?>
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
									value="Add <?php echo $pageinfo['title']; ?>" />
							</div>

						</div>



					</div>
                    

                 

                </div>
            </div>
        </section>
		<form>
</div>