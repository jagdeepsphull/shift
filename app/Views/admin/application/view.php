<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>View <?php echo $pageinfo['title']; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a
                                href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">View</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
  

    <!-- Main content -->
    <form name="addform" action="" method="post"  enctype="multipart/form-data">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Applicantion Information</h3>
                            </div>
							
							<!-- /.card-header -->
                            <div class="card-body">


                                <div class="row">
								
									<div class="col-lg-4 mb-5 mt-2">
										<div class="card-header">
											<h3 class="card-title">Applicant Information</h3>
										</div>
										<div class="card border-0 shadow">
											<img src="<?php echo base_url('uploads/profile/'.$application->u_photo );?>" style="max-width:110px; border-radius:10%; border: 5px solid #e9ecf3; margin: 10px auto 0 10px" alt="">
											<div class="card-body">
												<div class="mb-4">
													<h3 class="h4 mb-0"><?php echo $application->u_fname.' '. $application->u_lname; ?></h3>
													<h5 class="mt-2"><span class="mb-0 text-primary">Licence No - </span><?php echo $application->u_licence_no; ?></h5>
													<h5 class="mt-2"><span class="mb-0 text-primary">Licence Province - </span><?php echo getProvinceName($application->u_l_provice); ?></h5>
												</div>
												<ul class="list-unstyled mb-4">
													<li class="mb-3"><a href="mailto:<?php echo $application->u_email; ?>"><i class="far fa-envelope display-25 mr-1 text-secondary"></i><?php echo $application->u_email; ?></a></li>
													<li class="mb-3"><?php echo whatsappPhoneLink($application->u_phone, 'Message ' . $application->u_fname . ' on WhatsApp'); ?></li>
													<li><a href="#!"><i class="fas fa-map-marker-alt display-25 mr-1 text-secondary"></i><?php echo $application->u_address1.', '.getCityName($application->u_city).', '.getProvinceName($application->u_provice) .', '.$application->u_pincode ; ?></a></li>
												</ul>
											</div>
										</div>
									</div>
									
								
									<div class="col-lg-4 mb-5 mt-2">
										<div class="card-header">
											<h3 class="card-title">Employer Information</h3>
										</div>
										<div class="card border-0 shadow">
											<div class="card-body">
												<div class="mb-4">
													<h3 class="h4 mb-0"><?php echo $u_comp_name;?> (<?php echo $u_fname.' '. $u_lname; ?>) </h3>
													<h5 class="mt-2"><span class="mb-0 text-primary">Store No. - </span><?php echo $u_licence_no; ?></h5>
													<h5 class="mt-2"><span class="mb-0 text-primary">Store Licence Province - </span><?php echo getProvinceName($u_l_provice); ?></h5>
												</div>
												<ul class="list-unstyled mb-4">
													<li class="mb-3"><a href="mailto:<?php echo $u_email; ?>"><i class="far fa-envelope display-25 mr-1 text-secondary"></i><?php echo $u_email; ?></a></li>
													<li class="mb-3"><?php echo whatsappPhoneLink($u_phone, 'Message ' . $u_comp_name . ' on WhatsApp'); ?></li>
													<li><a href="#!"><i class="fas fa-map-marker-alt display-25 mr-1 text-secondary"></i><?php echo $u_address1.', '.getCityName($u_city).', '.getProvinceName($u_provice). ', '.$u_pincode ; ?></a></li>
												</ul>
											</div>
										</div>
									</div>
									
									<div class="col-lg-3 ml-5 mt-2">
										<div class="card-header">
											<h3 class="card-title">Shift Post Information</h3>
										</div>
										<div class="ps-lg-1-6 ps-xl-5 mt-4 ml-3">
											<div class="mb-3 wow fadeIn">
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Shift ID</span>
												</div>
												<p><?php echo $application->p_job_title;?></p>
												<hr>
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Employer Rate</span>
												</div>
												<p> CAD$ <?php echo $application->p_hourly_rate;?></p>
												<hr>
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Actual Rate</span>
												</div>
												<p> CAD$ <?php echo $application->p_ac_hourly_rate;?></p>
												<hr>
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Shift Date</span>
												</div>
												<p><?php echo dateFormat($application->p_dates);?></p>
												<hr>
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Shift Time</span>
												</div>
												<p><?php echo $application->p_shift_time;?></p>
												<hr>
											</div>
										</div>
										<div class="ps-lg-1-6 ps-xl-5  mt-4 ml-3">
											<div class="mb-3 wow fadeIn">
												<div class="text-start mb-1-6 wow fadeIn">
													<span class="mb-0 text-primary">Job Application Hourly Rate/Remarks</span>
												</div>
												<p><?php echo $application->sj_applied_desc;?></p>
											</div>
										</div>
										
									</div>
                                </div>

                                <div class="row">
                                    
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Response/Message</label>
											<input type="hidden" name="p_id" value="<?php echo $application->p_id ; ?>" >
											<input type="hidden" name="u_id" value="<?php echo $application->u_id; ?>" >
											<?php if($application->sj_is_approved ==0){ ?>
											<textarea rows="4"  cols="50" required class="form-control" placeholder="Response/Message" name="sj_admin_comment"><?php echo $sj_admin_comment;?></textarea>
											<?php } else {?>
											<div class="border p-3 mb-2 "><?php echo $application->sj_admin_comment ; ?></div>
											<?php } ?>
                                        </div>
                                    </div>
								
								</div>
								
								<div class="row">	
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Approval Status</label>
											<?php if($application->sj_is_approved ==0){ ?>
                                            <select class="form-control " name="sj_is_approved" id="sj_is_approved">
                                                <?php if($application_approved){ ?>
                                                <?php foreach($application_approved as $ky=>$vl){ ?>
                                                <option value="<?php echo $ky; ?>"
                                                    <?php echo ($application->sj_is_approved==$ky)?"selected":""; ?>>
                                                    <?php echo $vl; ?></option>
                                                <?php } ?>
                                                <?php } ?>
                                            </select>
											<?php } else {?>
											<div class="border p-3 m-2"><?php echo $application_approved[$application->sj_is_approved];?></div>
											<?php } ?>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <!-- /.card-body -->
                            <div class="card-footer">
								<?php if($application->sj_is_approved ==0){ ?>
								<input type="submit" name="savedata" class="btn btn-primary" value="Update <?php echo $application->pageinfo['title']; ?>" />
								<?php } ?>
                            </div>

                        </div>



                    </div>
                </div>



            </div>
            </section>
      </form>
</div>
