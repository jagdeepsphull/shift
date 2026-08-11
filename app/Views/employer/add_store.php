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
	                                <input type="text" class="form-control" name="s_phone" placeholder="Enter Store Phone" value="<?php echo esc($s_phone); ?>">
	                            </div>
	                        </div>
	                    </div>

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
