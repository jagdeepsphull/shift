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
    
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="<?php echo base_url('assets/front/assets/js/jquery-min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.validate.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/popper.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/bootstrap.min.js') ; ?>"></script>
	
	
	
    <script src="<?php echo base_url('assets/front/assets/js/owl.carousel.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/wow.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.nav.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/scrolling-nav.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.easing.min.js') ; ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.counterup.min.js') ; ?>"></script>      
    <script src="<?php echo base_url('assets/front/assets/js/waypoints.min.js') ; ?>"></script>   
    <script src="<?php echo base_url('assets/front/assets/js/main.js') ; ?>"></script>
	<script src="<?php echo base_url('assets/front/assets/js/custom.js');?>"></script>
	
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
		
		$( document ).ready( function (){
			
			// Handle View Button Click
            $(document).on('click', '.employer-btn', function () {
                const cname = $(this).data('cname');
                const licen = $(this).data('licen');
                const address = $(this).data('address');
                const approved = $(this).data('approved');
                const appremarks = $(this).data('appremarks');
                const shiftfor = $(this).data('shiftfor');
                const shift_date = $(this).data('shift_date');
                const shift_time = $(this).data('shift_time');
                const shift_rate = $(this).data('shift_rate');
                const sofskills = $(this).data('sofskills');
                const ofcser = $(this).data('ofcser');

                // Populate modal with row data
                $('#modalName').val(cname);
                $('#modalLicen').val(licen);
                $('#modalAddress').val(address);
                $('#modalApproved').val(approved);
                $('#modalAppRemarks').val(appremarks);
                $('#modalShiftFor').val(shiftfor);
                $('#modalShiftDate').val(shift_date);
                $('#modalShiftTime').val(shift_time);
                $('#modalShiftRate').val(shift_rate);
                $('#modalSoftSkills').val(sofskills);
                $('#modalOffSer').val(ofcser);

                // Show the modal
                $('#empModal').modal('show');
            });
			
			// Handle View Button Click
            $(document).on('click', '.shift-btn', function () {
                const shiftfor = $(this).data('shiftfor');
                const shift_date = $(this).data('shift_date');
                const shift_time = $(this).data('shift_time');
                const shift_rate = $(this).data('shift_rate');
                const sofskills = $(this).data('sofskills');
                const ofcser = $(this).data('ofcser');
                const address = $(this).data('address');

                // Populate modal with row data
                $('#modalShiftFor1').val(shiftfor);
                $('#modalShiftDate1').val(shift_date);
                $('#modalShiftTime1').val(shift_time);
                $('#modalShiftRate1').val(shift_rate);
                $('#modalSoftSkills1').val(sofskills);
                $('#modalOffSer1').val(ofcser);
                $('#modalAddress1').val(address);

                // Show the modal
                $('#viewModal').modal('show');
            });
			
			
			
			pid = $('#hprovince').val();
			chid = $('#hcity').val();
			// alert($('#hprovince').val());
			getpcities(pid);
			
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
		
		$( document ).ready( function (){
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
		
		
		$(function () {
			// Applied shifts. Column 0 is the internal application id, hidden in the
			// markup; without an explicit order DataTables sorted on it and undid
			// the query's ordering. Sort by the shift date (column 3), soonest
			// first, using the ISO value in each cell's data-order attribute.
			var tbllist = $('#joblist').DataTable({
			   order: [[3, 'asc']],
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
			
			// Attached to the page, not to the buttons: DataTables only keeps the
			// current page of rows in the DOM, so a direct binding would leave
			// every row past the first page without a working popover.
			$(document.body).popover({
				selector: '[data-toggle="popover"]',
				trigger: 'click',
				html: false,
				container: 'body'
			});

			$(document).on('click', '[data-toggle="popover"]', function () {
				$('[data-toggle="popover"]').not(this).popover('hide');
			});

			$(document).on('click', function (e) {
				if (!$(e.target).closest('[data-toggle="popover"], .popover').length) {
					$('[data-toggle="popover"]').popover('hide');
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
			
			$('.date').datepicker({
				multidate: true,
				format: 'dd-mm-yyyy'
			});
			
		});
		
		
		
		
	</script>

	<?= view('partials/phone_input_script') ?>

</body>

</html>