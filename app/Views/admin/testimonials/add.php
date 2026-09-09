<?php
/**
 * Add a testimonial: the whole card, not just its two blocks of text.
 *
 * The fields are laid out in the order the card reads - the quote first, then
 * the person it came from, then how it is shown. `$t_*` come from
 * `getTableInfo()`, which seeds one variable per column, so a rejected save
 * redraws exactly what was typed.
 */
$t_rating = ($t_rating ?? '') !== '' ? (int) $t_rating : 5;
$t_status = ($t_status ?? '') !== '' ? (int) $t_status : 1;
?>
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Add <?php echo $pageinfo['title']; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
			  <li class="breadcrumb-item"><a href="<?php echo base_url('sadmin/'.$pageinfo['link'].'');?>"><?php echo $pageinfo['title']; ?></a></li>
              <li class="breadcrumb-item active">Add <?php echo $pageinfo['title']; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->

    </section>

<!-- Main content -->
    <section class="content">
      <div class="container-fluid">
	  <?php
			if(session()->getFlashdata('error_msg')){echo '<div class="alert alert-danger">'.session()->getFlashdata('error_msg').'</div>';}

		?>
		<?php if (validation_errors()): ?>
			<div class="alert alert-danger">
				<?php echo validation_errors(); ?>
			</div>
		<?php endif; ?>
        <div class="row">
          <!-- left column -->
          <div class="col-md-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><?php echo $pageinfo['title']; ?> <small>Detail</small></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form id="colorForm" method="post" action="<?php echo base_url('sadmin/'.$pageinfo['link'].'/add');?>"  enctype="multipart/form-data">
                <div class="card-body">
                  <div class="row">
                    <div class="col-sm-6">
					  <div class="form-group">
						<label for="t_title">Title <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="t_title" name="t_title" placeholder="Enter Title" maxlength="150" value="<?php echo esc($t_title);?>" required>
						<small class="form-text text-muted">The heading on the card, e.g. "A Trusted Name for Exceptional Service".</small>
					  </div>
					 </div>
					</div>
					<div class="row">
                    <div class="col-sm-12">
					  <div class="form-group">
						<label for="t_description">Description <span class="text-danger">*</span></label>
						<textarea class="form-control" id="t_description" name="t_description" rows="5" placeholder="Enter Description" required><?php echo esc($t_description);?></textarea>
						<small class="form-text text-muted">The quote itself. Line breaks are kept on the home page.</small>
					  </div>
					 </div>

				</div>

				<?php /* Who said it. Optional throughout - the home page card
				   leaves out any line it has nothing for, so a quote with no
				   name still renders rather than showing an empty footer. */ ?>
				<hr>
				<h5 class="mb-3">Who said it</h5>

				<div class="row">
					<div class="col-sm-4">
					  <div class="form-group">
						<label for="t_name">Name</label>
						<input type="text" class="form-control" id="t_name" name="t_name" placeholder="e.g. Sarah M." maxlength="100" value="<?php echo esc($t_name ?? '');?>">
					  </div>
					</div>
					<div class="col-sm-4">
					  <div class="form-group">
						<label for="t_role">Role</label>
						<input type="text" class="form-control" id="t_role" name="t_role" placeholder="e.g. Job Seeker" maxlength="100" value="<?php echo esc($t_role ?? '');?>">
					  </div>
					</div>
					<div class="col-sm-4">
					  <div class="form-group">
						<label for="t_location">Location</label>
						<input type="text" class="form-control" id="t_location" name="t_location" placeholder="e.g. Toronto, ON" maxlength="120" value="<?php echo esc($t_location ?? '');?>">
					  </div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-4">
					  <div class="form-group">
						<label for="t_image">Photo</label>
						<input type="file" class="form-control" id="t_image" name="t_image" accept="image/*">
						<small class="form-text text-muted">JPG, PNG, GIF or WebP, up to 2&nbsp;MB. Square images look best. Leave this empty and the placeholder thumb is used.</small>
					  </div>
					</div>
					<div class="col-sm-2">
					  <div class="form-group">
						<label>Preview</label><br>
						<img src="<?php echo esc(testimonialPhoto(''), 'attr');?>" alt="No photo" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid #e9ecf3;">
					  </div>
					</div>
					<div class="col-sm-3">
					  <div class="form-group">
						<label for="t_rating">Rating</label>
						<select class="form-control" id="t_rating" name="t_rating" required>
							<?php foreach ($ratings as $value => $label) { ?>
								<option value="<?php echo (int) $value;?>" <?php if ($t_rating === (int) $value) { echo 'selected="selected"'; }?>><?php echo esc($label);?></option>
							<?php } ?>
						</select>
					  </div>
					</div>
					<div class="col-sm-3">
					  <div class="form-group">
						<label for="t_status">Status</label>
						<select class="form-control" id="t_status" name="t_status" required>
							<?php foreach ($status as $value => $label) { ?>
								<option value="<?php echo (int) $value;?>" <?php if ($t_status === (int) $value) { echo 'selected="selected"'; }?>><?php echo esc($label);?></option>
							<?php } ?>
						</select>
						<small class="form-text text-muted">Only Active testimonials reach the home page.</small>
					  </div>
					</div>
				</div>

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                  <button type="submit"  name="savedata" class="btn btn-primary" value="Add <?php echo $pageinfo['title']; ?>">Add <?php echo $pageinfo['title']; ?></button>
				  <a class="btn btn-danger" href="<?php echo base_url('sadmin/'.$pageinfo['link']);?>">Cancel</a>
                </div>
              </form>
            </div>
            <!-- /.card -->
            </div>
          <!--/.col (left) -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
