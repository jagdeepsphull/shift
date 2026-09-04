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
			/* The slug, not the code: ?kind= is a URL, and employerKindBySlug()
			   is what reads it back. */
			$kindqs = ($kindSlug ?? '') !== '' ? '?kind='.$kindSlug : '';

			/* An owner is a group, not a shop. What they own are the rows in
			   the stores list, each with its own name and number, and their
			   own `u_licence_no` is the single store number an account was
			   signed up with - one shop's number standing for a chain of them,
			   or blank. So the Owners list names the first column for what it
			   actually holds and leaves the store number to the screen that
			   can be right about it.

			   Only that list. All Employers and Managers are still per shop -
			   a manager runs one - and there the two columns read true. Keyed
			   on the slug, which is what the config calls this kind, rather
			   than on the code behind it. */
			$isOwnerList = ($kindSlug ?? '') === 'owner';
			$nameHeading = $isOwnerList ? 'Group Name' : 'Store Name';
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
                    <th><?php echo $nameHeading; ?></th>
                    <?php if(!$isOwnerList){ ?><th>Store No.</th><?php } ?>
                    <th>Contact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
					<?php /* Ahead of Status, not after it: the table script gives the
					   last two columns their responsive priority on the strength of
					   every admin list ending Status then Action, and a column
					   slipped between them would take Status off a narrow screen. */ ?>
                    <th>Agreement Done</th>
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
					   of its own: the table already fills the width, and one more
					   column pushed Status and the buttons off the screen. */ ?>
                    <td><?php echo esc($user->u_comp_name); ?><br><small class="text-muted"><?php echo employerKindName($user); ?></small></td>
                    <?php if(!$isOwnerList){ ?><td><?php echo esc($user->u_licence_no); ?></td><?php } ?>
                    <td><?php echo esc($user->u_fname . ' ' . $user->u_lname); ?></td>
                    <td><?php echo esc($user->u_email); ?></td>
                    <?php /* An owner's and a manager's alike: it is the
                       contact person's own mobile, not the shop's counter
                       phone - that one lives on the store record. */ ?>
                    <td><?php echo whatsappPhoneLink($user->u_phone, 'Message ' . trim($user->u_fname . ' ' . $user->u_lname) . ' on WhatsApp'); ?></td>
                    <td><?php echo agreementDoneBadge($user->u_agreement_done ?? 0); ?></td>
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
					<th><?php echo $nameHeading; ?></th>
					<?php /* DataTables wants the foot to match the head column
					   for column, so this one goes with it. */ ?>
					<?php if(!$isOwnerList){ ?><th>Store No.</th><?php } ?>
                    <th>Contact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
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
