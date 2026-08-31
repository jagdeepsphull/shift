<?php
/**
 * Who has taken themselves off the e-mail list, newest first.
 *
 * Deliberately a separate screen from Manage Email rather than a filter on it.
 * That screen is the administrator's switchboard - what an account *may* be
 * sent - and this is a list of people who asked us to stop, which is not a
 * setting to be adjusted so much as an instruction to be honoured. Keeping them
 * apart means nobody clears an opt-out while tidying up permissions.
 *
 * @var array|object $users  `users` rows with u_unsubscribed_at set
 * @var bool         $ready  false until the migration has been run
 */

/** The account's kind, in words. Same wording as the Manage Email list. */
$typeLabel = static function ($user) use ($usersubtype) {
    $usertype = (int) $user->u_usertype;

    if ($usertype === 0) {
        return 'Administrator';
    }

    if ($usertype === 2) {
        $sub = $usersubtype[$user->u_usersubtype] ?? '';

        return 'Applicant' . ($sub !== '' ? ' - ' . $sub : '');
    }

    return 'Employer - ' . employerKindName($user);
};
?>
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Unsubscribed</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/' . $pageinfo['link']);?>">Manage <?php echo $pageinfo['title']; ?></a></li>
              <li class="breadcrumb-item active">Unsubscribed</li>
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
                <h3 class="card-title">Accounts that opted out from the link in an e-mail</h3>
                <div class="card-tools">
                  <a href="<?php echo base_url('sadmin/' . $pageinfo['link']);?>" class="btn btn-sm btn-default">
                    <i class="fas fa-arrow-left"></i> Back to Manage <?php echo $pageinfo['title']; ?>
                  </a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

                <?php if (! $ready) { ?>
                  <div class="alert alert-warning mb-0">
                    The unsubscribe columns are not in the database yet. Run <code>php spark migrate</code>
                    on this server, then reload this page.
                  </div>
                <?php } else { ?>

                <p class="text-muted">
                  These accounts are sent none of the optional e-mails, whatever the boxes on Manage Email say
                  and whoever is ticked as a recipient on a shift. They are still sent a password reset they ask
                  for, and notice that a shift they were booked on has been cancelled.
                </p>

                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Unsubscribed On</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
				  if($users){
					  foreach($users as $record){
						  $when = strtotime((string) $record->u_unsubscribed_at);
						  ?>
						  <tr>
							<td><?php echo (int) $record->u_id;?></td>
							<td><?php echo esc(trim($record->u_fname . ' ' . $record->u_lname));?></td>
							<td><?php echo esc($record->u_email);?></td>
							<td><?php echo esc($typeLabel($record));?></td>
							<td><?php echo $when ? esc(date('d M Y, H:i', $when)) : '-';?></td>
							<td><?php echo $status[$record->u_status] ?? '-';?></td>
							<td>
							  <?php /* A post, not a link: this changes something, and a link that
							           changes something is one a crawler in the admin panel can follow. */ ?>
							  <form action="" method="post" class="d-inline"
							        onsubmit="return confirm('Send e-mails to this account again? They asked to stop.');">
								<input type="hidden" name="u_id" value="<?php echo (int) $record->u_id;?>">
								<button type="submit" name="resubscribe" value="1" class="btn btn-warning">
								  <i class="fas fa-undo"></i> Re-subscribe
								</button>
							  </form>
							</td>
						  </tr>
						  <?php
					  }
				  } else {
					  ?>
					  <tr><td colspan="7" class="text-center text-muted">Nobody has unsubscribed.</td></tr>
					  <?php
				  }
				  ?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Unsubscribed On</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </tfoot>
                </table>

                <?php } ?>

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
