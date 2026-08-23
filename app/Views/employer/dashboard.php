<!-- Content Wrap -->
<div class="col-lg-9 col-md-8">
    <div class="dashboard-body">
        <div class="dashboard-caption">

            <div class="dashboard-caption-header">
                <h4><i class="ti-settings"></i><?php echo $pageTitle; ?></h4>
            </div>

            <div class="dashboard-caption-wrap">

                <!-- Overview -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="dashboard-stat widget-1">
                            <div class="dashboard-stat-content">
                                <h4><?php echo $restot[0]->totjobs;?></h4> <span>Shift Jobs</span>
                            </div>
                            <div class="dashboard-stat-icon"><i class="ti-location-pin"></i></div>
                        </div>
                    </div>

				</div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="dashboard-gravity-list with-icons">
                            <h4>Recent Shift Jobs</h4>
                            <ul>
                                <?php if($joblists){ ?>
                                <?php foreach($joblists as $jobs){ ?>
                                <li> <i class="dash-icon-box ti-layers"></i><h4><?php echo esc($jobs->p_job_title);?></h4><br/>
									<hr />
                                    <?php echo substr($jobs->p_jobinfo, 10, 200).'...';?>
                                    <a href="#" class="close-list-item"><i class="fa fa-close"></i></a>
                                </li>
                                <?php } ?>
                                <?php } ?>


                            </ul>
                        </div>
                    </div>

                </div>

           

        </div>
    </div>
</div>

</div>
</div>
</section>
<!-- General Detail End -->
