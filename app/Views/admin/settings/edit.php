<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo $pageinfo['title']; ?> Edit</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">Edit</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <?php  
						echo session()->getFlashdata('error_msg');
						
					?>
    <!-- Main content -->
    <form name="editform" action="" method="post">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Settings</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

                                <div class="row">
                       
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Website Name</label> <span>Job offers by</span>
                                            <input type="text" class="form-control" name="s_sitename" placeholder="Enter Website Name" value="<?php echo $s_sitename;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Contact Person</label> <span>Company Address</span>
                                            <input type="text" class="form-control" name="s_companyaddress" placeholder="Enter Company Address" value="<?php echo $s_companyaddress;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Phone no.</label> <span>Contact Number</span>
                                            <input type="text" class="form-control" name="s_contactno" placeholder="Enter Contact No." value="<?php echo $s_contactno;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Email Id</label> <span>Email ID</span>
                                            <input type="email" class="form-control" name="s_email" placeholder="Enter Email Id" value="<?php echo esc($s_email);?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Booking Copy Email</label> <span>Copied on shift-booked e-mails. Leave blank for no copy.</span>
                                            <input type="email" class="form-control" name="s_agency_copy_email" placeholder="Enter Booking Copy Email" value="<?php echo $s_agency_copy_email ?? '';?>">
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->

                            </div>



                        </div>
                    </div>
                </div>

                    <div class="row">
                        <!-- left column -->
                        <div class="col-md-12">

                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Social Media Links</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">

                                    <?php /* The footer and the contact page both read these, so the two
                                           pages can no longer disagree about which account is the
                                           agency's. A box left empty hides its icon rather than
                                           linking to nowhere, which is what the Facebook one did. */ ?>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Facebook</label> <span>Leave blank to hide the icon</span>
                                                <input type="text" class="form-control" name="s_facebook_url" placeholder="https://www.facebook.com/yourpage" value="<?php echo esc($s_facebook_url ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>X (Twitter)</label> <span>Leave blank to hide the icon</span>
                                                <input type="text" class="form-control" name="s_twitter_url" placeholder="https://x.com/yourhandle" value="<?php echo esc($s_twitter_url ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Instagram</label> <span>Leave blank to hide the icon</span>
                                                <input type="text" class="form-control" name="s_instagram_url" placeholder="https://www.instagram.com/yourhandle" value="<?php echo esc($s_instagram_url ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                  



                                </div>
                                <!-- /.card-body -->
                                 <!-- /.card-body -->
                                 <div class="card-footer">
                                    <input type="submit" name="updatedata" class="btn btn-primary"
                                        value="Edit <?php echo $pageinfo['title']; ?>" />
                                </div>


                            </div>



                        </div>
                    </div>


                 

                </div>
            </div>
        </section>
        <form>

        