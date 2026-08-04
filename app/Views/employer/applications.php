<!-- Content Wrap -->
<div class="col-lg-9 col-md-8">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <h4><i class="ti-briefcase"></i>Applications</h4>
            </div>

            <div class="dashboard-caption-wrap">

                <!-- row -->
              
                <!-- row -->
                <div class="table-responsive">
                    <table id="applicationlist" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Shift</th>
                                <th>Application Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($applicationslist){?>
                            <?php foreach($applicationslist as $applicationslist){
                                $candid = custom()->get_where('users',array('u_id'=>$applicationslist->u_id));
                                $postjob = custom()->get_where('post_job',array('p_id'=>$applicationslist->p_id));

                                ?>
                            <tr>
                                <td><?php echo $candid[0]->u_fname.' '.$candid[0]->u_lname;?></td>
                                <td><?php echo $postjob[0]->p_job_title;?></td>
                                <td><?php echo dateFormat($applicationslist->sj_applied_date, 'd M Y');?></td>
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
