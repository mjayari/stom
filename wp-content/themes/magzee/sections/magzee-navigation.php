<!-- Header Widget Info -->
    <div class="header-widget-info d-lg-block d-none">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="header-wrapper">
                        <div class="brand-logo">
                            <div class="logo">
                            	<?php
		                        if(has_custom_logo()) {   
		                            the_custom_logo();
		                        }
		                        else { ?>
		                        	<a href="<?php echo esc_url(home_url( '/' )); ?>" class="navbar-brand">
		                        		<?php echo esc_html(get_bloginfo('name')); ?>
		                        	</a>
		                        <?php }
		                        $magzee_description = get_bloginfo( 'description');
		                        if ($magzee_description) : ?>
		                            <p class="site-description"><?php echo esc_html($magzee_description); ?></p>
		                        <?php endif; ?>
                            </div>
                        </div>
						<?php 
						$magzee_hs_nav_contact_info	= get_theme_mod('hs_nav_contact_info','1');
						$magzee_nav_ct_info1_icon	= get_theme_mod('nav_ct_info1_icon','fa-clock-o');
						$magzee_nav_ct_info1_ttl	= get_theme_mod('nav_ct_info1_ttl');
						$magzee_nav_ct_info1_subttl	= get_theme_mod('nav_ct_info1_subttl');
						$magzee_nav_ct_info2_icon	= get_theme_mod('nav_ct_info2_icon','fa-phone');
						$magzee_nav_ct_info2_ttl	= get_theme_mod('nav_ct_info2_ttl');
						$magzee_nav_ct_info2_subttl	= get_theme_mod('nav_ct_info2_subttl');
						$magzee_nav_ct_info3_icon	= get_theme_mod('nav_ct_info3_icon','fa-clock-o');
						$magzee_nav_ct_info3_ttl	= get_theme_mod('nav_ct_info3_ttl');
						$magzee_nav_ct_info3_subttl	= get_theme_mod('nav_ct_info3_subttl');
						if($magzee_hs_nav_contact_info =='1'){
						?>
                        <div class="header-right">
                            <div class="header-info">
							    <div class="header-carousel">
									<?php if(!empty($magzee_nav_ct_info1_icon) || !empty($magzee_nav_ct_info1_ttl) || !empty($magzee_nav_ct_info1_subttl)): ?>
										<div class="widget widget_contact widget_first">
											<div class="contact-area">
												<?php if(!empty($magzee_nav_ct_info1_icon)): ?>
													<div class="contact-icon"><i class="fa <?php echo esc_attr($magzee_nav_ct_info1_icon); ?>"></i></div>
												<?php endif; ?>
												
												<?php if(!empty($magzee_nav_ct_info1_ttl) || !empty($magzee_nav_ct_info1_subttl)): ?>
													<a href="" class="contact-info">
														<span class="text"><?php echo esc_html($magzee_nav_ct_info1_ttl); ?></span>
														<span class="title"><?php echo esc_html($magzee_nav_ct_info1_subttl); ?></span>
													</a>
												<?php endif; ?>	
											</div>
										</div>
									<?php endif; ?>
							       
								   <?php if(!empty($magzee_nav_ct_info2_icon) || !empty($magzee_nav_ct_info2_ttl) || !empty($magzee_nav_ct_info2_subttl)): ?>
										<div class="widget widget_contact widget_second">
											<div class="contact-area">
												<?php if(!empty($magzee_nav_ct_info2_icon)): ?>
													<div class="contact-icon"><i class="fa <?php echo esc_attr($magzee_nav_ct_info2_icon); ?>"></i></div>
												<?php endif; ?>
												
												<?php if(!empty($magzee_nav_ct_info2_ttl) || !empty($magzee_nav_ct_info2_subttl)): ?>
													<a href="" class="contact-info">
														<span class="text"><?php echo esc_html($magzee_nav_ct_info2_ttl); ?></span>
														<span class="title"><?php echo esc_html($magzee_nav_ct_info2_subttl); ?></span>
													</a>
												<?php endif; ?>	
											</div>
										</div>
									<?php endif; ?>
									
							         <?php if(!empty($magzee_nav_ct_info3_icon) || !empty($magzee_nav_ct_info3_ttl) || !empty($magzee_nav_ct_info3_subttl)): ?>
										<div class="widget widget_contact widget_third">
											<div class="contact-area">
												<?php if(!empty($magzee_nav_ct_info3_icon)): ?>
													<div class="contact-icon"><i class="fa <?php echo esc_attr($magzee_nav_ct_info3_icon); ?>"></i></div>
												<?php endif; ?>
												
												<?php if(!empty($magzee_nav_ct_info3_ttl) || !empty($magzee_nav_ct_info3_subttl)): ?>
													<a href="" class="contact-info">
														<span class="text"><?php echo esc_html($magzee_nav_ct_info3_ttl); ?></span>
														<span class="title"><?php echo esc_html($magzee_nav_ct_info3_subttl); ?></span>
													</a>
												<?php endif; ?>	
											</div>
										</div>
									<?php endif; ?>
							    </div>
                            </div>
                        </div>
						<?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="navigator-wrapper">
		<!-- Mobile Toggle -->
	    <div class="theme-mobile-nav d-lg-none d-block <?php echo esc_attr(specia_sticky_menu()); ?>">
	        <div class="container">
	            <div class="row">
	                <div class="col-md-12">
	                    <div class="theme-mobile-menu">
	                        <div class="headtop-mobi">
	                            <div class="headtop-shift">
	                                <a href="javascript:void(0);" class="header-sidebar-toggle open-toggle"><span></span></a>
	                                <a href="javascript:void(0);" class="header-sidebar-toggle close-button"><span></span></a>
	                                <div id="mob-h-top" class="mobi-head-top animated"></div>
	                            </div>
	                        </div>
	                        <div class="mobile-logo">
                             	<?php
                                if(has_custom_logo()) {
                                    the_custom_logo();
                                }
                                else { ?>
	                            	<a href="<?php echo esc_url(home_url( '/' )); ?>" class="navbar-brand">
	                            		<?php echo esc_html(get_bloginfo('name')); ?>
	                            	</a>
                                <?php }
                                $magzee_description = get_bloginfo( 'description');
                                if ($magzee_description) : ?>
                                    <p class="site-description"><?php echo esc_html($magzee_description); ?></p>
                                <?php endif; ?>
	                        </div>
	                        <div class="menu-toggle-wrap">
	                            <div class="hamburger-menu">
	                                <a href="javascript:void(0);" class="menu-toggle">
	                                    <div class="top-bun"></div>
	                                    <div class="meat"></div>
	                                    <div class="bottom-bun"></div>
	                                </a>
	                            </div>
	                        </div>
	                        <div id="mobile-m" class="mobile-menu">
	                            <div class="mobile-menu-shift">
	                                <a href="javascript:void(0);" class="close-style close-menu"></a>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	    <!-- / -->
	    <!-- Top Menu -->
        <div class="xl-nav-area d-none d-lg-block">
            <div class="navigation <?php echo esc_attr(specia_sticky_menu()); ?>">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="theme-menu">
                                <nav class="menubar">
                                    <?php	
        								wp_nav_menu( 
											array(
												'theme_location' => 'primary_menu',
												'container'  => '',
												'menu_class' => 'menu-wrap',
												'fallback_cb' => 'specia_fallback_page_menu::fallback',
												'walker' => new specia_nav_walker()
											)
										);
                                    ?>                        
                                </nav>
                                <div class="menu-right">
                                    <ul class="wrap-right">
                                        <li class="search-button">
                                            <form method="get" id="searchform" class="search-form search-default search-bg" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                                                <div>
                                                    <span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'magzee' ); ?></span>
                                                    <input type="search" class="search-field header-search-field" placeholder="<?php esc_attr_e( 'Search', 'magzee' ); ?>" value="" name="s" id="s" />
                                                    <button role="button" type="submit" class="search-submit" aria-label="<?php esc_attr_e( 'Search', 'magzee' ); ?>">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </li>
										<?php 
										$magzee_header_cart		= get_theme_mod('header_cart','1');
										if($magzee_header_cart == '1') { ?>
                                        <li class="cart-wrapper">
                                            <div class="cart-icon-wrap">
												<?php if ( class_exists( 'WooCommerce' ) ) { ?>
                                                <a href="javascript:void(0)" id="cart"><i class="fa fa-shopping-bag"></i>
                                                <?php 
                                                if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                                                    $count = WC()->cart->cart_contents_count;
                                                    $cart_url = wc_get_cart_url();
                                                    
                                                    if ( $count > 0 ) {
                                                    ?>
                                                         <span><?php echo esc_html( $count ); ?></span>
                                                    <?php 
                                                    }
                                                    else {
                                                        ?>
                                                        <span><?php echo esc_html_e('0','magzee');?></span>
                                                        <?php 
                                                    }
                                                }
                                                ?>
                                                </a>
												<?php } ?>
                                            </div>
                                            
                                            <!-- Shopping Cart -->
                                            <?php if ( class_exists( 'WooCommerce' ) ) { ?>
                                            <div id="header-cart" class="shopping-cart">
                                                <div class="cart-body">                                            
                                                    <?php get_template_part('woocommerce/cart/mini','cart');     ?>
                                                </div>
                                            </div>
                                            <?php } ?>
                                            <!--end shopping-cart -->
                                        </li>
                                        <?php
										}
										$magzee_hdr_btn_hs  	= get_theme_mod('header_book_hide_show','1');
                                    	$magzee_button_label	= get_theme_mod('button_label','Book Now');
										$magzee_button_url		= get_theme_mod('button_url');
										$magzee_button_target 	= get_theme_mod('button_target');
										
                                        if(($magzee_button_target)== 1) {
                                            $magzee_button_target= "target='_blank'"; 
                                        }   
                                        if($magzee_hdr_btn_hs == '1') {
                                        ?>
                                        <li class="menu-item header_btn">
                                            <a href="<?php echo esc_url($magzee_button_url); ?>" <?php echo $magzee_button_target; ?> class="bt-primary bt-effect-1"><?php echo esc_html($magzee_button_label); ?></a>
                                        </li>
                                        <?php } ?>
                                    </ul>                            
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- / -->
				
		
</header>

<?php
if ( !is_page_template( 'templates/template-homepage-one.php' )) {
	specia_breadcrumbs_style(); 
}
?>