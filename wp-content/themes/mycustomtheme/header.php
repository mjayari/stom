<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Blog Site Template">

    <?php
    wp_head();
    ?>

</head>

<body>
    <header id="header">
        <?php
        if (function_exists('the_custom_logo')) {
            //the_custom_logo();
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo = wp_get_attachment_image_src($custom_logo_id);

        }
        ?>
        <div class="mobile-menu">
            <div id="menu-icon-container" class="menu-icon-container" onclick="toggleMenu()">
                <div id="menu-icon" class="menu-icon">
                    <span class="navbar-toggler-icon"></span>
                </div>
            </div>
            <div id="mobile-logo-container" class="mobile-logo-container">
                <img id="mobile-logo" class="mobile-logo" src="<?php echo $logo[0]; ?>" alt="logo">
            </div>

        </div>



        <div id="logo">
            <div id="mobile-logo-container-2" class="mobile-logo-container-2">
                <img id="mobile-logo-2" class="logo" src="<?php echo $logo[0]; ?>" alt="logo">
            </div>
            <div class="slogan">
                <?php echo get_bloginfo('description'); ?>
            </div>

        </div>


        <nav class="">


            <div id="navigation" class="">
                <!--<ul>
                    <li>
                        <a href="#">Acceuil</a>
                    </li>
                    <li>
                        <a href="#">A Propos</a>
                    </li>
                </ul>-->
                <?php
                /*wp_nav_menu(
                    array(
                        'menu' => 'primary',
                        'container' => '',
                        'theme_location' => 'primary',
                        'items_wrap' => '<ul id="" class="">%3$s</ul>',
                    )
                );*/
                ?>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'main-menu',
                    'menu_class' => 'nav-menu', // Custom class for styling
                    'container' => 'nav', // Wrap menu inside a <nav> element
                    'items_wrap' => '<ul id="" class="ul-class">%3$s</ul>',
                    'depth' => 3, // Controls how many submenu levels to allow
                ));
                ?>
                <div style="color: white;margin-top: -9px;">&nbsp;|&nbsp;&nbsp;</div>
                <button class="btn btn-success btn-custom" style="margin-top: -6px;">Contactez-Nous</button>
            </div>

        </nav>
    </header>

    <script type="text/javascript">
        var mobile_menu_visibility = false;

        function toggleMenu() {
            var menu = document.getElementById("header");
            //var mobileLogo = document.getElementById("mobile-logo");
            //var mobileLogo2 = document.getElementById("mobile-logo-2");
            var mobileLogoContainer2 = document.getElementById("mobile-logo-container-2");
            var mobileLogoContainer = document.getElementById("mobile-logo-container");

            var menuIconContainer = document.getElementById("menu-icon-container");
            var menuIcon = document.getElementById("menu-icon");

            var logoImage = '<img id="mobile-logo" class="mobile-logo" src="<?php echo $logo[0]; ?>" alt="logo">';
            var logoImage2 = '<img id="mobile-logo-2" class="logo" src="<?php echo $logo[0]; ?>" alt="logo">';

            if (mobile_menu_visibility == false) {
                //menu.classList.toggle("show");
                menu.classList.add("show");
                //mobileLogo.style.display = 'none';
                //mobileLogo2.classList.add("hide-logo");
                mobileLogoContainer2.innerHTML = logoImage2;
                mobileLogoContainer.innerHTML = '';
                menuIconContainer.style.border = 'solid 1px black';
                menuIcon.style.border = 'solid 1px white';
                mobile_menu_visibility = true;
            } else {
                menu.classList.remove("show");
                //mobileLogo.style.display = 'block';
                //mobileLogo2.classList.remove("hide-logo");
                mobileLogoContainer2.innerHTML = '';
                mobileLogoContainer.innerHTML = logoImage;
                menuIconContainer.style.border = '0px';
                menuIcon.style.border = '0px';
                mobile_menu_visibility = false;
            }
            setTimeout(function () {
                mobileLogoContainer2.innerHTML = logoImage2;
            }, 1000);

            //alert('mobile_menu_visibility: ' + mobile_menu_visibility);


        }
    </script>