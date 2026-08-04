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
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
		<?php endif; ?>
        <div class="row">
          <div class="col-12">
  <div class="card">
              <div class="card-header">
                <h3 class="card-title"><a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/add');?>" class="btn btn-primary">Add <?php echo $pageinfo['title']; ?></a></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive">
                <table id="res-table-ex" class="display nowrap table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
                    <th><?php echo $pageinfo['title']; ?> Name</th>
                    <th><?php echo $pageinfo['title']; ?> Parent</th>
                    <th>Link/URL</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
				  if($headermenu){
					  foreach($headermenu as $record){
						  ?>
						  <tr>
							<td><?php echo $record->m_id;?></td>
							<td><?php echo $record->m_name;?></td>
							<td><?php echo ($record->mp_name) ? $record->mp_name : 'Self' ;?></td>
							<td><?php echo $record->m_link;?></td>
							<td>Level <?php echo $record->m_level;?></td>
							<td><?php echo $status[$record->m_status];?></td>
							<td><a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/edit/'.$record->m_id);?>" class="btn btn-success">Edit</a> 
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/delete/'.$record->m_id);?>" class="btn btn-danger" onclick="return confirm('Are you sure? You want to delete')">Delete</a>
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/changestatus/'.$record->m_id);?>" class="btn btn-warning">Change Status</a>
              
						  </tr>
						  <?php
					  }
				  }
				  ?>
                  
                  
                  </tbody>
                  <tfoot>
                  <tr>
					<th><?php echo $pageinfo['title']; ?> ID</th>
					<th><?php echo $pageinfo['title']; ?> Name</th>
                    <th><?php echo $pageinfo['title']; ?> Parent</th>
                    <th>Link/URL</th>
					<th>Level</th>
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
          