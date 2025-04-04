<?php
global $language;
$language = "french";
?>

<div id="comments" class="comments-area" style="direction: ltr;">

    <?php
    $args = array(
        //'post_id'           => 205,
        'status' => 'approve',
        'orderby' => 'comment_date',
        'order' => 'DESC',
        'walker' => null,
        'max_depth' => '3',
        'style' => 'ul',
        'callback' => null,
        'end-callback' => null,
        'type' => 'all',
        //'reply_text'        => 'Répondre',
        'page' => '',
        'per_page' => '5',
        'avatar_size' => '40',
        'reverse_top_level' => 'null',
        'reverse_children' => '',
        'format' => 'html5',
        'short_ping' => 'false',
        'echo' => 'true',
    );

    //wp_list_comments( $args );
    
    //$comments = get_comments($args);
    //echo '<pre>'; print_r( $comments ); echo "</pre>";
    
    /* get comments count 
    $args = array(
        'post_id' => 205,   // Use post_id, not post_ID
            'count'   => true // Return only the count
    );
    $comments_count = get_comments( $args );
    echo '$comments_count = ' . $comments_count . '</br>';*/

    /* get comments
    $comments = get_comments( array( 'post_id' => 205 ) ); 
    foreach ( $comments as $comment ) :
        echo $comment->comment_author . '</br>';                
        echo $comment->comment_content . '</br>';
    endforeach;*/

    /* */
    //echo wp_list_comments( array( 'post_id' => 205 ) );
    
    /*echo '<pre>'; print_r( get_comments( array('post_id' => 205) ) ); echo "</pre>";*/


    if (have_comments()) {
        ?>
        <h3 class="comment-title">
            Discussion:
            <?php
            printf(
                esc_html(_nx(
                    '(%1$s) commentaires',
                    '(%1$s) commentaires',
                    get_comments_number(),
                    'comment title',
                    'textdomain'
                )),
                number_format_i18n(get_comments_number()),
                '<span>' . get_the_title() . '</span>'
            );
            ?>
        </h3>

        <div class="navigation">
            <?php paginate_comments_links(); ?>
        </div>

        <ul class="comment-list">
            <?php
            wp_list_comments($args);
            ?>
        </ul>

        <div class="navigation">
            <?php paginate_comments_links(); ?>
        </div>

        <?php

        if (!comments_open() && get_comments_number()):
            ?>
            <p class="no-comments"><?php esc_html_e('Comments are closed.', 'textdomain'); ?></p>
            <?php
        endif;

    } else { ?>
        <h3 class="comment-title">
            <?php
            if ($language == "english" || $language == "arabic")
                echo 'Discussion: (' . get_comments_number() . ') comments';
            else
                if ($language == "french")
                    echo 'Discussion: (' . get_comments_number() . ') commentaires';

            ?>
        </h3>
        <?php
    }

    /* get comment form */
    if ($language == "english" || $language == 'arabic')
        include ABSPATH . 'wp-content/themes/mycustomtheme/template-parts/comments-form-1.php';
    else
        if ($language == "french")
            include ABSPATH . 'wp-content/themes/mycustomtheme/template-parts/comments-form-2.php';

    ?>

    <style type="text/css">
        .comments-area ul li ul {
            margin-left: 50px;
        }

        .comments-area ul li ul li ul {
            margin-left: 50px;
        }

        .comments-area ul {
            list-style: none;
            border-radius: 0 0 0 10px;
            border: solid 1px #ccc;
            border-top: 0;
            border-right: 0;
            padding: 10px 0 10px 20px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .comments-area li {
            position: relative;
        }

        .comments-area li article {
            padding-top: 10px;
        }

        .comments-area .reply {
            position: relative;
        }

        .comments-area .reply::before {
            content: '';
            position: absolute;
            left: 0;
            /*top: 3px;*/
            bottom: -1px;
            background-color: #ccc;
            height: 1px;
            box-sizing: border-box;
            width: 82px;
        }

        .comments-area p {
            font-size: 13px;
        }

        .comments-area form {
            font-size: 14px;
        }
    </style>

</div>