<script src="https://cdn.tiny.cloud/1/4i61lqp4mo176sc5mqr7nmxzsqsiz9kivvkuju4f6enap7k9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
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
                                            <input type="email" class="form-control" name="s_email" placeholder="Enter Email Id" value="<?php echo $s_email;?>">
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
                                    <h3 class="card-title">Other Setings</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-sm-12">
                                             <div class="form-group">
                                                <label>Disclaimer</label>
                                                <textarea name="s_disclaimer" class="summernote"><?php echo $s_disclaimer; ?></textarea>
                                            </div>
                                       
                                        
                                        </div>
                                    </div>
                                  
                                    <div class="row">
                                        <div class="col-sm-12">
                                             <div class="form-group">
                                                <label>Terms And Conditions</label>
                                                <textarea name="s_terms_conditions" class="summernote" ><?php echo $s_terms_conditions; ?></textarea>
                                            </div>
                                       
                                        
                                        </div>
                                    </div>
                                  
                                    <div class="row">
                                        <div class="col-sm-12">
                                             <div class="form-group">
                                                <label>Privacy Policy</label>
                                                <textarea name="s_privacy_policy" class="summernote" ><?php echo $s_privacy_policy; ?></textarea>
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

        