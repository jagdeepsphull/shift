<?php

/**
 * Post New Shift, the employer's own form.
 *
 * Laid out as three numbered sections in the order somebody actually thinks
 * about a shift - which branch and what for, when it runs and what it pays,
 * then what the person walking in needs to know. It was one long run of fields
 * before, with the columns set to `col-sm-2`: a twelfth-of-the-row each, so on
 * a tablet every box was squeezed to about 90px. The grid below is
 * `col-12 col-md-6 col-lg-4`, which is one per row on a phone, two on a tablet
 * and three on a desktop.
 *
 * Presentation lives in partials/shift_form_styles.php, scoped to
 * `.ps-shift-page`. The names and ids are load-bearing and unchanged: the
 * footer script hangs the store defaults off `#p_store_id`, the pickers bind to
 * `.date` and `.timePicker`, and `#p_jobinfo` has to stay inside a `.form-group`
 * that carries its label.
 */
?>
<?= view('partials/shift_form_styles') ?>

<!-- Content Wrap -->
<div class="col-lg-9 col-md-8 ps-shift-page">
	<div class="dashboard-body">
		<div class="dashboard-caption">

			<div class="dashboard-caption-header">
				<div>
					<h4><i class="lni-briefcase"></i>Post New Shift</h4>
					<p class="ps-page-sub">Tell us where the shift is, when it runs and what it needs. Applicants see it as soon as it is approved.</p>
				</div>
			</div>

			<?php echo session()->getFlashdata('error_msg'); ?>

			<form name="post-job" action="" method="post">
				<div class="dashboard-caption-wrap">

					<?php /* A manager registers without a location, so their first
					   visit here has nothing to post a shift against. */ ?>
					<?php if (empty($stores)) { ?>
						<div class="alert alert-warning">
							You have no stores yet. <a href="<?php echo base_url('employer/add_store'); ?>">Add a store</a>
							before posting a shift.
						</div>
					<?php } ?>

					<!-- 1 ------------------------------------------------ where -->
					<div class="card ps-card">
						<div class="ps-card-header">
							<span class="ps-step">1</span>
							<div>
								<h5>Where and what</h5>
								<p>The branch this shift is at, and the role you need covered.</p>
							</div>
						</div>
						<div class="ps-card-body">
							<div class="row">
								<div class="col-12 col-md-6 col-lg-4">
									<div class="form-group">
										<label>Store (Location)<span class="ps-req">*</span></label>
										<?php /* The id is what the footer script hangs the shift
										   defaults off: choosing a store ticks what that store
										   normally offers. */ ?>
										<select required class="form-control" name="p_store_id" id="p_store_id">
											<option value="">-- Choose Store --</option>
											<?php if ($stores) {
											    foreach ($stores as $store) { ?>
													<option value="<?php echo $store->s_id ?>" <?php echo ($p_store_id == $store->s_id) ? 'selected' : ''; ?>><?php echo esc($store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : '')) ?></option>
											<?php }
											} ?>
										</select>
										<small class="ps-hint">Choosing a store ticks what it usually offers, below.</small>
									</div>
								</div>
								<div class="col-12 col-md-6 col-lg-4">
									<div class="form-group">
										<label>Shift Requested For<span class="ps-req">*</span></label>
										<select required class="form-control" name="p_shift_for">
											<option value="" selected>-- Choose Shift Requested For --</option>
											<?php if ($shift_for) {
											    foreach ($shift_for as $shifts) { ?>
													<option value="<?php echo $shifts->sf_id ?>" <?php echo ($p_shift_for == $shifts->sf_id) ? 'selected' : ''; ?>><?php echo $shifts->sf_name ?></option>
											<?php }
											} ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- 2 ------------------------------------------- when and pay -->
					<?php
					    /**
					     * What the shift pays is the group's decision, not the branch's, so a
					     * manager does not see the rate here - `can_set_rate` is resolved in
					     * Employer::setup(). The field is left out of the markup rather than
					     * hidden with CSS: it carries `required`, and a required control the
					     * browser cannot show is a form that refuses to submit and says why
					     * only in the console.
					     *
					     * Two fields rather than three, so they split the row evenly.
					     */
					    $rateColumn = $can_set_rate ? 'col-12 col-md-6 col-lg-4' : 'col-12 col-md-6';
					?>
					<div class="card ps-card">
						<div class="ps-card-header">
							<span class="ps-step">2</span>
							<div>
								<h5><?php echo $can_set_rate ? 'When and what it pays' : 'When it runs'; ?></h5>
								<p><?php echo $can_set_rate ? 'The date, the hours and the hourly rate you are offering.' : 'The date and the hours you need covered.'; ?></p>
							</div>
						</div>
						<div class="ps-card-body">
							<div class="row">
								<div class="<?php echo $rateColumn; ?>">
									<div class="form-group">
										<label>Shift Date<span class="ps-req">*</span></label>
										<div class="input-group">
											<input type="text" required class="form-control date" name="p_dates" placeholder="Pick date" value="<?php echo esc($p_dates); ?>">
											<div class="input-group-append">
												<span class="input-group-text"><i class="lni-calendar"></i></span>
											</div>
										</div>
									</div>
								</div>
								<div class="<?php echo $rateColumn; ?>">
									<div class="form-group">
										<label>Shift Time<span class="ps-req">*</span></label>
										<div class="input-group">
											<input required type="text" class="form-control timePicker" name="p_shift_time" placeholder="Shift Time" value="<?php echo esc($p_shift_time); ?>">
											<div class="input-group-append">
												<span class="input-group-text"><i class="lni-alarm-clock"></i></span>
											</div>
										</div>
									</div>
								</div>
								<?php if ($can_set_rate) { ?>
									<div class="col-12 col-md-6 col-lg-4">
										<div class="form-group">
											<label>Hourly Rate<span class="ps-req">*</span></label>
											<div class="input-group">
												<div class="input-group-prepend">
													<span class="input-group-text">$</span>
												</div>
												<input type="number" required min="10" max="200" class="form-control" name="p_hourly_rate" placeholder="Enter Hourly Rate" value="<?php echo esc($p_hourly_rate); ?>">
												<div class="input-group-append">
													<span class="input-group-text">/ hr</span>
												</div>
											</div>
											<small class="ps-hint">Between $10 and $200.</small>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>

					<!-- 3 ------------------------------------------ what it needs -->
					<div class="card ps-card">
						<div class="ps-card-header">
							<span class="ps-step">3</span>
							<div>
								<h5>What this shift needs</h5>
								<p>Ticked from the store's own defaults - change them for this shift only.</p>
							</div>
						</div>
						<div class="ps-card-body">
							<div class="row">
								<div class="col-12 col-md-6 col-lg-4">
									<?= view('partials/checkbox_grid', [
									    'name' => 'p_skills', 'label' => 'Software', 'items' => $software_skills,
									    'idKey' => 'ss_id', 'labelKey' => 'ss_name', 'selected' => $p_skills, 'required' => true,
									]) ?>
								</div>

								<div class="col-12 col-md-6 col-lg-4">
									<?php /* Optional, like Additional Details beside it. A shift that
									   offers nothing out of the ordinary is a real shift, and
									   requiring a tick here only taught people to pick one at
									   random to get past the guard. */ ?>
									<?= view('partials/checkbox_grid', [
									    'name' => 'p_services', 'label' => 'Details', 'items' => $store_service,
									    'idKey' => 'st_id', 'labelKey' => 'st_service_name', 'selected' => $p_services, 'required' => false,
									]) ?>
								</div>

								<div class="col-12 col-md-6 col-lg-4">
									<?php /* Not required, unlike the two beside it: this master starts
									   empty, and a required group with nothing to tick would make
									   posting a shift impossible until somebody filled it in. */ ?>
									<?= view('partials/checkbox_grid', [
									    'name' => 'p_additional_details', 'label' => 'Additional Details', 'items' => $additional_details,
									    'idKey' => 'ad_id', 'labelKey' => 'ad_name', 'selected' => $p_additional_details, 'required' => false,
									]) ?>
								</div>

								<div class="col-12">
									<div class="form-group mb-0">
										<?php /* Named apart from the "Additional Details" tick-box group
										   above it, which is a different field on a different table. */ ?>
										<label for="p_jobinfo">Additional details for agency</label>
										<textarea class="form-control summernote" name="p_jobinfo" id="p_jobinfo" rows="10"><?php echo $p_jobinfo; ?></textarea>
										<small class="ps-hint">Anything else worth knowing - parking, the door to use, who to ask for.</small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="ps-actions">
						<input type="hidden" id="base" value="<?php echo base_url(); ?>">
						<a class="ps-cancel" href="<?php echo base_url('employer/all_jobs'); ?>">Cancel</a>
						<input type="submit" class="btn btn-common" name="savepostjob" value="Post Shift" />
					</div>

				</div>
			</form>

		</div>
	</div>
</div>

</div>
</div>
</section>
<!-- General Detail End -->
