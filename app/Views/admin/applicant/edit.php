<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Update <?php echo $pageinfo['title']; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a>
                        </li>
                        <li class="breadcrumb-item "><a
                                href="<?php echo base_url($adminpath.'/'.$link);?>"><?php echo $pageinfo['title']; ?>
                                List</a></li>
                        <li class="breadcrumb-item ">Edit</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
  

    <!-- Main content -->
    <form name="addform" action="" method="post"  enctype="multipart/form-data">
        <section class="content">
            <div class="container-fluid">
			<?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}		
					
		?>
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
		<?php endif; ?>
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $pageinfo['title']; ?> Information - (<?php echo getShiftForName($u_usersubtype) ?>)</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
											<label>Select Applicant Type</label>											
											<select  name="u_usersubtype" id="u_usersubtype" class="form-control">
												<option value="" >-- Select Applicant Type --</option>
												<?php if($shift_for){
														foreach($shift_for as $shifts) {?>
													<option value="<?php echo $shifts->sf_id; ?>" <?php echo ($u_usersubtype == $shifts->sf_id) ? 'Selected' :'' ?>><?php echo $shifts->sf_name?></option>
												<?php 	}
												}
												?>
											</select>	
										</div>
									</div>
								</div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input required
    onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}"  type="text" class="form-control" placeholder="Enter First Name" name="u_fname" value="<?php echo $u_fname; ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input required
    onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}"   type="text" class="form-control" placeholder="Enter Last Name" name="u_lname" value="<?php echo $u_lname; ?>">
                                        </div>
                                    </div>
                                </div>
								

                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Licence Province</label>
                                            <select   class="form-control " name="u_l_provice" id="province_L_list" >
												<option value="">Select Province</option>
                                                <?php if($province){ ?>
                                                <?php foreach($province as $record){ ?>
                                                <option value="<?php echo $record->p_id; ?>"
                                                    <?php echo ($u_l_provice==$record->p_id)?"selected":""; ?>>
                                                    <?php echo $record->p_name; ?></option>
                                                <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Licence No.</label>
                                            <input   type="text" class="form-control" placeholder="Enter Licence No." name="u_licence_no" value="<?php echo $u_licence_no; ?>">
                                        </div>
                                    </div>
                                </div>
								
                                <div class="row">
                                    
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Email Id (As their userid)</label>
                                            <input readonly type="email" class="form-control" placeholder="Enter Email Id" name="u_email" value="<?php echo $u_email; ?>">
                                        </div>
                                    </div>
									<div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Mobile No.</label>
                                            <input   type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Mobile No." name="u_phone" value="<?php echo $u_phone; ?>" maxlength="<?= PHONE_LENGTH ?>" inputmode="numeric" pattern="[0-9]{<?= PHONE_LENGTH ?>}" data-phone-input>
                                        </div>
                                    </div>
                                    

                                </div>

                                <div class="row">
                                    
                                    
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea required class="form-control" placeholder="Enter Address" name="u_address1"><?php echo $u_address1; ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Province</label><input type="hidden" id="hprovince" value="<?php echo $u_provice; ?>" >
                                            <select   class="form-control " name="u_provice" onChange="getpcities(this.value);">
                                                <?php if($province){ ?>
                                                <?php foreach($province as $record){ ?>
                                                <option value="<?php echo $record->p_id; ?>"
                                                    <?php echo ($u_provice==$record->p_id)?"selected":""; ?>>
                                                    <?php echo $record->p_name; ?></option>
                                                <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>City</label><input type="hidden" id="hcity" value="<?php echo $u_city; ?>" >
											<select   name="u_city" id="city" class="form-control">
												<option value="">Select City</option>
											</select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Zipcode</label>
											<input required class="form-control" placeholder="Enter Zipcode" name="u_pincode" value="<?php echo $u_pincode; ?>" >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                     
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select   class="form-control " name="u_status" id="u_status">
                                                <?php if($status){ ?>
                                                <?php foreach($status as $ky=>$vl){ ?>
                                                <option value="<?php echo $ky; ?>"
                                                    <?php echo ($u_status==$ky)?"selected":""; ?>>
                                                    <?php echo $vl; ?></option>
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
                                        value="Update <?php echo $pageinfo['title']; ?>" />
                            </div>

                        </div>



                    </div>
                </div>



            </div>
            </section>
      </form>
</div>
