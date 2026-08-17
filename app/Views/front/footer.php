    <!-- Footer -->
    <footer id="footer" class="footer-area">
      <div class="wz-footer">
        <div class="wz-footer-bottom">
          <div class="wz-social">
            <a href="#" aria-label="Facebook"><i class="lni-facebook-filled"></i></a>
            <a href="https://x.com/reliefshifts" target="_blank" rel="noopener" aria-label="X"><i class="lni-twitter-filled"></i></a>
            <a href="https://www.instagram.com/reliefshifts?igsh=ZGwydWl5NTg0OXln" target="_blank" rel="noopener" aria-label="Instagram"><i class="lni-instagram-filled"></i></a>
          </div>
          <?php if (! empty($settings[0]->s_email)) { ?>
            <p class="wz-footer-email">
              <a href="mailto:<?php echo esc($settings[0]->s_email); ?>"><?php echo esc($settings[0]->s_email); ?></a>
            </p>
          <?php } ?>
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

    <!-- select2, before the page script below that initialises it -->
    <script src="<?php echo base_url('assets/front/plugins/select2/js/select2.full.min.js'); ?>"></script>

    <script src="<?php echo base_url('assets/front/assets/js/theme.js'); ?>"></script>

    <script>
      /**
       * Dress every dropdown on the site in select2.
       *
       * `width: '100%'` and nothing else: it writes a percentage onto the
       * container, which resolves correctly whenever the field is shown. The
       * other two options ('resolve' and 'style') read the element's width at
       * the moment they run, and half the dropdowns on the registration form
       * start inside a `d-none` row - they were measured at 0 and stayed
       * collapsed at about a sixth of their column once revealed.
       *
       * Safe to call more than once: select2 leaves an already-initialised
       * element alone rather than stacking a second widget on it.
       */
      function applySelect2(root) {
        if (!$.fn.select2) { return; }

        $(root || document).find('select').each(function () {
          var $select = $(this);

          // DataTables builds and re-builds its own length menu; leave it to
          // manage its markup, and leave the hidden helpers alone.
          if ($select.closest('.dataTables_wrapper').length || $select.is('[hidden], [data-no-select2]')) { return; }

          $select.select2({ width: '100%', minimumResultsForSearch: 8 });
        });
      }

      $(document).ready(function () {
        applySelect2();

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
          // change.select2 rather than change: it tells select2 to re-read the
          // options it was just handed, without firing the handlers bound to this
          // select - one of which is the request that produced this data.
          success: function (data) { $('#city').html(data).trigger('change.select2'); }
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

        // Registration asks for one of three things, and each wants a
        // different set of fields:
        //
        //   Owner     - one login, many stores: a person, not a location, so
        //               no licence or address. They add each store afterwards
        //               from My Stores.
        //   Manager   - runs one of a group's existing stores: the group they
        //               answer to, and which of its stores. No address - the
        //               store they pick already has one.
        //   Applicant - unchanged: their own licence and address.
        //
        // The value is the code the database stores. Which of them is an
        // employer is not guessed from that number: the option carries
        // data-employer, written from the same config the save reads.
        function regType() {
          return $('#usrtpe').val() || '';
        }

        <?php
        /* The dropdown value that stands for each employer role, read from the
           same config the form and the save use. Renumbering a kind there
           moves this with it. */
        $codeForRole = static function (int $role) use ($registerTypes) {
            foreach (($registerTypes ?? []) as $code => $def) {
                if ($def['empRole'] === $role) {
                    return (string) $code;
                }
            }

            return '';
        };
        ?>
        var EMPLOYER_OWNER = '<?php echo $codeForRole(1); ?>';
        var EMPLOYER_MANAGER = '<?php echo $codeForRole(2); ?>';

        var isOwner = function () {
          return $('#usrtpe option:selected').data('employer') === 1;
        };
        var isMultiStore = function () { return regType() === EMPLOYER_OWNER; };
        var needsGroup = function () { return regType() === EMPLOYER_MANAGER; };

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

          // Store name / corporate group name, and the applicant's own type.
          // A manager names no store: they pick one that already exists.
          toggleRows($('.agncy'), !owner || needsGroup());
          toggleRows($('.usrsubtpe'), owner);

          // Only a manager answers to a corporate group, and only a manager
          // then picks which of its stores they run.
          toggleRows($('.grouponly'), !needsGroup());
          toggleRows($('.storepick'), !needsGroup());

          // Licence and address describe a location: asked of a single-store
          // owner and of an applicant. A multi-store owner has none yet, and a
          // manager's belong to the store they chose.
          toggleRows($('.storeonly'), isMultiStore() || needsGroup());

          if (owner) {
            $('#compnamelbl').text(isMultiStore() ? 'Corporate Group Name' : 'Store Name');
            $('#u_comp_name').attr(
              'placeholder',
              isMultiStore() ? 'Enter Your Corporate Group Name' : 'Enter Your Store Name'
            );
            $('#websitelbl').contents().first().replaceWith(
              isMultiStore() ? 'Corporate Group Website ' : 'Store Website '
            );
            $('#liprov').text('Store Registration Province');
            $('#lireg').text('Store Number');
            $('#u_licence_no').attr('placeholder', 'Enter Store Number');
          } else {
            $('#liprov').text('Applicant Licence Province');
            $('#lireg').text('Applicant Licence No.');
            $('#u_licence_no').attr('placeholder', 'Enter Licence No.');
          }
        }

        // The stores of the chosen corporate group, the same way the province
        // dropdown fills the city one. Asking with no group selected would
        // return the "no stores" option, so clear it back to the placeholder
        // instead and leave the request unsent.
        function getgroupstores(val) {
          if (!val) {
            $('#u_store_id').html('<option value="">-- Select Store --</option>').trigger('change.select2');

            return;
          }

          $.ajax({
            type: 'POST',
            url: '<?php echo base_url($settings[0]->s_frontpath . '/ajax_getstorelist'); ?>',
            data: { groupid: val, storeid: $('#hstoreid').val() },
            success: function (data) { $('#u_store_id').html(data).trigger('change.select2'); }
          });
        }

        // A failed submit comes back with the group still chosen, so refill the
        // list before the user sees it rather than making them pick twice.
        $('#u_parent_id').on('change', function () {
          // Only what they just chose is theirs to keep; the previous store
          // belonged to a different group.
          $('#hstoreid').val('');
          getgroupstores($(this).val());
        });

        applyRegType();

        if ($('#u_parent_id').val()) {
          getgroupstores($('#u_parent_id').val());
        }

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

        // A mobile number is stored as ten bare digits - no country code,
        // brackets or dashes - which is what PHONE_PATTERN enforces on the
        // server. The field itself refuses anything else as it is typed
        // (see partials/phone_input_script), so this is the backstop for a
        // short number rather than a badly formatted one.
        $.validator.addMethod('phoneUSCAN', function (value, element) {
          return this.optional(element) || /^[0-9]{<?= PHONE_LENGTH ?>}$/.test(value);
        }, 'Please enter a <?= PHONE_LENGTH ?> digit mobile number (digits only, e.g. 9053047303).');

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
            // A manager names no store - they pick one that already exists.
            u_comp_name: { required: function () { return isOwner() && !needsGroup(); } },
            u_parent_id: { required: needsGroup },
            u_store_id: { required: needsGroup },
            u_usersubtype: { required: function () { return !isOwner(); } },
            u_fname: { required: true, alphaspace: true },
            u_lname: { required: true, alphaspace: true },
            // Not asked of a multi-store owner, nor of a manager - see
            // applyRegType above.
            u_l_provice: { required: function () { return !isMultiStore() && !needsGroup(); } },
            u_licence_no: { required: function () { return !isMultiStore() && !needsGroup(); } },
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
            u_parent_id: 'Please choose the corporate group you belong to.',
            u_store_id: 'Please choose the store you run.',
            u_fname: { required: 'First name  is required.', alphaspace: "Letters, spaces and - ' ( ) . only." },
            u_lname: { required: 'Last name  is required.', alphaspace: "Letters, spaces and - ' ( ) . only." },
            u_l_provice: 'Province is required.',
            u_licence_no: 'Licence number is required.',
            u_email: {
              required: 'Email is required.',
              validEmail: 'Please enter a valid email address ending with a proper domain (e.g., .com, .org).'
            },
            u_phone: {
              required: 'Mobile number is required.',
              phoneUSCAN: 'Please enter a <?= PHONE_LENGTH ?> digit mobile number (digits only, e.g. 9053047303).'
            },
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

    <?= view('partials/phone_input_script') ?>

  </body>
</html>
