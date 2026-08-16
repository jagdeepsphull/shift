<?php

/**
 * Store (Location), the one question the top of both admin shift forms asks.
 *
 * It used to ask three: a User Type to narrow by, then the employer, then that
 * employer's store. But a store belongs to exactly one employer, so naming the
 * store names the employer with it - the controller reads `store.u_id` and
 * writes it to `post_job.u_id`. The other two dropdowns only asked the
 * administrator to say the same thing twice, and let them say it twice
 * differently.
 *
 * The list is every active store of every active employer, grouped under the
 * employer that owns it, so two branches of different chains that share a name
 * are still told apart. On the edit form the shift's own store is added to it
 * even if it has since been deactivated - the alternative is a form that
 * quietly moves the shift somewhere else the first time it is saved.
 *
 * Under the field, whoever manages the chosen store is named in bold - the
 * person the shift will actually be arranged with, which is the one thing the
 * picker itself does not say. The store name is not repeated: it is the option
 * showing in the dropdown. Most stores have no manager account, and those name
 * the owner instead, so the line always says who to expect to deal with. Both
 * names travel on the option, so it follows the picker as it changes without
 * another request - see admin/footer.php.
 *
 * @var array  $shift_stores rows from Sadmin::shiftStoreOptions()
 * @var mixed  $p_store_id   the store already chosen, if any
 * @var string $store_note   optional line under the field, in place of the
 *                           sentence this renders by default
 */
$store_note = $store_note ?? '';
$group      = null;
$chosen     = null;

foreach (($shift_stores ?? []) as $store) {
    if ((string) ($p_store_id ?? '') === (string) $store->s_id) {
        $chosen = $store;
        break;
    }
}
?>
<div class="col-sm-6">
	<div class="form-group">
		<label>Store (Location)</label>
		<select class="form-control" name="p_store_id" id="p_store_id" required>
			<option value="">-- Select Store --</option>
			<?php foreach (($shift_stores ?? []) as $store) { ?>
				<?php
                    // Trimmed: several company names were typed with a trailing
                    // space, and the line below closes a bracket right after it.
                    $owner = trim((string) $store->u_comp_name) !== ''
			            ? trim((string) $store->u_comp_name)
			            : 'Employer #' . (int) $store->u_id;

			        if ($group !== $owner) {
			            if ($group !== null) {
			                echo '</optgroup>';
			            }

			            echo '<optgroup label="' . esc($owner, 'attr') . '">';
			            $group = $owner;
			        }

			        $label = $store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : '');
			        ?>
				<option value="<?php echo (int) $store->s_id; ?>"
					data-owner="<?php echo esc($owner, 'attr'); ?>"
					data-manager="<?php echo esc((string) ($store->managers ?? ''), 'attr'); ?>"
					<?php echo ((string) ($p_store_id ?? '') === (string) $store->s_id) ? 'selected' : ''; ?>>
					<?php echo esc($label); ?></option>
			<?php } ?>
			<?php if ($group !== null) { echo '</optgroup>'; } ?>
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
