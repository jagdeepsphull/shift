<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Manage Stores<?php echo $ownerRow ? ' &mdash; '.esc($ownerRow['u_comp_name']) : ''; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/employer');?>">Employers</a></li>
              <li class="breadcrumb-item active">Stores</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
		<?php
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}

			/* Every row action carries the chain it was started from, so saving
			   or deleting comes back to that chain rather than to every store
			   in the system. */
			$ownerqs = $owner ? '?owner='.(int)$owner : '';
		?>
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

		<div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Store List</h3>

				<a href="<?php echo base_url($adminpath.'/stores/add'.$ownerqs);?>" class="btn btn-info float-sm-right">Add Store</a>
				<?php if($owner){ ?>
				<a href="<?php echo base_url($adminpath.'/stores');?>" class="btn btn-secondary float-sm-right mr-2">All Stores</a>
				<?php } ?>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive1 p-2">
                <table id="example1" class="table table-bordered table-striped datatablecss" data-order-col="1" data-order-dir="asc">
                  <thead>
                  <tr>
                    <th>Store ID</th>
                    <th>Store Name</th>
                    <th>Employer</th>
                    <th>Store No.</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Links</th>
					<?php /* The employer's answer, not the store's: the agreement is
					   signed by the account, so every location of one chain shows the
					   same word. Ahead of Status because the table script gives the
					   last two columns their responsive priority on the strength of
					   every admin list ending Status then Action. */ ?>
                    <th>Agreement Done</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
				  <?php if($stores){?>
                  <tbody>
				  <?php foreach($stores as $store){
						$ownerName = getPharmacyName($store->u_id);
						$mapLink   = storeMapLink($store);
				  ?>
                  <tr>
                    <td><?php echo $store->s_id; ?></td>
                    <td><?php echo esc($store->s_name); ?>
						<?php if($store->s_location_label !== ''){ ?>
						<br><small class="text-muted"><?php echo esc($store->s_location_label); ?></small>
						<?php } ?>
					</td>
                    <td><a href="<?php echo base_url($adminpath.'/stores?owner='.(int)$store->u_id);?>"><?php echo esc($ownerName !== '' ? $ownerName : '#'.$store->u_id); ?></a></td>
                    <td><?php echo esc($store->s_number); ?></td>
                    <td><?php echo esc($store->s_address); ?><br><small class="text-muted"><?php echo esc(getCityName($store->s_city).' '.$store->s_pincode); ?></small></td>
                    <td><?php echo esc($store->s_phone); ?></td>
                    <td>
						<?php /* The pasted Google link where there is one, otherwise a
						   search for the address - so every store with an address has
						   something to tap. The badge says which it is, because only a
						   pasted link points at a pin somebody chose. */ ?>
						<?php if($mapLink !== ''){ ?>
						<a href="<?php echo esc($mapLink); ?>" target="_blank" rel="noopener noreferrer" class="badge <?php echo $store->s_map_url !== '' ? 'badge-success' : 'badge-secondary'; ?>">
							<?php echo $store->s_map_url !== '' ? 'Map pin' : 'Map search'; ?></a>
						<?php }else{ ?>
						<span class="text-muted">&ndash;</span>
						<?php } ?>
						<?php if($store->s_website !== ''){ ?>
						<a href="<?php echo esc(safeUrl($store->s_website)); ?>" target="_blank" rel="noopener noreferrer" class="badge badge-info">Website</a>
						<?php } ?>
					</td>
                    <td><?php echo agreementDoneBadge(userAgreementDone($store->u_id)); ?></td>
                    <td><?php if($store->s_status=='1'){?><span class="badge badge-success">Active</span><?php }else{?><span class="badge badge-warning">Inactive</span><?php }?></td>
                    <td><a href="<?php echo base_url($adminpath.'/stores/edit/'.$store->s_id.$ownerqs);?>" class="btn btn-success" title="Edit"><i class="fas fa-edit"></i></a>
						<?php if($store->s_status=='1'){?>
						<a href="<?php echo base_url($adminpath.'/stores/changestatus/'.$store->s_id.$ownerqs);?>" class="btn btn-secondary" title="Deactivate" onclick="return confirm('Deactivate this store? It will stop being offered on new shifts.')"><i class="fas fa-ban"></i></a>
						<?php }else{?>
						<a href="<?php echo base_url($adminpath.'/stores/changestatus/'.$store->s_id.$ownerqs);?>" class="btn btn-warning" title="Activate"><i class="fas fa-check"></i></a>
						<?php }?>
						<a href="<?php echo base_url($adminpath.'/stores/delete/'.$store->s_id.$ownerqs);?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure? You want to delete')"><i class="fas fa-trash-alt"></i></a>
						</td>
                  </tr>
					  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  <tr>
                    <th>Store ID</th>
                    <th>Store Name</th>
                    <th>Employer</th>
                    <th>Store No.</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Links</th>
                    <th>Agreement Done</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>

     </div>
  </section>
    </div>
