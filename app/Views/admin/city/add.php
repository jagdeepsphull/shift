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
            <!-- jquery validation -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><?php echo $pageinfo['title']; ?> <small>Detail</small></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form id="colorForm" method="post" action="<?php echo base_url('sadmin/'.$pageinfo['link'].'/add');?>"  enctype="multipart/form-data">
                <div class="card-body">
                  <div class="row">
					<div class="col-sm-4">
					  <div class="form-group">
						<label for="c_name">Province Name</label>
						<select class="form-control " name="c_province" required>
							<option value="" > -- Select Province --</option>
							<?php if($province){ ?>
							<?php foreach($province as $record){ ?>
							<option value="<?php echo $record->p_id; ?>"
								<?php echo ($u_provice==$record->p_id)?"selected":""; ?>>
								<?php echo $record->p_name; ?></option>
							<?php } ?>
							<?php } ?>
						</select>
					  </div>
					 </div>
					
                    <div class="col-sm-4">
					  <div class="form-group">
						<label for="c_name">Name</label>
						<input type="text" class="form-control" id="c_name" name="c_name" placeholder="Enter Name" value="<?php echo $c_name;?>" required>
					  </div>
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
          <!-- right column -->
          <div class="col-md-6">

          </div>
          <!--/.col (right) -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
	