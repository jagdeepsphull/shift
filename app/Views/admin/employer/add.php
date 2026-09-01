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
                                <h3 class="card-title"><?php echo $pageinfo['title']; ?> Information</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

								<?php /* The same three kinds registration offers. All are
								   `u_usertype` 1 and differ by `u_emp_role` / `u_parent_id`;
								   the script in admin/footer.php shows and hides the rows
								   below to match, exactly as the public form does. */ ?>
								<div class="row">
									<div class="col-sm-6">
										<div class="form-group">
											<label>Employer Type</label>
											<select required class="form-control" name="emp_kind" id="emp_kind">
												<option value="">-- Select Employer Type --</option>
												<?php foreach($employerKinds as $kindcode=>$empkind){ ?>
												<option value="<?php echo (int) $kindcode; ?>"
													<?php echo ((string) ($emp_kind ?? '')===(string) $kindcode)?"selected":""; ?>>
													<?php echo $empkind['short']; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>

									<?php /* A manager runs a store on behalf of a multi-store
									   owner, so the group is who they answer to and is required.
									   Nobody else is asked. */ ?>
									<div class="col-sm-6 grouponly">
										<div class="form-group">
											<label>Corporate Group</label>
											<select required class="form-control" name="u_parent_id" id="u_parent_id">
												<option value="">-- None --</option>
												<?php if(!empty($pharmacy_groups)){ ?>
												<?php foreach($pharmacy_groups as $group){ ?>
												<option value="<?php echo $group->u_id; ?>"
													<?php echo (($u_parent_id ?? '')==$group->u_id)?"selected":""; ?>>
													<?php echo esc($group->u_comp_name); ?></option>
												<?php } ?>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<?php /* A manager runs one of the group's existing stores, so
								   they say which rather than describing one of their own -
								   the same question registration asks, filled by the same
								   endpoint. Hidden for everybody else. */ ?>
								<div class="row storepick">
									<div class="col-sm-6">
										<div class="form-group">
											<label>Store</label>
											<input type="hidden" id="hstoreid" value="<?php echo (int) ($u_store_id ?? 0); ?>">
											<select required class="form-control" name="u_store_id" id="u_store_id">
												<option value="">-- Select Store --</option>
											</select>
										</div>
									</div>
								</div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input  required
    onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}" type="text" class="form-control" placeholder="Enter First Name" name="u_fname" value="<?php echo esc($u_fname); ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input  required
    onblur="if (this.value == '') {this.value = '';}"
    onfocus="if (this.value == '') {this.value = '';}" type="text" class="form-control" placeholder="Enter Last Name" name="u_lname" value="<?php echo esc($u_lname); ?>">
                                        </div>
                                    </div>
                                </div>
								

                                <div class="row">
                                    <?php /* A manager is not asked for it at all: the store they
                                       pick already has a name, and registration saves them a
                                       blank u_comp_name for exactly that reason. */ ?>
                                    <div class="col-sm-3 owneronly">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <?php /* "Store Name" for a single location, "Corporate Group
                                               Name" for a multi-store owner - the same column either way. */ ?>
                                            <label id="compnamelbl">Store Name</label>
                                            <input  required type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Name" name="u_comp_name" value="<?php echo esc($u_comp_name); ?>">
                                        </div>
                                    </div>
									<?php /* Licence and address describe one location, so a
									   multi-store owner is never asked for them: theirs belong
									   to each store added afterwards. */ ?>
									<div class="col-sm-3 storeonly">
                                        <div class="form-group">
                                            <label>Store Registration Province</label>
                                            <select  required class="form-control " name="u_l_provice" id="province_L_list" >
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
                                    <div class="col-sm-6 storeonly">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Store No.</label>
                                            <input  required type="text" class="form-control" placeholder="Enter Store No." name="u_licence_no" value="<?php echo esc($u_licence_no); ?>">
                                        </div>
                                    </div>
                                </div>
								
                                <div class="row">
                                    
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Email Id (As their userid)</label>
                                            <input  required type="email" class="form-control" placeholder="Enter Email Id" name="u_email" value="<?php echo esc($u_email); ?>">
                                        </div>
                                    </div>
									<div class="col-sm-3">										
										<div class="form-group">
											<label>Password</label>
											<input type="text" class="form-control" placeholder="Enter  Password" name="u_password" id="mainpassword" value="">
										</div>
									</div>
									<div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Mobile No.</label>
                                            <input  required type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Mobile No." name="u_phone" value="<?php echo esc($u_phone); ?>" maxlength="<?= PHONE_LENGTH ?>" inputmode="numeric" pattern="[0-9]{<?= PHONE_LENGTH ?>}" data-phone-input>
                                        </div>
                                    </div>
									<div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <?php /* The group's corporate site for a multi-store owner,
                                               the store's own page for a single one. Optional. */ ?>
                                            <label>Website <small class="text-muted">(optional)</small></label>
                                            <input type="text" class="form-control" placeholder="example.com" name="u_website" value="<?php echo esc($u_website); ?>">
                                        </div>
                                    </div>

                                </div>

                                <div class="row storeonly">

                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea required class="form-control" placeholder="Enter Address" name="u_address1"><?php echo esc($u_address1); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Province</label>
                                            <select  required class="form-control " name="u_provice" id="provincelist" onChange="getpcities(this.value);">
												<option value="">Select Province</option>
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
                                            <label>City</label>
											<select  required name="u_city" id="city" class="form-control">
												<option value="">Select City</option>
											</select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Postal Code</label>
											<input required class="form-control" placeholder="Enter Postal Code" name="u_pincode" value="<?php echo esc($u_pincode); ?>" >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                     
                                    <div class="col-sm-3">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select  required class="form-control " name="u_status" id="u_status">
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
                                    <?php /* Back-office fact: whether the signed agreement is on
                                       file. Asked of every employer kind - owner, group and
                                       manager alike - so it sits outside the kind-specific rows. */ ?>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label class="d-block">Agreement</label>
                                            <div class="custom-control custom-checkbox mt-2">
                                                <input type="checkbox" class="custom-control-input" id="u_agreement_done" name="u_agreement_done" value="1" <?php echo ((int) ($u_agreement_done ?? 0) === 1)?"checked":""; ?>>
                                                <label class="custom-control-label" for="u_agreement_done">Agreement Done</label>
                                            </div>
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
            </section>
      </form>
</div>
