<!-- Footer Section Start -->
    <footer id="footer" class="footer-area section-padding" style="padding:0px;">
      <?php /* ?><div class="container">
        <div class="container">
          <div class="row">
            
            <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
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
            
           
          </div>
        </div>  
      </div> </div>  */ ?>
      <div id="copyright">
        <div class="container">
          <div class="row">
            <div class="col-md-6">
				<div class="copyright-content" style="margin:0px;">
				  <div class="social-icon">
					  <a class="facebook" href="#"><i class="lni-facebook-filled"></i></a>
					  <a class="twitter" href="https://x.com/reliefshifts"target="_blank"><i class="lni-twitter-filled"></i></a>
					  <a class="instagram" href="https://www.instagram.com/reliefshifts?igsh=ZGwydWl5NTg0OXln" target="_blank"><i class="lni-instagram-filled"></i></a>
					</div>
				</div>
            </div>
			<div class="col-md-6">
              <div class="copyright-content" style="margin:0px;">
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
    

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
	
	<script>
		// Scroll to top when the page reloads
		/* window.onbeforeunload = function () {
		  window.scrollTo(0, 0);
		}; */
	
		$( document ).ready( function (){
			
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
				$("#city").html(data);
			}
			});
		}
	
		$(function () {
			$('#joblistH').DataTable({
			  
			  "paging": true,
			  "lengthChange": false,
			  "searching": true,
			  "ordering": true,
			  "info": true,
			  "autoWidth": true,
			  "responsive": false,
			  order: [[4, 'asc']], // Shift Date, soonest first
			   "autoWidth": false,
				scrollX: true,
			});
			
		});
		$(function () {
			$('#joblist').DataTable({
			  "paging": true,
			  "lengthChange": false,
			  "searching": true,
			  "ordering": true,
			  "info": true,
			  "autoWidth": true,
			  "responsive": true,
			});
			
		});
		
		$( document ).ready( function (){
			
			//$(".owl-carousel").owlCarousel();
			
			/* $('.datepicker').datepicker({
				startDate: '-3d'
			}); */
			
			var usrt = $("#usrtpe").val();
			if( usrt == 1){
				$('.agncy').removeClass("d-none");
				$('.usrsubtpe').addClass("d-none");
			} else{
				$('.agncy').addClass("d-none");
				$('.usrsubtpe').removeClass("d-none");
			}
				
			
			$("#usrtpe").on("change", function(){
				if($(this).val() == 1){
					$('#liprov').text('Store Registration Province');
					$('#lireg').text('Store Number');
					
					$('#u_licence_no').attr('placeholder', 'Enter Store Number');
					
					$('.agncy').removeClass("d-none");
					$('.usrsubtpe').addClass("d-none");
				} else{
					$('#liprov').text('Applicant License Province');
					$('#lireg').text('Applicant Licence No.');

					$('#u_licence_no').attr('placeholder', 'Enter Licence No.');

					$('.agncy').addClass("d-none");
					$('.usrsubtpe').removeClass("d-none");
				}
				
			});
			
			
			
		
		
			$(".megamenu").on("click", function(e) {
				e.stopPropagation();
			});
		
		
			$('.dropdown-submenu > a').on("click", function(e) {
				var submenu = $(this);
				$('.dropdown-submenu .dropdown-menu').removeClass('show');
				submenu.next('.dropdown-menu').addClass('show');
				e.stopPropagation();
			});

			$('.dropdown').on("hidden.bs.dropdown", function() {
				// hide any open menus when parent closes
				$('.dropdown-menu.show').removeClass('show');
			}); 
			
			// console.log("jQuery version:", $.fn.jquery); // Log jQuery version
        // console.log("Validation Plugin loaded:", typeof $.fn.validate !== "undefined"); 
			
		
			// Custom validator for strong password
			$.validator.addMethod("strongPassword", function (value, element) {
				return this.optional(element) || 
					/[A-Z]/.test(value) && // At least one uppercase letter
					/[a-z]/.test(value) && // At least one lowercase letter
					/\d/.test(value) &&    // At least one digit
					/[@$!%*?&#]/.test(value) && // At least one special character
					value.length >= 8;     // Minimum length of 8 characters
			}, "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.");
			
			// Custom validation method for phone numbers
			$.validator.addMethod("phoneUSCAN", function (value, element) {
				return this.optional(element) || /^\+?1?[-.\s]?(\(?\d{3}\)?)[-.\s]?\d{3}[-.\s]?\d{4}$/.test(value);
			}, "Please enter a valid phone number (e.g., (123) 456-7890 or 123-456-7890).");
			
			// Custom validation method for canada pincode
			$.validator.addMethod("validateCanadianPostalCode", function (value, element) {
				return this.optional(element) || /^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/.test(value);
			}, "Please enter a valid zipcode(e.g., M5A 1A1 )");
			

			// Custom validation method for email with .com or similar
			$.validator.addMethod("validEmail", function (value, element) {
				return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
			}, "Please enter a valid email address (e.g., user@example.com).");
			
			// Personal names: letters (including accented ones), spaces and the
			// punctuation real names actually contain - hyphens, apostrophes,
			// brackets and periods. Digits and other symbols are still rejected.
			$.validator.addMethod("alphaspace", function(value, element) {
				return this.optional(element) || /^[\p{L}\s'’\-().,]+$/u.test(value);
			}, "Letters, spaces and - ' ( ) . only.");



			$("#register-form").validate({
				rules: {
					u_usertype: {
						required: true
					},
					u_comp_name: {
						required: function () {
							return $("#usrtpe").val() == "1";
						}
					},
					u_usersubtype: {
						required: function () {
							return $("#usrtpe").val() == "2";
						}
					},
					u_fname: {
						required: true,
						alphaspace: true
					},
					u_lname: {
						required: true,
						alphaspace: true
					},
					u_l_provice: {
						required: true
					},
					u_licence_no: {
						required: true
					},
					u_email: {
						required: true,
						validEmail: true
					},
					u_phone: {
						required: true,
						phoneUSCAN: true
					},
					u_pincode: {
						validateCanadianPostalCode: true
					},
					password: {
						required: true,
						strongPassword: true
					},
					conf_password: {
						required: true,
						equalTo: "#mainpassword"
					}
				},
				messages: {
					u_usertype: "Please select user type.",
					u_usersubtype: "Please select Candidate type.",
					u_comp_name: "Store name is required.",
					
					u_fname:  {
						required: "First name  is required.",
						alphaspace: "Letters, spaces and - ' ( ) . only."
					},
					u_lname:  {
						required: "Last name  is required.",
						alphaspace: "Letters, spaces and - ' ( ) . only."
					},
					
					u_l_provice: "Province is required.",
					u_licence_no: "Licence number is required.",
					u_email: {
						required: "Email is required.",
						validEmail: "Please enter a valid email address ending with a proper domain (e.g., .com, .org)."
					},
					u_phone: {
						required: "Phone number is required.",
					},
					password: {
						required: "Password is required.",
						strongPassword: "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character."
					},
					conf_password: {
						required: "Please confirm your password.",
						equalTo: "Passwords do not match."
					}
				},
				submitHandler: function (form) {
					//alert("Form submitted successfully!");
					form.submit();
				}
			});
			
			$("#forgot-form").validate({
				rules: {
					
				},
				messages: {
					
				},
				submitHandler: function (form) {
					//alert("Form submitted successfully!");
					form.submit();
				}
			});
			
			$("#reset-form").validate({
				rules: {
					password: {
						required: true,
						strongPassword: true
					},
				},
				messages: {
					password: {
						required: "Password is required.",
						strongPassword: "Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character."
					},
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
			

