<!-- Content Wrap -->
<div class="col-lg-9 col-md-8">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <h4><i class="ti-briefcase"></i>Applied Shifts</h4>
            </div>
			<?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}		
			
			
		?>
            <div class="dashboard-caption-wrap">

                <!-- row -->
               
                <!-- row -->
                <div class="table-responsive">
                    <table id="joblist" class="table table-hover">
                        <thead>
                            <tr class="">
                                <th  class="d-none" ></th>
                                <th>Applied Shifts <i class="fa fa-sort"></i></th>
                                <th>Employer/Shift</th>
                                <th>Shift Date</th>
                                <th>Shift Time</th>
                                <th>Messages</th>
                                <th>Status</th>
                                <?php /* <th scope="col">Action</th> */ ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($appliedjobs){ ?>
                                <?php foreach($appliedjobs as $jobs){ 
									if($jobs->sj_is_approved == 0){
										$clas = 'text-primary';
									} else if($jobs->sj_is_approved == 2){
										$clas = 'text-danger';
									} else if($jobs->sj_is_approved == 1){
										$clas = 'text-success';
									}
									$employer = ci_db()->table('users')
										->select('u_comp_name, u_licence_no, u_address1, u_city, u_provice, u_pincode')
										->getWhere(['u_id' => $jobs->agency_id])
										->getRow();
									
									// pr($employer);
									// pr($jobs);
									
								?>
                                    <tr>
                                        <td class="d-none" ><?php echo $jobs->sj_id; ?></td>
                                        <td><?php echo $jobs->p_job_title; ?></td>
                                        <td>
											<?php //echo getPharmacyName($jobs->agency_id); ?>
											<?php if($jobs->sj_is_approved == 1){ ?>
												<button class="btn btn-info btn-sm employer-btn" data-cname="<?php echo $employer->u_comp_name; ?>" data-licen="<?php echo $employer->u_licence_no; ?>" data-address="<?php echo $employer->u_address1.', '.getCityName($employer->u_city).', '.getProvinceName($employer->u_provice). ', '.$employer->u_pincode ; ?>" data-shift="<?php echo $jobs->p_job_title; ?>" data-approved="<?php echo dateformat($jobs->modified); ?>" data-appremarks="<?php echo $jobs->sj_admin_comment; ?>" data-shiftfor="<?php echo getShiftForName($jobs->p_shift_for);?>" data-shift_date="<?php echo dateformat($jobs->p_dates); ?>" data-shift_time="<?php echo $jobs->p_shift_time; ?>" data-shift_rate="<?php echo 'CAD$ '.$jobs->p_ac_hourly_rate.'/Hour'; ?>" data-sofskills="<?php echo getSoftwareSkills($jobs->p_skills); ?>" data-ofcser="<?php echo getStoreServices($jobs->p_services); ?>" >View Detail</button>
											<?php } else { ?>
												<button class="btn btn-info btn-sm shift-btn" data-shiftfor="<?php echo getShiftForName($jobs->p_shift_for);?>" data-shift_date="<?php echo dateformat($jobs->p_dates); ?>" data-shift_time="<?php echo $jobs->p_shift_time; ?>"  data-shift_rate="<?php echo 'CAD$ '.$jobs->p_ac_hourly_rate.'/Hour'; ?>" data-sofskills="<?php echo getSoftwareSkills($jobs->p_skills); ?>" data-ofcser="<?php echo getStoreServices($jobs->p_services); ?>" data-address="<?php echo getCityName($employer->u_city).', '.getProvinceName($employer->u_provice) ; ?>"  >View Detail</button>
											
											<?php } ?>
											
											
										</td>
                                        <td><?php echo dateformat($jobs->p_dates); ?></td>
                                        <td><?php echo $jobs->p_shift_time; ?></td>
                                        <td>
										<?php if(trim((string) $jobs->sj_applied_desc) !== ''){ ?>
											<button type="button" class="btn btn-info popover-btn mb-2" data-toggle="popover" data-content="<?php echo esc($jobs->sj_applied_desc, 'attr');?>">My Message</button>  <br/>
										<?php } ?>
										<?php if(trim((string) $jobs->sj_admin_comment) !== ''){ ?>
											<button type="button" class="btn btn-warning popover-btn" data-toggle="popover" data-content="<?php echo esc($jobs->sj_admin_comment, 'attr');?>">Agency Message</button>
										<?php } ?>
										</td>
                                        <td class="font-weight-bold <?php echo $clas; ?>"><?php echo ($jobs->sj_is_approved == 2) ? 'Assigned To Someone Else' : $application_approved[$jobs->sj_is_approved]; ?></td>
                                        
                                        <?php /* <td>
                                            <div class="job-buttons">
                                                <a href="#" class="btn btn-danger manage-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Remove"><i class="lni lni-circle-minus"></i></a>
                                            </div>
                                        </td> */ ?>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        
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
    <div class="modal fade" id="empModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Employer/Shift Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#empModal').modal('hide');">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<form class="form">
						<div class="form-group">
							<label>Store Name:</label> <input id="modalName" class="form-control" >
						</div>
						<div class="form-group">
							<label>Store No.:</label> <input id="modalLicen" class="form-control" >
						</div>
						<div class="form-group">
							<label>Store Adress:</label> <input id="modalAddress" class="form-control" >
						</div>
						<div class="form-group">
							<label>Approval Date:</label> <input id="modalApproved" class="form-control" >
						</div>
						<div class="form-group">
							<label>Approval Remarks From Agency:</label> <input id="modalAppRemarks" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift For:</label> <input id="modalShiftFor" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Date:</label> <input id="modalShiftDate" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Time:</label> <input id="modalShiftTime" class="form-control" >
						</div>
						<div class="form-group">
							<label>Posted Shift Rate:</label> <input id="modalShiftRate" class="form-control" >
						</div>
						<div class="form-group">
							<label>Softwares:</label> <input id="modalSoftSkills" class="form-control" >
						</div>
						<div class="form-group">
							<label>Services:</label> <input id="modalOffSer" class="form-control" >
						</div>
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal" onclick="$('#empModal').modal('hide');">Close</button>
                </div>
            </div>
        </div>
    </div>
	
	
	<!-- Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Shift Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#viewModal').modal('hide');">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<form class="form">
						<div class="form-group">
							<label>Shift For:</label> <input id="modalShiftFor1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Store Location:</label> <input id="modalAddress1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Date:</label> <input id="modalShiftDate1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Shift Time:</label> <input id="modalShiftTime1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Posted Shift Rate:</label> <input id="modalShiftRate1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Softwares:</label> <input id="modalSoftSkills1" class="form-control" >
						</div>
						<div class="form-group">
							<label>Services:</label> <input id="modalOffSer1" class="form-control" >
						</div>
						
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal" onclick="$('#viewModal').modal('hide');">Close</button>
                </div>
            </div>
        </div>
    </div>
