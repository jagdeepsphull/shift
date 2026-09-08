<?php

/**
 * The up and down arrows on a back-office list the agency orders by hand.
 *
 * Shared by all eight of them - Province, City, Shift For, Softwares, Services,
 * Additional Details, Resources Menu and Testimonials - so the eight cannot
 * drift apart, and so the rule about which arrow is drawn lives in one place.
 *
 * The arrows move a row one place, so the row at the top has no up and the one
 * at the bottom no down. Both are drawn as a disabled button rather than left
 * out, so the buttons beside them do not shift along the row from one line to
 * the next.
 *
 * `$groupKey` is for the two lists that are ordered inside something rather
 * than end to end: a city among the cities of its province, a resources link
 * among the children of its menu. Then "the top" means the top of that group,
 * which is the row the mover in the controller would refuse to move up.
 *
 * @var array   $rows     the whole list, in the order it is drawn
 * @var int     $index    where this row sits in it
 * @var object  $record   the row itself
 * @var string  $idKey    property holding the row's id
 * @var string  $labelKey property holding the name, for the screen-reader label
 * @var string  $link     the module segment, from $pageinfo['link']
 * @var ?string $groupKey property the list is ordered within, where it is
 */
$groupKey = $groupKey ?? null;

$sameGroup = static function ($other) use ($record, $groupKey) {
    return $groupKey === null || $other->{$groupKey} == $record->{$groupKey};
};

$hasUp   = $index > 0 && $sameGroup($rows[$index - 1]);
$hasDown = isset($rows[$index + 1]) && $sameGroup($rows[$index + 1]);

$label = (string) ($record->{$labelKey} ?? '');
?>
<?php if ($hasUp) { ?>
<a href="<?php echo base_url('sadmin/' . $link . '/moveup/' . $record->{$idKey}); ?>" class="btn btn-secondary" title="Move up" aria-label="Move <?php echo esc($label, 'attr'); ?> up the list"><i class="fas fa-arrow-up"></i></a>
<?php } else { ?>
<span class="btn btn-secondary disabled" aria-hidden="true"><i class="fas fa-arrow-up"></i></span>
<?php } ?>
<?php if ($hasDown) { ?>
<a href="<?php echo base_url('sadmin/' . $link . '/movedown/' . $record->{$idKey}); ?>" class="btn btn-secondary" title="Move down" aria-label="Move <?php echo esc($label, 'attr'); ?> down the list"><i class="fas fa-arrow-down"></i></a>
<?php } else { ?>
<span class="btn btn-secondary disabled" aria-hidden="true"><i class="fas fa-arrow-down"></i></span>
<?php } ?>
