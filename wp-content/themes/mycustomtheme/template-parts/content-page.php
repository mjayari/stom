<div class="">
    <div class="page-head"
        style="background-image: url(<?php echo get_site_url() . '/wp-content/themes/mycustomtheme/assets/images/fight-against-cancer.jpg' ?>);">

        <div class="container" style="position: relative;">
            <h1><?php the_title(); ?></h1>
        </div>


    </div>

    <div style="padding: 10px;">
        <div class="container">
            <div style="display: flex;">
                <div style="flex: 1;">
                    <div class="meta mb-3"><span class="date">Publié le: <?php the_date(); ?> | </span>
                        <?php
                        the_tags('<span class="tag"><i class="fa fa-tag"></i>', '</span><span class="tag"><i class="fa fa-tag"></i>', '</span>');
                        ?>
                        <span class="comment"><a href="#comments"><i class='fa fa-comment'></i>
                                <?php comments_number(); ?></a></span>
                    </div>
                    <?php
                    the_content();
                    ?>
                    <hr />
                    <?php
                    comments_template();
                    ?>
                </div>
                <div>
                    <?php
                    custom_breadcrumbs();
                    ?>
                </div>

            </div>

        </div>

    </div>


</div>