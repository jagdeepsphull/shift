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
			echo email_failure_notice();		
					
		?>
        <div class="row">
          <div class="col-12">
  <div class="card">
              <div class="card-header">
                
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
					<th>Shift ID</th>
                    <th>Employer</th>
					<th>Candidate</th>
					<th>Messages</th>
					<th>Shift Date</th>
					<th>Shift Time</th>
					<th>Approval Status</th>
					<th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
				  if($applicationslist){
					  foreach($applicationslist as $record){
							$candid = custom()->get_where('users',array('u_id'=>$record->u_id));
							$postjob = custom()->get_where('post_job',array('p_id'=>$record->p_id));
						  ?>
						  <tr>
							<td><?php echo $record->sj_id;?></td>
							<td><?php echo $postjob[0]->p_job_title;?></td>
							<td><?php echo $record->u_comp_name;?></td>
							<td><?php echo $candid[0]->u_fname.' '.$candid[0]->u_lname;?></td>
							<td>
								<?php if(trim((string) $record->sj_applied_desc) !== '') {?>
									<button type="button" class="btn btn-info popover-btn mb-2" data-toggle="popover" data-content="<?php echo esc($record->sj_applied_desc, 'attr');?>">Applicant Message</button>
								<?php }?>
								<?php if(trim((string) $record->sj_admin_comment) !== '') {?>
									<button type="button" class="btn btn-warning popover-btn" data-toggle="popover" data-content="<?php echo esc($record->sj_admin_comment, 'attr');?>">Agency Message</button>
								<?php }?>
							</td>
							<td data-order="<?php echo shiftDateSortValue($record); ?>"><?php echo dateFormat($record->p_dates);?></td>
							<td><?php echo $postjob[0]->p_shift_time;?></td>
							<td><?php echo $application_approved[$record->sj_is_approved];?></td>
							<td>
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/view/'.$record->sj_id );?>" class="btn btn-success">View Detail</a> 
							<!--<a href="<?php //echo base_url('sadmin/'.$pageinfo['link'].'/approve/'.$record->sj_id );?>" class="btn btn-warning">Approve </a> 
							<a href="<?php //echo base_url('sadmin/'.$pageinfo['link'].'/reject/'.$record->sj_id );?>" class="btn btn-danger">Reject </a> 
							<a href="<?php //echo base_url('sadmin/'.$pageinfo['link'].'/delete/'.$record->p_id);?>" class="btn btn-danger" onclick="return confirm('Are you sure? You want to delete')">Delete</a> -->
						  </tr>
						  <?php
					  }
				  }
				  ?>
                  
                  
                  </tbody>
                  <tfoot>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
					<th>Shift ID</th>
                    <th>Employer</th>
					<th>Candidate</th>
					<th>Applicant Message</th>
					<th>Shift Date</th>
					<th>Shift Time</th>
					<th>Approval Status</th>
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
          