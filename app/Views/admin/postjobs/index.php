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
                <table id="example1" class="table table-bordered table-striped datatablecss" data-order-col="6" data-order-dir="desc">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Shift ID</th>
                    <th>Employer</th>
                    <th>City</th>
                    <th>Province</th>
                    <th>Shift Requested For</th>
                    <th>Shift Date</th>
                    <th>Shift Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
				  <?php if($jobs){?>
                  <tbody>
					<?php 
					foreach($jobs as $job){
						
						// Check for approved saved/applied jobs
						$applied_approved = ci_db()->table('stu_saved_applied_jobs')
							->where('p_id', $job->p_id)
							->where('sj_is_approved', 1)
							->countAllResults();
						
					?>
                  <tr class="<?php echo ($job->p_approved==1) ? 'bg-gradient-success'  : '' ;?> <?php echo ($job->p_approved==3) ? 'bg-gradient-warning'  : '' ;?>" >
                    <td><?php echo $job-> p_id ; ?></td>
                    <td><?php echo $job->p_job_title; ?></td>
                    <td><?php echo getPharmacyName($job-> u_id ); ?></td>
                    <td><?php echo getCityName($job->p_city); ?></td>
                    <td><?php echo getProvinceName($job->p_province); ?></td>
                    <td><?php echo getShiftForName($job->p_shift_for); ?></td>
                    <td data-order="<?php echo shiftDateSortValue($job, 'DESC'); ?>"><?php echo dateFormat($job->p_dates); ?></td>
					<td><?php echo $approved[$job->p_approved];?></td>
                    <td>
					<?php if($applied_approved === 0 && $job->p_approved != 3){ ?>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/edit/'.$job->p_id);?>" class="btn btn-success"><i class="fas fa-edit"></i> Edit</a> <a href="<?php echo base_url($adminpath.'/'.$link.'/delete/'.$job->p_id);?>"  class="btn btn-danger"  onclick="return confirm('Are you sure? You want to delete')"><i class="fas fa-trash-alt"></i> Delete</a>
					<?php } ?>
					</td>
                  </tr>
                 
                  
				  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  <tr>
                    <th>ID</th>
                    <th>Shift ID</th>
                    <th>Employer</th>
                    <th>City</th>
                    <th>Province</th>
                    <th>Shift Requested For</th>
                    <th>Shift Date</th>
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