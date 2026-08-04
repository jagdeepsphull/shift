<!-- End Navigation -->
<div class="clearfix"></div>

<!-- Title Header Start -->
<section class="inner-header-title" style="background-image:url(<?php echo base_url('assets/front/img/account.jpg'); ?>);">
    <div class="container">
        <h1>Agency Account</h1>
    </div>
</section>
<div class="clearfix"></div>
<!-- Title Header End -->
<?php  
						
						if(session()->getFlashdata('error_msg')){echo session()->getFlashdata('error_msg');}
					?>
<!-- Tab Section Start -->
<section class="tab-sec gray">
    <div class="container">
        <div class="col-lg-8 col-md-8 col-sm-12 col-lg-offset-2 col-md-offset-2">
            <div class="new-logwrap">

                <ul class="nav modern-tabs nav-tabs theme-bg" id="simple-design-tab">
                    <li class="active"><a href="#login">Login</a></li>
                    <li><a href="#register">Register</a></li>
                </ul>

                <div class="tab-content">

                    <div id="login" class="tab-pane fade in active">
                        <form id="login-form" class="form" action="" method="post">
                            <div class="form-group">
                                <label>User Id (RA ID)</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your RA ID"
                                        name="username" id="username" required>
                                    <i class="theme-cl ti-user"></i>
                                </div>
                            </div>


                            <div class="form-group">
                                <label>Password</label>
                                <div class="input-with-icon">
                                    <input type="password" class="form-control" placeholder="Enter Your Password"
                                        name="password" id="password" required>
                                    <i class="theme-cl ti-lock"></i>
                                </div>
                            </div>


                            <div class="form-groups">
                                <input type="submit" name="loginSubmit" class="btn btn-primary theme-bg full-width"
                                    value="Login">
                            </div>

                        </form>
                    </div>


                    <div id="register" class="tab-pane fade">
                        <form id="register-form" class="form" action="<?php echo base_url($clink.'/register');?>"
                            method="post">
                            <div class="form-group">
                                <label>User Id (RA ID)</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your RA ID"
                                        name="u_userid" id="u_userid" required>
                                    <i class="theme-cl ti-user"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Agency Name</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Company Name"
                                        name="u_a_comp_name" id="u_a_comp_name" required>
                                    <i class="theme-cl ti-home"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <div class="input-with-icon">
                                    <input type="email" class="form-control" placeholder="Enter Your Email"
                                        name="u_a_email" id="u_a_email" required>
                                    <i class="theme-cl ti-email"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Mobile</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Mobile"
                                        name="u_a_cp_mobile" name="u_a_cp_mobile" required>
                                    <i class="theme-cl ti-email"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <div class="input-with-icon">
                                    <input type="password" class="form-control" placeholder="Enter Your Password"
                                        name="password" id="password" required>
                                    <i class="theme-cl ti-lock"></i>
                                </div>
                            </div>


                            <div class="register-account text-center">
                                By hitting the <span class="theme-cl">"Register"</span> button, you agree to the <a
                                    class="theme-cl" href="#">Terms conditions</a> and <a class="theme-cl"
                                    href="#">Privacy Policy</a>
                            </div>

                            <div class="form-groups">
                                <input type="submit" name="signupSubmit" class="btn btn-primary theme-bg full-width"
                                    value="Register yourself">
                            </div>


                        </form>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>
<!-- Tab section End -->
