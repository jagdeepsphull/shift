<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="#" type="image/png">
    <title>Pick A Shift | <?php echo $pageinfo['title']; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.min.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/slick.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/select2.min.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/all.min.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/fancybox.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/aos.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/style.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/customefont.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/responsive.css');?>">
</head>

<body>

    <!-- Preloader -->
    <div id="preloader">
        <div id="ctn-preloader" class="ctn-preloader">
            <div class="animation-preloader">
                <div class="spinner"></div>
                <div class="txt-loading">
                    <span data-text-preloader="L" class="letters-loading">
                        L
                    </span>
                    <span data-text-preloader="O" class="letters-loading">
                        O
                    </span>
                    <span data-text-preloader="A" class="letters-loading">
                        A
                    </span>
                    <span data-text-preloader="D" class="letters-loading">
                        D
                    </span>
                    <span data-text-preloader="I" class="letters-loading">
                        I
                    </span>
                    <span data-text-preloader="N" class="letters-loading">
                        N
                    </span>
                    <span data-text-preloader="G" class="letters-loading">
                        G
                    </span>
                </div>
            </div>
            <div class="loader-section section-left"></div>
            <div class="loader-section section-right"></div>
        </div>
    </div>



    <!-- Section Login -->
    <section>
        <div class="login_register-wrapper pd-t-120">
            <div class="container">
                <div class="login_register-inner-wrapper">
                    <div class="row">
                        <div class="mx-auto col-lg-6 col-md-12 col-sm-12">
                            <div class="login-wrapper">
                                <h2 class="text-uppercase">Login Account</h2>
                                <p>Enter username and password to log on:</p>
								<?php  
									if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}
								?>
                                <form class="login" id="loginForm" action="<?php echo base_url('sadmin/login');?>" method="post" required>
                                    <input type="text" name="username" class="input-text input-field" placeholder="User Id">
                                    <input type="password" name="password" class="input-text input-field" placeholder="Password" required>
											
									<label for="captcha">Verification Code</label><img class="ml-2 mb-3" style="max-width:40%; margin-left:5px;" src="<?php echo site_url('front/test_cap');  ?>" />
									<input required type="text" value="" class="form-control" id="captcha" size="6" name="captcha">
												
									
                                    <div class="submit-remember d-flex align-items-center flex-wrap mt-2">
                                        <button type="submit" name="loginSubmit" value="loginSubmit" class="theme-btn position-relative overflow-hidden text-uppercase">Login</button>
                                        
                                    </div>
                                    <!--<a class="lost-password position-relative" href="#">Lost Your Password?</a> -->
                                </form>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>






    <!-- scripts-->
    <script src="<?php echo base_url('assets/js/jquery.min.js');?>"></script>
    <script src="<?php echo base_url('assets/js/slick.min.js');?>"></script>
    <script src="<?php echo base_url('assets/js/bootstrap.bundle.min.js');?>"></script>
    <script src="<?php echo base_url('assets/js/bootstrap-input-spinner.js');?>"></script>
    <script src="<?php echo base_url('assets/js/select2.min.js');?>"></script>
    <script src="<?php echo base_url('assets/js/fancybox.js');?>"></script>
    <script src="<?php echo base_url('assets/js/aos.js');?>"></script>
    <script src="<?php echo base_url('assets/js/custom-script.js');?>"></script>
	<!-- jquery-validation -->
	<script src="<?php echo base_url('assets/admin/plugins/jquery-validation/jquery.validate.min.js');?>"></script>
	<script src="<?php echo base_url('assets/admin/plugins/jquery-validation/additional-methods.min.js');?>"></script>
	<script>


	$(function () {
	  $('#loginForm').validate({
		rules: {
		  username: {
			required: true,
			minlength: 3
		  },
		  password: {
			required: true,
			minlength: 3
		  },
		  terms: {
			required: true
		  },
		},
		messages: {
		  username: {
			required: "Please enter a user id",
			minlength: "Your password must be at least 3 characters long"
		  },
		  password: {
			required: "Please provide a password",
			minlength: "Your password must be at least 5 characters long"
		  },
		},
		errorElement: 'span',
		errorPlacement: function (error, element) {
		  error.addClass('invalid-feedback');
		  element.closest('.form-group').append(error);
		},
		highlight: function (element, errorClass, validClass) {
		  $(element).addClass('is-invalid');
		},
		unhighlight: function (element, errorClass, validClass) {
		  $(element).removeClass('is-invalid');
		}
	  });
	});
	</script>
    <!-- svg sprite-->
    <div class="svg-wrapper">
        <svg>

            <symbol id="icon-view" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </symbol>

            <symbol id="icon-compare" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 3 21 3 21 8"></polyline>
                <line x1="4" y1="20" x2="21" y2="3"></line>
                <polyline points="21 16 21 21 16 21"></polyline>
                <line x1="15" y1="15" x2="21" y2="21"></line>
                <line x1="4" y1="4" x2="9" y2="9"></line>
            </symbol>

            <symbol id="icon-pen" viewBox="0 0 24 24">
                <path d="M12 21h9c0.552 0 1-0.448 1-1s-0.448-1-1-1h-9c-0.552 0-1 0.448-1 1s0.448 1 1 1zM15.793 2.793l-12.5 12.5c-0.122 0.121-0.217 0.28-0.263 0.465l-1 4c-0.039 0.15-0.042 0.318 0 0.485 0.134 0.536 0.677 0.862 1.213 0.728l4-1c0.167-0.041 0.33-0.129 0.465-0.263l12.5-12.5c0.609-0.609 0.914-1.41 0.914-2.207s-0.305-1.598-0.914-2.207-1.411-0.915-2.208-0.915-1.598 0.305-2.207 0.914zM17.207 4.207c0.219-0.219 0.504-0.328 0.793-0.328s0.574 0.109 0.793 0.328 0.328 0.504 0.328 0.793-0.109 0.574-0.328 0.793l-12.304 12.304-2.115 0.529 0.529-2.115z"></path>
            </symbol>

            <symbol id="icon-list" viewBox="0 0 20 20">
                <path d="M14.4 9h-5.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1h5.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1zM16.4 14h-7.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1h7.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1zM8.6 6h7.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1h-7.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1zM5.4 9h-1.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1h1.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1zM5.4 14h-1.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1h1.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1zM5.4 4h-1.8c-0.552 0-0.6 0.447-0.6 1s0.048 1 0.6 1h1.8c0.552 0 0.6-0.447 0.6-1s-0.048-1-0.6-1z"></path>
            </symbol>

            <symbol id="icon-grid" viewBox="0 0 32 32">
                <path d="M8 12h4v-4h-4v4zM14 12h4v-4h-4v4zM20 8v4h4v-4h-4zM8 18h4v-4h-4v4zM14 18h4v-4h-4v4zM20 18h4v-4h-4v4zM8 24h4v-4h-4v4zM14 24h4v-4h-4v4zM20 24h4v-4h-4v4z"></path>
            </symbol>


        </svg>
    </div>


</body>

</html>
