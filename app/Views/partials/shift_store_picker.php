<?php

/**
 * Store (Location), the question the top of both admin shift forms asks - now
 * as two dropdowns rather than one.
 *
 * It used to ask three: a User Type to narrow by, then the employer, then that
 * employer's store. But a store belongs to exactly one employer, so naming the
 * store names the employer with it - the controller reads `store.u_id` and
 * writes it to `post_job.u_id`. The other two dropdowns only asked the
 * administrator to say the same thing twice, and let them say it twice
 * differently.
 *
 * That left one dropdown holding every store on the site, grouped under bold
 * headings for the employer that owns them. It is correct but long: on a site
 * with a few chains the administrator scrolls past every other company to find
 * a branch. So the heading is now a dropdown of its own - pick the group on the
 * left, and the one on the right holds only that group's stores.
 *
 * Two things about the split are load-bearing:
 *
 *   - The group dropdown has NO `name`, so it is never posted. The shift form
 *     saves with `cleanArray($this->input->post())` written straight onto
 *     `post_job`, so any extra named field on this form is an unknown column
 *     and a SQL error. The group is a way of finding the store, not an answer
 *     the form collects - the store still names the employer by itself.
 *   - Both dropdowns are rendered already narrowed to the chosen store's group.
 *     Nothing has to run on load to put the form in the right state, which is
 *     what keeps the edit form's saved tick-boxes alone (see admin/footer.php:
 *     the defaults are only copied when somebody changes the store).
 *
 * Under the store field, whoever manages the chosen store is named in bold -
 * the person the shift will actually be arranged with, which is the one thing
 * the picker itself does not say. The store name is not repeated: it is the
 * option showing in the dropdown. Most stores have no manager account, and
 * those name the owner instead, so the line always says who to expect to deal
 * with. Both names travel on the option, so it follows the picker as it changes
 * without another request - see admin/footer.php.
 *
 * On the edit form the shift's own store is in the list even if it has since
 * been deactivated - the alternative is a form that quietly moves the shift
 * somewhere else the first time it is saved. Its group comes with it, for the
 * same reason.
 *
 * @var array  $shift_stores rows from Sadmin::shiftStoreOptions()
 * @var mixed  $p_store_id   the store already chosen, if any
 * @var string $store_note   optional line under the field, in place of the
 *                           sentence this renders by default
 */
$store_note    = $store_note ?? '';
$chosen        = null;
$chosenGroupId = 0;

/** @var array<int, string> employer id => the name to show for them */
$groups = [];

/** @var array<int, array<int, array<string, mixed>>> employer id => their stores */
$byGroup = [];

foreach (($shift_stores ?? []) as $store) {
    $ownerId = (int) $store->u_id;

    // Trimmed: several company names were typed with a trailing space, and the
    // line under the field closes a bracket right after it.
    $owner = trim((string) $store->u_comp_name) !== ''
        ? trim((string) $store->u_comp_name)
        : 'Employer #' . $ownerId;

    $groups[$ownerId] = $owner;

    $byGroup[$ownerId][] = [
        'id'      => (int) $store->s_id,
        'label'   => $store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : ''),
        'owner'   => $owner,
        'manager' => trim((string) ($store->managers ?? '')),
    ];

    if ((string) ($p_store_id ?? '') === (string) $store->s_id) {
        $chosen        = $store;
        $chosenGroupId = $ownerId;
    }
}

// Only the chosen group's stores are offered until somebody picks a group -
// the dropdown starts empty rather than holding all of them.
$offered = $byGroup[$chosenGroupId] ?? [];
?>
<div class="col-sm-4">
	<div class="form-group">
		<label>Employer (Group)</label>
		<?php /* No name: see the note at the top of this file - anything named
		   on this form is written to `post_job` as a column. */ ?>
		<select class="form-control" id="p_store_group" required>
			<option value="">-- Select Group --</option>
			<?php foreach ($groups as $ownerId => $ownerName) { ?>
				<option value="<?php echo (int) $ownerId; ?>"
					<?php echo $chosenGroupId === (int) $ownerId ? 'selected' : ''; ?>>
					<?php echo esc($ownerName); ?></option>
			<?php } ?>
		</select>
		<small class="form-text text-muted">
			Pick the company first; the store list beside this holds only theirs.
		</small>
	</div>
</div>
<div class="col-sm-4">
	<div class="form-group">
		<label>Store (Location)</label>
		<select class="form-control" name="p_store_id" id="p_store_id" required>
			<option value="">-- Select Store --</option>
			<?php foreach ($offered as $option) { ?>
				<option value="<?php echo (int) $option['id']; ?>"
					data-owner="<?php echo esc($option['owner'], 'attr'); ?>"
					data-manager="<?php echo esc($option['manager'], 'attr'); ?>"
					<?php echo ((string) ($p_store_id ?? '') === (string) $option['id']) ? 'selected' : ''; ?>>
					<?php echo esc($option['label']); ?></option>
			<?php } ?>
		</select>
		<small class="form-text text-muted" id="store_owner_note">
			<?php if ($store_note !== '') { ?>
				<?php echo esc($store_note); ?>
			<?php } elseif ($chosen === null) { ?>
				The shift is posted for the employer that owns this store.
			<?php } elseif (trim((string) ($chosen->managers ?? '')) !== '') { ?>
				Managed by (<strong><?php echo esc(trim((string) $chosen->managers)); ?></strong>)
			<?php } else { ?>
				<?php /* No manager account on this store, which is the usual case -
				   the owner runs it themselves. */ ?>
				Owned by (<strong><?php echo esc(trim((string) $chosen->u_comp_name) !== '' ? trim((string) $chosen->u_comp_name) : 'Employer #' . (int) $chosen->u_id); ?></strong>)
			<?php } ?>
		</small>
	</div>
</div>

<?php /* Every group's stores, for the script in admin/footer.php that refills
   the second dropdown. Data, not code: jQuery is loaded at the foot of the
   page, so the behaviour lives down there with the rest of the picker's.
   JSON_HEX_TAG is what stops a store named with a `</script>` in it closing
   this block early. */ ?>
<script type="application/json" id="shift_store_options">
	<?php echo json_encode($byGroup, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>
</script>
