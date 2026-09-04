<!-- Content Wrap -->
<div class="col-lg-9 col-md-8">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <h4><i class="ti-briefcase"></i>Applicants for <?php echo esc($job[0]->p_job_title); ?></h4>
            </div>

            <div class="dashboard-caption-wrap">

                <!-- row -->
                <div class="row">

                    

                </div>
                <!-- row -->
                <div class="table-responsive">
                    <table id="candidatelist" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Mobile</th>
                                <th>Email Id</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($appliedlist){?>
                            <?php foreach($appliedlist as $jobuser){
														
														?>
                            <tr>
                                <td><?php echo esc($jobuser->u_fname . ' ' . $jobuser->u_lname);?>
                                </td>
                                <?php /* The pharmacist's own mobile, and this
                                   is the screen an owner is on when they want to
                                   ask one of them something - so it opens the
                                   chat rather than waiting to be copied out. */ ?>
                                <td><?php echo portalWhatsappPhoneLink($jobuser->u_phone, 'Message ' . trim($jobuser->u_fname . ' ' . $jobuser->u_lname) . ' on WhatsApp');?></td>
                                <td><?php echo esc($jobuser->u_email);?></td>
                                <td data-order="<?php echo esc($jobuser->sj_applied_date, 'attr');?>"><?php echo dateFormat($jobuser->sj_applied_date);?></td>
                                <td><?php echo $appliedstatus[$jobuser->sj_status];?></td>
                                <td>
									<a href="#" class="btn btn-success manage-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"><i class="lni lni-pencil"></i></a>
									<a href="#" class="btn btn-danger manage-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Remove"><i class="lni lni-circle-minus"></i></a>
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

