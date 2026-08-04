<!-- Start post Area -->
<section id="browsejobs" class="about-area section-padding">
	<div class="container">
		<div class="section-header text-center"  style="margin-bottom: 0px;">
          <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s" style="padding-bottom: 0px;">RECENT POSTINGS</h2>
		 
        </div>
		<div class="row justify-content-center d-flex">
			<div class="col-lg-11 post-list">
			 <!-- /.card-header -->
              <div class="card-body table-responsive1 p-2" style="1height: 300px;">
                <table id="joblistH" class="table table-bordered table-striped datatablecss">
                  <thead>
                  <tr>
                    <th>Shift ID</th>
                    <th>Shift Requested For</th>
                    <th>Province</th>
                    <th>City</th>
                    <th>Shift Date</th>
                    <th>Shift Time</th>
                    <th>Details</th>
                  </tr>
                  </thead>
				  <?php if($jobs){?>
                  <tbody>
				  <?php foreach($jobs as $job){?>
                  <tr>
                    <td><?php echo $job->p_job_title; ?></td>
                    <td><?php echo getShiftForName($job->p_shift_for); ?></td>
                    <td><?php echo getProvinceName($job->p_province); ?></td>
                    <td><?php echo getCityName($job->p_city); ?></td>
                    <td data-order="<?php echo shiftDateSortValue($job); ?>"><?php echo dateFormat($job->p_dates); ?></td>
                    <td><?php echo $job->p_shift_time; ?></td>
                    <td><a href="<?php echo base_url('front/job_detail/'.$job->p_id)?>">Learn More</a></td>
                    
                  </tr>
                 
                  
				  <?php } ?>
                  </tbody>
				  <?php } ?>
                  <tfoot>
                  
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body -->
			
			
			
			

			</div>
			
			
		</div>
	</div>	
</section>
<!-- End post Area -->
			
<!-- Services Section Start -->
    <section id="services" class="section-padding">
      <div class="container">
        <div class="section-header text-center">
          <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">What Makes Us Stand Out</h2>
		  <p align="justify">With over 20 years of Experience in Retail Pharmacy Patient Care, We Provide Solution Oriented Care for Better Patient Outcomes. We have Qualified Professionals with Excellent Communication Skills and Clinical Knowledge. Time and again Our Professionals are involved in Clinical Discussions with Peers to remain Up-to-Date with Current guidelines.<p><br>
		  <h4 align="left">Some of the additional Services we provide include:</h4>
		  <p align="justify">Referral services provided for Business Succession Planning , Website Design & Development, Social Media Marketing, Inventory Management and Self-Certification, Complementary In-store Pharmacy Resource Hub, Business Process Management, Advertising and Marketing, Cyber security, IT management, Business Insurance Needs.</p><br>
          <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="row">
          <!-- Services item -->
          <div class="col-md-6 col-lg-4 col-xs-12">
            <div class="services-item wow fadeInRight" data-wow-delay="0.3s">
              <div class="icon">
                <i class="lni-cog"></i>
              </div>
              <div class="services-content">
                <h3><a href="#"> Easily accessible, Always Ready  </a></h3>
                <p align="justify">We can be reached with a Phone call, Email or through our portal. Easily accessible, Always Ready (Once you Contact Us, We will understand your specific situation and recommend the best possible staffing solution. We help you grow your business with patient focused initiatives with highly qualified professionals.</p>
              </div>
            </div>
          </div>
          <!-- Services item -->
          <div class="col-md-6 col-lg-4 col-xs-12">
            <div class="services-item wow fadeInRight" data-wow-delay="0.6s">
              <div class="icon">
                <i class="lni-stats-up"></i>
              </div>
              <div class="services-content">
                <h3><a href="#">Our Process </a></h3>
                <p align="justify">All account Creation Requests, Whether it is from The Applicant or from The Employer, will be verified before the Account Activation & Approval. We welcome new graduates to gain experience with our world class training program. Our current portfolio includes several satisfied clients including Shoppers Drug Mart, Pharmasave, Pharmachoice, IDA, Guardian and numerous Independent Pharmacies.</p>
              </div>
            </div>
          </div>
          <!-- Services item -->
          <div class="col-md-6 col-lg-4 col-xs-12">
            <div class="services-item wow fadeInRight" data-wow-delay="0.9s">
              <div class="icon">
                <i class="lni-users"></i>
              </div>
              <div class="services-content">
                <h3><a href="#">Our Mission</a></h3>
                <p align="justify">Our mission is to become one stop shop for staffing needs of the healthcare industry. We provide complementary Resources to enhance your workflow and time management. Please visit the Resource section on this website for more details.</p>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </section>
    <!-- Services Section End -->
<?php /*?>
    <!-- About Section start -->
    <div class="about-area section-padding bg-gray">
      <div class="container">
        <div class="row">
          <div class="col-lg-6 col-md-12 col-xs-12 info">
            <div class="about-wrapper wow fadeInLeft" data-wow-delay="0.3s">
              <div>
                <div class="site-heading">
                  <h2 class="section-title">Your Trusted Pharmacy Staffing Partner</h2>
                </div>
                <div class="content">
                  <p>
                    At Pickashift, we believe that exceptional patient care begins with exceptional pharmacy professionals. Since our founding, we’ve dedicated ourselves to connecting healthcare facilities, pharmacies, and clinics with top-tier pharmacy talent that not only meets qualifications but exceeds expectations. We understand that the right staffing isn’t just about filling positions—it’s about finding the perfect fit that aligns with your values, culture, and mission
                  </p>
                  <a href="#" class="btn btn-common mt-3">Read More</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 col-xs-12 wow fadeInRight" data-wow-delay="0.3s">
            <img class="img-fluid" src="<?php echo base_url('assets/front/assets/img/about/img-1.png') ?>" alt="" >
          </div>
        </div>
      </div>
    </div>
    <!-- About Section End -->

    <!-- Features Section Start -->
    <section id="features" class="section-padding">
      <div class="container">
        
        <div class="row">
          <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
            <div class="content-left">
              <div class="box-item wow fadeInLeft" data-wow-delay="0.3s">
                <span class="icon">
                  <i class="lni-rocket"></i>
                </span>
                <div class="text">
                  <h4>Our Mission </h4>
                  <p>To revolutionize pharmacy staffing by delivering personalized solutions that empower pharmacies to provide seamless, patient-centered care. </p>
                </div>
              </div>
              
              
            </div>
          </div>
          <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
            <div class="show-box wow fadeInUp" data-wow-delay="0.3s">
              <img src="<?php echo base_url('assets/front/assets/img/feature/intro-mobile.png') ?>" alt="">
            </div>
          </div>
          <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
            <div class="content-right">
              <div class="box-item wow fadeInRight" data-wow-delay="0.3s">
                <span class="icon">
                  <i class="lni-laptop-phone"></i>
                </span>
                <div class="text">
                  <h4>Years Of Experience </h4>
                  <p>At PharmaProRelief, we’ve spent years on building a reputation for excellence in the pharmacy staffing industry, Over the past decade, we’ve honed our process, fine-tuning every step to ensure that we provide the most reliable, efficient, and high-quality staffing solutions in the industry. </p>
                </div>
              </div>
              
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Features Section End -->   

	<?php */?>

    <!-- Call To Action Section Start -->
    <?php /* <section id="cta" class="section-padding">
      <div class="container">
		<div class="section-header text-center">
          <h4 class="section-title wow fadeInDown" data-wow-delay="0.3s">Apply Here</h4>
		  
          <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="row">
          <div class="col-lg-5 col-md-5 col-xs-12 wow fadeInLeft bg-gray" data-wow-delay="0.3s">           
            <div class="cta-text">
              <h4> For Pharmacist</h4>
              <p>Our relief hiring service provides long term and short term pharmacy shifts </p>
            </br><a href="<?php echo base_url('pharmacist') ; ?>" class="btn btn-common">Register Now</a>
            </div>
          </div>
          <div class="col-lg-1 col-md-1 col-xs-12 wow fadeInLeft" data-wow-delay="0.3s">           
            
          </div>
          <div class="col-lg-5 col-md-5 col-xs-12 text-right wow fadeInRight bg-gray" data-wow-delay="0.3s">
			<div class="cta-text">
              <h4> For Owner </h4>
              <p>We provide pre-screened, highly qualified pharmacists for temporary/permanent roles. </p>
            </br><a href="<?php echo base_url('owner') ; ?>" class="btn btn-common">Register Now</a>
			</div>
          </div>
        </div>
      </div>
    </section> */ ?>
    <!-- Call To Action Section Start -->
