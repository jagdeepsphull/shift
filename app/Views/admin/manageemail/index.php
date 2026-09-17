<?php
/**
 * Manage Email: every account on the site, each with a button to its own
 * e-mail permissions page.
 *
 * One list for all three account types deliberately - the feature is about
 * recipients, and an administrator, an employer and an applicant are all
 * recipients. The User Type column is what tells them apart.
 */

/**
 * The account's kind, in words.
 *
 * The applicant's own type comes from `shift_for` through getShiftForName(),
 * the same lookup every other screen uses. It used to read a hard-coded list
 * in AppSettings that had drifted from the table, printing labels - a
 * "Personal Support Worker" among them - that exist nowhere else on the site.
 */
$typeLabel = static function ($user) {
    $usertype = (int) $user->u_usertype;

    if ($usertype === 0) {
        return 'Administrator';
    }

    if ($usertype === 2) {
        $sub = trim((string) getShiftForName($user->u_usersubtype));

        return 'Applicant' . ($sub !== '' ? ' - ' . $sub : '');
    }

    return 'Employer - ' . employerKindName($user);
};

/**
 * How much of the mail this user still receives.
 *
 * Counted against the e-mails this account can actually be sent, not the whole
 * config: an applicant blocked from the two employer-only types is not "2
 * blocked" - neither was ever going to arrive. An administrator is a recipient
 * of none of them, so there is nothing to summarise.
 */
$permSummary = static function ($user) {
    // The recipient's own opt-out is shown ahead of the boxes below, because it
    // overrides all of them. "All emails" beside somebody who unsubscribed from
    // their inbox would be a promise this screen keeps and the send sites do
    // not - and it is the reading that leads an administrator to go looking for
    // the bug in the mailer.
    if (userHasUnsubscribed($user)) {
        return '<span class="badge badge-dark">Unsubscribed</span>';
    }

    $offered = emailTypesFor($user);

    if ($offered === []) {
        return '<span class="text-muted">&mdash;</span>';
    }

    $blocked = array_intersect(
        array_map('intval', array_filter(explode(',', (string) ($user->u_email_blocked ?? '')), 'strlen')),
        array_keys($offered)
    );

    if ($blocked === []) {
        return '<span class="badge badge-success">All emails</span>';
    }

    if (count($blocked) >= count($offered)) {
        return '<span class="badge badge-danger">All blocked</span>';
    }

    return '<span class="badge badge-warning">' . count($blocked) . ' of ' . count($offered) . ' blocked</span>';
};
?>
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
              <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item active">Manage <?php echo $pageinfo['title']; ?></li>
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
                <h3 class="card-title">Who receives which e-mails</h3>
                <div class="card-tools">
                  <a href="<?php echo base_url('sadmin/' . $pageinfo['link'] . '/unsubscribed');?>" class="btn btn-sm btn-default">
                    <i class="fas fa-ban"></i> Unsubscribed
                  </a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Email Permissions</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
				  if($users){
					  foreach($users as $record){
						  ?>
						  <tr>
							<td><?php echo (int) $record->u_id;?></td>
							<td><?php echo esc(trim($record->u_fname . ' ' . $record->u_lname));?></td>
							<td><?php echo esc($record->u_email);?></td>
							<td><?php echo esc($typeLabel($record));?></td>
							<td><?php echo $permSummary($record);?></td>
							<td><?php echo $status[$record->u_status] ?? '-';?></td>
							<td>
							<a href="<?php echo base_url('sadmin/' . $pageinfo['link'] . '/permissions/' . (int) $record->u_id);?>" class="btn btn-primary"><i class="fas fa-envelope-open-text"></i> Email Permissions</a>
							</td>
						  </tr>
						  <?php
					  }
				  }
				  ?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Email Permissions</th>
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
