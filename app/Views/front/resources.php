<!-- Services Section Start -->
    <section id="services" class="section-padding  section-gap">
      <div class="container">
    <div class="container mt-5">
	
	
		
        
        <div class="row">
            <div class="col-12">
				<?php if($headermenu_parent_only){?>
					<div class="row">
						<?php foreach($headermenu_parent_only as $hp_only){ ?>
												
						<div class="col-md-3 mb-3">
                            <div class="card">
                                <div class="card-header bg-info" id="heading<?php echo $hp_only->m_id ; ?>">
                                    <h3 class="mb-0">
                                        <a class="btn btn-info text-uppercase" href="<?php echo $hp_only->m_link ; ?>" target="_blank" ><?php echo $hp_only->m_name ; ?></a>
                                    </h3>
                                </div>
							</div>
						</div>
						
							
						<?php } ?>
					</div>
				<?php }?>
			</div>
        </div>
		<hr>
		<div class="row section-padding">
            <div class="col-12">
				  <div class="dropdown-divider bg-info-pink"></div>
			</div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="accordion" id="dynamicAccordion">
					<?php 
						if($headermenu_parent) {
						?>
					<div class="row">
					<?php
						foreach($headermenu_parent as $category){
							$subcategories = custom()->get_where('headermenu',array('m_parentid'=>$category->m_id,'m_status'=>1));
					?>
						<!-- Item 1 -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-header bg-info-pink" id="heading<?php echo $category->m_id ; ?>">
                                    <h3 class="mb-0">
                                        <button class="btn btn-link text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $category->m_id ; ?>" aria-expanded="false" aria-controls="collapse<?php echo $category->m_id ; ?>">
                                            <?php echo $category->m_name ; ?>
                                        </button>
                                    </h3>
                                </div>
                                <div id="collapse<?php echo $category->m_id ; ?>" class="collapse" aria-labelledby="heading<?php echo $category->m_id ; ?>" data-bs-parent="#dynamicAccordion">
                                    <div class="card-body">
								<?php  if($subcategories) { 
										foreach($subcategories as $sub){ 			
									?>
                                        <a class="dropdown-item" href="<?php echo $sub->m_link ; ?>" target="_blank" ><?php echo $sub->m_name ; ?></a>
										
								<?php } 
									} ?>
								   </div>
								
                                </div>
                            </div>
                        </div>
					<?php }?>
					</div>
					<?php
						}?>

                    <!-- Add more rows and items dynamically as needed -->
                </div>
            </div>
        </div>
    </div>		
		
		
		
		
      </div>
    </section>
    <!-- Services Section End -->