    <!-- Footer -->
    <footer id="footer" class="footer-area">
      <div class="wz-footer">
        <div class="wz-footer-grid">

          <div>
            <h5>About us</h5>
            <div class="wz-footer-brand">
              <img src="<?php echo base_url('assets/front/assets/img/logo.png'); ?>" alt="<?php echo esc($settings[0]->s_sitename); ?>">
            </div>
            <address>
              <?php echo nl2br(esc($settings[0]->s_companyaddress)); ?>
              <?php if (! empty($settings[0]->s_email)) { ?>
                <br><a href="mailto:<?php echo esc($settings[0]->s_email); ?>"><?php echo esc($settings[0]->s_email); ?></a>
              <?php } ?>
              <?php if (! empty($settings[0]->s_contactno)) { ?>
                <br><a href="tel:<?php echo esc($settings[0]->s_contactno); ?>"><?php echo esc($settings[0]->s_contactno); ?></a>
              <?php } ?>
            </address>
          </div>

          <div>
            <h5>Shifts</h5>
            <ul>
              <li><a href="<?php echo base_url(); ?>#browsejobs">Browse Shifts</a></li>
              <li><a href="<?php echo base_url('front/login'); ?>">Post a Shift</a></li>
              <li><a href="<?php echo base_url('front/login'); ?>">Apply for a Shift</a></li>
              <li><a href="<?php echo base_url('resources'); ?>">Resources</a></li>
            </ul>
          </div>

          <div>
            <h5>Company</h5>
            <ul>
              <li><a href="<?php echo base_url('contact'); ?>">Contact Us</a></li>
              <li><a href="<?php echo base_url('terms_conditions'); ?>">Terms &amp; Conditions</a></li>
              <li><a href="<?php echo base_url('privacy_policy'); ?>">Privacy Policy</a></li>
            </ul>
          </div>

          <div>
            <h5>Account</h5>
            <ul>
              <?php if (! empty($isUserLoggedIn)) { ?>
                <li><a href="<?php echo $myaccountLink; ?>">Dashboard</a></li>
                <li><a href="<?php echo $logoutLink; ?>">Logout</a></li>
              <?php } else { ?>
                <li><a href="<?php echo base_url('front/login'); ?>">Login</a></li>
                <li><a href="<?php echo base_url('front/signup'); ?>">Create an account</a></li>
                <li><a href="<?php echo base_url('front/forgot_password'); ?>">Forgot password</a></li>
              <?php } ?>
            </ul>
          </div>

        </div>

        <div class="wz-footer-bottom">
          <div class="wz-social">
            <a href="#" aria-label="Facebook"><i class="lni-facebook-filled"></i></a>
            <a href="https://x.com/reliefshifts" target="_blank" rel="noopener" aria-label="X"><i class="lni-twitter-filled"></i></a>
            <a href="https://www.instagram.com/reliefshifts?igsh=ZGwydWl5NTg0OXln" target="_blank" rel="noopener" aria-label="Instagram"><i class="lni-instagram-filled"></i></a>
          </div>
          <p class="wz-copy">
            &copy; <?php echo date('Y'); ?>
            <a rel="nofollow" href="<?php echo base_url(); ?>"><?php echo esc($settings[0]->s_sitename); ?></a>
            All rights reserved
          </p>
        </div>
      </div>
    </footer>

    <a href="#" class="back-to-top" aria-label="Back to top"><i class="lni-arrow-up"></i></a>

    <!-- jQuery is still required: DataTables, jQuery Validate and the page
         scripts below all depend on it. Bootstrap 5 itself does not. -->
    <script src="<?php echo base_url('assets/front/assets/js/jquery-min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.validate.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/bootstrap5.bundle.min.js'); ?>"></script>

    <script src="<?php echo base_url('assets/front/assets/js/owl.carousel.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/wow.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/assets/js/jquery.easing.min.js'); ?>"></script>

    <!-- daterangepicker (needs moment): the shift-date range in the hero search -->
    <script src="<?php echo base_url('assets/front/plugins/moment/moment.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/plugins/daterangepicker/daterangepicker.js'); ?>"></script>

    <!-- DataTables (Bootstrap 5 build) -->
    <script src="<?php echo base_url('assets/front/plugins/datatables/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/plugins/datatables-responsive/js/dataTables.responsive.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/front/plugins/datatables-responsive/js/responsive.bootstrap5.min.js'); ?>"></script>

    <script src="<?php echo base_url('assets/front/assets/js/theme.js'); ?>"></script>

    <script>
      $(document).ready(function () {
        // Province -> city, used by the sign-up form.
        if ($('#hprovince').length) {
          getpcities($('#hprovince').val());
        }
      });

      function getpcities(val) {
        var ciid = $('#hcity').val();
        $.ajax({
          type: 'POST',
          url: '<?php echo base_url($settings[0]->s_frontpath . '/ajax_getcitylist'); ?>',
          data: { statecode: val, ciid: ciid },
          success: function (data) { $('#city').html(data); }
        });
      }

      $(function () {
        // The applicant's applied-shift table, where it appears on a front page.
        if ($('#joblist').length) {
          $('#joblist').DataTable({
            paging: true, lengthChange: false, searching: true,
            ordering: true, info: true, autoWidth: false, responsive: true
          });
        }

        // Registration asks for one of four things, and each wants a
        // different set of fields (change request B4):
        //
        //   manager          - runs one store for a pharmacy group: the
        //                      store's licence and address, plus the group
        //                      they answer to, which is required.
        //   owner_multi      - one login, many stores: a person, not a
        //                      location, so no licence or address. They add
        //                      each store afterwards from My Stores.
        //   owner_individual - one store, answering to nobody: the same
        //                      fields as a manager, minus the group.
        //   applicant        - unchanged: their own licence and address.
        function regType() {
          return $('#usrtpe').val() || '';
        }

        var STORE_SIDE = ['manager', 'owner_multi', 'owner_individual'];

        var isOwner = function () { return $.inArray(regType(), STORE_SIDE) !== -1; };
        var isMultiStore = function () { return regType() === 'owner_multi'; };
        var needsGroup = function () { return regType() === 'manager'; };

        // A field left marked `required` while hidden makes the browser refuse
        // to submit without ever showing why, so the attribute has to travel
        // with the visibility.
        function toggleRows(rows, hide) {
          rows.toggleClass('d-none', hide);

          rows.find('input, select, textarea').each(function () {
            var field = $(this);

            // Remember what the markup asked for the first time round.
            if (field.data('wasRequired') === undefined) {
              field.data('wasRequired', field.prop('required'));
            }

            field.prop('required', hide ? false : field.data('wasRequired'));
          });
        }

        function applyRegType() {
          var owner = isOwner();

          // Store name / pharmacy name, and the applicant's own type.
          toggleRows($('.agncy'), !owner);
          toggleRows($('.usrsubtpe'), owner);

          // Only a manager answers to a pharmacy group.
          toggleRows($('.grouponly'), !needsGroup());

          // Licence and address describe a location: asked of a single-store
          // owner and of an applicant, never of a multi-store owner.
          toggleRows($('.storeonly'), isMultiStore());

          if (owner) {
            $('#compnamelbl').text(isMultiStore() ? 'Pharmacy Name' : 'Store Name');
            $('#u_comp_name').attr(
              'placeholder',
              isMultiStore() ? 'Enter Your Pharmacy Name' : 'Enter Your Store Name'
            );
            $('#liprov').text('Store Registration Province');
            $('#lireg').text('Store Number');
            $('#u_licence_no').attr('placeholder', 'Enter Store Number');
          } else {
            $('#liprov').text('Applicant License Province');
            $('#lireg').text('Applicant Licence No.');
            $('#u_licence_no').attr('placeholder', 'Enter Licence No.');
          }
        }

        applyRegType();
        $('#usrtpe').on('change', applyRegType);

        // ------------------------------------------------ form validation ---

        $.validator.addMethod('strongPassword', function (value, element) {
          return this.optional(element) ||
            /[A-Z]/.test(value) &&
            /[a-z]/.test(value) &&
            /\d/.test(value) &&
            /[@$!%*?&#]/.test(value) &&
            value.length >= 8;
        }, 'Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.');

        $.validator.addMethod('phoneUSCAN', function (value, element) {
          return this.optional(element) || /^\+?1?[-.\s]?(\(?\d{3}\)?)[-.\s]?\d{3}[-.\s]?\d{4}$/.test(value);
        }, 'Please enter a valid phone number (e.g., (123) 456-7890 or 123-456-7890).');

        $.validator.addMethod('validateCanadianPostalCode', function (value, element) {
          return this.optional(element) || /^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/.test(value);
        }, 'Please enter a valid zipcode(e.g., M5A 1A1 )');

        $.validator.addMethod('validEmail', function (value, element) {
          return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
        }, 'Please enter a valid email address (e.g., user@example.com).');

        // Personal names: letters (including accented ones), spaces and the
        // punctuation real names actually contain - hyphens, apostrophes,
        // brackets and periods. Digits and other symbols are still rejected.
        $.validator.addMethod('alphaspace', function (value, element) {
          return this.optional(element) || /^[\p{L}\s'’\-().,]+$/u.test(value);
        }, "Letters, spaces and - ' ( ) . only.");

        $('#register-form').validate({
          rules: {
            reg_type: { required: true },
            u_comp_name: { required: isOwner },
            u_parent_id: { required: needsGroup },
            u_usersubtype: { required: function () { return !isOwner(); } },
            u_fname: { required: true, alphaspace: true },
            u_lname: { required: true, alphaspace: true },
            // Not asked of a multi-store owner - see applyRegType above.
            u_l_provice: { required: function () { return !isMultiStore(); } },
            u_licence_no: { required: function () { return !isMultiStore(); } },
            u_email: { required: true, validEmail: true },
            u_phone: { required: true, phoneUSCAN: true },
            u_pincode: { validateCanadianPostalCode: true },
            password: { required: true, strongPassword: true },
            conf_password: { required: true, equalTo: '#mainpassword' }
          },
          messages: {
            reg_type: 'Please select user type.',
            u_usersubtype: 'Please select Candidate type.',
            u_comp_name: 'This name is required.',
            u_parent_id: 'Please choose the pharmacy group you belong to.',
            u_fname: { required: 'First name  is required.', alphaspace: "Letters, spaces and - ' ( ) . only." },
            u_lname: { required: 'Last name  is required.', alphaspace: "Letters, spaces and - ' ( ) . only." },
            u_l_provice: 'Province is required.',
            u_licence_no: 'Licence number is required.',
            u_email: {
              required: 'Email is required.',
              validEmail: 'Please enter a valid email address ending with a proper domain (e.g., .com, .org).'
            },
            u_phone: { required: 'Phone number is required.' },
            password: {
              required: 'Password is required.',
              strongPassword: 'Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.'
            },
            conf_password: { required: 'Please confirm your password.', equalTo: 'Passwords do not match.' }
          },
          submitHandler: function (form) { form.submit(); }
        });

        $('#forgot-form').validate({ submitHandler: function (form) { form.submit(); } });

        $('#reset-form').validate({
          rules: { password: { required: true, strongPassword: true } },
          messages: {
            password: {
              required: 'Password is required.',
              strongPassword: 'Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.'
            }
          },
          submitHandler: function (form) { form.submit(); }
        });
      });
    </script>

  </body>
</html>
