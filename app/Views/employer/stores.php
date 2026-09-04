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
	                        <?php /* Nine columns of address and contact detail do not fit a
	                           phone. min-tablet-p folds the middle seven into the panel under
	                           the row from below a tablet, leaving the store and its Edit
	                           button; all keeps those two in the row at every width. */ ?>
	                        <thead>
	                            <tr>
	                                <th class="all">Store Name</th>
	                                <th class="min-tablet-p">Store Number</th>
	                                <th class="min-tablet-p">Address</th>
	                                <th class="min-tablet-p">City</th>
	                                <th class="min-tablet-p">Province</th>
	                                <th class="min-tablet-p">Phone</th>
	                                <th class="min-tablet-p">Manager</th>
	                                <th class="min-tablet-p">Status</th>
	                                <th class="all">Action</th>
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
	                                <td><?php $manager = ($store_managers ?? [])[(int) $store->s_id] ?? null; ?>
	                                <?php if ($manager) { ?>
	                                    <?php echo esc(trim($manager->u_fname . ' ' . $manager->u_lname)); ?>
	                                    <?php if ($manager->u_email) { ?><br><small class="text-muted"><?php echo esc($manager->u_email); ?></small><?php } ?>
	                                    <?php /* The manager's own mobile. The
	                                       store's line is the column before this
	                                       one and stays plain: that is the
	                                       counter phone, and a WhatsApp mark on
	                                       it would promise a chat nobody reads. */ ?>
	                                    <?php if ($manager->u_phone) { ?><br><small><?php echo portalWhatsappPhoneLink($manager->u_phone, 'Message ' . trim($manager->u_fname . ' ' . $manager->u_lname) . ' on WhatsApp'); ?></small><?php } ?>
	                                    <?php if ((int) $manager->u_status !== 1) { ?><br><small class="text-danger">Awaiting approval</small><?php } ?>
	                                <?php } else { ?>
	                                    <small class="text-muted">No manager</small>
	                                <?php } ?></td>
	                                <td><?php echo $store->s_status == 1 ? 'Active' : 'Inactive'; ?></td>
	                                <td><?php if ((int) $store->u_id === (int) ($store_owner_id ?? 0)) { ?>
                                    <a class="btn btn-sm btn-info" href="<?php echo base_url('employer/edit_store/' . $store->s_id); ?>">Edit</a>
                                <?php } else { ?>
                                    <small class="text-muted">Your corporate group's</small>
                                <?php } ?></td>
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
