<!-- Footer Section Start -->
    <footer id="footer" class="footer-area section-padding">
      <div class="container">
        <div class="container">
          <div class="row">
            
            <?php /* <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
              <h3 class="footer-titel">Products</h3>
              <ul class="footer-link">
                <li><a href="#">Tracking</a></li>
                <li><a href="#">Application</a></li>
                <li><a href="#">Resource Planning</a></li>
                <li><a href="#">Enterprise</a></li>           
                <li><a href="#">Employee Management</a></li>           
              </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
              <h3 class="footer-titel">Resources</h3>
              <ul class="footer-link">
                <li><a href="#">Payment Options</a></li>
                <li><a href="#">Fee Schedule</a></li>
                <li><a href="#">Getting Started</a></li>
                <li><a href="#">Identity Verification</a></li>
                <li><a href="#">Card Verification</a></li>
              </ul>
            </div>  */ ?>
           
          </div>
        </div>  
      </div> 
      <div id="copyright">
        <div class="container">
          <div class="row">
            <div class="col-md-12">
              <div class="copyright-content">
                <p>Copyright &copy; <?php echo date('Y')?> <a rel="nofollow" href="<?php echo $settings[0]->s_path; ?>" ><?php echo $settings[0]->s_sitename; ?></a> All Right Reserved</p>
              </div>
            </div>
          </div>
        </div>
      </div>   
    </footer> 
    <!-- Footer Section End -->

    <!-- Go to Top Link -->
    <a href="#" class="back-to-top">
    	<i class="lni-arrow-up"></i>
    </a>

	<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script> -->
    
    <!-- jQuery first, then Popper.js, then Bootstrap JS.

         Popper is the `umd` build. The file that used to be named here,
         assets/front/assets/js/popper.min.js, does not exist - and the one
         beside this at plugins/popper/popper.min.js is the ES module, which
         sets no `window.Popper` either. Bootstrap 4's tooltip throws without
         it, which is what summernote's toolbar builder calls: the throw left
         this page with a toolbar-less editor, an unhidden textarea behind it,
         and no date or time picker at all, because everything below the
         summernote line in the ready block never ran. -->
    <script src="<?php echo base_url('assets/front/assets/js/jquery-min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.validate.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/plugins/popper/umd/popper.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/bootstrap.min.js') ; ?>"></script>
	
    <script src="<?php echo base_url('assets/front/assets/js/owl.carousel.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/wow.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.nav.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/scrolling-nav.js') ; ?>"></script>
    <!-- <script src="<?php echo base_url('assets/front/assets/js/jquery.easing.min.js') ; ?>"></script> -->
    <!--<script src="<?php echo base_url('assets/front/assets/js/jquery.counterup.min.js') ; ?>"></script>  -->    
    <script src="<?php echo base_url('assets/front/assets/js/waypoints.min.js') ; ?>"></script>   
    <script src="<?php echo base_url('assets/front/assets/js/main.js') ; ?>"></script>
	<!-- <script src="<?php echo base_url('assets/front/assets/js/custom.js');?>"></script> -->

	<!-- datepicker -->
	<script src="<?php echo base_url('assets/front/plugins/bootstrap/js/bootstrap-datepicker.js');?>"></script>
	
		
	<!-- daterangepicker -->
	<script src="<?php echo base_url('assets/front/plugins/moment/moment.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/daterangepicker/daterangepicker.js');?>"></script>
	<!-- Summernote -->
	<script src="<?php echo base_url('assets/front/plugins/summernote/summernote-bs4.min.js');?>"></script>
	
	<!-- DataTables  & Plugins -->
	<script src="<?php echo base_url('assets/front/plugins/datatables/jquery.dataTables.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/datatables-responsive/js/dataTables.responsive.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/datatables-responsive/js/responsive.bootstrap4.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/datatables-buttons/js/dataTables.buttons.min.js');?>"></script>
	<script src="<?php echo base_url('assets/front/plugins/datatables-buttons/js/buttons.bootstrap4.min.js');?>"></script>
	<!-- select2, and the shared initialiser -->
	<script src="<?php echo base_url('assets/front/plugins/select2/js/select2.full.min.js');?>"></script>
	<script>
	/* Dress every dropdown in select2. width:'100%' because a select measured
	   inside a hidden row stays collapsed with any other setting; the theme is
	   bootstrap4, which this area's stylesheet actually is; the search box only
	   appears once a list is big enough to need one. */
	$(function () {
		if (!$.fn.select2) { return; }

		$('select').each(function () {
			var $select = $(this);

			if ($select.closest('.dataTables_wrapper').length || $select.is('[hidden], [data-no-select2]')) { return; }

			$select.select2({ width: '100%', theme: 'bootstrap4', minimumResultsForSearch: 8 });
		});
	});
	</script>

	


	
	
	<script>
	
		$(document).ready(function () {
			$('[data-toggle="tooltip"]').tooltip();
			
			$('#date-range').daterangepicker({
				autoApply: true,
				opens: 'center', // Options: 'left', 'right', 'center'
				drops: 'down',   // Options: 'down', 'up'
				locale: {
					format: 'YYYY-MM-DD'
				},
				
			});
			
			
			// Handle View Button Click
            $(document).on('click', '.view-btn', function () {
                const shiftfor = $(this).data('shiftfor');
                const shift_date = $(this).data('shift_date');
                const shift_time = $(this).data('shift_time');
				const shift_rate = $(this).data('shift_rate');
                const sofskills = $(this).data('sofskills');
                const ofcser = $(this).data('ofcser');

                // Populate modal with row data
                $('#modalShiftFor').val(shiftfor);
                $('#modalShiftDate').val(shift_date);
                $('#modalShiftTime').val(shift_time);
				$('#modalShiftRate').val(shift_rate);
                $('#modalSoftSkills').val(sofskills);
                $('#modalOffSer').val(ofcser);

                // Show the modal
                $('#viewModal').modal('show');
            });
			
			// Handle View Button Click
            $(document).on('click', '.applicant-btn', function () {
                const name = $(this).data('name');
                const licen = $(this).data('licen');
                const licen_prov = $(this).data('licen_prov');
                const shiftfor = $(this).data('shiftfor');

                // Populate modal with row data
                $('#modalName').val(name);
                $('#modalLicen').val(licen);
                $('#modalLicen_prov').val(licen_prov);
                $('#modalShiftFor1').val(shiftfor);

                // Show the modal
                $('#applicantModal').modal('show');
            });
			
			
			
		});
	
		function getpcities(val) {
			// alert(val);
			ciid = $('#hcity').val();
			$.ajax({
			type: "POST",
			url: "<?php echo base_url($settings[0]->s_frontpath.'/ajax_getcitylist');?>",
			//data:'statecode='+val+'ciid='+ciid,
			data: {statecode: val, ciid: ciid},
			success: function(data){
				$("#city").html(data).trigger('change.select2');
			}
			});
		}
		$(function () {
			// Shift list. Column 0 is the internal shift id, hidden and used only
			// as a tie-breaker; the list is ordered by the shift date (column 4),
			// soonest first, via the ISO value in each cell's data-order attribute.
			$('#joblist').DataTable({
			  columnDefs: [
					{
						targets: 0,       // Target the first column (index 0)
						visible: false,   // Make it invisible
						searchable: false // Optional: Disable searching on the hidden column
					}
				],
			   order: [[4, 'asc']],
			  "paging": true,
			  "lengthChange": false,
			  "searching": true,
			  "ordering": true,
			  "info": true,
			  "autoWidth": true,
			  "responsive": true,
			});

			// Candidates who applied to one shift. Its own columns again: the
			// first is the candidate's name, so it must stay visible and it is
			// what the list is ordered by.
			$('#candidatelist').DataTable({
			   order: [[0, 'asc']],
			  "paging": true,
			  "lengthChange": false,
			  "searching": true,
			  "ordering": true,
			  "info": true,
			  "autoWidth": true,
			  "responsive": true,
			});

			// Applications list has its own columns - it must not inherit the
			// shift list's hidden first column, which here is the applicant name.
			$('#applicationlist').DataTable({
			   order: [[2, 'desc']],
			  "paging": true,
			  "lengthChange": false,
			  "searching": true,
			  "ordering": true,
			  "info": true,
			  "autoWidth": true,
			  "responsive": true,
			});

			$("#example1").DataTable({
			  "responsive": true, "lengthChange": false, "autoWidth": false,
			  "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
			}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
			
			$('.summernote').summernote({
			  height: 150, //set editable area's height
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
			
			
		});
		
		$( document ).ready( function (){
			
			pid = $('#hprovince').val();
			chid = $('#hcity').val();
			
			getpcities(pid);
			
			
			// Custom validator for strong password
			$.validator.addMethod("strongPassword", function (value, element) {
				return this.optional(element) || 
					/[A-Z]/.test(value) && // At least one uppercase letter
					/[a-z]/.test(value) && // At least one lowercase letter
					/\d/.test(value) &&    // At least one digit
					/[@$!%*?&#]/.test(value) && // At least one special character
					value.length >= 8;     // Minimum length of 8 characters
			}, "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.");
			
			$("#change-pass").validate({
				rules: {
					current_password: {
						required: true
					},					
					new_password: {
						required: true,
						strongPassword: true
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
						strongPassword: "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character."
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
		
		
		
	</script>
	</body>
</html>
