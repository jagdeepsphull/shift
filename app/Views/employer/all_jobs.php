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
                                <th>Job id</th>
                                <th>Shift ID</th>
                                <th>City</th>
								<th>Province</th>
								<th>Shift Date</th>
								<th>Shift Time</th>
								<th>Assigned To</th>
								<th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($jobslist){?>
                            <?php foreach($jobslist as $job){
								//$applied_cand = custom()->get_where('stu_saved_applied_jobs',array('p_id'=>$job->p_id,'sj_status'=>1,'sj_is_approved'=>1));
								// $selected_cand = custom()->get_where('stu_saved_applied_jobs',array('p_id'=>$job->p_id,'sj_status'=>1,'sj_is_approved'=>1));
								// pr($selected_cand);
								
								$selected_cand = custom()->query("select phrmcist.* from stu_saved_applied_jobs ssa,  users phrmcist where ssa.u_id = phrmcist.u_id and ssa.p_id = ".$job->p_id. " and ssa.sj_status =1 and ssa.sj_is_approved =1  ");
								
								//qry();
								//pr($selected_cand);
								
							?>
                            <tr>
                                <td><?php echo $job->p_id;?></td>
                                <td><?php echo $job->p_job_title;?></td>
                                <td><?php echo getCityName($job->p_city); ?></td>
								<td><?php echo getProvinceName($job->p_province); ?></td>
								<td data-order="<?php echo shiftDateSortValue($job); ?>"><?php echo dateFormat($job->p_dates); ?></td>
								<td><?php echo $job->p_shift_time; ?></td>
								<td><a href="<?php //echo base_url("employer/applied_candidates/".$job->p_id);?>"><?php //echo count($applied_cand);?></a> 
								<?php if($selected_cand){ $application = $selected_cand[0];?>
								<button type="button" data-toggle="tooltip" data-placement="top" title="View Applicant Details" class="btn btn-info btn-sm applicant-btn" data-shiftfor="<?php echo getShiftForName($job->p_shift_for);?>" data-name="<?php echo $application->u_fname.' '. $application->u_lname; ?>" data-licen="<?php echo $application->u_licence_no; ?>" data-licen_prov="<?php echo getProvinceName($application->u_l_provice); ?>" >View</button>
								<?php }?>
								</td>
                                <!-- <td><?php echo ($job->p_status==0) ? 'Pending for Approval'  : 'Approved' ; ?></td> -->
                                <td><?php echo $approved[$job->p_approved] ; ?></td>
                                <td>
									<button type="button" data-toggle="tooltip" data-placement="top" title="View Shift Details"  class="btn btn-info btn-sm view-btn" data-shiftfor="<?php echo getShiftForName($job->p_shift_for);?>" data-shift_date="<?php echo dateFormat($job->p_dates); ?>" data-shift_time="<?php echo $job->p_shift_time; ?>"  data-shift_rate="<?php echo '$'.$job->p_hourly_rate; ?>" data-sofskills="<?php echo getSoftwareSkills($job->p_skills); ?>" data-ofcser="<?php echo getStoreServices($job->p_services); ?>">View</button>
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
							<label>Shift Requested For:</label> <input id="modalShiftFor" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Date:</label> <input id="modalShiftDate" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Time:</label> <input id="modalShiftTime" class="form-control" >
						</div>
						<div class="form-group">
							<label>Your Shift Rate:</label> <input id="modalShiftRate" class="form-control" >
						</div>
						<div class="form-group">
							<label>Softwares:</label> <input id="modalSoftSkills" class="form-control" >
						</div>
						<div class="form-group">
							<label>Details:</label> <input id="modalOffSer" class="form-control" >
						</div>
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

 <!-- Modal -->
    <div class="modal fade" id="applicantModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Applicant Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<form class="form">
						<div class="form-group">
							<label>Applicant Name:</label> <input id="modalName" class="form-control" >
						</div>
						<div class="form-group">
							<label>Licence No.:</label> <input id="modalLicen" class="form-control" >
						</div>
						<div class="form-group">
							<label>Licence Province:</label> <input id="modalLicen_prov" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Requested For:</label> <input id="modalShiftFor1" class="form-control" >
						</div>
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
