<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo $pageinfo['title']; ?> Add</h1>
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
    <form name="addform" action="" method="post">
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
                                                <label>Choose Agency</label>
                                                <select class="form-control " name="u_id"
                                                    id="p_country">
                                                    <?php if($agencies){?>
                                                    <?php foreach($agencies as $agency){?>
                                                    <option value="<?php echo $agency->u_id;?>">
                                                        <?php echo $agency->u_userid;?></option>
                                                    <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Company Name</label> <span>Job offers by</span>
                                            <input type="text" class="form-control" name="p_company_name"
                                                placeholder="Enter Company Name" value="<?php echo $p_company_name;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Contact Person</label> <span>Job offers by</span>
                                            <input type="text" class="form-control" name="p_contact_person"
                                                placeholder="Enter Contact Person"
                                                value="<?php echo $p_contact_person;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Phone no.</label> <span>Job offers by</span>
                                            <input type="text" class="form-control" name="p_phone"
                                                placeholder="Enter Phone No." value="<?php echo $p_phone;?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Email Id</label> <span>Job offers by</span>
                                            <input type="email" class="form-control" name="p_email"
                                                placeholder="Enter Email Id" value="<?php echo $p_email;?>">
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
                                    <h3 class="card-title">Job Detail</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">


                                    <div class="row">
                                        <div class="col-sm-6">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Job Title</label>
                                                <input type="text" class="form-control" name="p_job_title"  placeholder="Enter Job Title" value="<?php echo $p_job_title; ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Category</label>
                                                <select class="form-control" name="js_id">
                                                    <?php if($categories){?>
                                                      <?php foreach($categories as $category){?>
                                                        <option value="<?php echo $category->js; ?>"><?php echo $category->js_title; ?></option>
                                                      <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Number of Openings</label>
                                                <input type="number" class="form-control" name="p_noopening" placeholder="Enter Number od Openings" value="<?php echo $p_noopening; ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Job Timings</label>
                                                <input type="number" class="form-control" name="p_job_timing" placeholder="Enter Job Timings" value="<?php echo $p_job_timing; ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Gender</label>
                                                <select class="form-control" name="p_gender">
                                                    <?php if($gender){?>
                                                    <?php foreach($gender as $ky=>$vl){?>
                                                    <option value="<?php echo $ky;?>"><?php echo $vl;?></option>
                                                    <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                       
                                    </div>
                                    <div class="row">
                                    
                                        <div class="col-sm-3">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Minimum Age</label>
                                                <input type="text" class="form-control" name="p_minage" placeholder="Enter Minimum Age"  value="<?php echo $p_minage; ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Minimum Qualification</label>
                                                <select class="form-control" name="p_minqualification">
                                                    <?php if($qualification){?>
                                                    <?php foreach($qualification as $ky=>$vl){?>
                                                    <option value="<?php echo $ky;?>"><?php echo $vl;?></option>
                                                    <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Minimum Experience</label>
                                                <select class="form-control" name="p_minexperence">
	                                            <?php for($i=1;$i<=40;$i++){?>
	                                            <option value="<?php echo $i;?>"><?php echo $i;?></option>
	                                            <?php } ?>

	                                        </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Enter Salary</label>
                                                <input type="text" class="form-control" name="p_salary"
	                                            value="<?php echo $p_salary;?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Enter Interview Detail</label>
                                                <input type="text" class="form-control" name="p_interview_details"
	                                            value="<?php echo $p_interview_details;?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Enter Job Detail</label>
                                                <input type="text" class="form-control" name="p_jobinfo"
	                                            value="<?php echo $p_jobinfo;?>">
                                            </div>
                                        </div>
                                    </div>




                                </div>
                                <!-- /.card-body -->

                            </div>



                        </div>
                    </div>

                    <div class="row">
                        <!-- left column -->
                        <div class="col-md-12">

                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Other Info</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">


                                    <div class="row">
                                        <div class="col-sm-4">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Country</label>
                                                <select class="form-control " name="p_country"
                                                    id="p_country">
                                                    <?php if($countries){?>
                                                    <?php foreach($countries as $country){?>
                                                    <option value="<?php echo $country->id;?>">
                                                        <?php echo $country->cname;?></option>
                                                    <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <!-- text input -->
                                            <div class="form-group">
                                                <label>Features</label>
                                                <select class="form-control" name="p_featured">
                                                    <?php if($featured){?>
                                                    <?php foreach($featured as $ky=>$vl){?>
                                                    <option value="<?php echo $ky;?>"><?php echo $vl;?></option>
                                                    <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        

                                    </div>
                            



                                </div>
                                <!-- /.card-body -->

                                <!-- /.card-body -->
                                <div class="card-footer">
                                    <input type="submit" name="savedata" class="btn btn-primary"
                                        value="Add <?php echo $pageinfo['title']; ?>" />
                                </div>

                            </div>



                        </div>
                    </div>

                 

                </div>
            </div>
        </section>
        <form>