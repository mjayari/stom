<?php 
	$magzee_hs_service		= get_theme_mod('hide_show_service','on'); 
	$magzee_service_title	= get_theme_mod('service_title'); 
	$magzee_service_desc	= get_theme_mod('service_description');
	if($magzee_hs_service == 'on') :
?>
	<section id="unique-service" class="service-magzee">
		<div class="container">
			<div class="row text-center padding-top-60 padding-bottom-30">
				<div class="col-sm-12">
				<?php if ($magzee_service_title) : ?>
					<h2 class="section-heading wow zoomIn"><?php echo wp_filter_post_kses($magzee_service_title); ?></h2>
				<?php endif; ?>
				
				<?php if ($magzee_service_desc) : ?>
					<p class="section-description"><?php echo esc_html($magzee_service_desc); ?></p>
				<?php endif; ?>
				</div>
			</div>
			<div class="row service-row text-center padding-bottom-60">
				<?php 
					for($service =1; $service<4; $service++) 
					{
						if( get_theme_mod('service-page'.$service)) 
						{
							$service_query = new WP_query('page_id='.get_theme_mod('service-page'.$service,true));
							while( $service_query->have_posts() ) 
							{ 
								$service_query->the_post();
								$image = wp_get_attachment_url( get_post_thumbnail_id($post->ID));
								$img_arr[] = $image;
								$id_arr[] = $post->ID;
							}    
						}
					}
				?>
				<?php if(!empty($id_arr))
				{ ?>
				<?php 
					$i=1;
					foreach($id_arr as $id)
					{ 
						$title	= get_the_title( $id ); 
						$post	= get_post($id); 
						
						$content = $post->post_content;
						$content = apply_filters('the_content', $content);
						$content = str_replace(']]>', ']]>', $content);
				?> 
				<div class="col-md-4 col-sm-4 margin-bottom-30">
					<div class="service-box wow fadeInUp">
						<div class="inner">
							<div class="service-icon-box">
								<?php
									$image 			= wp_get_attachment_url( get_post_thumbnail_id($post->ID));
									$thumbnail_id 	= get_post_thumbnail_id( $post->ID );
									$alt			= get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
									if( !empty($image) ) { ?>
										<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($alt); ?>" >
									<?php } else { ?>
									<?php if( get_post_meta(get_the_ID(),'icons', true ) ): ?>
										<i class="specia-icon fa <?php echo esc_attr(get_post_meta( get_the_ID(),'icons', true)); ?>"></i>
								<?php
									endif;
									} 
								?>
							</div>
							<div class="service-title"><a href="<?php echo esc_url( get_permalink() ); ?>"> <?php echo esc_html($title); ?> </a></div>
							<div class="service-description">
								<p> <?php echo $content; ?> </p>
								<a href="<?php echo esc_url( get_post_meta( get_the_ID(),'service_links', true) ); ?>" class="more-link"><?php _e('Read More', 'magzee'); ?></a>
							</div>
						</div>
					</div>
				</div>
				<?php } wp_reset_postdata(); ?>
				<?php $i++; 
				}  ?>
			</div>
		</div>
	</section>
<div class="clearfix"></div>
<?php endif; ?>

