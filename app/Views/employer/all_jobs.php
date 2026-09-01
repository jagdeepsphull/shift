<!-- Content Wrap -->
<div class="col-lg-9 col-md-9">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <h4><i class="ti-briefcase"></i>All Jobs</h4>
            </div>
			<?php  echo session()->getFlashdata('error_msg'); ?>
            <div class="dashboard-caption-wrap">

                <!-- row -->
                <div class="row">

                    

                </div>
                <!-- row -->
                <div class="table-responsive">
                    <table id="joblist" class="table table-hover">
                        <thead>
                            <tr>
                                <?php /* min-tablet-p: the responsive extension keeps these columns
								   in the row from a tablet up and folds them into the panel under
								   the row on a phone, where four columns squeezed into the screen
								   wrapped every one of them into a stack of single words. The shift
								   and its View button are what the row is for, so those two stay. */ ?>
                                <th>Job id</th>
                                <th>Shift ID</th>
                                <th class="min-tablet-p">Store</th>
                                <th class="min-tablet-p">Location</th>
								<th class="min-tablet-p">Shift Date</th>
								<th class="min-tablet-p">Shift Time</th>
								<th class="min-tablet-p">Assigned To</th>
								<th class="min-tablet-p">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($jobslist){?>
                            <?php foreach($jobslist as $job){
								// Who is booked on this shift, looked up in the controller for the
								// whole list at once. Null while nobody is on it.
								$booked      = $bookings[(int) $job->p_id] ?? null;
								$booked_name = $booked ? trim($booked->u_fname.' '.$booked->u_lname) : '';
								$booked_msg  = $booked ? trim((string) $booked->sj_admin_comment) : '';

								// Which branch the shift was raised for. An owner with several
								// of them could not tell one row from another without it. A shift
								// from before the stores existed carries p_store_id 0 and belongs
								// to the login itself, which is what its company name says.
								$store_name = ((int) $job->p_store_id > 0) ? getStoreName($job->p_store_id) : '';
								$store_name = ($store_name !== '') ? $store_name : getPharmacyName($job->u_id);
							?>
                            <tr>
                                <td><?php echo $job->p_id;?></td>
                                <td><?php echo esc($job->p_job_title);?></td>
                                <td><?php echo esc($store_name); ?></td>
                                <?php /* City and province read as one place, so they are shown
								   in one column - either half on its own when the other is missing. */ ?>
								<td><?php echo esc(implode(', ', array_filter([getCityName($job->p_city), getProvinceName($job->p_province)]))); ?></td>
								<td data-order="<?php echo shiftDateSortValue($job); ?>"><?php echo dateFormat($job->p_dates); ?></td>
								<td><?php echo $job->p_shift_time; ?></td>
								<td>
								<?php if($booked){
									// The applicant's details, spelled out in the cell. This used to
									// be a View button behind a modal; the few fields it held fit
									// here, so there is nothing left to open.
									$booked_licen      = trim((string) $booked->u_licence_no);
									$booked_licen_prov = trim((string) getProvinceName($booked->u_l_provice));
									$booked_shift_for  = trim((string) getShiftForName($job->p_shift_for));
								?>
								<span class="d-block"><?php echo esc($booked_name); ?></span>
								<?php if($booked_licen !== ''){ ?>
								<span class="d-block text-muted">Licence No.: <?php echo esc($booked_licen); ?></span>
								<?php } ?>
								<?php if($booked_licen_prov !== ''){ ?>
								<span class="d-block text-muted">Licence Province: <?php echo esc($booked_licen_prov); ?></span>
								<?php } ?>
								<?php if($booked_shift_for !== ''){ ?>
								<span class="d-block text-muted">Shift Requested For: <?php echo esc($booked_shift_for); ?></span>
								<?php } ?>
								<?php } else { echo '-'; }?>
								</td>
                                <!-- <td><?php echo ($job->p_status==0) ? 'Pending for Approval'  : 'Approved' ; ?></td> -->
                                <td><?php echo $approved[$job->p_approved] ; ?></td>
                                <td>
									<button type="button" data-toggle="tooltip" data-placement="top" title="View Shift Details"  class="btn btn-info btn-sm view-btn" data-shiftfor="<?php echo getShiftForName($job->p_shift_for);?>" data-shift_date="<?php echo esc(dateFormat($job->p_dates), 'attr') ?>" data-shift_time="<?php echo esc($job->p_shift_time, 'attr') ?>" <?php if ($can_set_rate) { ?>data-shift_rate="<?php echo (trim((string) $job->p_hourly_rate) !== '') ? '$' . $job->p_hourly_rate : 'Not set yet'; ?>"<?php } ?> data-sofskills="<?php echo getSoftwareSkills($job->p_skills); ?>" data-ofcser="<?php echo getStoreServices($job->p_services); ?>" data-assigned="<?php echo esc($booked_name, 'attr'); ?>" <?php if ($can_see_message) { ?>data-message="<?php echo esc($booked_msg, 'attr'); ?>"<?php } ?>>View</button>
									<?php /* Only while the shift is still awaiting approval. Checking
								   p_approved too stops the nightly expiry job (which sets
								   p_status = 0) from re-opening past shifts for editing. */ ?>
								<?php if($job->p_status==0 && $job->p_approved==0){ ?>
									<a href="<?php echo base_url("employer/edit_job/".$job->p_id);?>" class="btn btn-success manage-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"><i class="lni lni-pencil"></i></a>
									<a href="<?php echo base_url("employer/delete_job/".$job->p_id);?>" onclick="return confirm('Are you sure you want to delete this Job?');" class="btn btn-danger manage-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Remove"><i class="lni lni-circle-minus"></i></a>
									<?php } ?>
                                </td>

                            </tr>
                            <?php }?>
                            <?php }?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

</div>
</div>
</section>
<!-- General Detail End -->
 <!-- Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Shift Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<form class="form">
						<div class="form-group">
							<label>Shift Requested For:</label> <input id="modalShiftFor" class="form-control" readonly>
						</div>
						<div class="form-group">
							<label>Shift Date:</label> <input id="modalShiftDate" class="form-control" readonly>
						</div>
						<div class="form-group">
							<label>Shift Time:</label> <input id="modalShiftTime" class="form-control" readonly>
						</div>
						<?php /* The group sets what a shift pays, so a manager is not
						   shown it back here either - see Employer::setup(). */ ?>
						<?php if ($can_set_rate) { ?>
							<div class="form-group">
								<label>Shift Hourly Rate:</label> <input id="modalShiftRate" class="form-control" readonly>
							</div>
						<?php } ?>
						<div class="form-group">
							<label>Softwares:</label> <input id="modalSoftSkills" class="form-control" readonly>
						</div>
						<div class="form-group">
							<label>Details:</label> <input id="modalOffSer" class="form-control" readonly>
						</div>
						<?php /* The booking, when there is one. Hidden by the footer script on a
						   shift nobody is on yet, rather than shown empty. */ ?>
						<div class="form-group" id="modalAssignedGroup">
							<label>Assigned To:</label> <input id="modalAssigned" class="form-control" readonly>
						</div>
						<?php /* The note the administrator left on the booking, which a
						   manager is not shown - see Employer::setup(). */ ?>
						<?php if ($can_see_message) { ?>
							<div class="form-group" id="modalMessageGroup">
								<label>Message:</label> <textarea id="modalMessage" class="form-control" rows="3" readonly></textarea>
							</div>
						<?php } ?>
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

