<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Update Store</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/stores');?>">Store List</a></li>
                        <li class="breadcrumb-item">Edit</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <form name="addform" action="" method="post">
        <section class="content">
            <div class="container-fluid">
			<?php if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');} ?>
			<?php if (validation_errors()): ?>
				<div class="alert alert-danger"><?php echo validation_errors(); ?></div>
			<?php endif; ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Store Information</h3>
                            </div>
                            <div class="card-body">

								<?php /* Which owner this location belongs to. Only owners are
								   listed: a manager does not own a location, they run one of
								   their group's - which store that is belongs on their own form,
								   not here. With managers gone there is nothing for a type
								   picker to narrow, so the form asks for the owner and no more.
								   An account that already holds one location and may not take
								   another is refused by the controller. */ ?>
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Owner</label>
                                            <select required class="form-control" name="u_id" id="store_owner">
												<option value="">-- Select Owner --</option>
                                                <?php if($owners){ ?>
                                                <?php foreach($owners as $record){ ?>
                                                <?php /* As on add: the kind is spelled out only when it
                                                   is not an Owner. A store still held by a manager
                                                   from before they were assigned one on the
                                                   employer form is put back on this list by the
                                                   controller, and reads as such. */ ?>
                                                <option value="<?php echo $record->u_id; ?>"
                                                    <?php echo ($u_id==$record->u_id)?"selected":""; ?>>
                                                    <?php echo esc($record->u_comp_name . (employerKindCode($record) === 1 ? '' : ' ('.employerKindName($record).')')); ?></option>
                                                <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Store Name</label>
                                            <input required type="text" class="form-control" placeholder="Enter Store Name" name="s_name" value="<?php echo esc($s_name); ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Store No.</label>
                                            <input type="text" class="form-control" placeholder="Enter Store No." name="s_number" value="<?php echo esc($s_number); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea required class="form-control" placeholder="Enter Address" name="s_address"><?php echo esc($s_address); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Province</label><input type="hidden" id="hprovince" value="<?php echo $s_province; ?>">
                                            <select required class="form-control" name="s_province" id="provincelist" onChange="getpcities(this.value);">
												<option value="">Select Province</option>
                                                <?php if($province){ ?>
                                                <?php foreach($province as $record){ ?>
                                                <option value="<?php echo $record->p_id; ?>"
                                                    <?php echo ($s_province==$record->p_id)?"selected":""; ?>>
                                                    <?php echo $record->p_name; ?></option>
                                                <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>City</label><input type="hidden" id="hcity" value="<?php echo $s_city; ?>">
											<select required name="s_city" id="city" class="form-control">
												<option value="">Select City</option>
											</select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Postal Code</label><span class="text-danger font-weight-bold ml-1" style="font-size:12px;">(e.g. M5A 1A1)</span>
											<input class="form-control" placeholder="Enter Postal Code" name="s_pincode" value="<?php echo esc($s_pincode); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Store Phone</label>
                                            <input type="text" class="form-control" placeholder="Enter Store Phone" name="s_phone" value="<?php echo esc($s_phone); ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control" name="s_status">
                                                <option value="1" <?php echo ($s_status==='' || $s_status==1)?"selected":""; ?>>Active</option>
                                                <option value="0" <?php echo ($s_status!=='' && $s_status==0)?"selected":""; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

								<?php /* What a shift at this store starts with. Only a starting
								   point: choosing this store on the shift form copies these in,
								   and the shift keeps its own copy from the moment it is saved -
								   so correcting one shift never reaches back here, and changing
								   these never reaches back into shifts already posted. */ ?>
								<div class="row">
									<div class="col-sm-12">
										<h5 class="mt-2 mb-1">Shift defaults</h5>
										<p class="text-muted small">Ticked here, these arrive already ticked on a new shift at this store. Changing them on a shift affects that shift only; change them here to affect future ones.</p>
									</div>
									<div class="col-sm-3">
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 's_skills', 'label' => 'Software', 'items' => $software_skills, 'idKey' => 'ss_id', 'labelKey' => 'ss_name', 'selected' => $s_skills, 'required' => false,]) ?>
										</div>
									</div>
									<div class="col-sm-3">
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 's_services', 'label' => 'Details', 'items' => $store_service, 'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $s_services, 'required' => false,]) ?>
										</div>
									</div>
									<div class="col-sm-3">
										<div class="form-group">
											<?= view('partials/checkbox_grid', ['name' => 's_additional_details', 'label' => 'Additional Details', 'items' => $additional_details, 'idKey' => 'ad_id', 'labelKey' => 'ad_name', 'selected' => $s_additional_details, 'required' => false,]) ?>
										</div>
									</div>
								</div>

								<?= view('partials/store_location_fields', [
									'locationLabel' => $s_location_label,
									'mapUrl'        => $s_map_url,
									'website'       => $s_website,
								]) ?>

                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <input type="submit" name="savedata" class="btn btn-primary" value="Update Store" />
                                <a href="<?php echo base_url($adminpath.'/stores'.($owner ? '?owner='.(int)$owner : ''));?>" class="btn btn-default">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
</div>
