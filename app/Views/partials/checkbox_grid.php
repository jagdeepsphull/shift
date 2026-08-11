<?php

/**
 * A tick-box grid, replacing the Ctrl-click multi-select lists on the shift
 * forms. Used by all four (employer add/edit, agency add/edit) so the four
 * cannot drift apart.
 *
 * The old control silently wiped every previous choice on a plain click, which
 * made it the most error-prone field on the form.
 *
 * @var string $name     field name, without the trailing []
 * @var string $label    the visible legend
 * @var array  $items    rows to offer
 * @var string $idKey    property on each row holding the value
 * @var string $labelKey property on each row holding the text
 * @var mixed  $selected comma separated string, or an array of ids
 * @var bool   $required whether at least one must be ticked
 */
$selectedList = is_array($selected ?? null)
    ? $selected
    : array_filter(array_map('trim', explode(',', (string) ($selected ?? ''))), 'strlen');

$required = $required ?? false;
$groupId  = 'cbg_' . preg_replace('/[^a-z0-9]/i', '_', $name);
?>
<div class="form-group">
    <label class="d-block"><?= esc($label) ?><?= $required ? ' <span class="text-danger">*</span>' : '' ?></label>

    <div class="border rounded p-2 checkbox-grid" id="<?= esc($groupId, 'attr') ?>"
         data-required="<?= $required ? '1' : '0' ?>"
         data-label="<?= esc($label, 'attr') ?>"
         style="max-height: 190px; overflow-y: auto;">
        <div class="row">
            <?php foreach (($items ?: []) as $item) {
                $value = $item->{$idKey};
                $text  = $item->{$labelKey};
                $id    = $groupId . '_' . $value;
                ?>
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input"
                               id="<?= esc($id, 'attr') ?>"
                               name="<?= esc($name, 'attr') ?>[]"
                               value="<?= esc($value, 'attr') ?>"
                               <?= in_array((string) $value, array_map('strval', $selectedList), true) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="<?= esc($id, 'attr') ?>"><?= esc($text) ?></label>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php if (! defined('CHECKBOX_GRID_JS')) {
    define('CHECKBOX_GRID_JS', true); ?>
<script>
/* HTML's own `required` on a checkbox means "this exact box must be ticked",
   not "one of the group" - so the group rule is enforced here instead. Server
   side still validates; this only saves a round trip. */
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.querySelectorAll) return;

    var groups = form.querySelectorAll('.checkbox-grid[data-required="1"]');

    for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        if (g.querySelector('input[type=checkbox]:checked')) continue;

        e.preventDefault();
        g.classList.add('border-danger');
        alert('Please tick at least one option for "' + (g.getAttribute('data-label') || 'this field') + '".');
        g.scrollIntoView({ block: 'center' });
        return;
    }
}, true);

document.addEventListener('change', function (e) {
    if (e.target && e.target.type === 'checkbox') {
        var g = e.target.closest ? e.target.closest('.checkbox-grid') : null;
        if (g) g.classList.remove('border-danger');
    }
});
</script>
<?php } ?>
