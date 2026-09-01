<!-- End Navigation -->
<div class="clearfix"></div>

<!-- Title Header Start -->
<section class="inner-header-title" style="background-image:url(<?php echo base_url('assets/front/img/account.jpg'); ?>);">
    <div class="container">
        <h1>Applicant Account</h1>
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
                                <label>User Id (Mobile / Email)</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Mobile / Email"
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
                                <input type="hidden" name="redirurlbk"
                                    value="<?php echo $_SERVER['HTTP_REFERER']; ?>" />
                                <input type="submit" name="loginSubmit" class="btn btn-primary theme-bg full-width"
                                    value="Login">
                            </div>
                            <div class="register-account text-center">
                                Forgot Password <a
                                    class="theme-cl" href="#">Click here</a> to reset.</a>
                            </div>

                        </form>
                    </div>


                    <div id="register" class="tab-pane fade">
                        <form id="register-form" class="form" action="<?php echo base_url($clink.'/register');?>"
                            method="post">
                            <div class="form-group">
                                <label>User Id (Email / Mobile)</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Email / Mobile"
                                        name="userid" id="userid" required>
                                    <i class="theme-cl ti-user"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>First Name</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your First Name"
                                        name="fname" id="fname" required>
                                    <i class="theme-cl ti-home"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Last Name"
                                        name="lname" id="lname" required>
                                    <i class="theme-cl ti-home"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <div class="input-with-icon">
                                    <input type="email" class="form-control" placeholder="Enter Your Email" name="email"
                                        id="email" required>
                                    <i class="theme-cl ti-email"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Mobile</label>
                                <div class="input-with-icon">
                                    <input type="text" class="form-control" placeholder="Enter Your Mobile" name="mobile"
                                        id="mobile" required
                                        maxlength="<?= PHONE_LENGTH ?>" inputmode="numeric"
                                        pattern="[0-9]{<?= PHONE_LENGTH ?>}" data-phone-input>
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
                                <input type="hidden" name="jobtype" value="<?php if($ujobtype){ echo $ujobtype; }else{ echo 0;}?>"/>
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

<!-- ============================ Call To Action ================================== -->
<section class="theme-bg call-to-act-wrap">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">

                <div class="call-to-act">
                    <div class="call-to-act-head">
                        <h3>Want to Become a Success Employers / Employee ?</h3>
                        <span>We'll help you to grow your career and growth.</span>
                    </div>
                   
                </div>

            </div>
        </div>
    </div>
</section>
<!-- ============================ Call To Action End ================================== -->

<!-- ============================ Before Footer ================================== -->
<div class="before-footer">
    <div class="container">
        <div class="row">

            <div class="col-md-6 col-sm-6">
                <div class="jb4-form-fields">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email address">
                        <span class="input-group-btn">
                            <button class="btn theme-bg" type="submit"><span
                                    class="fa fa-paper-plane-o"></span></button>
                        </span>
                    </div>
                </div>
            </div>

           <!--  <div class="col-md-6 col-sm-6 hill">
                <ul class="job stock-facts">
                <li><span>2744</span></br>Jobs Posted</li>
                    <li><span>2365</span></br>Candidates</li>
                    <li><span>2021</span></br>Agencies</li>
                </ul>
            </div> -->

        </div>
    </div>
</div>
<!-- ============================ Before Footer ================================== -->