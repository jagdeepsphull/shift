	<!-- Content Wrap -->
	<div class="col-lg-9 col-md-8">
	    <div class="dashboard-body">
	        <div class="dashboard-caption">

	            <div class="dashboard-caption-header">
	                <h4><i class="lni-files"></i>Document Center</h4>
	            </div>

	            <div class="dashboard-caption-wrap">
	                <?php foreach ($documents as $doc) { ?>
	                    <?php if ($doc['available']) { ?>
	                        <a class="btn btn-common mr-2 mb-2" download
	                           href="<?php echo base_url('uploads/documents/' . rawurlencode($doc['file'])); ?>">
	                            <i class="lni-download mr-2"></i><?php echo esc($doc['title']); ?>
	                        </a>
	                    <?php } else { ?>
	                        <button type="button" class="btn btn-secondary mr-2 mb-2" disabled
	                                title="This file is not available yet">
	                            <i class="lni-download mr-2"></i><?php echo esc($doc['title']); ?>
	                        </button>
	                    <?php } ?>
	                <?php } ?>
	            </div>

	        </div>
	    </div>
	</div>

	</div>
	</div>
	</section>
	<!-- General Detail End -->
