<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo $pageinfo['title']; ?> Send Email</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a
                                href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">Add</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <?php  
						echo session()->getFlashdata('error_msg');
						
					?>
    <!-- Main content -->
    <form name="addform" action="<?php echo base_url('sadmin/send'); ?>" method="post">
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Company Information</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

                                <div class="row">
                               
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>To: (separate multiple emails with commas):</label> 
                                            <!--<input type="email" name="to" id="to" required class="form-control" 
                                                placeholder="Enter Email id" value="<?php echo $p_company_name;?>"> -->
											<textarea name="to" id="to" rows="3" required class="form-control" 
                                                placeholder="Enter Email id" ></textarea>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Subject</label> 
                                            <input type="text" name="subject" id="subject" required class="form-control" 
                                                placeholder="Enter Subject"
                                                value="<?php echo $p_contact_person;?>">
                                        </div>
                                    </div>
								</div>
								<div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Message</label> 
                                            <textarea name="message" id="message" rows="5" required class="form-control" ></textarea>
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
                                    <h3 class="card-title"></h3>
                                </div>
                                <!-- /.card-header -->
                                
                                <!-- /.card-body -->

                                <!-- /.card-body -->
                                <div class="card-footer">
                                    <input type="submit" name="savedata" class="btn btn-primary"
                                        value="Send <?php echo $pageinfo['title']; ?>" />
                                </div>

                            </div>



                        </div>
                    </div>

                 

                </div>
            </div>
        </section>
        <form>