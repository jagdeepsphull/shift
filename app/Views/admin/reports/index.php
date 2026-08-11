<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><?php echo $pageinfo['title']; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item active"><?php echo $pageinfo['title']; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Monthly figures</h3>
              </div>

              <div class="card-body">
                <form method="get" class="form-inline mb-3">
                  <label class="mr-2">From</label>
                  <input type="date" name="from" value="<?php echo esc($from, 'attr'); ?>" class="form-control mr-3">
                  <label class="mr-2">To</label>
                  <input type="date" name="to" value="<?php echo esc($to, 'attr'); ?>" class="form-control mr-3">
                  <button type="submit" class="btn btn-primary mr-2">Show</button>
                  <a class="btn btn-outline-secondary"
                     href="<?php echo base_url($adminpath.'/reports?export=csv&from='.urlencode($from).'&to='.urlencode($to)); ?>">Export CSV</a>
                </form>

                <div class="row mb-3">
                  <div class="col-md-4">
                    <div class="info-box">
                      <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Shifts booked</span>
                        <span class="info-box-number"><?php echo $totals['bookings']; ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="info-box">
                      <span class="info-box-icon bg-info"><i class="fas fa-store"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">New employers</span>
                        <span class="info-box-number"><?php echo $totals['employers']; ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="info-box">
                      <span class="info-box-icon bg-warning"><i class="fas fa-user-plus"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">New applicants</span>
                        <span class="info-box-number"><?php echo $totals['applicants']; ?></span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>Month</th>
                        <th class="text-right">Shifts booked</th>
                        <th class="text-right">New employers</th>
                        <th class="text-right">New applicants</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rows as $r) { ?>
                        <tr>
                          <td><?php echo esc($r['label']); ?></td>
                          <td class="text-right">
                            <?php if ($r['bookings']) { ?>
                              <a href="<?php echo base_url($adminpath.'/applications?filter=booked'); ?>"><?php echo $r['bookings']; ?></a>
                            <?php } else { echo '0'; } ?>
                          </td>
                          <td class="text-right">
                            <?php if ($r['employers']) { ?>
                              <a href="<?php echo base_url($adminpath.'/employer'); ?>"><?php echo $r['employers']; ?></a>
                            <?php } else { echo '0'; } ?>
                          </td>
                          <td class="text-right">
                            <?php if ($r['applicants']) { ?>
                              <a href="<?php echo base_url($adminpath.'/applicant'); ?>"><?php echo $r['applicants']; ?></a>
                            <?php } else { echo '0'; } ?>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                    <tfoot>
                      <tr class="font-weight-bold">
                        <td>Total</td>
                        <td class="text-right"><?php echo $totals['bookings']; ?></td>
                        <td class="text-right"><?php echo $totals['employers']; ?></td>
                        <td class="text-right"><?php echo $totals['applicants']; ?></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <p class="text-muted small mb-0">
                  Bookings are counted on the date the agency confirmed them. That date was not recorded before
                  6&nbsp;August&nbsp;2026, so earlier bookings are counted on the date the application arrived
                  instead &mdash; the closest date the records hold. New employers and applicants are counted on
                  their registration date.
                </p>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /.container-fluid -->
    </section>
  </div>
  <!-- /.content-wrapper -->
