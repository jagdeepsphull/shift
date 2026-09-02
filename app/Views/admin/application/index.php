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
					<th>Store Address</th>
					<th>Book Shift For</th>
					<th>Lic. No.</th>
					<th>Shift Requested For</th>
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
							/* The shift title, its time, the applicant's name and licence,
							   the store and the shift's requested type all come down with the
							   list itself. They were a query each per row here - on a screen
							   showing every application, that was the whole cost of the page. */

							/* The branch the shift is at, which for a multi-store employer is
							   not the address on their login. `employer_id` rather than `u_id`:
							   the row carries the applicant's `u_id`, and shiftStore() wants
							   the shift's owner. */
							$store = shiftStore((object) [
								'p_store_id' => $record->p_store_id,
								'u_id'       => $record->employer_id,
							]);

							/* Stacked - street, then town, then province - as on the shift
							   list, and only the parts actually filled, or a store with no
							   town or postcode leaves blank lines behind it. */
							$storeAddress = $store ? array_values(array_filter([
								trim((string) $store->s_address),
								trim((string) getCityName($store->s_city)),
								trim((string) implode(' ', array_filter([
									trim((string) getProvinceName($store->s_province)),
									trim((string) $store->s_pincode),
								], static fn ($part) => $part !== ''))),
							], static fn ($part) => $part !== '')) : [];

							$applicantName = trim($record->applicant_fname.' '.$record->applicant_lname);
							$applicantLic  = trim((string) $record->applicant_licence);
						  ?>
						  <tr>
							<td><?php echo $record->sj_id;?></td>
							<td><?php echo esc($record->p_job_title);?></td>
							<td><?php if($storeAddress){ foreach($storeAddress as $line){ ?><span class="d-block"><?php echo esc($line); ?></span><?php } } else { echo '-'; } ?></td>
							<td><?php echo ($applicantName !== '') ? esc($applicantName) : '-';?></td>
							<td><?php echo ($applicantLic !== '') ? esc($applicantLic) : '-';?></td>
							<td><?php echo esc(getShiftForName($record->p_shift_for));?></td>
							<td data-order="<?php echo shiftDateSortValue($record); ?>"><?php echo dateFormat($record->p_dates);?></td>
							<td><?php echo $record->p_shift_time;?></td>
							<td><?php echo $application_approved[$record->sj_is_approved];?></td>
							<td>
							<a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'/view/'.$record->sj_id );?>" class="btn btn-success">View Detail</a> 
							<?php /* The two messages, as icons beside View Detail rather than a
							   column of their own: they are on a minority of rows, and a whole
							   column carrying two words of button was wider than the address it
							   was pushing off the screen. The colours are the ones the buttons
							   had, so a row still reads the same at a glance, and the title
							   names the message on the bubble it opens. */ ?>
							<?php if(trim((string) $record->sj_applied_desc) !== '') {?>
								<button type="button" class="btn btn-info popover-btn" data-toggle="popover" title="Applicant Message" data-content="<?php echo esc($record->sj_applied_desc, 'attr');?>"><i class="fas fa-comment-dots"></i></button>
							<?php }?>
							<?php if(trim((string) $record->sj_admin_comment) !== '') {?>
								<button type="button" class="btn btn-warning popover-btn" data-toggle="popover" title="Agency Message" data-content="<?php echo esc($record->sj_admin_comment, 'attr');?>"><i class="fas fa-comments"></i></button>
							<?php }?>
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
					<th>Store Address</th>
					<th>Book Shift For</th>
					<th>Lic. No.</th>
					<th>Shift Requested For</th>
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
          