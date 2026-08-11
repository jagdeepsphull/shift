	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-9">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header d-flex justify-content-between align-items-center">
	                <h4><i class="ti-home"></i>My Stores</h4>
	                <?php if ($can_add_store) { ?>
	                <a class="btn btn-common" href="<?php echo base_url('employer/add_store'); ?>">Add Store</a>
	                <?php } ?>
	            </div>
	            <?php echo session()->getFlashdata('error_msg'); ?>
	            <div class="dashboard-caption-wrap">

	                <div class="table-responsive">
	                    <table id="storelist" class="table table-hover">
	                        <thead>
	                            <tr>
	                                <th>Store Name</th>
	                                <th>Store Number</th>
	                                <th>Address</th>
	                                <th>City</th>
	                                <th>Province</th>
	                                <th>Phone</th>
	                                <th>Status</th>
	                                <th>Action</th>
	                            </tr>
	                        </thead>
	                        <tbody>
	                            <?php if ($stores) { ?>
	                            <?php foreach ($stores as $store) { ?>
	                            <tr>
	                                <td><?php echo esc($store->s_name); ?></td>
	                                <td><?php echo esc($store->s_number); ?></td>
	                                <td><?php echo esc($store->s_address); ?></td>
	                                <td><?php echo getCityName($store->s_city); ?></td>
	                                <td><?php echo getProvinceName($store->s_province); ?></td>
	                                <td><?php echo esc($store->s_phone); ?></td>
	                                <td><?php echo $store->s_status == 1 ? 'Active' : 'Inactive'; ?></td>
	                                <td><a class="btn btn-sm btn-info" href="<?php echo base_url('employer/edit_store/' . $store->s_id); ?>">Edit</a></td>
	                            </tr>
	                            <?php } ?>
	                            <?php } ?>
	                        </tbody>
	                    </table>
	                </div>

	            </div>
	        </div>
	    </div>
	</div>

	</div>
	</div>
	</section>
	<!-- General Detail End -->
