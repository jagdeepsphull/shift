<?php

/**
 * Where a store actually is, and where it lives on the web.
 *
 * Shared by all three forms that maintain a store - the employer's own
 * add/edit pages and the back office's - so the three cannot drift apart. The
 * postal address is asked for separately by each of them; these are the fields
 * that go with it.
 *
 * The map link is pasted by hand from Google Maps (Share > Copy link) rather
 * than geocoded: a pharmacy inside a supermarket, or one of four units in a
 * plaza, is not reliably found from its street address, and the person filling
 * this in knows exactly which pin is theirs.
 *
 * @var string $locationLabel current `s_location_label`
 * @var string $mapUrl        current `s_map_url`
 * @var string $website       current `s_website`
 * @var bool   $showWebsite   false where the form has its own website field
 */
$showWebsite = $showWebsite ?? true;
?>
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Location Name <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" name="s_location_label"
                   placeholder="e.g. Inside Real Canadian Superstore"
                   value="<?= esc($locationLabel ?? '') ?>">
            <small class="form-text text-muted">
                What to call the spot when the street address alone will not find it.
            </small>
        </div>
    </div>

    <div class="col-sm-6">
        <div class="form-group">
            <label>Google Maps Link <small class="text-muted">(optional)</small></label>
            <input type="url" class="form-control" name="s_map_url"
                   placeholder="https://maps.app.goo.gl/..."
                   value="<?= esc($mapUrl ?? '') ?>">
            <small class="form-text text-muted">
                In Google Maps, find this store, then Share &gt; Copy link and paste it here.
                Left blank, the shift page links to a search for the address above.
            </small>
        </div>
    </div>
</div>

<?php if ($showWebsite) { ?>
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Store Website <small class="text-muted">(optional)</small></label>
            <input type="url" class="form-control" name="s_website"
                   placeholder="https://example.com"
                   value="<?= esc($website ?? '') ?>">
            <small class="form-text text-muted">
                Only if this location has a page of its own. Left blank, the employer's
                website is used.
            </small>
        </div>
    </div>
</div>
<?php } ?>
