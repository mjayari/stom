<?php 
	$magzee_hs_slider	= get_theme_mod('hide_show_slider','on');
	if( $magzee_hs_slider == 'on' ): 
?>

<?php 
	for($slide =1; $slide<4; $slide++) 
	{
		if( get_theme_mod('slider-page'.$slide)) 
		{
			$slidequery = new WP_query('page_id='.get_theme_mod('slider-page'.$slide,true));
			while( $slidequery->have_posts() ) 
			{ 
				$slidequery->the_post();
				$image = wp_get_attachment_url( get_post_thumbnail_id($post->ID));
				$img_arr[] = $image;
				$id_arr[] = $post->ID;
			}    
		}
	}
?>

<?php if(!empty($id_arr))
{ ?>
	<section id="slider-section" class="slider-wrapper slider-section-five">
		<div class="main-slider arrows-small arrows-transparent">
			
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
			
			<div class="item">
			
				<?php
					$image 			= wp_get_attachment_url( get_post_thumbnail_id($post->ID));
					$thumbnail_id 	= get_post_thumbnail_id( $post->ID );
					$alt 			= get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
				?>
				<img src="<?php echo esc_url($image);?>" alt="<?php echo esc_attr($alt); ?>">
				<div class="specia-slider <?php echo get_post_meta( get_the_ID(),'slider_caption_align', true); ?>" style="background: rgba(0,0,0,0.3);">
					<div class="specia-table">
                        <div class="specia-table-cell">
                            <div class="container">                                
                                <div class="specia-content">
                                	<h6 data-animation="fadeInRight" data-delay="90ms"><?php echo wp_kses_post($title); ?><span></span></h6>

                                	<?php $slider_subtitle 	= get_post_meta( get_the_ID(),'slider_subtitle', true);
											if(!empty($slider_subtitle)):
									?>
										<h1 data-animation="fadeInUp" data-delay="350ms"><?php echo wp_kses_post($slider_subtitle); ?><span></span></h1>
										<span data-animation="fadeInUp" data-delay="650ms">
									<?php endif; ?>
									
									<?php echo $content; ?>
									</span>

									<?php if( get_post_meta(get_the_ID(),'slidebutton', true ) ): ?>
										<a data-animation="fadeInUp" data-delay="850ms" href="<?php echo esc_url( get_post_meta( get_the_ID(),'slidebuttonlink', true) ); ?>" class="bt-primary bt-effect-1 specia-btn-1"><?php echo esc_html( get_post_meta( get_the_ID(),'slidebutton', true) ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		
			<?php $i++; } ?>  
			
		</div>
	</section>

	<div class="clearfix"></div>

<?php } wp_reset_postdata(); endif; ?>