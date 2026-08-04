/*************************************
@@File: Job Stock  Template Custom Js

All custom js files contents are below
**************************************
* 00. Loader
* 01. Company Brand Carousel
* 02. Client Story testimonial
* 03. Bootstrap wysihtml5 editor
* 04 Grid Slider
* 05 Grid Slider 2
* 06. Tab Js
* 07. Add field Script
* 08 Add Field
* 09 Background Image
* 10 City Select
* 11 Styles
**************************************/

(function($){
"use strict";

	//Loader
	$(window).on('load', function() {
		$(".Loader").fadeOut("slow");;
	});
	
	$(window).on('load', function() {
		$('.stock-facts li span').counterUp({
			delay: 100,
			time: 800
		});
	});
	 

	/* $("#plugin-css").setAttribute('disabled', false);
	$("#all-css").setAttribute('disabled', false);
	$("#plugin-css").removeAttribute('disabled');
	$("#all-css").removeAttribute('disabled'); */

	
	/*--- Client Story testimonial --*/
	$("#client-testimonial-slider").owlCarousel({
		items:3,
		itemsDesktop:[1199,3],
		itemsDesktopSmall:[979,2],
		itemsTablet:[768,1],
		pagination: false,
		navigation:false,
		navigationText:["",""],
		autoPlay:true
	});
	
	
	
	/*---Tab Js --*/
	$("#simple-design-tab a").on('click', function(e){
		e.preventDefault();
		$(this).tab('show');
	});
	
	/*-----Add field Script------*/
	$('.extra-field-box').each(function() {
    var $wrapp = $('.multi-box', this);
    $(".add-field", $(this)).on('click', function() {
        $('.dublicat-box:first-child', $wrapp).clone(true).appendTo($wrapp).find('input').val('').focus();
    });
    $('.dublicat-box .remove-field', $wrapp).on('click', function() {
        if ($('.dublicat-box', $wrapp).length > 1)
            $(this).parent('.dublicat-box').remove();
		});
	});
	
	//   Background image ------------------
		var a = $(".bg");
		a.each(function (a) {
			if ($(this).attr("data-bg")) $(this).css("background-image", "url(" + $(this).data("bg") + ")");
		});
		
	
	
	
	


	$('.sliding').click(function(event) { alert(222);
		value = $('.slidme').css('right') === '262px' ? 0 : '262px';
		  $('.slidme').animate({
			  right: value
		  });
	});


	$(".review").click(function(){
		var pid = this.id;
		var spid = pid.split('_');
		pid = spid[1];
		var base_url = $('#base').val();
		var shtml;
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"front/ajax_reviews",
			  data: {pid:pid},
			  dataType: 'html',
			  success: function(data){
					shtml = data.split('[s]');;
			  		$("#reviewtb").html(shtml[1]);
			  		$("#pjid").val(shtml[0]);
			   },
				error: function() {
					alert('Error occured');
				}

		}); 

		
	});


	$("#p_country").change(function(){
		var cid;
		var base_url = $('#base').val();
		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		var newli='<option value=""> - Choose State - </option>';
		cid = $(this).val();
		
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"agency/ajax_states",
			  data: {cid:cid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  //alert('data');
				//  alert(data);
				  $.each(data,function(index, value){
						  newli +='<option value="'+value.id+'">'+value.name+'</option>';
					  
					});
				$("#p_state").html(newli);
				$("#fspin").html('');
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});




	$("#u_a_country").change(function(){
		var cid;
		var base_url = $('#base').val();
		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		var newli='<option value=""> - Choose State - </option>';
		cid = $(this).val();
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"agency/ajax_states",
			  data: {cid:cid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  //alert('data');
				//  alert(data);
				  $.each(data,function(index, value){
						  newli +='<option value="'+value.id+'">'+value.name+'</option>';
					  
					});
				$("#u_a_state").html(newli);
				$("#fspin").html('');
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});


	
	$("#u_s_tcountry").change(function(){
		var cid;
		var base_url = $('#base').val();
		$("#tfspin").html('<i class="fa fa-spinner fa-spin"></i>');
		var newli='<option value=""> - Choose State - </option>';
		cid = $(this).val();
		
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"candidate/ajax_states",
			  data: {cid:cid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  //alert('data');
				//  alert(data);
				  $.each(data,function(index, value){
						  newli +='<option value="'+value.id+'">'+value.name+'</option>';
					  
					});
				$("#u_s_tstate").html(newli);
				$("#tfspin").html('');
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});

	$("#u_s_pcountry").change(function(){
		var cid;
		var base_url = $('#base').val();
		$("#pfspin").html('<i class="fa fa-spinner fa-spin"></i>');
		var newli='<option value=""> - Choose State - </option>';
		cid = $(this).val();
		
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"candidate/ajax_states",
			  data: {cid:cid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  //alert('data');
				//  alert(data);
				  $.each(data,function(index, value){
						  newli +='<option value="'+value.id+'">'+value.name+'</option>';
					  
					});
				$("#u_s_pstate").html(newli);
				$("#pfspin").html('');
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});

	/* $("#opendiv").click(function(){
		alert(2332);
	}); */

	$('.opendiv').click(function(event) {


		$('.vertxt').text($('.vertxt').text() === 'Verify >' ? 'Verify <' : 'Verify >');

		var value = $('#leftpopup').css('left') === '-300px' ? 0 : '-300px';
		
		  $('#leftpopup').animate({
			left: value,
			position:'absolute'
		  });
	});

	$(".clkme").click(function(){
		var id = this.id;
		if(id=='m1'){
			if($("#p1").is( ":visible",true )){
				$("#p2,#p3,#p4,#p5").slideUp();
				$("#p1").slideUp();
			}else{
				$("#p2,#p3,#p4,#p5").slideUp();
				$("#p1").slideDown();
			} 
			
			
			
			
		}
	});

	$("#m1").click(function(){ 
		
		if($("#footpopup").is( ":visible",true )){
			$("#footpopup").slideUp();
		}else{
			$("#footpopup").slideDown();
		} 
		
		
		
		
	});
 

	$("#submitverify").click(function(){
		
		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		
		var stu_name = $('#stu_name').val();
		var stu_contactno = $("#stu_contactno").val();
		var base_url = $('#base').val();
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"front/ajax_verify_request",
			  data: {stu_name:stu_name,stu_contactno:stu_contactno},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  /* alert(data); */
				  if(data==1){
					$("#fspin").html('<div class="bg-success" style="color:#ffffff;padding:0 5px;">Your request has been submitted. Team will contact you soon.</div>');
				  }else if(data==2){
					$("#fspin").html('<div class="bg-danger" style="color:#ffffff;padding:0 5px;">Your have already submitted request.</div>');
				  }else{
					$("#fspin").html('<div class="bg-warning" style="color:#ffffff;padding:0 5px;">Something went wrong. Please try again</div>');
				  }
				
				
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});


	$("#submitreview").click(function(){
		
		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		
		var stu_review = $('#stu_review').val();
		var pjid = $('#pjid').val();
		var base_url = $('#base').val();
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"front/ajax_save_review",
			  data: {stu_review:stu_review,pjid:pjid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  /* alert(data); */
				  if(data==1){
					$("#fspin").html('<div class="bg-success" style="color:#ffffff;padding:0 5px;">Your review has been submitted.</div>');
				  }else if(data==2){
					$("#fspin").html('<div class="bg-danger" style="color:#ffffff;padding:0 5px;">Your have already submitted Review.</div>');
				  }else if(data==3){
					$("#fspin").html('<div class="bg-danger" style="color:#ffffff;padding:0 5px;">Your have to be logged in to submitted Review.</div>');
				  }else{
					$("#fspin").html('<div class="bg-warning" style="color:#ffffff;padding:0 5px;">Something went wrong. Please try again</div>');
				  }
				
				
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});



	$("a[data-target=#myModal]").click(function(ev) {
		ev.preventDefault();
		var target = $(this).attr("href");
	
		// load the url and show modal on success
		$("#myModal .modal-body").load(target, function() { 
			 $("#myModal").modal("show"); 
		});
	});


	$("#getintouch_submit").click(function(){
		
		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		
		var getintouch_name = $('#getintouch_name').val();
		var getintouch_email = $("#getintouch_email").val();
		var getintouch_msg = $("#getintouch_msg").val();
		var pj_id = $("#pj_id").val();
		var base_url = $('#base').val();
		var captcha = $('#captcha').val();
		
		if(getintouch_name=="" || getintouch_email==""){
			$("#fspin").html('<span class="bg-success" style="color:#ffffff;padding:0 5px;">Please enter Name and valid Email</span>');
		}else{

			$.ajax({
				type: "POST",
				url:  base_url+"front/ajax_getintouch",
				data: {name:getintouch_name,email:getintouch_email,msg:getintouch_msg,pj_id:pj_id,captcha:captcha},
				dataType: 'JSON',
				success: function(data){
					//console.log(data);
					/* alert(data); */
					if(data==1){
						$('#getintouch_name').val('');
						$("#getintouch_email").val('');
						$("#getintouch_msg").val('');
						$('#captcha').val('');

						$("#fspin").html('<div class="bg-success" style="color:#ffffff;padding:0 5px;">Your request has been submitted. Team will contact you soon.</div>');
					}else if(data==2){
						$("#fspin").html('<div class="bg-danger" style="color:#ffffff;padding:0 5px;">Your have already submitted request.</div>');
					}else if(data==3){
						$("#fspin").html('<div class="bg-danger" style="color:#ffffff;padding:0 5px;">You must submit the word that appears in the image.</div>');
					}else{
						$("#fspin").html('<div class="bg-warning" style="color:#ffffff;padding:0 5px;">Something went wrong. Please try again</div>');
					}
					
					
				
				},
					error: function() {
						alert('Error occured');
					}

			});
		}

		
	});
	
	$(".shortlistbtn").click(function(){
		var uid,jobid;
		var base_url;

		$("#fspin").html('<i class="fa fa-spinner fa-spin"></i>');
		
		uid = this.id;
		base_url = $('#base').val();
		jobid = $("#job_"+uid).val();
		
		$.ajax({
			  type: "POST",
			  url:  base_url+"agency/ajax_shortlist",
			  data: {uid:uid,jobid:jobid},
			  dataType: 'JSON',
			  success: function(data){
				  //console.log(data);
				  /* alert(data); */
				  if(data==1){
					$("#short_"+uid).html('<span class="btn theme-btn btn-shortlist" style="background:#1DB9AA;">Invieted</span>');
				  }else if(data==2){
					alert("You have crossed number of invitation allocated to you.");
				  }
				
				/* $("#p_state").html(newli); */
				$("#fspin").html('');
			  
			   },
				error: function() {
					alert('Error occured');
				}

		});

		
	});

	// --------- Job List --------
	var options = {
		url: "./assets/js/resources/joblist.json",

		getValue: "name",

		list: {
			match: {
				enabled: true
			}
		}
	};
	
	// --------- Companies --------
	var options = {
		url: "./assets/js/resources/companies.json",

		getValue: "name",

		list: {
			match: {
				enabled: true
			}
		}
	};

	
	
	// --------- Location --------
	var options = {
		url: "./assets/js/resources/location.json",

		getValue: "name",

		list: {
			match: {
				enabled: true
			}
		}
	};

	
		
	// Styles ------------------
    function csselem() {
        $(".slideshow-container .slideshow-item").css({
            height: $(".slideshow-container").outerHeight(true)
        });
        $(".slider-container .slider-item").css({
            height: $(".slider-container").outerHeight(true)
        });
    }
    csselem();	
			
	})(jQuery);