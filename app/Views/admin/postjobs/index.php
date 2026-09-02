<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Manage <?php echo $pageinfo['title']; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item active"><?php echo $pageinfo['title']; ?> List</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
		<?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}
			echo email_failure_notice();		
					
		?>
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
		<div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><?php echo $pageinfo['title']; ?> List </h3> 

				<a href="<?php echo base_url($adminpath.'/'.$link.'/add');?>" class="btn btn-info  float-sm-right">Add <?php echo $pageinfo['title']; ?></a>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive1 p-2" style="1height: 300px;">
                <!-- Column 0 is the record id, hidden by the shared table script:
                     newest shift on top, matching the order the controller sends. -->
                <table id="example1" class="table table-bordered table-striped datatablecss" data-order-col="0" data-order-dir="desc">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Shift ID</th>
                    <th>Store Address</th>
                    <th>Applicant type</th>
                    <th>Applicant</th>
                    <th>Lic. No.</th>
                    <th>Shift Date</th>
                    <th>Shift Time</th>
                    <th>Shift Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
				  <?php if($jobs){?>
                  <tbody>
					<?php 
					foreach($jobs as $job){

						// Every shift carries both buttons, a booked one
						// included. A booked applicant who cannot make it has
						// to be swapped for another, and that is done on the
						// shift form - so the row that most needs Edit was the
						// one row that did not offer it. Sadmin::postjobs()
						// lets the same two actions through, so a typed URL and
						// these buttons agree.

						// The branch the shift is at. City and province are
						// part of the address, so the two columns that used to
						// carry them on their own are gone. A shift from before
						// the stores existed falls back to the employer's login
						// columns, which is where its address has always come
						// from.
						$store = shiftStore($job);

						// Stacked - street, then town, then province - rather
						// than run together on one line: the column is read
						// down the page looking for a branch, and three short
						// lines are quicker to scan than one long one that
						// wraps where the column happens to end. Only the parts
						// actually filled, or a store with no town or postcode
						// leaves blank lines behind it.
						$storeAddress = $store ? array_values(array_filter([
							trim((string) $store->s_address),
							trim((string) getCityName($store->s_city)),
							trim((string) implode(' ', array_filter([
								trim((string) getProvinceName($store->s_province)),
								trim((string) $store->s_pincode),
							], static fn ($part) => $part !== ''))),
						], static fn ($part) => $part !== '')) : [];

						// Who is working the shift, looked up for the whole
						// list at once in the controller. Null while nobody is
						// on it, which is every shift still on the board.
						$booked     = $bookings[(int) $job->p_id] ?? null;
						$bookedName = $booked ? trim($booked->u_fname.' '.$booked->u_lname) : '';

						// The type of the person on the shift once there is
						// one, and until then the type the shift is asking for
						// - the same answer either way in the ordinary case,
						// since an applicant applies for their own type, and
						// the truthful one when an administrator has placed
						// somebody of another type on it himself.
						$applicantType = $booked
							? getShiftForName($booked->u_usersubtype)
							: getShiftForName($job->p_shift_for);
					?>
                  <tr class="<?php echo ($job->p_approved==1) ? 'bg-gradient-success'  : '' ;?> <?php echo ($job->p_approved==3) ? 'bg-gradient-warning'  : '' ;?>" >
                    <td><?php echo $job-> p_id ; ?></td>
                    <td><?php echo esc($job->p_job_title); ?></td>
                    <td><?php if($storeAddress){ foreach($storeAddress as $line){ ?><span class="d-block"><?php echo esc($line); ?></span><?php } } else { echo '-'; } ?></td>
                    <td><?php echo ($applicantType !== '') ? esc($applicantType) : '-'; ?></td>
                    <td><?php echo ($bookedName !== '') ? esc($bookedName) : '-'; ?></td>
                    <td><?php echo ($booked && trim((string) $booked->u_licence_no) !== '') ? esc($booked->u_licence_no) : '-'; ?></td>
                    <td data-order="<?php echo shiftDateSortValue($job); ?>"><?php echo dateFormat($job->p_dates); ?></td>
                    <td><?php echo esc($job->p_shift_time); ?></td>
					<td><?php echo $approved[$job->p_approved];?></td>
                    <td>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/edit/'.$job->p_id);?>" class="btn btn-success"><i class="fas fa-edit"></i> Edit</a>
					<?php /* A booked shift warns before it goes: deleting it
					   takes the shift off the applicant who was told it was
					   theirs, and they are e-mailed that it is cancelled. */ ?>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/delete/'.$job->p_id);?>"  class="btn btn-danger"  onclick="return confirm('<?php echo ($job->p_approved == 3) ? 'Somebody is booked on this shift. Deleting it cancels their booking and e-mails them. Are you sure?' : 'Are you sure? You want to delete'; ?>')"><i class="fas fa-trash-alt"></i> Delete</a>
					</td>
                  </tr>
                 
                  
				  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  <tr>
                    <th>ID</th>
                    <th>Shift ID</th>
                    <th>Store Address</th>
                    <th>Applicant type</th>
                    <th>Applicant</th>
                    <th>Lic. No.</th>
                    <th>Shift Date</th>
                    <th>Shift Time</th>
                    <th>Shift Status</th>
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