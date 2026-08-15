<?php
/* New employers, then one tab per employer kind, in the order the sidebar
   lists them. The combined tab stays first and is not just the sum of the
   others: pre-B4 accounts carry role 0 and belong to no kind, so dropping it
   would hide them. */
$employerTabs = [
    '' => ['label' => 'New Employers', 'empty' => 'employers', 'rows' => $new_employers],
];

foreach (($employerKinds ?? []) as $kindCode => $kindDef) {
    $employerTabs[$kindDef['slug']] = [
        'label' => $kindDef['label'],
        'empty' => strtolower($kindDef['label']),
        'rows'  => $new_employers_by_kind[$kindCode] ?? [],
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
                      <thead><tr><th>Applicant</th><th>Shift</th><th>Employer</th><th>Status</th><th>Received</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_applications as $r) { ?>
                        <tr>
                          <td><?php echo esc($r->u_fname . ' ' . $r->u_lname); ?></td>
                          <td><?php echo esc($r->p_job_title); ?></td>
                          <td><?php echo esc($r->u_comp_name); ?></td>
                          <td><?php echo esc($application_approved[$r->sj_is_approved] ?? '-'); ?></td>
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
                      <thead><tr><th>Store</th><th>Contact</th><th>Email</th><th>Status</th><th>Registered</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($tab['rows'] as $r) { ?>
                        <tr>
                          <td><?php echo esc($r->u_comp_name); ?></td>
                          <td><?php echo esc($r->u_fname . ' ' . $r->u_lname); ?></td>
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
                      <thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Status</th><th>Registered</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_applicants as $r) { ?>
                        <tr>
                          <td><?php echo esc($r->u_fname . ' ' . $r->u_lname); ?></td>
                          <td><?php echo esc($usersubtype[$r->u_usersubtype] ?? '-'); ?></td>
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
                      <thead><tr><th>Shift</th><th>Store</th><th>Shift date</th><th>Status</th><th>Posted</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach ($new_shifts as $r) { ?>
                        <tr>
                          <td><?php echo esc($r->p_job_title); ?></td>
                          <td><?php echo esc($r->u_comp_name); ?></td>
                          <td><?php echo dateFormat($r->p_dates); ?></td>
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