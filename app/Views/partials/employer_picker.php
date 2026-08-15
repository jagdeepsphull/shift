<?php

/**
 * User Type + Choose Employer, the pair at the top of both admin shift forms.
 *
 * The two forms held identical copies of the employer select; they share this
 * so the pair cannot drift, the way `checkbox_grid` is shared.
 *
 * The type picker narrows the employer list to one `employerKinds` slug. Every
 * employer is already in the page, so the narrowing is done in the browser
 * (see the block in admin/footer.php) rather than over another request.
 *
 * Each employer option carries `data-kind` - '' for the pre-B4 accounts that
 * carry role 0 and belong to no kind. Those stay reachable through "All
 * Employers", which is why that entry is the default here, exactly as it is in
 * the sidebar.
 *
 * @var array  $agencies      active employer rows
 * @var array  $employerKinds config map, code => ['label' => ...]
 * @var mixed  $u_id          currently chosen employer
 */
?>
<?= view('partials/employer_kind_select', [
	'employerKinds' => $employerKinds ?? [],
	'filters'       => '#u_id',
	'id'            => 'u_emp_kind',
	'col'           => 'col-sm-4',
]) ?>
<div class="col-sm-4">
	<!-- text input -->
	<div class="form-group">
		<label>Choose Employer</label>
		<select class="form-control " name="u_id" id="u_id" required>
			<option value="">-- Select Employer -- </option>
			<?php if($agencies){?>
			  <?php foreach($agencies as $agency){?>
			  <option value="<?php echo $agency->u_id;?>" data-kind="<?php echo employerKindCode($agency);?>" <?php echo ($u_id==$agency->u_id)?"selected":""; ?> >
				  <?php echo $agency->u_comp_name;?></option>
			  <?php } ?>
			<?php } ?>
		</select>
	</div>
</div>
