<?php
    /* customize comments form */

    $comment_send = 'Ajouter le commentaire';
    $comment_reply = 'Laisser un commentaire';
    $comment_reply_to = 'Répondre';
    
    $comment_label = 'Commentaire';
    $comment_author = 'Nom';
    $comment_email = 'E-Mail';
    $comment_body = 'Commenter';
    $comment_url = 'Siteweb';

    $comment_cookies_1 = ' En commentant vous acceptez la';
    $comment_cookies_2 = ' Politique de confidentialité';
    /*$comment_cookies_1 = ' By commenting you accept the';
    $comment_cookies_2 = ' Privacy Policy';*/
    
    $comment_before = 'L\'enregistrement n\'est pas nécessaire.';
    //$comment_before = 'Registration isn\'t required.';
    
    $comment_cancel = 'Annuler réponse';
    //$comment_cancel = 'Cancel Reply';
    
    $site_key = "6LeuwAohAAAAAFbQwfJRzLi0uQT8I6UKxLrAROYv";

    $fields =  array(

        //Author field
        'author' =>
          '<p class="comment-form-author"><div class="form-group"><label for="author">' . __( 'Nom', 'domainreference' ) . '</label> ' .
          ( $req ? '<span class="required">*</span>' : '' ) .
          '<input id="author" class="form-control" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) .
          '"  /></div></p>',

        //Email Field
        'email' =>
          '<p class="comment-form-email"><div class="form-group"><label for="email">' . __( 'Email', 'domainreference' ) . '</label> ' .
          ( $req ? '<span class="required">*</span>' : '' ) .
          '<input id="email" name="email" class="form-control" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .
          '"  /></div></p>',

        //URL Field
        /*'url' =>
          '<p class="comment-form-url"><div class="form-group"><label for="url">' . __( 'Siteweb (optionnel)', 'domainreference' ) . '</label>' .
          '<input id="url" name="url" class="form-control" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) .
          '"   /></p>',*/
        
        //Cookies
        //'cookies' => '<input type="checkbox" required>' . $comment_cookies_1 . '<a href="' . get_privacy_policy_url() . '">' . $comment_cookies_2 . '</a>',

    );

    $comments_args = array(

        'fields'                =>  apply_filters( 'comment_form_default_fields', $fields ),

        'comment_field'         =>  '<p class="comment-form-comment"><div class="form-group"><label for="comment">' . _x( 'Comment', 'noun' ) .
                                    '</label><textarea id="comment" name="comment" class="form-control"  rows="8" aria-required="true">' .
                                    '</textarea></div></p>',

        // Change the title of send button
        'label_submit'          => __( $comment_send ),

        // Change the title of the reply section
        'title_reply'           => __( $comment_reply ),

        // Change the title of the reply section
        //'title_reply_to'        => __( $comment_reply_to ),

        //Cancel Reply Text
        //'cancel_reply_link'     => __( $comment_cancel ),

        // Redefine your own textarea (the comment body).
        'comment_field'         => '<p class="comment-form-comment"><div class="form-group"><label for="comment">' . __( $comment_label, 'domainreference' ) . '</label><textarea class="form-control"  id="comment" name="comment" aria-required="true" placeholder=""></textarea></div></p>'
        . '<div class="g-recaptcha" data-sitekey="' . esc_attr($site_key) . '"></div><br/>',
        
        //Message Before Comment
        'comment_notes_before'  => __( $comment_before),
        
        // Remove "Text or HTML to be displayed after the set of comment fields".
        'comment_notes_after'   => '',
        
        //Submit Button ID
        'id_submit'             => __( 'comment-submit' ),

        //Submit Button Class
        'class_submit'             => 'btn btn-md btn-primary',
        
    );

    comment_form($comments_args); 
?>