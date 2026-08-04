	
    <!-- Contact Section Start -->
    <section id="contact" class="section-padding  section-gap">    
      <div class="container">
        <div class="section-header text-center">          
          <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Contact Us</h2>
          <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="row contact-form-area wow fadeInUp" data-wow-delay="0.3s">   
          <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 align-items-right">
              <h3 class="footer-titel">Contact:</h3>
              <p align="left">
			  <ul class="address">
                 <li>
                  <a href="mailto:info@reliefshifts.com"><i class="lni-envelope"></i> Email: info@reliefshifts.com</a>
                </li>
				<li>
                  <i class="lni-phone"></i> +1 (905) 304-7303</a>
                </li>
              </ul>
			  </p>
			  <div class="social-icon">
                  <a class="facebook" href="#"><i class="lni-facebook-filled"></i></a>
                  <a class="twitter" href="https://x.com/pickashift"target="_blank"><i class="lni-twitter-filled"></i></a>
                  <a class="instagram" href="https://www.instagram.com/pickashift/" target="_blank"><i class="lni-instagram-filled"></i></a>
                </div>
            </div>
		  <div class="col-lg-7 col-md-12 col-sm-12">
            <div class="contact-block">
              <form id="contactForm" class="form" action="" method="post">
				<div class="row">
					<div class="col-md-12">
						<?php  echo session()->getFlashdata('error_msg'); ?>
					</div>
				</div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <input type="text" class="form-control" id="name" name="name" placeholder="Name" required data-error="Please enter your name">
                      <div class="help-block with-errors"></div>
                    </div>                                 
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <input type="text" placeholder="Email" id="email" class="form-control" name="email" required data-error="Please enter your email">
                      <div class="help-block with-errors"></div>
                    </div> 
                  </div>
                  <div class="col-md-12">
                    <div class="form-group">
                      <input type="text" placeholder="Subject" id="msg_subject" name="msg_subject" class="form-control" required data-error="Please enter your subject">
                      <div class="help-block with-errors"></div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group"> 
                      <textarea class="form-control" id="message" name="message" placeholder="Your Message" rows="7" data-error="Write your message" required></textarea>
                      <div class="help-block with-errors"></div>
                    </div>
					<div class="col-md-4">
					<div class="form-group">
					<label for="captcha">Verification Code</label><img class="ml-2 mb-3" src="<?php echo site_url('front/test_cap');  ?>" />
					<input type="text" value="" class="form-control" id="captcha" placeholder="Verification Code" size="6" name="captcha">
					</div>
					</div>
                    <div class="submit-button text-left">
                      <button class="btn btn-common disabled" name="contactsub" id="form-submit" type="submit" value="submit" style="pointer-events: all; cursor: pointer;">Send Message</button>
                      <div id="msgSubmit" class="h3 text-center hidden"></div> 
                      <div class="clearfix"></div> 
                    </div>
                  </div>
                </div>            
              </form>
            </div>
          </div>
          
        </div>
      </div> 
    </section>
    <!-- Contact Section End -->
