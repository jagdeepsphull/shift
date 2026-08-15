<?php
/**
 * One user's e-mail permissions: a checkbox per e-mail this account can be
 * sent, which is not the whole `emailTypes` list - the controller narrows it to
 * the ones their side of the site ever receives, through `emailTypesFor()`.
 *
 * Ticked means "send it". What is stored is the inverse - the blocked codes -
 * so the tick states are worked out here from the block list, through the same
 * checkbox_grid partial the shift and store forms use.
 */
$blocked = array_map('intval', array_filter(explode(',', (string) ($user['u_email_blocked'] ?? '')), 'strlen'));

// The grid wants rows shaped like master-table records.
$typeItems = [];
$allowed   = [];

foreach ($emailTypes as $typeCode => $typeDef) {
    $typeItems[] = (object) ['code' => (int) $typeCode, 'label' => $typeDef['label']];

    if (! in_array((int) $typeCode, $blocked, true)) {
        $allowed[] = (int) $typeCode;
    }
}
?>
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Email Permissions</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/dashboard');?>">Home</a></li>
			  <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/' . $pageinfo['link']);?>">Manage <?php echo $pageinfo['title']; ?></a></li>
              <li class="breadcrumb-item active">Permissions</li>
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
          <div class="col-md-8">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><?php echo esc(trim($user['u_fname'] . ' ' . $user['u_lname']));?> &lt;<?php echo esc($user['u_email']);?>&gt;</h3>
              </div>
              <!-- form start -->
              <form method="post" action="<?php echo base_url('sadmin/' . $pageinfo['link'] . '/permissions/' . (int) $user['u_id']);?>">
                <div class="card-body">
                  <p class="text-muted">Ticked e-mails are sent to this user; anything unticked is withheld. Password reset e-mails are always sent - without one, a locked-out account could never be recovered. A booking that has been cancelled is always sent too: the applicant has already been told the shift is theirs.</p>

                  <?php if ($typeItems === []) { ?>
                  <?php /* An administrator: the messages here are about
                     registering, being approved, posting a shift and being
                     booked on one, and an administrator is the recipient of
                     none of them. */ ?>
                  <div class="alert alert-info mb-0">This account is not a recipient of any of these e-mails - they go to employers and applicants.</div>
                  <?php } else { ?>
                  <div class="row">
                    <div class="col-sm-8">
                      <div class="form-group">
                        <?= view('partials/checkbox_grid', [
                            'name'     => 'email_allowed',
                            'label'    => 'E-mails this user receives',
                            'items'    => $typeItems,
                            'idKey'    => 'code',
                            'labelKey' => 'label',
                            'selected' => $allowed,
                            'required' => false,
                        ]) ?>
                      </div>
                    </div>
                  </div>
                  <?php } ?>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                  <?php /* Nothing to save when nothing was offered. */ ?>
                  <?php if ($typeItems !== []) { ?>
                  <button type="submit" name="savedata" value="1" class="btn btn-primary">Save Permissions</button>
                  <?php } ?>
                  <a class="btn btn-default" href="<?php echo base_url('sadmin/' . $pageinfo['link']);?>">Back</a>
                </div>
              </form>
            </div>
            <!-- /.card -->
          </div>
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
