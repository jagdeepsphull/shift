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
                                <h3 class="card-title"><?php echo $pageinfo['title']; ?> Information</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">

								<?php /* Changing this is how an employer is converted between
								   kinds - notably how one of the accounts that predates the
								   multi-store feature becomes a multi-store owner. The
								   controller refuses a conversion that would contradict the
								   records already attached to the account. */ ?>
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
											<?php /* Hidden for a manager: the count is of stores this
											   account owns, and a manager owns none - the one they
											   run belongs to their group, and is the dropdown
											   below. Reading "0 store(s)" beside it says the
											   opposite of what is true. */ ?>
											<small class="form-text text-muted owneronly">
												<?php echo (int)($store_count ?? 0); ?> store(s) on this account.
												<a href="<?php echo base_url($adminpath.'/stores?owner='.(int)$u_id);?>">Manage stores</a>
											</small>
										</div>
									</div>

									<div class="col-sm-6 grouponly">
										<div class="form-group">
											<label>Corporate Group</label>
											<select required class="form-control" name="u_parent_id" id="u_parent_id">
												<option value="">-- None --</option>
												<?php if(!empty($pharmacy_groups)){ ?>
												<?php foreach($pharmacy_groups as $group){ ?>
												<?php if((int)$group->u_id === (int)$u_id){ continue; } /* an account cannot answer to itself */ ?>
												<option value="<?php echo $group->u_id; ?>"
													<?php echo (($u_parent_id ?? '')==$group->u_id)?"selected":""; ?>>
													<?php echo esc($group->u_comp_name); ?></option>
												<?php } ?>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<?php /* Which of the group's stores this manager runs - the same
								   question the add form and registration ask, filled by the same
								   endpoint, so an account edited here ends up the shape one
								   created there does. The hidden input is the store already on
								   the account, which the script re-selects once the list has
								   loaded. Hidden for everybody else. */ ?>
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
    onfocus="if (this.value == '') {this.value = '';}"  type="text" class="form-control" placeholder="Enter Last Name" name="u_lname" value="<?php echo $u_lname; ?>">
                                        </div>
                                    </div>
                                </div>
								

                                <div class="row">
                                    <?php /* As on add: a manager is not asked for a name of their
                                       own, because the store they picked already has one - and
                                       the controller saves them a blank `u_comp_name` for that
                                       reason. The label reads "Corporate Group Name" for a
                                       multi-store owner; same column either way. */ ?>
                                    <div class="col-sm-3 owneronly">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <label id="compnamelbl">Store Name</label>
                                            <input  type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Name" name="u_comp_name" value="<?php echo $u_comp_name; ?>">
                                        </div>
                                    </div>
									<?php /* The login's own licence and address. A multi-store
									   owner keeps whatever is here - a shift created outside the
									   store flow still reads its location off these columns -
									   but is not asked to maintain it. */ ?>
									<div class="col-sm-3 storeonly">
                                        <div class="form-group">
                                            <label>Store Registration Province</label>
                                            <select  class="form-control " name="u_l_provice" id="province_L_list" >
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
                                            <input  type="text" class="form-control" placeholder="Enter Store No." name="u_licence_no" value="<?php echo $u_licence_no; ?>">
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
                                            <input  type="text" class="form-control" placeholder="Enter <?php echo $pageinfo['title']; ?> Mobile No." name="u_phone" value="<?php echo $u_phone; ?>">
                                        </div>
                                    </div>
									<div class="col-sm-4">
                                        <!-- text input -->
                                        <div class="form-group">
                                            <?php /* The group's corporate site for a multi-store owner,
                                               the store's own page for a single one. Optional. */ ?>
                                            <label>Website <small class="text-muted">(optional)</small></label>
                                            <input type="url" class="form-control" placeholder="https://example.com" name="u_website" value="<?php echo esc($u_website); ?>">
                                        </div>
                                    </div>
                                    

                                </div>

                                <div class="row storeonly">

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
                                            <label>City</label><input type="hidden" id="hcity" value="<?php echo $u_city; ?>" >
											<select  required name="u_city" id="city" class="form-control">
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
                                            <select class="form-control " name="u_status" id="u_status">
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
