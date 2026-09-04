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
<?php /* pdfmake and its font file are 949 KB gzipped, and only the PDF export
   button uses them. Loading them lazily was tried and reverted: DataTables
   fixes its button list at init, so the tables had to wait for the download
   before drawing at all - the listings took twice as long to appear. With the
   Expires header now set in .htaccess these are fetched once a day rather than
   once a page, which is the cheaper half of the problem solved anyway. */ ?>
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
		// Shift form (postjobs add/edit): choosing a store fills the three
		// tick-box groups from that store's own defaults.
		//
		// Nothing here loads a store list any more. The form asks for the
		// store and nothing else - a store belongs to one employer, so the
		// server reads the employer off it - and every store is already an
		// option in the page, grouped under the employer that owns it.
		if ($('#p_store_id').length) {
			// The three tick-box groups a store can hold defaults for.
			var defaultGroups = ['p_skills', 'p_services', 'p_additional_details'];

			// Store (Location) is two dropdowns: the employer group on the left,
			// and only that group's stores on the right. The page arrives already
			// narrowed to the chosen store's group, so nothing here runs on load -
			// that is what leaves the edit form's saved tick-boxes alone.
			(function () {
				var $group = $('#p_store_group');
				var $store = $('#p_store_id');
				var raw    = $('#shift_store_options').text();
				var byGroup;

				if (!$group.length) { return; }

				try {
					byGroup = JSON.parse(raw);
				} catch (e) {
					// Without the list the group dropdown can only empty the store
					// one, which would be worse than leaving both alone.
					return;
				}

				$group.on('change', function () {
					var stores = byGroup[$(this).val()] || [];

					// Built as text nodes rather than html: a store name and a
					// company name are typed by hand and nobody vets a bracket
					// out of them.
					$store.empty().append($('<option>').val('').text('-- Select Store --'));

					$.each(stores, function (i, store) {
						$store.append(
							$('<option>').val(store.id).text(store.label)
								.attr('data-owner', store.owner)
								.attr('data-manager', store.manager)
						);
					});

					// Changing group clears the store, so `change` fires with an
					// empty value: the note goes back to its default sentence and
					// no defaults are copied. Untargeted on purpose - it reaches
					// both the handler below and select2's own, which is what
					// redraws the closed dropdown.
					$store.val('').trigger('change');
				});
			})();


			// Always by store: it is the only thing the form asks for.
			function fillDefaults(ask) {
				$.ajax({
					type: 'POST',
					url: '<?php echo base_url('sadmin/ajax_getstoredefaults'); ?>',
					data: ask,
					dataType: 'json',
					success: function (defaults) {

						$.each(defaultGroups, function (i, field) {
							var $group = $('#cbg_' + field);

							if (!$group.length) { return; }

							// Replace rather than add to what is ticked: the
							// admin asked for this store, so this store's
							// defaults are the answer, not the last one's.
							$group.find('input[type=checkbox]').prop('checked', false);

							$.each(defaults[field] || [], function (j, id) {
								$group.find('input[value="' + id + '"]').prop('checked', true);
							});

							$group.removeClass('border-danger');
						});
					}
				});
			}

			// Whoever manages the chosen store, under the field - the person the
			// shift will be arranged with, which is the one thing the picker
			// itself does not say. The store name is not repeated: it is the
			// option showing in the dropdown. Most stores have no manager
			// account, and those name the owner instead, so the line always says
			// who to expect to deal with.
			//
			// Both names travel on the option, so this needs no request - and are
			// built as text nodes rather than html, because they are somebody's
			// name and nobody vets an apostrophe out of it.
			function showStoreManager($option) {
				var $note = $('#store_owner_note');

				if (!$note.length) { return; }

				var manager = $option.data('manager');
				var owner = $option.data('owner');

				if (!$option.val()) {
					$note.text('The shift is posted for the employer that owns this store.');
				} else if (manager) {
					$note.empty().append('Managed by (', $('<strong>').text(manager), ')');
				} else if (owner) {
					$note.empty().append('Owned by (', $('<strong>').text(owner), ')');
				} else {
					$note.text('The shift is posted for the employer that owns this store.');
				}
			}

			// Picking a store fills the groups from what that store offers.
			// Bound to `change`, so it only ever runs when somebody chooses:
			// the edit screen loads holding the shift's own saved boxes, and
			// those are left exactly as they are.
			$('#p_store_id').on('change', function () {
				var storeId = $(this).val();

				showStoreManager($(this).find('option:selected'));

				if (!storeId) { return; }

				fillDefaults({ s_id: storeId });
			});
		}

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
			$("#city").html(data).trigger('change.select2');
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

	// ---- Employer type, on the back-office employer form -------------------
	//
	// The same three kinds public registration offers, and the same rules about
	// which fields each one is asked for - see the matching block in
	// front/footer.php. Kept in step with it deliberately: an administrator
	// adding an employer has to produce the same record the employer would have
	// produced by registering.
	$(function () {
		var kind = $('#emp_kind');

		if (!kind.length) {
			return;
		}

		// A field left marked `required` while hidden makes the browser refuse
		// to submit without ever showing why, so the attribute has to travel
		// with the visibility.
		function toggle(nodes, hide) {
			nodes.toggleClass('d-none', hide);

			nodes.find('input, select, textarea').addBack('input, select, textarea').each(function () {
				var field = $(this);

				if (field.data('wasRequired') === undefined) {
					field.data('wasRequired', field.prop('required'));
				}

				field.prop('required', hide ? false : field.data('wasRequired'));
			});
		}

		<?php /* The codes the Employer Type dropdown posts, taken from the same
		   config it is drawn from - so renumbering a kind moves this with it
		   rather than leaving a comparison that silently never matches. */ ?>
		<?php
		$kindCodeForRole = static function (int $role) {
		    foreach (config('AppSettings')->employerKinds as $code => $def) {
		        if ($def['filter']['u_emp_role'] === $role) {
		            return (string) $code;
		        }
		    }

		    return '';
		};
		?>
		var KIND_OWNER = '<?php echo $kindCodeForRole(1); ?>';
		var KIND_MANAGER = '<?php echo $kindCodeForRole(2); ?>';

		function apply() {
			var value = kind.val() || '';
			var isOwner = value === KIND_OWNER;
			var isManager = value === KIND_MANAGER;

			// Only a manager answers to a corporate group, and only a manager
			// picks one of that group's stores.
			toggle($('.grouponly'), !isManager);
			toggle($('.storepick'), !isManager);

			// Licence and address describe one location, and neither kind is
			// asked for one: an owner's belong to each store they add
			// afterwards, and a manager's to the store they picked.
			toggle($('.storeonly'), isOwner || isManager);

			// The name column is the account's own. A manager has none - the
			// store they picked already has a name.
			toggle($('.owneronly'), isManager);

			$('#compnamelbl').text(isOwner ? 'Corporate Group Name' : 'Store Name');
		}

		apply();
		kind.on('change', apply);

		// The store list belongs to the chosen group, so it is refilled
		// whenever that changes - and on load, because a form coming back from
		// a failed save still has the group and store it was posted with.
		var group = $('#u_parent_id');
		var storeSelect = $('#u_store_id');

		if (group.length && storeSelect.length) {
			function loadGroupStores(keepChosen) {
				var groupId = group.val();

				if (!groupId) {
					storeSelect.html('<option value="">-- Select Store --</option>').trigger('change.select2');

					return;
				}

				$.ajax({
					type: 'POST',
					url: '<?php echo base_url($settings[0]->s_frontpath . '/ajax_getstorelist'); ?>',
					data: { groupid: groupId, storeid: keepChosen ? $('#hstoreid').val() : '' },
					success: function (data) { storeSelect.html(data).trigger('change.select2'); }
				});
			}

			loadGroupStores(true);

			group.on('change', function () {
				// The store that was chosen belonged to the previous group.
				$('#hstoreid').val('');
				loadGroupStores(false);
			});
		}
	});

  $(function () {

	  // ---- Excel / PDF downloads, shared by every admin listing -------------
	  //
	  // jszip, pdfmake and buttons.html5 are already loaded above, so the
	  // downloads are pure configuration.

	  // The <title> is the site name on every admin page, so an export named
	  // after it would be the same file name on all eleven screens. The page
	  // heading is what the admin was just looking at.
	  var screenName = $.trim($('.content-header h1').first().text()).replace(/\s+/g, ' ') || 'Listing';

	  // Excel rejects : \ / ? * [ ] in a sheet name, and Windows rejects most
	  // of the same in a file name, so anything that is not a word character
	  // becomes a dash. The date is what makes yesterday's download tell
	  // itself apart from today's.
	  var exportStamp = new Date();
	  var exportFilename = screenName.replace(/[^\w]+/g, '-').replace(/^-+|-+$/g, '')
		  + '-' + exportStamp.getFullYear()
		  + '-' + ('0' + (exportStamp.getMonth() + 1)).slice(-2)
		  + '-' + ('0' + exportStamp.getDate()).slice(-2);

	  // Which columns belong in a download.
	  //
	  // Deliberately an index test rather than the ":visible" selector that
	  // reads so much better. Responsive hides a collapsed column by setting
	  // display:none on its <th> (dataTables.responsive.js, _setColumnVis),
	  // and DataTables resolves a bare ":visible" string as a jQuery test
	  // against those same <th> nodes - so ":visible" would quietly drop
	  // columns from the spreadsheet whenever the browser window was narrow.
	  // An index is the same at every window size.
	  //
	  // Column 0 is the primary key hidden by the columnDef below, and the
	  // last column holds the row's buttons; neither means anything in a
	  // spreadsheet. A photo column has nothing to give one either - the
	  // exporter strips the <img> and leaves an empty string - so it goes too.
	  function exportableColumns(lastIndex) {
		  return function (idx, data, node) {
			  if (idx === 0 || idx === lastIndex) {
				  return false;
			  }

			  return ! /\b(image|photo|logo)\b/i.test($(node).text());
		  };
	  }

	  function exportButtons(lastIndex) {
		  var columns = exportableColumns(lastIndex);

		  // Which columns the Column visibility menu may switch. Everything but
		  // column 0: that is the primary key the columnDefs below hide on every
		  // one of these lists, so offering it back is offering a column of row
		  // numbers nobody asked for. Indexes rather than a selector string, for
		  // the same reason the exports use them.
		  var switchable = [];

		  for (var i = 1; i <= lastIndex; i++) {
			  switchable.push(i);
		  }

		  return [
			  {
				  extend: 'excelHtml5',
				  text: '<i class="fas fa-file-excel"></i> Excel',
				  titleAttr: 'Download this list as an Excel file',
				  className: 'btn-success',
				  // No title: excelHtml5 writes one as a merged cell ABOVE the
				  // headings, which pushes the data down a row and stops Excel
				  // reading the sheet as a plain grid to sort or filter. The
				  // screen name goes on the sheet tab instead, where it costs
				  // nothing. Excel refuses [ ] * / \ ? : in a tab name and
				  // rejects the whole file past 31 characters.
				  title: null,
				  sheetName: screenName.replace(/[\[\]\*\/\\\?\:]/g, '').slice(0, 31),
				  filename: exportFilename,
				  exportOptions: { columns: columns }
			  },
			  {
				  extend: 'pdfHtml5',
				  text: '<i class="fas fa-file-pdf"></i> PDF',
				  titleAttr: 'Download this list as a PDF',
				  className: 'btn-danger',
				  title: screenName,
				  filename: exportFilename,
				  orientation: 'landscape',
				  pageSize: 'A4',
				  exportOptions: { columns: columns },
				  customize: function (doc) {
					  // Eight columns of names and e-mail addresses do not fit
					  // at pdfmake's 12pt default, and its automatic widths are
					  // sized to content, which lets one long address squeeze
					  // every other column to nothing. Equal shares of the page
					  // and a smaller face keep the whole row readable.
					  doc.defaultStyle.fontSize = 8;
					  doc.styles.tableHeader.fontSize = 9;

					  var table = doc.content[1].table;

					  if (table.body.length) {
						  table.widths = $.map(table.body[0], function () { return '*'; });
					  }

					  doc.content[1].layout = 'lightHorizontalLines';
				  }
			  },
			  {
				  extend: 'colvis',
				  columns: switchable
			  }
		  ];
	  }

	  // Every admin list shares this table. A page can set its own default sort
	  // with data-order-col / data-order-dir on the <table> element, rather than
	  // inheriting the hidden id column below.
	  var $adminTable = $("#example1");
	  var orderCol = $adminTable.data('order-col');
	  var orderDir = $adminTable.data('order-dir') || 'desc';
	  var defaultOrder = (orderCol === undefined || orderCol === '')
		  ? [[0, 'desc']]
		  : [[parseInt(orderCol, 10), orderDir]];

	  // A list the admin orders by hand - Shift For, with its up and down
	  // arrows. The rows arrive in the order somebody put them in, so there is
	  // nothing for the client to sort: an automatic sort on arrival would show
	  // one order while the arrows moved rows about in another, and a heading
	  // clicked later would do the same. Search and the downloads are untouched.
	  var manualOrder = $adminTable.data('manual-order') ? true : false;

	  // Read from the markup rather than hard-coded: the eleven screens that
	  // share this id have between four and ten columns.
	  var adminLastColumn = $adminTable.find('thead tr').first().find('th').length - 1;

	  $adminTable.DataTable({
		  columnDefs: [
            {
                targets: 0,       // Target the first column (index 0)
                visible: false,   // Make it invisible
                searchable: false // Optional: Disable searching on the hidden column
            },
            {
                // Every admin list ends with Status then Action. When the
                // responsive plugin runs out of width it drops columns from the
                // right, which took the row's buttons - and the pending/active
                // flag next to them - off the screen. These two are the point of
                // the row, so a wide data column folds into the expandable child
                // row ahead of them.
                targets: -1,
                responsivePriority: 1
            },
            {
                targets: -2,
                responsivePriority: 2
            }
        ],
       order: manualOrder ? [] : defaultOrder,
		  ordering: ! manualOrder,
		  "responsive": true, "lengthChange": false, "autoWidth": false,
		  scrollX: true,
			scrollCollapse: true,
		  "buttons": exportButtons(adminLastColumn)
		}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
	  
	  var $resTable = $("#res-table-ex");
	  var resLastColumn = $resTable.find('thead tr').first().find('th').length - 1;

	  $resTable.DataTable({
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
		  "buttons": exportButtons(resLastColumn)
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
	
	  
	/**
	 * The date and hours pickers, on every `.date` and `.timePicker` box
	 * inside `root` - the whole page when called with nothing.
	 *
	 * A function rather than run once inline, for the same reason as
	 * applySelect2 below: the admin's Add Shift form adds date-and-hours
	 * rows after load ("Add More"), and each needs its pickers. Safe on a
	 * box already dressed - bootstrap-datepicker leaves it alone, and
	 * daterangepicker replaces its own.
	 *
	 * Each hours box opens on its own value. This used to read the first
	 * box's value and open every box on it, which with one box on the page
	 * made no difference.
	 */
	window.applyShiftPickers = function (root) {
		var $root = $(root || document);

		$root.find('.date').datepicker({
			multidate: false,
			startDate: new Date(),
			minDate: 0, // Restrict past dates, allowing only today and future dates
			format: 'dd-mm-yyyy'
		});

		$root.find('.timePicker').each(function () {
			// Get prepopulated value from the input
			var prePopulatedValue = $(this).val();

			// Split the prepopulated value into start and end times
			var startTime = prePopulatedValue ? prePopulatedValue.split(' - ')[0] : moment().format('HH:mm');
			var endTime = prePopulatedValue ? prePopulatedValue.split(' - ')[1] : moment().add(1, 'hours').format('HH:mm');

			$(this).daterangepicker({
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
	};

	window.applyShiftPickers();

	

		
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
	  
	  /**
	   * Dress every dropdown in the back office in select2.
	   *
	   * This used to read `$('.select2').select2()`, which matched the two
	   * selects on the Resources form and nothing else - the plugin was loaded
	   * on every page and used on one.
	   *
	   * `width: '100%'` and nothing else: it writes a percentage onto the
	   * container, which resolves whenever the field is shown. 'resolve' and
	   * 'style' read the width at the moment they run, and the employer form
	   * hides half its rows until a type is chosen - those were measured at 0
	   * and stayed collapsed once revealed.
	   *
	   * Safe to call twice: select2 leaves an initialised element alone.
	   */
	  window.applySelect2 = function (root) {
		  if (!$.fn.select2) { return; }

		  $(root || document).find('select').each(function () {
			  var $select = $(this);

			  // DataTables owns its length menu and rebuilds it; leave that be.
			  if ($select.closest('.dataTables_wrapper').length || $select.is('[hidden], [data-no-select2]')) { return; }

			  $select.select2({ width: '100%', theme: 'bootstrap4', minimumResultsForSearch: 8 });
		  });
	  };

	  window.applySelect2();
	  
	  
	  
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

<?= view('partials/phone_input_script') ?>
<?= view('partials/rate_input_script') ?>
<?= view('partials/select2_focus_script') ?>
<?= view('partials/shift_more_rows_script') ?>
<?= view('partials/shift_date_filter_script') ?>
</body>
</html>
