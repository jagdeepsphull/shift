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
                        <li class="breadcrumb-item"><a
                                href="<?php echo base_url('sadmin/'.$pageinfo['link'].'');?>"><?php echo $pageinfo['title']; ?></a>
                        </li>
                        <li class="breadcrumb-item active">Edit <?php echo $pageinfo['title']; ?></li>
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
                    <!-- jquery validation -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $pageinfo['title']; ?> <small>Detail</small></h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form id="colorForm" method="post"
                            action="<?php echo base_url('sadmin/'.$pageinfo['link'].'/edit/'.$id);?>"
                            enctype="multipart/form-data">
                            <div class="card-body">
                            
                                <div class="row">
								
									<div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="m_parentid">Parent Menu</label>
                                            <select class="form-control select2 select2-hidden-accessible" name="m_parentid" id="m_parentid" style="width: 100%;" data-select2-id="1" tabindex="-1" aria-hidden="true" required>
                                                <option selected="selected" value="0">Self</option>
                                                <?php if($headermenu_select){
													foreach($headermenu_select as $category){?>
													<option value="<?php echo $category->m_id;?>"
                                                    <?php if($m_parentid==$category->m_id){ echo 'selected="selected"';}?>>
                                                    <?php echo $category->m_name;?></option>
                                                <?php 
														}
													}?>
                                            </select>
                                        </div>
                                    </div>
									
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="m_name">Header Menu Name</label>
                                            <input type="text" class="form-control" id="m_name" name="m_name"
                                                placeholder="Enter Header Menu Name" value="<?php echo $m_name;?>"
                                                required>
                                        </div>
                                    </div>
                                    
									 <div class="col-sm-4 reslnk" >
									  <div class="form-group">
										<label for="m_name">Resource Link</label>
										<input type="text" class="form-control" id="m_link" name="m_link" placeholder="Enter Resource Link" value="<?php echo $m_link;?>" required>
									  </div>
									 </div>
									
                                </div>
                                
								

                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <button type="submit" name="updatedata" class="btn btn-primary"
                                    value="Update <?php echo $pageinfo['title']; ?>">Update
                                    <?php echo $pageinfo['title']; ?></button>
                                <a class="btn btn-danger"
                                    href="<?php echo base_url('sadmin/'.$pageinfo['link']);?>">Cancel</a>
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