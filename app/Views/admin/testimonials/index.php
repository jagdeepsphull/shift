<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><?php echo $pageinfo['title']; ?> List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item active"><?php echo $pageinfo['title']; ?> List</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
	  <?php
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}

		?>
        <div class="row">
          <div class="col-12">
  <div class="card">
              <div class="card-header">
                <h3 class="card-title"><a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/add');?>" class="btn btn-primary">Add <?php echo $pageinfo['title']; ?></a></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <?php // Oldest first, so the list reads in the order the home page carousel rotates them. ?>
                <?php /* `data-manual-order` turns DataTables' own sorting off for
                   this list - see the footer. The order here is the one the
                   arrows below set, and a table that re-sorted itself on
                   arrival would show a different order to the one being
                   edited. */ ?>
                <table id="example1" class="table table-bordered table-striped" data-manual-order="1">
                  <thead>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
				  if($testimonials){
					  foreach($testimonials as $index => $record){
						  ?>
						  <tr>
							<td><?php echo $record->t_id;?></td>
							<td><?php echo esc($record->t_title);?></td>
							<td><?php echo esc($record->t_description);?></td>
							<td><?php echo $status[$record->t_status];?></td>
							<td><a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/edit/'.$record->t_id);?>" class="btn btn-success">Edit</a>
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/delete/'.$record->t_id);?>" class="btn btn-danger" onclick="return confirm('Are you sure? You want to delete')">Delete</a>
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/changestatus/'.$record->t_id);?>" class="btn btn-warning">Change Status</a>
							<?= view('partials/list_order_arrows', ['rows' => $testimonials, 'index' => $index, 'record' => $record, 'idKey' => 't_id', 'labelKey' => 't_title', 'link' => $pageinfo['link'], 'groupKey' => null]) ?>
							</td>
						  </tr>
						  <?php
					  }
				  }
				  ?>


                  </tbody>
                  <tfoot>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
                    <th>Title</th>
                    <th>Description</th>
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
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
