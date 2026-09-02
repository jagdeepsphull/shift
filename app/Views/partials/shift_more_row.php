<?php
/**
 * One extra date-and-hours row on the admin's Add Shift form: what "Add More"
 * puts under the first row, and what a save that comes back rejected puts back.
 *
 * Each of these rows is one more shift - the same store, rate, software and
 * status as the first row, on another day or at other hours. The controller
 * writes one post_job row for each, so these fields are arrays, and are
 * deliberately not named for the columns (`p_dates`, `p_shift_time`): the form
 * is saved with cleanArray($this->input->post()) written straight onto
 * post_job, and anything named for a column lands in it as that column.
 *
 * Add only. The edit form works on one shift and has no rows to add.
 *
 * Rendered twice by postjobs/add.php: once for each row that came back from a
 * failed save, and once blank inside a <template> for the script to copy - see
 * partials/shift_more_rows_script.php.
 *
 * @var string $date the date as typed, dd-mm-yyyy, or ''
 * @var string $time the hours as typed, HH:mm - HH:mm, or ''
 */
?>
<div class="row" data-shift-more-row>
	<div class="col-sm-4">
		<div class="form-group">
			<label>Select Date</label>
			<input required type="text" class="form-control date" name="p_more_dates[]" placeholder="Pick date" value="<?php echo esc($date ?? ''); ?>">
		</div>
	</div>
	<div class="col-sm-4">
		<div class="form-group">
			<label>Shift Time</label>
			<input required type="text" class="form-control timePicker" name="p_more_shift_time[]" placeholder="Shift Time" value="<?php echo esc($time ?? ''); ?>">
		</div>
	</div>
	<div class="col-sm-4">
		<div class="form-group">
			<?php /* A blank label, so the button lines up with the boxes
			   beside it rather than with their labels. */ ?>
			<label class="d-block">&nbsp;</label>
			<?php /* type="button": a bare <button> in a form is a submit. */ ?>
			<button type="button" class="btn btn-outline-danger" data-shift-more-remove title="Remove this row" aria-label="Remove this row">&times;</button>
		</div>
	</div>
</div>
