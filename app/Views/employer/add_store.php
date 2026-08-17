	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="ti-home"></i>Add Store</h4>
	            </div>

	            <?php echo session()->getFlashdata('error_msg'); ?>

	            <form name="store-form" action="" method="post">
	                <div class="dashboard-caption-wrap">

	                    <div class="row">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group">
	                                <h4>Store Detail</h4>
	                            </div>
	                        </div>
	                    </div>

	                    <div class="row">
	                        <div class="col-sm-6">
	                            <div class="form-group">
	                                <label>Store Name</label>
	                                <input type="text" required class="form-control" name="s_name" placeholder="Enter Store Name" value="<?php echo esc($s_name); ?>">
	                            </div>
	                        </div>
	                        <div class="col-sm-6">
	                            <div class="form-group">
	                                <label>Store Number</label>
	                                <input type="text" required class="form-control" name="s_number" placeholder="Enter Store Number" value="<?php echo esc($s_number); ?>">
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
	                                <select required class="form-control" name="s_province" onChange="getpcities(this.value);">
	                                    <option value="">Select Province</option>
	                                    <?php if ($province) { ?>
	                                    <?php foreach ($province as $record) { ?>
	                                    <option value="<?php echo $record->p_id; ?>" <?php echo ($s_province == $record->p_id) ? 'selected' : ''; ?>>
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
	                                <label>Postal Code</label><span class="text-danger font-weight-bold ml-1" style="font-size:12px;">e.g., M5A 1A1</span>
	                                <input class="form-control" placeholder="Enter Postal Code" name="s_pincode" value="<?php echo esc($s_pincode); ?>">
	                            </div>
	                        </div>
	                    </div>

	                    <div class="row">
	                        <div class="col-sm-6">
	                            <div class="form-group">
	                                <label>Store Phone</label>
	                                <input type="text" class="form-control" name="s_phone" placeholder="Enter Store Phone" value="<?php echo esc($s_phone); ?>" maxlength="<?= PHONE_LENGTH ?>" inputmode="numeric" pattern="[0-9]{<?= PHONE_LENGTH ?>}" data-phone-input>
	                            </div>
	                        </div>
	                    </div>

	                    <?php /* What a shift at this store starts with. Only a starting
	                       point: choosing this store on the shift form copies these in,
	                       and the shift keeps its own copy from the moment it is saved -
	                       so correcting one shift never reaches back here, and changing
	                       these never reaches back into shifts already posted. The same
	                       block the back office's store form shows. */ ?>
	                    <div class="row">
	                        <div class="col-md-12 col-sm-12">
	                            <h5 class="mt-2 mb-1">Shift defaults</h5>
	                            <p class="text-muted small">Ticked here, these arrive already ticked on a new shift at this store - whether you post it or your manager does. Changing them on a shift affects that shift only; change them here to affect future ones.</p>
	                        </div>
	                        <div class="col-sm-4">
	                            <?= view('partials/checkbox_grid', ['name' => 's_skills', 'label' => 'Software', 'items' => $software_skills, 'idKey' => 'ss_id', 'labelKey' => 'ss_name', 'selected' => $s_skills, 'required' => false,]) ?>
	                        </div>
	                        <div class="col-sm-4">
	                            <?= view('partials/checkbox_grid', ['name' => 's_services', 'label' => 'Details', 'items' => $store_service, 'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $s_services, 'required' => false,]) ?>
	                        </div>
	                        <div class="col-sm-4">
	                            <?= view('partials/checkbox_grid', ['name' => 's_additional_details', 'label' => 'Additional Details', 'items' => $additional_details, 'idKey' => 'ad_id', 'labelKey' => 'ad_name', 'selected' => $s_additional_details, 'required' => false,]) ?>
	                        </div>
	                    </div>

	                    <?= view('partials/store_location_fields', [
	                        'locationLabel' => $s_location_label,
	                        'mapUrl'        => $s_map_url,
	                        'website'       => $s_website,
	                    ]) ?>

	                    <div class="row mrg-top-30">
	                        <div class="col-md-12 col-sm-12">
	                            <div class="form-group text-center">
	                                <input type="submit" class="btn btn-common" name="savestore" value="Add Store" />
	                            </div>
	                        </div>
	                    </div>

	                </div>
	            </form>

	        </div>
	    </div>
	</div>

	</div>
	</div>
	</section>
	<!-- General Detail End -->
