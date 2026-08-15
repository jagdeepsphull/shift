<?php

/**
 * The "User Type" picker: narrows an employer <select> to one `employerKinds`
 * slug. Used ahead of the employer picker on the shift forms and the store
 * forms, so the two behave the same way.
 *
 * It carries no `name`. It only filters the list beside it, and posting it
 * would put a value on the record that nothing reads.
 *
 * The narrowing happens in the browser, over options already in the page - see
 * the block keyed on `data-filters` in admin/footer.php. Each employer option
 * has to carry `data-kind` for it to work.
 *
 * "All Employers" is the default and cannot be removed: an account registered
 * before the kinds existed carries role 0 and belongs to none of them, so that
 * entry is the only place it appears.
 *
 * @var array  $employerKinds config map, code => ['label' => ...]
 * @var string $filters       CSS selector of the employer select this narrows
 * @var string $id            id for this select
 * @var string $col           bootstrap column class for the wrapper
 */
$id  = $id ?? 'u_emp_kind';
$col = $col ?? 'col-sm-4';
?>
<div class="<?= esc($col, 'attr') ?>">
	<div class="form-group">
		<label for="<?= esc($id, 'attr') ?>">User Type</label>
		<select class="form-control" id="<?= esc($id, 'attr') ?>" data-filters="<?= esc($filters, 'attr') ?>">
			<option value="">-- All Employers --</option>
			<?php foreach (($employerKinds ?? []) as $kindCode => $kindDef) { ?>
			<option value="<?= (int) $kindCode ?>"><?= esc($kindDef['label']) ?></option>
			<?php } ?>
		</select>
	</div>
</div>
