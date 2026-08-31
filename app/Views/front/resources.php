<?php
/**
 * Resources: the link directory.
 *
 * Two lists out of the one `headermenu` table, split by the controller:
 * `headermenu_parent_only` are top-level rows that carry a link of their own,
 * shown as the quick-link tiles; `headermenu_parent` are top-level rows with no
 * link, which exist only to head a panel of children.
 *
 * Presentation is in assets/front/assets/css/theme.css under "resources".
 * The panels are Bootstrap collapses - the chevron is turned by CSS off the
 * aria-expanded Bootstrap already maintains, so no script of ours is involved.
 */
?>
<section id="services" class="section-padding">
  <div class="wz-shell">

    <?php if ($headermenu_parent_only) { ?>
      <h2 class="wz-res-label">Quick links</h2>

      <div class="wz-res-quick">
        <?php foreach ($headermenu_parent_only as $hp_only) { ?>
          <a class="wz-res-tile" href="<?php echo esc($hp_only->m_link, 'attr'); ?>" target="_blank" rel="noopener noreferrer">
            <span class="wz-res-tile-name"><?php echo esc($hp_only->m_name); ?></span>
            <span class="wz-res-tile-arrow" aria-hidden="true"></span>
          </a>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($headermenu_parent) { ?>
      <h2 class="wz-res-label">Browse by category</h2>

      <div class="wz-res-grid accordion" id="dynamicAccordion">
        <?php foreach ($headermenu_parent as $category) {
            $subcategories = custom()->get_where('headermenu', ['m_parentid' => $category->m_id, 'm_status' => 1]);
            $wz_panel      = 'collapse' . (int) $category->m_id;
            $wz_head       = 'heading' . (int) $category->m_id;
        ?>
          <div class="wz-res-card">
            <h3 class="wz-res-head" id="<?php echo $wz_head; ?>">
              <?php /* aria-expanded is Bootstrap's to maintain once it binds, and
                 the chevron is drawn off it, so the arrow can never disagree with
                 the panel. It starts "false" because the panel starts closed. */ ?>
              <button class="wz-res-toggle" type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#<?php echo $wz_panel; ?>"
                      aria-expanded="false"
                      aria-controls="<?php echo $wz_panel; ?>">
                <span class="wz-res-name"><?php echo esc($category->m_name); ?></span>
                <?php if ($subcategories) { ?>
                  <span class="wz-res-count"><?php echo count($subcategories); ?></span>
                <?php } ?>
                <span class="wz-res-chevron" aria-hidden="true"></span>
              </button>
            </h3>

            <div id="<?php echo $wz_panel; ?>" class="collapse" aria-labelledby="<?php echo $wz_head; ?>" data-bs-parent="#dynamicAccordion">
              <div class="wz-res-links">
                <?php if ($subcategories) { ?>
                  <?php foreach ($subcategories as $sub) { ?>
                    <a class="wz-res-link" href="<?php echo esc($sub->m_link, 'attr'); ?>" target="_blank" rel="noopener noreferrer">
                      <span><?php echo esc($sub->m_name); ?></span>
                      <span class="wz-res-link-arrow" aria-hidden="true"></span>
                    </a>
                  <?php } ?>
                <?php } else { ?>
                  <p class="wz-res-empty">Nothing here yet.</p>
                <?php } ?>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

  </div>
</section>
