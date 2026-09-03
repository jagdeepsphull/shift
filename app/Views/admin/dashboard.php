<?php
/* New employers, then one tab per employer kind, in the order the sidebar
   lists them. The combined tab stays first and is not just the sum of the
   others: pre-B4 accounts carry role 0 and belong to no kind, so dropping it
   would hide them. */
$employerTabs = [
    '' => [
        'label' => 'New Employers',
        'empty' => 'employers',
        'rows'  => $new_employers,
        /* What the person on a row is called, and whether the kind needs a
           column saying so. A row on the combined tab may be either kind, so
           its columns are named for what the two have in common and the kind is
           spelled out beside them. A per-kind tab already knows: it names the
           columns after its own kind - Owner Name, Manager Phone - and drops
           the column, which would have read the same on every row. */
        'noun'      => 'Employer',
        'showKind'  => true,
        'showStore' => false,
    ],
];

foreach (($employerKinds ?? []) as $kindCode => $kindDef) {
    $employerTabs[$kindDef['slug']] = [
        'label'    => $kindDef['label'],
        'empty'    => strtolower($kindDef['label']),
        'rows'     => $new_employers_by_kind[$kindCode] ?? [],
        // 'short' is the singular the employer listing already shows a row by,
        // so the tabs cannot start calling an Owner something else.
        'noun'     => $kindDef['short'],
        'showKind' => false,
        /* Which branch, for the kind that runs one. `picksStore` is the same
           flag the two employer forms read to decide whether to offer a store
           to choose, so the column appears exactly where there is one store to
           name - an owner's group holds many, and no one of them belongs in a
           column of their row. */
        'showStore' => employerKindRole($kindCode)['picksStore'],
    ];
}
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo $employer_users; ?></h3>

                <p>Employer</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/employer');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?php echo $applicant_users; ?></h3>

                <p>Applicant</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/applicant');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
			<div class="small-box bg-danger">
              <div class="inner">
                <h3><?php echo count($jobs); ?></h3>

                <p>Total Shifts</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/postjobs');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
			<div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo count($new_jobs); ?></h3>

                <p>New Shifts</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/postjobs?filter=new');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
			<!-- small box -->			
			<div class="small-box bg-primary">
              <div class="inner">
                <h3><?php echo count($applicationslist); ?></h3>

                <p>Total Applications</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/applications');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
			<!-- small box -->			
			<div class="small-box bg-dark">
              <div class="inner">
                <h3><?php echo count($booked_applications); ?></h3>

                <p>Booked Applications</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url($adminpath.'/applications?filter=booked');?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        </div>
        <!-- /.row -->
        <!-- Main row -->
        <div class="row">
          <div class="col-12">
            <div class="card card-primary card-outline card-tabs">
              <div class="card-header p-0 pt-1 border-bottom-0">
                <div class="d-flex justify-content-between align-items-center pr-3">
                  <ul class="nav nav-tabs" id="new-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active" id="tab-apps" data-toggle="pill" href="#pane-apps" role="tab">
                        New Applications <span class="badge badge-primary"><?php echo count($new_applications); ?></span>
                      </a>
                    </li>
                    <?php foreach ($employerTabs as $slug => $tab) { ?>
                    <li class="nav-item">
                      <a class="nav-link" id="tab-emp<?php echo $slug ? '-' . $slug : ''; ?>" data-toggle="pill" href="#pane-emp<?php echo $slug ? '-' . $slug : ''; ?>" role="tab">
                        <?php echo esc($tab['label']); ?> <span class="badge badge-primary"><?php echo count($tab['rows']); ?></span>
                      </a>
                    </li>
                    <?php } ?>
                    <li class="nav-item">
                      <a class="nav-link" id="tab-app" data-toggle="pill" href="#pane-app" role="tab">
                        New Applicants <span class="badge badge-primary"><?php echo count($new_applicants); ?></span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="tab-shifts" data-toggle="pill" href="#pane-shifts" role="tab">
                        New Shifts <span class="badge badge-primary"><?php echo count($new_shifts); ?></span>
                      </a>
                    </li>
                  </ul>
                  <form method="get" class="form-inline">
                    <label class="mr-2 mb-0 text-muted small">Last</label>
                    <select name="new_days" class="form-control form-control-sm" onchange="this.form.submit()">
                      <?php foreach ([7 => '7 days', 14 => '14 days', 30 => '30 days', 90 => '90 days'] as $d => $lbl) { ?>
                        <option value="<?php echo $d; ?>" <?php echo ($new_days == $d) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                      <?php } ?>
                    </select>
                  </form>
                </div>
              </div>
              <div class="card-body">
                <div class="tab-content" id="new-tabs-content">

                  <div class="tab-pane fade show active" id="pane-apps" role="tabpanel">
                    <?php if (!$new_applications) { ?>
                      <p class="text-muted mb-0">No new applications in the last <?php echo $new_days; ?> days.</p>
                    <?php } else { ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                      <thead><tr><th>Shift ID</th><th>Store Address</th><th>Applicant</th><th>Lic. No.</th><th>Applicant type</th><th>Shift Date</th><th>Shift Time</th><th>Shift Status</th><th>Received</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_applications as $r) {
                        /* The branch the shift is at, which for a multi-store
                           employer is not the address on their login.
                           `employer_id` rather than `u_id`: the row carries the
                           applicant's `u_id`, and shiftStore() wants the
                           shift's owner. */
                        $storeAddress = storeAddressLines(shiftStore((object) [
                            'p_store_id' => $r->p_store_id,
                            'u_id'       => $r->employer_id,
                        ]));

                        $applicantName = trim($r->u_fname . ' ' . $r->u_lname);
                        $applicantLic  = trim((string) $r->u_licence_no);

                        /* What the applicant registered as, falling back to the
                           type the shift asked for on an account from before
                           the field was filled. The two agree in the ordinary
                           case, and where they do not it is the applicant's own
                           that says who is being booked. */
                        $applicantType = getShiftForName($r->u_usersubtype) ?: getShiftForName($r->p_shift_for);
                      ?>
                        <tr>
                          <td><?php echo esc($r->p_job_title); ?></td>
                          <td><?php if ($storeAddress) { foreach ($storeAddress as $line) { ?><span class="d-block"><?php echo esc($line); ?></span><?php } } else { echo '-'; } ?></td>
                          <td><?php echo ($applicantName !== '') ? esc($applicantName) : '-'; ?></td>
                          <td><?php echo ($applicantLic !== '') ? esc($applicantLic) : '-'; ?></td>
                          <td><?php echo ($applicantType !== '') ? esc($applicantType) : '-'; ?></td>
                          <td><?php echo dateFormat($r->p_dates); ?></td>
                          <td><?php echo esc($r->p_shift_time); ?></td>
                          <td><?php echo esc($approved[$r->p_approved] ?? '-'); ?></td>
                          <td><?php echo dateFormat($r->created); ?></td>
                          <td class="text-right"><a class="btn btn-xs btn-primary" href="<?php echo base_url($adminpath . '/applications/view/' . $r->sj_id); ?>">Open</a></td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table></div>
                    <?php } ?>
                  </div>

                  <?php foreach ($employerTabs as $slug => $tab) { ?>
                  <div class="tab-pane fade" id="pane-emp<?php echo $slug ? '-' . $slug : ''; ?>" role="tabpanel">
                    <?php if (!$tab['rows']) { ?>
                      <p class="text-muted mb-0">No new <?php echo esc($tab['empty']); ?> in the last <?php echo $new_days; ?> days.</p>
                    <?php } else { ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                      <?php /* The group leads whatever the tab is: it is what an
                         account is looked up by, and it stays "Employer Group"
                         even on the Owners tab - the group is the group, and an
                         owner is the account that holds it rather than
                         something the column is about. */ ?>
                      <thead><tr>
                        <th>Employer Group</th>
                        <?php if ($tab['showKind']) { ?><th>Employer Type</th><?php } ?>
                        <?php if ($tab['showStore']) { ?><th>Store Name</th><?php } ?>
                        <th><?php echo esc($tab['noun']); ?> Name</th>
                        <th><?php echo esc($tab['noun']); ?> Phone</th>
                        <th><?php echo esc($tab['noun']); ?> Email</th>
                        <th>Status</th>
                        <th>Registered on</th>
                        <th></th>
                      </tr></thead>
                      <tbody>
                      <?php foreach ($tab['rows'] as $r) {
                        $employerGroup = trim((string) $r->group_name);
                        $employerName  = trim($r->u_fname . ' ' . $r->u_lname);
                        $employerPhone = trim((string) $r->u_phone);

                        /* The branch as the store record has it now, falling
                           back to the copy on the manager's own row for one
                           whose store has since been removed. */
                        $storeName = trim((string) $r->store_name);

                        if ($storeName === '') {
                            $storeName = trim((string) $r->u_comp_name);
                        }
                      ?>
                        <tr>
                          <td><?php echo ($employerGroup !== '') ? esc($employerGroup) : '-'; ?></td>
                          <?php if ($tab['showKind']) { ?><td><?php echo esc(employerKindName($r)); ?></td><?php } ?>
                          <?php if ($tab['showStore']) { ?><td><?php echo ($storeName !== '') ? esc($storeName) : '-'; ?></td><?php } ?>
                          <td><?php echo ($employerName !== '') ? esc($employerName) : '-'; ?></td>
                          <td><?php echo ($employerPhone !== '') ? esc($employerPhone) : '-'; ?></td>
                          <td><?php echo esc($r->u_email); ?></td>
                          <td><?php echo $r->u_status ? 'Active' : '<span class="text-warning">Pending</span>'; ?></td>
                          <td><?php echo dateFormat($r->created); ?></td>
                          <?php /* Carrying the kind through means Save comes back
                             to this kind's list rather than All Employers, the
                             same way the sidebar lists link. */ ?>
                          <td class="text-right"><a class="btn btn-xs btn-primary" href="<?php echo base_url($adminpath . '/employer/edit/' . $r->u_id) . ($slug ? '?kind=' . $slug : ''); ?>">Open</a></td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table></div>
                    <?php } ?>
                  </div>
                  <?php } ?>

                  <div class="tab-pane fade" id="pane-app" role="tabpanel">
                    <?php if (!$new_applicants) { ?>
                      <p class="text-muted mb-0">No new applicants in the last <?php echo $new_days; ?> days.</p>
                    <?php } else { ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                      <thead><tr><th>Applicant Name</th><th>Applicant type</th><th>Lic. No.</th><th>Applicant Phone</th><th>Applicant Email</th><th>Status</th><th>Registered on</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_applicants as $r) {
                        $applicantName = trim($r->u_fname . ' ' . $r->u_lname);
                        $applicantLic  = trim((string) $r->u_licence_no);
                        $applicantPhone = trim((string) $r->u_phone);

                        /* Through `shift_for`, the way the applicant listing and
                           the edit screen read the same column. It is the table
                           the registration dropdown is built from, so it is what
                           `u_usersubtype` holds; the `usersubtype` config array
                           is an older list that no longer agrees with it. */
                        $applicantType = getShiftForName($r->u_usersubtype);
                      ?>
                        <tr>
                          <td><?php echo ($applicantName !== '') ? esc($applicantName) : '-'; ?></td>
                          <td><?php echo ($applicantType !== '') ? esc($applicantType) : '-'; ?></td>
                          <td><?php echo ($applicantLic !== '') ? esc($applicantLic) : '-'; ?></td>
                          <td><?php echo ($applicantPhone !== '') ? esc($applicantPhone) : '-'; ?></td>
                          <td><?php echo esc($r->u_email); ?></td>
                          <td><?php echo $r->u_status ? 'Active' : '<span class="text-warning">Pending</span>'; ?></td>
                          <td><?php echo dateFormat($r->created); ?></td>
                          <td class="text-right"><a class="btn btn-xs btn-primary" href="<?php echo base_url($adminpath . '/applicant/edit/' . $r->u_id); ?>">Open</a></td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table></div>
                    <?php } ?>
                  </div>

                  <div class="tab-pane fade" id="pane-shifts" role="tabpanel">
                    <?php if (!$new_shifts) { ?>
                      <p class="text-muted mb-0">No new shifts in the last <?php echo $new_days; ?> days.</p>
                    <?php } else { ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                      <thead><tr><th>Shift ID</th><th>Store Address</th><th>Store Phone</th><th>Shift Booked for</th><th>Lic. No.</th><th>Applicant type</th><th>Shift Date</th><th>Shift Time</th><th>Shift Status</th><th>Posted Date</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_shifts as $r) {
                        // The branch the shift is at, and the line at its
                        // counter. A shift from before the stores existed falls
                        // back to the employer's login, which is where its
                        // address and number have always come from.
                        $store        = shiftStore($r);
                        $storeAddress = storeAddressLines($store);
                        $storePhone   = trim((string) ($store->s_phone ?? ''));

                        // Who is working it, looked up for the whole tab at
                        // once. Null while nobody is on it, which is every
                        // shift still on the board.
                        $booked     = $new_shift_bookings[(int) $r->p_id] ?? null;
                        $bookedName = $booked ? trim($booked->u_fname . ' ' . $booked->u_lname) : '';
                        $bookedLic  = $booked ? trim((string) $booked->u_licence_no) : '';

                        /* The type of the person on the shift once there is
                           one, and until then the type the shift is asking for
                           - the same answer either way in the ordinary case,
                           and the truthful one when an administrator has put
                           somebody of another type on it himself. */
                        $applicantType = $booked
                            ? getShiftForName($booked->u_usersubtype)
                            : getShiftForName($r->p_shift_for);
                      ?>
                        <tr>
                          <td><?php echo esc($r->p_job_title); ?></td>
                          <td><?php if ($storeAddress) { foreach ($storeAddress as $line) { ?><span class="d-block"><?php echo esc($line); ?></span><?php } } else { echo '-'; } ?></td>
                          <td><?php echo ($storePhone !== '') ? esc($storePhone) : '-'; ?></td>
                          <td><?php echo ($bookedName !== '') ? esc($bookedName) : '-'; ?></td>
                          <td><?php echo ($bookedLic !== '') ? esc($bookedLic) : '-'; ?></td>
                          <td><?php echo ($applicantType !== '') ? esc($applicantType) : '-'; ?></td>
                          <td><?php echo dateFormat($r->p_dates); ?></td>
                          <td><?php echo esc($r->p_shift_time); ?></td>
                          <td><?php echo esc($approved[$r->p_approved] ?? '-'); ?></td>
                          <td><?php echo dateFormat($r->created); ?></td>
                          <td class="text-right"><a class="btn btn-xs btn-primary" href="<?php echo base_url($adminpath . '/postjobs/edit/' . $r->p_id); ?>">Open</a></td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table></div>
                    <?php } ?>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /.row (main row) -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->