<!-- Content Wrap -->
<div class="col-lg-9 col-md-8">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <?php /* Not "Applied Shifts" any more: the agency books an
                   applicant onto a shift from the back office without them
                   applying for anything, and this is the screen where they see
                   it - see Applicant::SHIFT_STATUSES. */ ?>
                <h4><i class="lni-briefcase"></i>My Shifts</h4>
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
                                <th>Shift</th>
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
									// The pill in the Status column. Was bold `text-primary` /
									// `text-success` / `text-danger` words; the classes are
									// defined in partials/portal_theme.php.
									if($jobs->sj_is_approved == 0){
										$clas = 'ps-status-wait';
									} else if($jobs->sj_is_approved == 2){
										$clas = 'ps-status-no';
									} else if($jobs->sj_is_approved == 1){
										$clas = 'ps-status-ok';
									}
									$employer = ci_db()->table('users')
										->select('u_comp_name, u_licence_no, u_address1, u_city, u_provice, u_pincode')
										->getWhere(['u_id' => $jobs->agency_id])
										->getRow();

									// An employer account deleted since the row was written, which
									// leaves the shift and the booking behind it. Read straight, the
									// lines below then take properties off null - a page of warnings
									// on a row whose only real fault is a blank company name. The
									// store's own details are looked up separately, just under here,
									// and are what this screen shows anyway.
									$employer = $employer ?: (object) [
										'u_comp_name' => '', 'u_licence_no' => '', 'u_address1' => '',
										'u_city' => 0, 'u_provice' => 0, 'u_pincode' => '',
									];
									
									// pr($employer);
									// pr($jobs);

									// The branch the shift is at, which for a multi-store owner is
									// not the address on their login - that is their head office,
									// and it is where this screen used to send people. A shift from
									// before the stores existed falls back to the login columns,
									// which is where its address has always come from.
									$store = shiftStore($jobs);

									// Joined with `, ` between the parts that are actually
									// filled: a store with no city or postcode used to read
									// "12 Fixture Lane, , ,".
									$storeAddress = $store ? implode(', ', array_filter([
										trim((string) $store->s_address),
										trim((string) getCityName($store->s_city)),
										trim((string) getProvinceName($store->s_province)),
										trim((string) $store->s_pincode),
									], static fn ($part) => $part !== '')) : '';

									// Only for a booking: this is the one screen where the
									// applicant has been told which building to turn up at, so
									// it is the one that owes them a way to find it.
									$storeMap = ($jobs->sj_is_approved == 1 && $store) ? storeMapLink($store) : '';

									// The branch's own name and number where there is one, so the
									// two boxes labelled Store agree with the address under them.
									// A pre-store shift falls back to the login's company name and
									// licence, which is what they have always read.
									$storeName   = ($store && trim((string) $store->s_name) !== '')
										? $store->s_name
										: (string) $employer->u_comp_name;
									$storeNumber = ($store && trim((string) $store->s_number) !== '')
										? $store->s_number
										: (string) $employer->u_licence_no;

									$storeLocation = implode(', ', array_filter([
										trim((string) getCityName($employer->u_city)),
										trim((string) getProvinceName($employer->u_provice)),
									], static fn ($part) => $part !== ''));
								?>
                                    <tr>
                                        <td class="d-none" ><?php echo $jobs->sj_id; ?></td>
                                        <td><?php echo esc($jobs->p_job_title); ?></td>
                                        <td>
											<?php //echo getPharmacyName($jobs->agency_id); ?>
											<?php if($jobs->sj_is_approved == 1){ ?>
												<button class="btn ps-chip employer-btn" data-cname="<?php echo esc($storeName, 'attr'); ?>" data-licen="<?php echo esc($storeNumber, 'attr'); ?>" data-address="<?php echo esc($storeAddress, 'attr'); ?>" data-map="<?php echo esc($storeMap, 'attr'); ?>" data-shift="<?php echo esc($jobs->p_job_title); ?>" data-approved="<?php echo dateformat($jobs->modified); ?>" data-appremarks="<?php echo esc($jobs->sj_admin_comment, 'attr') ?>" data-shiftfor="<?php echo getShiftForName($jobs->p_shift_for);?>" data-shift_date="<?php echo esc(dateformat($jobs->p_dates), 'attr') ?>" data-shift_time="<?php echo esc($jobs->p_shift_time, 'attr') ?>" data-shift_rate="<?php echo esc('CAD$ '.$jobs->p_ac_hourly_rate.'/Hour', 'attr') ?>" data-sofskills="<?php echo getSoftwareSkills($jobs->p_skills); ?>" data-ofcser="<?php echo getStoreServices($jobs->p_services); ?>" >View Detail</button>
											<?php } else { ?>
												<button class="btn ps-chip shift-btn" data-shiftfor="<?php echo getShiftForName($jobs->p_shift_for);?>" data-shift_date="<?php echo esc(dateformat($jobs->p_dates), 'attr') ?>" data-shift_time="<?php echo esc($jobs->p_shift_time, 'attr') ?>"  data-shift_rate="<?php echo esc('CAD$ '.$jobs->p_ac_hourly_rate.'/Hour', 'attr') ?>" data-sofskills="<?php echo getSoftwareSkills($jobs->p_skills); ?>" data-ofcser="<?php echo getStoreServices($jobs->p_services); ?>" data-address="<?php echo esc($storeLocation, 'attr'); ?>"  >View Detail</button>
											
											<?php } ?>
											
											
										</td>
                                        <td data-order="<?php echo shiftDateSortValue($jobs); ?>"><?php echo dateformat($jobs->p_dates); ?></td>
                                        <td><?php echo $jobs->p_shift_time; ?></td>
                                        <td>
										<?php if(trim((string) $jobs->sj_applied_desc) !== ''){ ?>
											<button type="button" class="btn ps-chip popover-btn mb-1" data-toggle="popover" data-content="<?php echo esc($jobs->sj_applied_desc, 'attr');?>">My Message</button>  <br/>
										<?php } ?>
										<?php if(trim((string) $jobs->sj_admin_comment) !== ''){ ?>
											<button type="button" class="btn ps-chip ps-chip-them popover-btn" data-toggle="popover" data-content="<?php echo esc($jobs->sj_admin_comment, 'attr');?>">Agency Message</button>
										<?php } ?>
										</td>
                                        <td><span class="ps-status <?php echo $clas; ?>"><?php echo ($jobs->sj_is_approved == 2) ? 'Assigned To Someone Else' : $application_approved[$jobs->sj_is_approved]; ?></span></td>
                                        
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
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Employer/Shift Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#empModal').modal('hide');">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<?php /* Eleven boxes in one column ran off the bottom of the screen
					   on a laptop. Two and three to a row where the values are short; the
					   ids are untouched, so the footer script still fills them. */ ?>
					<form class="form">
						<div class="row">
							<div class="col-md-7">
								<div class="form-group">
									<label>Store Name:</label>
									<input id="modalName" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-5">
								<div class="form-group">
									<label>Store No.:</label>
									<input id="modalLicen" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label>Store Address:</label>
									<input id="modalAddress" class="form-control" readonly>
									<?php /* The address as text finds nothing on a phone; this is
									   the same pin the booking e-mail and the shift page carry.
									   Hidden by the footer script on a shift whose store has no
									   address to search for. */ ?>
									<a id="modalMapLink" class="ps-map-link" href="#" target="_blank" rel="noopener noreferrer">
										<i class="lni lni-map-marker" aria-hidden="true"></i> Get directions on Google Maps
									</a>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-7">
								<div class="form-group">
									<label>Shift For:</label>
									<input id="modalShiftFor" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-5">
								<div class="form-group">
									<label>Approval Date:</label>
									<input id="modalApproved" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Shift Date:</label>
									<input id="modalShiftDate" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Shift Time:</label>
									<input id="modalShiftTime" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Posted Shift Rate:</label>
									<input id="modalShiftRate" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="form-group">
									<label>Approval Remarks From Agency:</label>
									<input id="modalAppRemarks" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Softwares:</label>
									<input id="modalSoftSkills" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Services:</label>
									<input id="modalOffSer" class="form-control" readonly>
								</div>
							</div>
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
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header  text-light bg-info">
                    <h5 class="modal-title" id="viewModalLabel">Shift Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#viewModal').modal('hide');">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
					<form class="form">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Shift For:</label>
									<input id="modalShiftFor1" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Store Location:</label>
									<input id="modalAddress1" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Shift Date:</label>
									<input id="modalShiftDate1" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Shift Time:</label>
									<input id="modalShiftTime1" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Posted Shift Rate:</label>
									<input id="modalShiftRate1" class="form-control" readonly>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Softwares:</label>
									<input id="modalSoftSkills1" class="form-control" readonly>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Services:</label>
									<input id="modalOffSer1" class="form-control" readonly>
								</div>
							</div>
						</div>
					</form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal" onclick="$('#viewModal').modal('hide');">Close</button>
                </div>
            </div>
        </div>
    </div>
