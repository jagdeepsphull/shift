<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Manage <?php echo $pageinfo['listtitle']; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/'.$link);?>">Employers</a></li>
              <li class="breadcrumb-item active"><?php echo $pageinfo['listtitle']; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
		<?php
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}
			echo email_failure_notice();

			/* Every row action carries the list it was started from, so saving,
			   activating or deleting comes back to the same kind rather than to
			   All Employers. */
			$kindqs = $kind ? '?kind='.$kind : '';
		?>
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

		<div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><?php echo $pageinfo['listtitle']; ?> List </h3>

				<a href="<?php echo base_url($adminpath.'/'.$link.'/add'.$kindqs);?>" class="btn btn-info  float-sm-right">Add <?php echo $pageinfo['title']; ?></a>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive1 p-2" style="1height: 300px;">
                <table id="example1" class="table table-bordered table-striped datatablecss" data-order-col="1" data-order-dir="asc">
                  <thead>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
                    <th>Store Name</th>
                    <th>Store No.</th>
                    <th>Conatact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
				  <?php if($users){?>
                  <tbody>
				  <?php foreach($users as $user){?>
                  <tr>
                    <td><?php echo $user->u_id; ?></td>
					<?php /* The kind sits under the name rather than in a column
					   of its own: the table already fills the width, and a ninth
					   column pushed Status and the buttons off the screen. */ ?>
                    <td><?php echo $user->u_comp_name; ?><br><small class="text-muted"><?php echo employerKindName($user); ?></small></td>
                    <td><?php echo $user->u_licence_no; ?></td>
                    <td><?php echo $user->u_fname.' ' .$user->u_lname; ?></td>
                    <td><?php echo $user->u_email; ?></td>
                    <td><?php echo $user->u_phone; ?></td>
                    <td><?php if($user->u_status=='1'){?><span class="badge badge-success">Active</span><?php }else{?><span class="badge badge-warning">Pending</span><?php }?></td>
                    <td><a href="<?php echo base_url($adminpath.'/'.$link.'/edit/'.$user->u_id.$kindqs);?>" class="btn btn-success" title="Edit"><i class="fas fa-edit"></i></a>
					<?php /* Activating here sends the same approval e-mail the
					   edit form does, so a new sign-up can be switched on
					   without opening the record. */ ?>
					<?php if($user->u_status=='1'){?>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/changestatus/'.$user->u_id.$kindqs);?>" class="btn btn-secondary" title="Deactivate" onclick="return confirm('Deactivate this account?')"><i class="fas fa-ban"></i></a>
					<?php }else{?>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/changestatus/'.$user->u_id.$kindqs);?>" class="btn btn-warning" title="Activate" onclick="return confirm('Activate this account and e-mail the user?')"><i class="fas fa-check"></i></a>
					<?php }?>
					<a href="<?php echo base_url($adminpath.'/'.$link.'/delete/'.$user->u_id.$kindqs);?>"  class="btn btn-danger" title="Delete"  onclick="return confirm('Are you sure? You want to delete')"><i class="fas fa-trash-alt"></i></a>

					</td>
                  </tr>


				  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  <tr>
					<th><?php echo $pageinfo['title']; ?> ID</th>
					<th>Store Name</th>
					<th>Store No.</th>
                    <th>Conatact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
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
