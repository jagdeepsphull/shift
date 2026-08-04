<footer class="main-footer">
    <strong><?php echo $settings[0]->s_sitename;?> 
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      Contact <?php echo $settings[0]->s_contactno;?>
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->




<!-- jQuery -->
<script src="<?php echo base_url();?>assets/admin/plugins/jquery/jquery.min.js"></script>
<!-- jquery-validation -->
<script src="<?php //echo base_url('assets/admin/plugins/jquery-validation/jquery.validate.min.js');?>"></script>

<script src="<?php //echo base_url('assets/admin/plugins/jquery-validation/additional-methods.min.js');?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?php echo base_url();?>assets/admin/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->


<!-- jQuery -->
<script src="<?php echo base_url('assets/admin/plugins/jquery/jquery.min.js');?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?php echo base_url('assets/front/assets/js/jquery.validate.min.js') ; ?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/jquery-ui/jquery-ui.min.js');?>"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>

<!-- datepicker -->
<script src="<?php echo base_url('assets/admin/plugins/bootstrap/js/bootstrap-datepicker.js');?>"></script>

<!-- daterangepicker -->
<script src="<?php echo base_url('assets/admin/plugins/daterangepicker/daterangepicker.js');?>"></script>

<!-- Bootstrap 4 -->
<script src="<?php echo base_url('assets/admin/plugins/bootstrap/js/bootstrap.bundle.min.js');?>"></script>

<!-- Select2 -->
<script src="<?php echo base_url('assets/admin/plugins/select2/js/select2.full.min.js');?>"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="<?php echo base_url('assets/admin/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js');?>"></script>
<!-- Ekko Lightbox -->
<script src="<?php echo base_url('assets/admin/plugins/ekko-lightbox/ekko-lightbox.min.js');?>"></script>
<!-- DataTables  & Plugins -->
<script src="<?php echo base_url('assets/admin/plugins/datatables/jquery.dataTables.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-responsive/js/dataTables.responsive.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-buttons/js/dataTables.buttons.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/jszip/jszip.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/pdfmake/pdfmake.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/pdfmake/vfs_fonts.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-buttons/js/buttons.html5.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-buttons/js/buttons.print.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/datatables-buttons/js/buttons.colVis.min.js');?>"></script>

<!-- ChartJS -->
<script src="<?php echo base_url('assets/admin/plugins/chart.js/Chart.min.js');?>"></script>
<!-- Sparkline -->
<script src="<?php echo base_url('assets/admin/plugins/sparklines/sparkline.js');?>"></script>
<!-- JQVMap -->
<script src="<?php echo base_url('assets/admin/plugins/jqvmap/jquery.vmap.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/jqvmap/maps/jquery.vmap.usa.js');?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?php echo base_url('assets/admin/plugins/jquery-knob/jquery.knob.min.js');?>"></script>
<!-- daterangepicker -->
<script src="<?php echo base_url('assets/admin/plugins/moment/moment.min.js');?>"></script>
<script src="<?php echo base_url('assets/admin/plugins/daterangepicker/daterangepicker.js');?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?php echo base_url('assets/admin/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js');?>"></script>
<!-- Summernote -->
<script src="<?php echo base_url('assets/admin/plugins/summernote/summernote-bs4.min.js');?>"></script>
<!-- overlayScrollbars -->
<script src="<?php echo base_url('assets/admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js');?>"></script>
<!-- AdminLTE App -->
<script src="<?php echo base_url('assets/admin/dist/js/adminlte.js');?>"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="<?php echo base_url('assets/admin/dist/js/demo.js');?>"></script> -->
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="<?php echo base_url('assets/admin/dist/js/pages/dashboard.js');?>"></script>
<!-- Summernote -->
<script src="<?php echo base_url();?>assets/admin/plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->


<script src="<?php echo base_url();?>assets/admin/dist/js/pages/dashboard.js"></script>

<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>


<script>

	$(document).ready(function () {
		$("#change-pass").validate({
				rules: {
					current_password: {
						required: true
					},					
					new_password: {
						required: true,
					//	strongPassword: true
					},
					confirm_password: {
						required: true,
						equalTo: "#mainpassword"
					}
				},
				messages: {
					current_password: "Current Password is required.",
					new_password: {
						required: "New Password is required.",
					//	strongPassword: "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character."
					},
					confirm_password: {
						required: "Please confirm your password.",
						equalTo: "Passwords do not match."
					}
				},
				submitHandler: function (form) {
					//alert("Form submitted successfully!");
					form.submit();
				}
			});
	  
    }); 
	
	 
	function getpcities(val) {
		// alert(val);
		ciid = $('#hcity').val();
		$.ajax({
		type: "POST",
		url: "<?php echo base_url($settings[0]->s_adminpath.'/ajax_getcitylist');?>",
		//data:'statecode='+val+'ciid='+ciid,
		data: {statecode: val, ciid: ciid},
		success: function(data){
			$("#city").html(data);
		}
		});
	}
	$(document).ready(function() {
		cid = $('#p_country').val();
		sid = $('#pstate').val();
		ciid = $('#pcity').val();

		pid = $('#hprovince').val();
		chid = $('#hcity').val();
		
		getpcities(pid);
		
		
		
		
	});
  $(function () {
	  
	  // Every admin list shares this table. A page can set its own default sort
	  // with data-order-col / data-order-dir on the <table> element, rather than
	  // inheriting the hidden id column below.
	  var $adminTable = $("#example1");
	  var orderCol = $adminTable.data('order-col');
	  var orderDir = $adminTable.data('order-dir') || 'desc';
	  var defaultOrder = (orderCol === undefined || orderCol === '')
		  ? [[0, 'desc']]
		  : [[parseInt(orderCol, 10), orderDir]];

	  $adminTable.DataTable({
		  columnDefs: [
            {
                targets: 0,       // Target the first column (index 0)
                visible: false,   // Make it invisible
                searchable: false // Optional: Disable searching on the hidden column
            }
        ],
       order: defaultOrder,
		  "responsive": true, "lengthChange": false, "autoWidth": false,
		  scrollX: true,
			scrollCollapse: true,
		  // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
		  "buttons": [ "colvis"]
		}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
	  
	  $("#res-table-ex").DataTable({
		  columnDefs: [
            {
                targets: 0,       // Target the first column (index 0)
                visible: false,   // Make it invisible
                searchable: false // Optional: Disable searching on the hidden column
            }
        ],
       order: [[0, 'desc']], // Sort by the first column in ascending order
		  "responsive": false, "lengthChange": false, "autoWidth": false,
		  scrollX: true,
			scrollCollapse: true,
		  // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
		  "buttons": [ "colvis"]
		}).buttons().container().appendTo('#res-table-ex_wrapper .col-md-6:eq(0)');
    
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
	  
	// Popovers are attached to the page rather than to the buttons themselves.
	// DataTables only keeps the current page of rows in the DOM, so binding to
	// the buttons directly used to leave every row past the first page dead.
	$(document.body).popover({
		selector: '[data-toggle="popover"]',
		trigger: 'click',
		html: false,
		container: 'body'
	});

	// Close other popovers before showing a new one
	$(document).on('click', '[data-toggle="popover"]', function () {
		$('[data-toggle="popover"]').not(this).popover('hide');
	});

	// Close popovers when clicking outside
	$(document).on('click', function (e) {
		if (!$(e.target).closest('[data-toggle="popover"], .popover').length) {
			$('[data-toggle="popover"]').popover('hide');
		}
	});
	
	  
	$('.date').datepicker({
		multidate: false,
		startDate: new Date(),
		minDate: 0, // Restrict past dates, allowing only today and future dates
		format: 'dd-mm-yyyy'
	});
	
	// Get prepopulated value from the input
	let prePopulatedValue = $('.timePicker').val(); 

	// Split the prepopulated value into start and end times
	let startTime = prePopulatedValue ? prePopulatedValue.split(' - ')[0] : moment().format('HH:mm');
	let endTime = prePopulatedValue ? prePopulatedValue.split(' - ')[1] : moment().add(1, 'hours').format('HH:mm');

	
	$('.timePicker').daterangepicker({
		timePicker: true,
		timePicker24Hour: true, // Use 24-hour format
		timePickerIncrement: 15, // Time intervals (e.g., 30 minutes)
		locale: {
			format: 'HH:mm', // Format for time display only
		},
		startDate: startTime, // Use parsed start time or default
		endDate: endTime, // Use parsed end time or default
		singleDatePicker: false, // Enable range selection
		autoApply: true // Automatically close on selection
	}, function(start, end, label) {
		console.log("Selected time range: " + start.format('HH:mm') + ' - ' + end.format('HH:mm'));
	});
	
	

		
		//$('.reslnk').hide(); 
		
		if($('#m_parentid').val() == 0) {
			//$('.reslnk').hide(); 
			$('#m_link').removeAttr('required');
		} else {
			//$('.reslnk').show(); 
			$('#m_link').attr('required', 'required');
		} 
		
		$('#m_parentid').change(function(){
			if($('#m_parentid').val() == 0) {
				//$('.reslnk').hide(); 
				$('#m_link').removeAttr('required');
			} else {
				//$('.reslnk').show(); 
				$('#m_link').attr('required', 'required');
			} 
		});
	  
	  $('.summernote').summernote({
		  toolbar: [
			// [groupName, [list of button]]
			['style', ['bold', 'italic', 'underline', 'clear']],
			['font', ['strikethrough', 'superscript', 'subscript']],
			['fontsize', ['fontsize']],
			['color', ['color']],
			['para', ['ul', 'ol', 'paragraph']],
			['height', ['height']]
		  ]
		});
	  $('#p_jobinfo').summernote({
			  toolbar: [
				// [groupName, [list of button]]
				['style', ['bold', 'italic', 'underline', 'clear']],
				['font', ['strikethrough', 'superscript', 'subscript']],
				['fontsize', ['fontsize']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['height', ['height']]
			  ]
			});
	  $('#p_interview_details').summernote({
		  toolbar: [
			// [groupName, [list of button]]
			['style', ['bold', 'italic', 'underline', 'clear']],
			['font', ['strikethrough', 'superscript', 'subscript']],
			['fontsize', ['fontsize']],
			['color', ['color']],
			['para', ['ul', 'ol', 'paragraph']],
			['height', ['height']]
		  ]
		});
	  
	  $('.select2').select2();
	  
	  
	  
    /* $(".datatablecss").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#datatablecss .col-md-6:eq(0)'); */
    /* $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
 */  
 
 
  

 
 
 
 });
 
 
 // user country code for selected option
let user_country_code = "IN";




</script>
</body>
</html>
