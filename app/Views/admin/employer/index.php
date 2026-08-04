<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Manage <?php echo $pageinfo['title']; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo base_url($adminpath.'/dashboard');?>">Home</a></li>
              <li class="breadcrumb-item active"><?php echo $pageinfo['title']; ?> List</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
		<?php 
			if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}
			echo email_failure_notice();		
			
			
		?>
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
		<div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><?php echo $pageinfo['title']; ?> List </h3> 

				<a href="<?php echo base_url($adminpath.'/'.$link.'/add');?>" class="btn btn-info  float-sm-right">Add <?php echo $pageinfo['title']; ?></a>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive1 p-2" style="1height: 300px;">
                <table id="example1" class="table table-bordered table-striped datatablecss" data-order-col="1" data-order-dir="asc">
                  <thead>
                  <tr>
                    <th><?php echo $pageinfo['title']; ?> ID</th>
                    <th>Store Name</th>
                    <th>Store No.</th>
                    <th>Conatact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
				  <?php if($users){?>
                  <tbody>
				  <?php foreach($users as $user){?>
                  <tr>
                    <td><?php echo $user->u_id; ?></td>
                    <td><?php echo $user->u_comp_name; ?></td>
                    <td><?php echo $user->u_licence_no; ?></td>
                    <td><?php echo $user->u_fname.' ' .$user->u_lname; ?></td>
                    <td><?php echo $user->u_email; ?></td>
                    <td><?php echo $user->u_phone; ?></td>
                    <td><?php echo ($user->u_status=='1')?"Active":"Deactive" ; ?></td>
                    <td><a href="<?php echo base_url($adminpath.'/'.$link.'/edit/'.$user->u_id);?>" class="btn btn-success"><i class="fas fa-edit"></i></a> 
					<!--<a href="<?php //echo base_url('sadmin/'.$pageinfo['link'].'/changestatus/'.$user->u_id);?>" class="btn btn-warning">Change Status</a> -->
					<a href="<?php echo base_url($adminpath.'/'.$link.'/delete/'.$user->u_id);?>"  class="btn btn-danger"  onclick="return confirm('Are you sure? You want to delete')"><i class="fas fa-trash-alt"></i></a>
					
					</td>
                  </tr>
                 
                  
				  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  <tr>
					<th><?php echo $pageinfo['title']; ?> ID</th>
					<th>Store Name</th>
					<th>Store No.</th>
                    <th>Conatact Person</th>
                    <th>Email ID</th>
                    <th>Mobile No.</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>	
			
     </div>
  </section>
    </div>