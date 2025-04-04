<?php
/*  FV Wordpress Flowplayer - HTML5 video player with Flash fallback    
    Copyright (C) 2013  Foliovision

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// class handling database shortcode generation and saving
class FV_Player_Db {

  private
    $edit_lock_timeout_seconds = 120,
    // TODO: Some of the sorting disabled due to poor performance
    $valid_order_by = array('id', 'player_name', 'date_created', 'author', /*'subtitles_count', 'chapters_count', 'transcript_count'*/ ),
    $videos_cache = array(),
    $video_atts_cache = array(),
    $video_meta_cache = array(),
    $players_cache = array(),
    //$player_atts_cache = array(),
    $player_meta_cache = array(),
    $player_ids_when_searching,
    $stopwords,  // used in get_search_stopwords method
    $database_upgrade_queries = false;

  public function __construct() {
    add_action( 'toplevel_page_fv_player', array($this, 'init_tables') );
    add_action( 'load-settings_page_fvplayer', array($this, 'init_tables') );
    
    add_filter( 'fv_flowplayer_args_pre', array($this, 'getPlayerAttsFromDb'), 5, 1 );
    add_filter( 'fv_player_item_pre', array($this, 'setCurrentVideoAndPlayer' ), 1, 3 );
    add_action( 'wp_head', array($this, 'cache_players_and_videos' ) );

    add_action( 'save_post', array($this, 'store_post_ids' ) );

    add_action( 'wp_ajax_fv_player_db_load', array($this, 'open_player_for_editing') );
    add_action( 'wp_ajax_fv_player_db_export', array($this, 'export_player_data') );
    add_action( 'wp_ajax_fv_player_db_import', array($this, 'import_player_data') );
    add_action( 'wp_ajax_fv_player_db_clone', array($this, 'clone_player') );
    add_action( 'wp_ajax_fv_player_db_remove', array($this, 'remove_player') );
    add_action( 'wp_ajax_fv_wp_flowplayer_retrieve_video_data', array($this, 'retrieve_video_data') ); // todo: nonce, move into controller/editor.php
    add_action( 'wp_ajax_fv_player_db_retrieve_all_players_for_dropdown', array($this, 'retrieve_all_players_for_dropdown') ); // todo: nonce
    add_action( 'wp_ajax_fv_player_db_save', array($this, 'db_store_player_data') );
  }

  public function init_tables() {
    if( !defined('SAVEQUERIES') ) {
      define( 'SAVEQUERIES', true );
    }

    global $wpdb;
    $wpdb->queries = array();

    FV_Player_Db_Player::initDB(true);
    FV_Player_Db_Player_Meta::initDB(true);
    FV_Player_Db_Video::initDB(true);
    FV_Player_Db_Video_Meta::initDB(true);

    $this->database_upgrade_queries = $wpdb->queries;
  }

  public function getDatabaseUpgradeStatus() {
    return $this->database_upgrade_queries;
  }

  public function getVideosCache() {
    return $this->videos_cache;
  }

  public function setVideosCache($cache) {
    return $this->videos_cache = $cache;
  }

  public function isVideoCached($id) {
    return isset($this->videos_cache[$id]);
  }

  public function getVideoMetaCache() {
    return $this->video_meta_cache;
  }

  public function setVideoMetaCache($cache) {
    return $this->video_meta_cache = $cache;
  }

  public function isVideoMetaCached($id_video, $id_meta = null) {
    return ($id_meta !== null ? isset($this->video_meta_cache[$id_video][$id_meta]) : isset($this->video_meta_cache[$id_video]));
  }

  public function getPlayersCache() {
    return $this->players_cache;
  }

  public function setPlayersCache($cache) {
    return $this->players_cache = $cache;
  }

  public function isPlayerCached($id) {
    return isset($this->players_cache[$id]);
  }

  public function getPlayerMetaCache() {
    return $this->player_meta_cache;
  }

  public function setPlayerMetaCache($cache) {
    return $this->player_meta_cache = $cache;
  }

  public function isPlayerMetaCached($id_player, $id_meta = null) {
    return ($id_meta !== null ? isset($this->player_meta_cache[$id_player][$id_meta]) : isset($this->player_meta_cache[$id_player]));
  }

  public function setCurrentVideoAndPlayer($aItem, $index, $aPlayer) {
    global $fv_fp;

    if (!empty($aPlayer['video_objects'][$index])) {
      $vid_obj = $aPlayer['video_objects'][$index];
      $fv_fp->currentVideoObject = $vid_obj;
      
      if( !empty($aItem['sources'][0]['src']) && ( is_numeric($aItem['sources'][0]['src']) ) || stripos($aItem['sources'][0]['src'],'preview-') === 0 ) {

        $new = array( 'sources' => array() );
        if( $src = $vid_obj->getSrc() ) {
          $new['sources'][] = array( 'src' => apply_filters('fv_flowplayer_video_src',$src,array()), 'type' => $fv_fp->get_mime_type($src) );
        }
        if( $src1 = $vid_obj->getSrc1() ) {
          $new['sources'][] = array( 'src' => apply_filters('fv_flowplayer_video_src',$src1,array()), 'type' => $fv_fp->get_mime_type($src1) );
        }
        if( $src2 = $vid_obj->getSrc2() ) {
          $new['sources'][] = array( 'src' => apply_filters('fv_flowplayer_video_src',$src2,array()), 'type' => $fv_fp->get_mime_type($src2));
        }
        if( $rtmp = $vid_obj->getRtmp() ) {
          $new['rtmp'] = $rtmp;
        }
        if( $rtmp_path = $vid_obj->getRtmpPath() ) {
          $ext = $fv_fp->get_mime_type($rtmp_path,false,true) ? $fv_fp->get_mime_type($rtmp_path,false,true).':' : false;
          $new['sources'][] = array( 'src' => $ext.$rtmp_path, 'type' => 'video/flash' );
        }
        
        if( count($new['sources']) ) {
          $aItem = $new;
        }
      }
            
      if ( count($vid_obj->getMetaData())) {
        foreach ($vid_obj->getMetaData() as $meta) {
          if ($meta->getMetaKey() == 'live' && $meta->getMetaValue() == 'true') {
            $aItem['live'] = 'true';
          }
          if ($meta->getMetaKey() == 'dvr' && $meta->getMetaValue() == 'true') {
            $aItem['dvr'] = 'true';
          }
          if ($meta->getMetaKey() == 'audio' && $meta->getMetaValue() == 'true') {
            $aItem['is_audio_stream'] = 'true';
          }
        }
      }
      
      if( $id = $vid_obj->getId() ) {
        $aItem['id'] = $id;
      }
      
      if( $start = $vid_obj->getStart() ) {
        $aItem['fv_start'] = $start;
      }
      if( $end = $vid_obj->getEnd() ) {
        $aItem['fv_end'] = $end;
      }      
      
    } else {
      $fv_fp->currentVideoObject = null;
      $fv_fp->currentPlayerObject = null;
    }

    return $aItem;
  }

  public function cache_players_and_videos() {
    global $posts;
    if( !empty($posts) && is_array($posts) ) {
      $player_ids = array();
      foreach( $posts AS $post ) {
        if (isset($post->post_content)) {
          preg_match_all( '/\[fvplayer id="(\d+)"[^\]]*\]/m', $post->post_content, $matches, PREG_SET_ORDER, 0 );
          if ( $matches && count( $matches ) ) {
            foreach ( $matches as $match ) {
              $player_ids[] = $match[1];
            }
          }
        }
      }

      if (count($player_ids)) {
        $this->cache_players_and_videos_do( $player_ids );
      }
    }
  }
  
  public function cache_players_and_videos_do( $player_ids ) {
    // load all players at once
    $this->query_players( array( 'ids' => $player_ids ) );

    // load all player meta
    new FV_Player_Db_Player_Meta( null, array( 'id_player' => $player_ids ), $this );

    // pre load all videos and their meta for these players
    $video_ids = array();
    foreach( $this->players_cache as $player ) {
      $video_ids = array_merge( $video_ids, explode( ',', $player->getVideoIds() ) );
    }

    if( count( $video_ids ) ) {
      new FV_Player_Db_Video( $video_ids, array(), $this );
      new FV_Player_Db_Video_Meta( null, array( 'id_video' => $video_ids ), $this );
    }
  }

  /**
   * Retrieves total number of players in the database.
   *
   * @return int Returns the total number of players in database.
   */
  public function getListPageCount() {
    global $wpdb;

    $cannot_edit_other_posts = !current_user_can('edit_others_posts');
    $author_id = get_current_user_id();

    // make total the number of players cached, if we've used search
    if (isset($_GET['s']) && $_GET['s']) {
      if( $this->player_ids_when_searching ) {
        $db_options = array(
          'select_fields'       => 'player_name, date_created, videos, author, status',
          'count'               => true,
          'search_by_video_ids' => $this->player_ids_when_searching
        );
  
        if( $cannot_edit_other_posts ) {
          $db_options['author_id'] = $author_id;
        }
  
        $total = $this->query_players( $db_options );
      } else {
        $total = 0;
      }
    } else {
      $query = 'SELECT Count(*) AS Total FROM ' . $wpdb->prefix .'fv_player_players';

      if( $cannot_edit_other_posts ) {
        
        $query .= ' WHERE author = ' . $author_id;
      }

      $total = $wpdb->get_row( $query );
      if ( $total ) {
        $total = $total->Total;
      }
    }

    if ($total) {
      return $total;
    } else {
      return 0;
    }
  }

  /**
   * Retrieves data for all players table shown in admin.
   *
   * @param $order_by  If set, data will be ordered by this column.
   * @param $order     If set, data will be ordered in this order.
   * @param $offset    If set, data will returned will be limited, starting at this offset.
   * @param $per_page  If set, data will returned will be limited, ending at this offset.
   * @param $single_id If set, data will be restricted to a single player ID.
   * @param $search    If set, results will be searched for using the GET search parameter.
   *
   * @return array     Returns an array of all list page results to be displayed.
   * @throws Exception When the underlying FV_Player_Db_Video class generates an error.
   */
  public function getListPageData($order_by, $order, $offset, $per_page, $single_id = null, $search = null) {
    // sanitize variables
    $order = (in_array($order, array('asc', 'desc')) ? $order : 'asc');
    $order_by = (in_array($order_by, $this->valid_order_by) ? $order_by : 'id');
    $author_id = get_current_user_id();
    $cannot_edit_other_posts = !current_user_can('edit_others_posts');

    // load single player, as requested by the user
    if ($single_id) {
      new FV_Player_Db_Player( $single_id, array(), $this );
    } else if ($search) {

      $direct_hit_cache = false;

      // Try to load the player which ID matches the search query it it's a number
      if( is_numeric($search) ) {
        new FV_Player_Db_Player( $search, array(), $this );

        $direct_hit_cache = $this->getPlayersCache();
      }

      // search for videos that are consistent with the search text
      // and load their players only
      $query_videos = array( 
        'fields_to_search' =>  array('src', 'src1', 'src2', 'caption', 'splash', 'splash_text'), 
        'search_string' => $search, 
        'like' => true, 
        'and_or' => 'OR'
      );

      $vids = $this->query_videos($query_videos);

      // if we have any data, assemble video IDs and load their players
      if ($vids !== false) {
        $player_video_ids = array();

        foreach ($vids as $video) {
          $player_video_ids[] = $video->getId();
        }

        // cache this, so we can use this in the FV_Player_Db_Player::getListPageCount() method
        $this->player_ids_when_searching = $player_video_ids;

        $db_options = array(
          'select_fields'       => 'player_name, date_created, videos, author, status',
          'order_by'            => $order_by,
          'order'               => $order,
          'offset'              => $offset,
          'per_page'            => $per_page,
          'search_by_video_ids' => $player_video_ids
        );

        if( $cannot_edit_other_posts ) {
          $db_options['author_id'] = $author_id;
        }

        $this->query_players( $db_options );
      }

      if( is_array($direct_hit_cache) ) {
        $cache = $this->getPlayersCache();
        $this->setPlayersCache( array_merge( $direct_hit_cache, $cache ) );
      }

    } else {
      // load all players, which will put them into the cache automatically

      $db_options = array(
        'select_fields' => 'player_name, date_created, videos, author, status',
        'order_by'      => $order_by,
        'order'         => $order,
        'offset'        => $offset,
        'per_page'      => $per_page,
      );

      if( $cannot_edit_other_posts ) {
        $db_options['author_id'] = $author_id;
      }

      $this->query_players( $db_options );
    }
    
    global $fv_fp;
    $stats_enabled = $fv_fp->_get_option('video_stats_enable');

    $players = $this->getPlayersCache();

    // get all video IDs used in all players
    if ($players && count($players)) {
      $videos = array();
      $result = array();

      foreach ($players as $player) {
        /* @var FV_Player_Db_Player $player */
        $videos = array_merge($videos, explode(',', $player->getVideoIds()));
      }

      // load all videos data at once
      if (count($videos)) {
        // TODO: This class should not provide search
        $vids_data = new FV_Player_Db_Video( $videos, array(), $this );

        // reset $videos variable and index all of our video data,
        // so they are easily accessible when building the resulting
        // display data
        if ($vids_data) {
          /* @var FV_Player_Db_Video[] $videos */
          $videos = array();
          if (count($this->getVideosCache())) {
            foreach ( $this->getVideosCache() as $video_object ) {
              $videos[ $video_object->getId() ] = $video_object;
            }
          }
        }

        // build the result
        foreach ($players as $player) {
          // player data first
          $result_row = new stdClass();
          $result_row->id = $player->getId();
          $result_row->player_name = $player->getPlayerName();
          $result_row->date_created = $player->getDateCreated();
          $result_row->thumbs = array();
          $result_row->author = $player->getAuthor();
          $result_row->subtitles_count = $player->getCount('subtitles');
          $result_row->chapters_count = $player->getCount('chapters');
          $result_row->transcript_count = $player->getCount('transcript');
          $result_row->status = __($player->getStatus(), 'fv-wordpress-flowplayer');
          $result_row->video_objects = $videos;

          // no player name, we'll assemble it from video captions and/or sources
          if (!$result_row->player_name) {
            $result_row->player_name = $player->getPlayerNameWithFallback();
          }

          foreach (explode(',', $player->getVideoIds()) as $video_id) {
            if( empty($videos[ $video_id ]) ) { // the videos field might point to a missing video
              continue;
            }
            
            $video = $videos[ $video_id ];
            
            $caption = $video->getCaption();
            if( !$caption ) {
              $caption = $video->getCaptionFromSrc();
            }
            
            // assemble video splash
            if (isset($videos[ $video_id ]) && $videos[ $video_id ]->getSplash()) {
              // use splash with caption / filename in a span
              $splash = apply_filters( 'fv_flowplayer_playlist_splash', $videos[ $video_id ]->getSplash() );
              $result_row->thumbs[] = '<div class="fv_player_splash_list_preview"><img src="'.esc_attr($splash).'" width="100" alt="'.esc_attr($caption).'" title="'.esc_attr($caption).'" loading="lazy" /><span>' . $caption . '</span></div>';
            } else if ( isset($videos[ $video_id ]) && $caption ) {
              // use caption
              $result_row->thumbs[] = '<div class="fv_player_splash_list_preview fv_player_list_preview_no_splash" title="' . esc_attr($caption) . '"><span>' . $caption . '</span></div>';
            }
            
            if( $stats_enabled ) {
              if( !isset($result_row->stats_play) ) $result_row->stats_play = 0;
              $result_row->stats_play += intval($video->getMetaValue('stats_play',true)); // todo: lower SQL count
            }
          }

          // join thumbnails
          $result_row->thumbs = join(' ', $result_row->thumbs);

          $result[] = $result_row;
        }

        return $result;
      }
    }

    return array();
  }
  


  /**
   * Generates a full code for a playlist from one that uses video IDs
   * stored in the database to one that uses the first video src attribute
   * Playlist items stay as IDs and are filled in flowplayer::build_playlist_html()
   *
   * @param array $atts Player attributes to build the player shortcode from.
   * @param array $preview_data Alternative data to use instead of the $atts array
   *                            when we want to show previews etc.
   *
   * @return array Returns augmented array of attributes that get picked up
   *               on the front-end side.
   * @throws Exception When any of the underlying classes throw an exception.
   */
  private function generateFullPlaylistCode($atts, $preview_data = null) {
    global $fv_fp;

    // check if we should change anything in the playlist code
    if ($preview_data || (isset($atts['playlist']) && preg_match('/^[\d,]+$/m', $atts['playlist']))) {
      $new_playlist_tag = array();      
      $first_video_data_cached = false;

      // serve what we can from the cache
      if (!$preview_data) {
        $ids    = explode( ',', $atts['playlist'] );
        $newids = array();

        // check the first video, which is the main one for the playlist
        if ( isset( $this->video_atts_cache[ $ids[0] ] ) ) {
          $first_video_data_cached = true;
          $atts                    = array_merge( $atts, $this->video_atts_cache[ $ids[0] ] );
        }

        // prepare cached data and IDs that still need loading from DB
        foreach ( $ids as $id ) {
          if ( isset( $this->video_atts_cache[ $id ] ) ) {
            $new_playlist_tag[] = $id;            
          } else {
            $newids[] = (int) $id;
          }
        }
      }

      if ($preview_data || count($newids)) {
        if ($preview_data) {
          $videos = $preview_data['videos'];
        } else {
          $videos = $fv_fp->current_player()->getVideos();
        }

        // cache first vid
        if (!$first_video_data_cached && $videos) {
          $vid = $videos[0]->getAllDataValues();

          // we need to keep the player id!
          $first_video = $vid;
          unset($first_video['id']);
          $atts = array_merge($atts, $first_video);
          $atts['video_objects'] = array($videos[0]);

          // don't cache if we're previewing
          if (!$preview_data) {
            $this->video_atts_cache[ $vid['id'] ] = $vid;
          }

          // remove the first video and keep adding the rest of the videos to the playlist tag
          array_shift( $videos );
        }

        // add rest of the videos into the playlist tag
        if ($videos && count($videos)) {
          foreach ( $videos as $k => $vid_object ) {
            $vid                               = $vid_object->getAllDataValues();
            $vid_id                            = isset($vid['id']) ? $vid['id'] : 'preview-'.($k+1);
            $atts['video_objects'][]           = $vid_object;
            $this->video_atts_cache[ $vid_id ] = $vid;
            $new_playlist_tag[]                = $vid_id;
          }

          $atts['playlist'] = implode(';', $new_playlist_tag);

        } else if (isset($videos) && is_array($videos)) {
          // only one video found, therefore this is not a playlist
          unset($atts['playlist']);
        }
      } else {
        // remove the first video from playlist, since that is
        // the video in src and would duplicate that video in player
        // as a result
        array_shift($new_playlist_tag);

        $atts['playlist'] = implode(';', $new_playlist_tag);
      }
    }
    
    return $atts;
  }


  /**
   * Maps attributes from database into their respective shortcode names.
   *
   * @param $att_name Attribute name from the database to map into shortcode format.
   *
   * @return mixed Returns the correct attribute name for shortcode use.
   */
  private function mapDbAttributes2Shortcode($att_name) {
    $atts_map = array(
      'playlist'       => 'liststyle',
      'video_ads'      => 'preroll',
      'video_ads_post' => 'postroll'
    );

    return (isset($atts_map[$att_name]) ? $atts_map[$att_name] : $att_name);
  }


  /**
   * Maps attributes values from database into their respective shortcode values.
   *
   * @param $att_name  Attribute name from the database.
   * @param $att_value Attribute value from the database.
   *
   * @return mixed Returns the correct attribute value for shortcode use.
   */
  private function mapDbAttributeValue2Shortcode($att_name, $att_value, $data) {
    switch ($att_name) {
      case 'playlist_advance':
        if($att_value == 'on' ) return 'true';
        if($att_value == 'off' ) return 'false';
      case 'share':
        if( $att_value == 'custom' && !empty($data['share_title']) && !empty($data['share_url']) ) {
          return $data['share_title'].';'.$data['share_url'];
        }
      case 'liststyle':
        // there was a bug which caused the Prev/Next Playlist style to save as prev/next rather than prevnext, so this code fixes the display without need to fix the database data
        if($att_value == 'prev/next' ) return 'prevnext';
    }

    return $att_value;
  }


  /**
   * Retrieves player attributes from the database
   * as opposed to getting them from the old full-text
   * shortcode format.
   *
   * @return array|mixed Returns an array with all player attributes in it.
   *                     If the player ID is not found, an empty array is returned.
   * @throws Exception When the underlying video object throws.
   */
  public function getPlayerAttsFromDb( $atts ) {
    // if we have a programatically-crafted shortcode that loads a player
    // to show a custom user playlist on the front-end, process it here
    if (isset( $atts['src'] ) && is_numeric( $atts['src'] ) && intval( $atts['src'] ) > 0 ) {
      return $this->setPlayerAttsFromNumericSrc( $atts );
    }

    global $fv_fp, $FV_Player_Db;

    $is_multi_playlist = false;

    if (isset($atts['id'])) {

      // video attributes which can still be set in shortcode
      // this makes the preview work with YouTube playlists obtained via API
      // this lets you set the splash screen for Vimeo channel
      $preserve = array();
      foreach( array('autoplay','splash','src','splash_text', 'share' ) AS $attr2preserve ) {
        if( !empty($atts[$attr2preserve]) ) {
          $preserve[$attr2preserve] = $atts[$attr2preserve];
        }
      }

      // numeric ID means we're coming from a shortcode somewhere in a post
      if (preg_match('/[\d,]+/', $atts['id']) === 1) {
        $is_multi_playlist = (strpos($atts['id'], ',') !== false);
        $real_id = ($is_multi_playlist ? substr($atts['id'], 0, strpos($atts['id'], ',')) : $atts['id']);

        //if ( isset( $this->player_atts_cache[ $real_id ]) && empty($atts['sort']) ) {
          //return $this->player_atts_cache[ $real_id ];
        //}

        if ($this->isPlayerCached($real_id)) {
          $player = $this->getPlayersCache();
          $player = $player[$real_id];
        } else {
          $player = new FV_Player_Db_Player( $real_id, array(), $FV_Player_Db );
        }

        // even if we have multi-playlist tag, if we cannot find the first player
        // we don't continue here, since we get all attributes from the first player
        if (!$player || !$player->getIsValid()) {
          return false;
        }

        $fv_fp->currentPlayerObject = $player;

        $data = $player->getAllDataValues();

        // did we find the player?
        if ( $data ) {
          foreach ( $data AS $k => $v ) {
            $k = $this->mapDbAttributes2Shortcode( $k );
            $v = $this->mapDbAttributeValue2Shortcode( $k, $v, $data );
            if ( $v ) {
              // we omit empty values and they will get set to defaults if necessary
              $atts[ $k ] = $v;
            }
          }

          // if we have multiple players, load them here
          // and merge their videos with first player's videos
          if ($is_multi_playlist) {
            $ids = explode(',', $atts['id']);
            array_shift($ids);

            foreach ($ids as $id_player) {
              if ($this->isPlayerCached($id_player)) {
                $additional_player = $this->getPlayersCache();
                $additional_player = $additional_player[$id_player];
              } else {
                $additional_player = new FV_Player_Db_Player( $id_player, array(), $FV_Player_Db );
              }

              $additional_player->getVideos();
              $data['videos'] .= ',' . $additional_player->getVideoIds();
            }

            $player->setVideos($data['videos']);
          }
          
          // check if we should change order of videos
          $ordered_videos = explode(',', $data['videos']);
          if (!empty($atts['sort']) && in_array($atts['sort'], array('oldest', 'newest', 'reverse', 'title'))) {

            switch ($atts['sort']) {
              case 'oldest':
                $ordered_videos_tmp = array();
                sort($ordered_videos);
                foreach (  $ordered_videos as $video_index ) {
                  $ordered_videos_tmp['v'.$video_index] = $video_index;
                }

                ksort($ordered_videos_tmp);
                $ordered_videos = array_values($ordered_videos_tmp);
                break;

              case 'newest':
                $ordered_videos_tmp = array();
                sort($ordered_videos);
                $index = count($ordered_videos);
                while($index) {
                  $ordered_videos_tmp['v'.$ordered_videos[--$index]] = $ordered_videos[$index];
                }

                $ordered_videos = array_values($ordered_videos_tmp);
                break;

              case 'reverse':
                $ordered_videos = array_reverse($ordered_videos);
                break;

              case 'title':
                $ordered_videos_tmp = array();
                foreach (  $FV_Player_Db->getVideosCache() as $video ) {
                  // if this is not one of our videos, bail out
                  if (!in_array($video->getId(), $ordered_videos)) {
                    continue;
                  }

                  $title = $video->getCaption();

                  if (!$title) {
                    $title = $video->getSplashText();
                  }

                  if (!$title) {
                    $title = $video->getSrc();
                  }

                  $ordered_videos_tmp[$title] = $video->getId();
                }

                ksort($ordered_videos_tmp);
                $ordered_videos = array_values($ordered_videos_tmp);
                break;
            }
            
            $data['videos'] = implode(',', $ordered_videos);
            $player->setVideos($data['videos']);
            
            if( !empty($atts['video_objects']) ) {
              $new_objects = array();
              foreach( $ordered_videos AS $v ) {
                foreach( $atts['video_objects'] AS $i ) {
                  if( $i->getId() == $v ) {
                    $new_objects[] = $i;
                  }
                }                
              }
              $atts['video_objects'] = $new_objects;                
            }
            
          }          

          // preload all videos
          $player->getVideos();

          // add playlist / single video data
          $atts = array_merge( $atts, $this->generateFullPlaylistCode(
          // we need to prepare the same attributes array here
          // as is ingested by generateFullPlaylistCode()
          // when parsing the new playlist code on the front-end
            array(
              'playlist' => $data['videos']
            )
          ) );

        }
        
        //$this->player_atts_cache[ $real_id ] = $atts;
        
      } else {
        // when ID is not numeric, it's most probably a preview that we need to build
        $preview = $this->db_store_player_data( json_decode( stripslashes($_POST['fv_player_preview_json']), true ) );
        $atts = array_merge( $atts, $FV_Player_Db->generateFullPlaylistCode( array(),$preview ));
        $fv_fp->currentPlayerObject = $preview['player'];
      }

      if( count($preserve) > 0 ) {
        $atts = array_merge( $atts, $preserve );
      }
            
    } else {
      $fv_fp->currentPlayerObject = null;
    }

    // clear player cache with our player IDs
    // if we're coming from multi-ID shortcode,
    // otherwise we'd store player with manually updated
    // and therefore invalid video IDs
    if ($is_multi_playlist) {
      $cache = $FV_Player_Db->getPlayersCache();
      unset($cache[$player->getId()]);
      $FV_Player_Db->setPlayersCache($cache);
    }

    return $atts;
  }

  /**
   * Creates an empty default player from a shortcode like [fvplayer src="1" playlist="1;2;3"]
   * and fills its videos data from the database. Used for custom user front-end playlists.
   *
   * The SRC attribute of the above shortcode must be a numeric ID of the first video in the playlist,
   * then the playlist must follow with all of the videos to be shown (including the first one from the SRC attribute).
   *
   * @param $atts Original player attributes coming from the execution point of this method's filter.
   *
   * @return array|mixed Returns an array with all player attributes in it.
   *                     If the player ID is not found, an empty array is returned.
   * @throws Exception When the underlying video object throws.
   */
  public function setPlayerAttsFromNumericSrc( $atts ) {

    global $fv_fp, $FV_Player_Db;

    if (isset( $atts['src'] ) && is_numeric( $atts['src'] ) && intval( $atts['src'] ) > 0 ) {
      $player = new FV_Player_Db_Player( false, array(
        'playlist' => ( !empty($atts['playlist']) ? $atts['playlist'] : $atts['src'] ),
      ), $FV_Player_Db );

      // fill-in videos data from the "playlist" shortcode parameter
      $player->setVideos( str_replace( ';', ',', $atts['playlist'] ) );
      $fv_fp->currentPlayerObject = $player;
      $data = $player->getAllDataValues();

      // preload all videos
      $player->getVideos();

      // add playlist / single video data
      $atts = array_merge( $atts, $this->generateFullPlaylistCode(
      // we need to prepare the same attributes array here
      // as is ingested by generateFullPlaylistCode()
      // when parsing the new playlist code on the front-end
        array(
          'playlist' => $data['videos']
        )
      ) );
    }

    return $atts;
  }

  public function db_load_player_data( $id ) {
    global $fv_fp;

    $this->getPlayerAttsFromDb( array( 'id' => $id ) );

    // fill the $out variable with player data
    $out = $fv_fp->current_player()->getAllDataValues();

    // load player meta data
    $meta = $fv_fp->current_player()->getMetaData();
    foreach ($meta as $meta_object) {
      if (!isset($out['meta'])) {
        $out['meta'] = array();
      }

      $out['meta'][] = $meta_object->getAllDataValues();
    }

    unset($out['video_objects'], $out['videos']);

    // fill the $out variable with video data
    $out['videos'] = array();
    foreach ($fv_fp->current_player()->getVideos() as $video) {
      // load video values
      $vid = $video->getAllDataValues();
      $vid['meta'] = array();

      // load all meta data
      $meta = $video->getMetaData();

      foreach ($meta as $meta_object) {
        $vid['meta'][] = $meta_object->getAllDataValues();
      }

      $out['videos'][] = $vid;
    }

    // load posts where this player is embedded
    $embeds_html = '';
    if( $posts = $fv_fp->current_player()->getMetaValue('post_id') ) {
      foreach( $posts AS $post_id ) {
        $embeds_html .= '<li><a href="'.get_permalink($post_id).'" target="_blank">'.get_the_title($post_id).'</a></li>';
      }
    }
    if( $embeds_html ) {
      $out['embeds'] = '<ol>'.$embeds_html.'</ol>';
    }

    $preview_data = $fv_fp->build_min_player( false, array( 'id' => $fv_fp->current_player()->getId() ) );
    $out['html'] = $preview_data['html'];

    return $out;
  }


  /**
   * Stored player data in a database from the POST data sent via AJAX
   * from the shortcode editor.
   *
   * @param array $data Alternative data to work with rather than getting these from $_POST.
   *                    Used when previews are being made.
   *
   * @return void|array Returns nothing when we're saving a new player into the DB,
   *                    otherwise returns a new unsaved player and video instances to be used as needed.
   * @throws Exception When any of the underlying objects throw.
   */
  public function db_store_player_data($data = null) {
    global $FV_Player_Db, $fv_fp;

    $player_options        = array();
    $video_ids             = array();
    
    $cannot_edit_other_posts = !current_user_can('edit_others_posts');
    $user_id = get_current_user_id();

    $post_data = null;
    if( is_array($data) ) {
      $post_data = $data;
    } else if( !empty($_POST['data']) && wp_verify_nonce( $_POST['nonce'],"fv-player-preview-".$user_id ) ) {
      if( json_decode( stripslashes($_POST['data']) ) ) {
        $post_data = json_decode( stripslashes($_POST['data']), true );

        // check if user can update player
        if(!empty($post_data['update']) && $cannot_edit_other_posts ) {
          $player_to_check = new FV_Player_Db_Player(intval($post_data['update']), array(), $FV_Player_Db);

          if( $player_to_check->getAuthor() !== $user_id ) {
            wp_send_json( array( 'error' => 'Security check failed.' ) );
          }
        }
      }
    }
    
    $ignored_player_fields = array(
      'fv_wp_flowplayer_field_subtitles_lang', // subtitles languages is a per-video value with global field name,
                                               // so the player should ignore it, as it will be added via video meta
      'fv_wp_flowplayer_field_popup', // never used, never shown in the UI, possibly a remnant of old code,
      'fv_wp_flowplayer_field_transcript', // transcript is a meta value, so it should not be stored globally per-player anymore
      'fv_wp_flowplayer_field_chapters', // chapters is a meta value, so it should not be stored globally per-player anymore
    );

    if ($post_data) {
      // parse and resolve deleted videos
      if (!$data && !empty($post_data['deleted_videos'])) { // todo: ajax!
        $deleted_videos = explode(',', $post_data['deleted_videos']);
        foreach ($deleted_videos as $d_id) {
          // we don't need to load this video data, just link it to a database
          // and then delete it
          // ... although we'll need at least 1 item in the data array to consider this
          //     video data valid for object creation
          $d_vid = new FV_Player_Db_Video(null, array('caption' => '1'), $this);
          $d_vid->link2db($d_id);
          $d_vid->delete();
        }
      }

      // parse and resolve deleted meta data
      if (!$data && !empty($post_data['deleted_video_meta'])) { // todo: probably not needed with Ajax saving
        $deleted_meta = explode(',', $post_data['deleted_video_meta']);
        foreach ($deleted_meta as $d_id) {
          // we don't need to load this meta data, just link it to a database
          // and then delete it
          // ... although we'll need at least 1 item in the data array to consider this
          //     meta data valid for object creation
          $d_meta = new FV_Player_Db_Video_Meta(null, array('meta_key' => '1'), $this);
          $d_meta->link2db($d_id);
          $d_meta->delete();
        }
      }

      // parse and resolve deleted meta data
      if (!$data && !empty($post_data['deleted_player_meta'])) { // todo: probably not needed with Ajax saving
        $deleted_meta = explode(',', $post_data['deleted_player_meta']);
        foreach ($deleted_meta as $d_id) {
          // we don't need to load this meta data, just link it to a database
          // and then delete it
          // ... although we'll need at least 1 item in the data array to consider this
          //     meta data valid for object creation
          $d_meta = new FV_Player_Db_Player_Meta(null, array('meta_key' => '1'), $this);
          $d_meta->link2db($d_id);
          $d_meta->delete();
        }
      }

      foreach ($post_data as $field_name => $field_value) {
        // global player or local video setting field
        if (strpos($field_name, 'fv_wp_flowplayer_field_') !== false) {
          if (!in_array($field_name, $ignored_player_fields)) {
            $option_name = str_replace( 'fv_wp_flowplayer_field_', '', $field_name );
            // global player option
            $player_options[ $option_name ] = $field_value;
          }
        } else if ($field_name == 'videos' && is_array($field_value)) {
          // iterate over all videos for the player
          foreach ($field_value as $video_index => $video_data) {
            // width and height are global options but are sent out for shortcode compatibility
            unset($video_data['fv_wp_flowplayer_field_width'], $video_data['fv_wp_flowplayer_field_height']);

            // remove global player HLS key option, as it's handled as meta data item
            // TODO: create proper API!
            unset($video_data['fv_wp_flowplayer_hlskey'], $video_data['fv_wp_flowplayer_hlskey_cryptic'], $video_data['fv_wp_flowplayer_field_encoding_job_id']);

            // strip video data of the prefix
            $new_video_data = array();
            foreach ($video_data as $key => $value) {
              if ($key === 'id') {
                $id = $value;
              } else {
                $new_video_data[ str_replace( 'fv_wp_flowplayer_field_', '', $key ) ] = $value;
              }
            }
            $video_data = $new_video_data;
            unset($new_video_data);

            // add any video meta data that we can gather
            $video_meta = array();

            if (!empty($post_data['video_meta']['video'][$video_index])) {
              foreach ($post_data['video_meta']['video'][$video_index] as $video_meta_section => $video_meta_array) {
                $meta_data_to_add = array(
                  'meta_key' => $video_meta_section,
                  'meta_value' => $video_meta_array['value']
                );

                if (isset($video_meta_array['id'])) {
                  $meta_data_to_add['id'] = (int) $video_meta_array['id'];
                }

                $video_meta[] = $meta_data_to_add;
              }
            }

            // add chapters
            if (!empty($post_data['video_meta']['chapters'][$video_index]['file']['value'])) {
              $chapters = array(
                'meta_key' =>'chapters',
                'meta_value' => $post_data['video_meta']['chapters'][$video_index]['file']['value']
              );

              if (!empty($post_data['video_meta']['chapters'][$video_index]['file']['id'])) {
                $chapters['id'] = $post_data['video_meta']['chapters'][$video_index]['file']['id'];
              }

              $video_meta[] = $chapters;
            }

            // add transcript
            if (!empty($post_data['video_meta']['transcript'][$video_index]['file']['value'])) {
              $transcript = array(
                'meta_key' =>'transcript',
                'meta_value' => $post_data['video_meta']['transcript'][$video_index]['file']['value']
              );

              if (!empty($post_data['video_meta']['transcript'][$video_index]['file']['id'])) {
                $transcript['id'] = $post_data['video_meta']['transcript'][$video_index]['file']['id'];
              }

              $video_meta[] = $transcript;
            }

            // call a filter which is server by plugins to augment
            // the $video_meta data with all the plugin data for this
            // particular video
            if (!empty($post_data['video_meta'])) {
              $video_meta = apply_filters( 'fv_player_db_video_meta_save', $video_meta, $post_data['video_meta'], $video_index);
            }

            // save the video
            $video = new FV_Player_Db_Video(null, $video_data, $this);

            // if we have video ID, link this video to DB
            if (isset($id)) {
              $video->link2db($id);
              unset($id);
            }

            // save only if we're not requesting new instances for preview purposes
            if (!$data) {
              $id_video = $video->save( $video_meta );
              if( !$id_video ) {
                global $wpdb;
                wp_send_json( array( 'fatal_error' => true, 'error' => 'Failed to save the video: '.$wpdb->last_error ) );
                exit;
              }
            } else {
              $video->link2meta( $video_meta );
            }

            // return videos as well as the full player
            if (!$data) {
              $video_ids[] = $id_video;
            } else {
              $video_ids[] = $video;
            }
          }
        }
      }

      // add all videos into this player
      if (!$data) {
        $player_options['videos'] = implode( ',', $video_ids );
      }

      // add any player meta data that we can gather
      $player_meta = array();

      if (!empty($post_data['player_meta']['player'])) {
        foreach ($post_data['player_meta']['player'] as $player_meta_section => $player_meta_array) {
          $meta_data_to_add = array(
            'meta_key' => $player_meta_section,
            'meta_value' => $player_meta_array['value']
          );

          if (isset($player_meta_array['id'])) {
            $meta_data_to_add['id'] = (int) $player_meta_array['id'];
          }

          $player_meta[] = $meta_data_to_add;
        }
      }

      // call a filter which is served by plugins to augment
      // the $player_meta data with all the plugin data for this
      // particular player
      if (!empty($post_data['player_meta'])) {
        $player_meta = apply_filters( 'fv_player_db_player_meta_save', $player_meta, $post_data['player_meta']);
      }

      // create and save the player
      $player = new FV_Player_Db_Player(null, $player_options, $FV_Player_Db);

      // if this player should have a "published" status, add it here
      if ( !empty( $post_data['status'] ) && $post_data['status'] == 'published' ) {
        $player->setStatus('published');
      }

      // save only if we're not requesting new instances for preview purposes
      if (!$data) {
        // link to DB, if we're doing an update
        if (!empty($post_data['update'])) {
          $player->link2db($post_data['update']);
        }

        $id = $player->save($player_meta);

        if ($id) {
          do_action('fv_player_db_save', $id);

          echo wp_json_encode( $this->db_load_player_data( $id ) );
        } else {
          global $wpdb;
          wp_send_json( array( 'fatal_error' => true, 'error' => 'Failed to save player: '.$wpdb->last_error ) );
        }
      } else {
        $player->link2meta( $player_meta );
        return array(
          'player' => $player,
          'videos' => $video_ids
        );
      }
    }

    if (!$data) {
      die();
    }
  }



  /**
   * AJAX method to return database data for the player ID given
   */
  public function open_player_for_editing() {
    global $fv_fp;

    if (isset($_POST['playerID']) && is_numeric($_POST['playerID']) && intval($_POST['playerID']) == $_POST['playerID']) {

      if( defined('DOING_AJAX') && DOING_AJAX &&
        ( empty($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'],"fv-player-db-load-".get_current_user_id() ) )
      ) {
        wp_send_json( array( 'error' => 'Security check failed.' ) );
        die();
      }

      // load player and its videos from DB
      if (!$this->getPlayerAttsFromDb(array( 'id' => $_POST['playerID'] ))) {
        header("HTTP/1.0 404 Not Found");
        die();
      }

      $userID = get_current_user_id();
      $cannot_edit_other_posts = !current_user_can('edit_others_posts');

      if( $cannot_edit_other_posts && $fv_fp->current_player() ) {
        $author = $fv_fp->current_player()->getAuthor();
        if( $userID !== $author ) {
          wp_send_json( array( 'error' => 'You don\'t have permission to edit this player.' ) );
          die();
        }
      }

      // check player's meta data for an edit lock
      if ($fv_fp->current_player() && count($fv_fp->current_player()->getMetaData())) {
        $edit_lock_found = false;
        foreach ($fv_fp->current_player()->getMetaData() as $meta_object) {
          $key = $meta_object->getMetaKey();
          $user_locked = str_replace('edit_lock_', '', $key);
          if ( strstr($key, 'edit_lock_') !== false ) {
            $edit_lock_found = true;

            if ( $user_locked != $userID) {
              // someone else is editing this video, first check the timestamp
              $last_tick = $meta_object->getMetaValue();
              if (time() - $last_tick > $this->edit_lock_timeout_seconds) {
                // timeout, remove lock, add lock for this user
                $meta_object->delete();

                $meta = new FV_Player_Db_Player_Meta(null, array(
                  'id_player' => $fv_fp->current_player()->getId(),
                  'meta_key' => 'edit_lock_'.$userID,
                  'meta_value' => time()
                ), $this);

                $meta->save();
              } else {
                $user = get_userdata($user_locked);
                $name = 'Somebody else';
                if( $user ) {
                  if( !empty($user->display_name) ) $name = $user->display_name;
                  if( !empty($user->user_nicename) ) $name = $user->user_nicename;
                }
                wp_send_json( array( 'error' => $name." is editing this player at the moment. Please try again later." ) );
                die();
              }
            } else {
              // same user, extend the lock
              $meta_object->setMetaValue(time());
              $meta_object->save();
            }
          }
        }

        // no edit lock meta record - create new one
        if (!$edit_lock_found) {
          $meta = new FV_Player_Db_Player_Meta( null, array(
            'id_player'  => $fv_fp->current_player()->getId(),
            'meta_key'   => 'edit_lock_' . $userID,
            'meta_value' => time()
          ), $this );

          $meta->save();
        }
      } else {
        // add player edit lock if none was found
        if ($fv_fp->current_player()) {
          $meta = new FV_Player_Db_Player_Meta( null, array(
            'id_player'  => $fv_fp->current_player()->getId(),
            'meta_key'   => 'edit_lock_' . $userID,
            'meta_value' => time()
          ), $this );

          $meta->save();
        }
      }

      $out = $this->db_load_player_data( $_POST['playerID'] );

      if( empty($out['videos']) ) {
        wp_send_json( array( 'error' => "Failed to load videos for this player." ) );
        exit;
      }

      header('Content-Type: application/json');
      if (version_compare(phpversion(), '5.3', '<')) {
        echo json_encode($out);
      } else {
        echo json_encode($out, true);
      }
    }

    wp_die();
  }

  /**
   * Search for players, set internal cache or return
   * count if $args['count'] is true
   *
   * @param array $args
   *
   * @return void|int
   */
  public function query_players( $args ) {
    $args = wp_parse_args( $args, array(
      'author_id' => false,
      'ids' => false, // should not be used together with count
      'offset' => false,
      'order' => false,
      'order_by' => false,
      'per_page' => false,
      'search_by_video_ids' => false,
      'select_fields' => false,
      'count' => false
    ) );

    $ids = array();
    if( is_array($args['ids']) ) {
      $ids = $args['ids'];
    } else if( $args['ids'] ) {
      $ids = explode( ',', $args['ids'] );
    }

    $query_ids = array();
    foreach ( $ids as $id_key => $id_value ) {
      // check if this player is not cached yet
      if (!$this->isPlayerCached($id_value)) {
        $query_ids[ $id_key ] = (int) $id_value;
      }
    }

    // Are we querying players by IDs, but is it all already cached?
    if( count($ids) > 0 && count($query_ids) == 0 ) {
      return;
    }

    // load multiple players via their IDs but a single query and return their values
    $select = 'p.*';
    if( !empty($args['select_fields']) ) {
      $select = 'p.id,'.esc_sql($args['select_fields']);
    }

    if($args['count']) {
      $select = 'count(*) as row_count';
    }

    $where = ' WHERE 1=1 ';
    if( count($query_ids) ) {
      $where .= ' AND p.id IN('. implode(',', $query_ids).') ';

    // if we have multiple video IDs to load players for, let's prepare a like statement here
    } else if( is_array($args['search_by_video_ids']) ) {
      $where_like_part = array();
      foreach ($args['search_by_video_ids'] as $player_video_id) {
        $player_video_id = intval($player_video_id);

        // TODO: What's the problem with FIND_IN_SET() ?
        $where_like_part[] = "(videos = \"$player_video_id\" OR videos LIKE \"%,$player_video_id\" OR videos LIKE \"$player_video_id,%\")";
      }

      $where .= ' AND (' . implode(' OR ', $where_like_part) . ') ';
    }

    if( !empty( $args['author_id']) ) {
      $where .= ' AND author ='.intval($args['author_id']).' ';
    }

    $order = '';
    if( !empty($args['order_by']) ) {

      // Verify that each order by is valid
      $order_by_items = explode( ',', $args['order_by'] );
      $order_by_items = array_map( 'trim', $order_by_items );

      foreach( $order_by_items AS $k => $v ) {
        if( !in_array($v, $this->valid_order_by ) ) {
          unset($order_by_items[$k]);
        }
      }

      if( count($order_by_items) > 0 ) {
        $order = ' ORDER BY '.implode( ', ', array_map( 'esc_sql', $order_by_items ) );
        if( !empty($args['order']) ) {
          if( in_array($args['order'], array( 'asc', 'desc' ) ) ) {
            $order .= ' '.esc_sql($args['order']);
          }
        }
      }
    }

    $limit = '';
    if( $args['offset'] !== false && $args['per_page'] !== false ) {
      $limit = ' LIMIT '.intval($args['offset']).', '.intval($args['per_page']);
    }

    global $wpdb;

    if($args['count']) {
      $group_order = '';
    } else {
      $group_order = 'GROUP BY p.id'.$order.$limit;
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $player_data = $wpdb->get_results( "SELECT {$select} FROM `{$wpdb->prefix}fv_player_players` AS p {$where} {$group_order}" );

    if($args['count']) {
      return intval($player_data[0]->row_count);
    }

    /**
     * Also load count of subtitles, cues, chapters and transcripts
     * 
     * If we do this is the original query with JOIN it takes 10x longer
     */
    if( is_admin() ) {
      $placeholders = implode( ', ', array_fill( 0, count( $player_data ), '%d' ) );

      $meta_counts = $wpdb->get_results(
        $wpdb->prepare(
          // $placeholders is a string of %d created above
          // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
          "SELECT p.id,
          count(subtitles.id) as subtitles_count,
          count(cues.id) as cues_count,
          count(chapters.id) as chapters_count,
          count(meta_transcript.id) as transcript_count
          FROM `{$wpdb->prefix}fv_player_players` AS p
          JOIN `{$wpdb->prefix}fv_player_videos` AS v on FIND_IN_SET(v.id, p.videos)
          LEFT JOIN `{$wpdb->prefix}fv_player_videometa` AS subtitles ON v.id = subtitles.id_video AND subtitles.meta_key like 'subtitles%'
          LEFT JOIN `{$wpdb->prefix}fv_player_videometa` AS cues ON v.id = cues.id_video AND cues.meta_key like 'cues%'
          LEFT JOIN `{$wpdb->prefix}fv_player_videometa` AS chapters ON v.id = chapters.id_video AND chapters.meta_key = 'chapters'
          LEFT JOIN `{$wpdb->prefix}fv_player_videometa` AS meta_transcript ON v.id = meta_transcript.id_video AND meta_transcript.meta_key LIKE 'transcript'
          WHERE p.id IN( $placeholders )
          GROUP BY p.id",
          wp_list_pluck( $player_data, 'id' )
        ),
        OBJECT_K
      );

      foreach( $player_data as $k => $v ) {
        if ( ! empty( $meta_counts[ $v->id ] ) ) {
          $meta_count = $meta_counts[ $v->id ];
          $player_data[ $k ]->subtitles_count = $meta_count->subtitles_count;
          $player_data[ $k ]->cues_count = $meta_count->cues_count;
          $player_data[ $k ]->chapters_count = $meta_count->chapters_count;
          $player_data[ $k ]->transcript_count = $meta_count->transcript_count;
        }
      }
    }

    $cache = array();

    foreach( $player_data AS $db_record ) {
      // create a new video object and populate it with DB values
      $record_id = $db_record->id;
      // if we don't unset this, we'll get warnings
      unset($db_record->id);

      $player_object = new FV_Player_Db_Player( null, get_object_vars( $db_record ), $this );
      $player_object->link2db( $record_id );

      // cache this player in DB object
      $cache[$record_id] = $player_object;
    }

    if ( ! empty( $cache ) ) {
      $this->setPlayersCache($cache);
    }
  }

  /**
   * Receive Heartbeat data and checks for DB edit lock.
   * In case the lock is found and valid, it will be extended.
   *
   * @param array $response Heartbeat response data to pass back to front end.
   * @param array $data Data received from the front end (unslashed).
   *
   * @return array Returns the same response as received, as we don't need to update it or read it anywhere in JS.
   * @throws Exception When the underlying meta object throws an exception.
   */
  function check_db_edit_lock( $response, $data ) {
    global $FV_Player_Db;

    $userID = get_current_user_id();

    // extend an existing lock
    if ( !empty( $data['fv_flowplayer_edit_lock_id'] ) ) {
      $player_id = $data['fv_flowplayer_edit_lock_id'];
      
      if ($FV_Player_Db && $FV_Player_Db->isPlayerCached($player_id)) {
        $player = $FV_Player_Db->getPlayersCache();
        $player = $player[$player_id];
      } else {
        $player = new FV_Player_Db_Player($player_id, array(), $FV_Player_Db);
      }

      if ($player->getIsValid()) {
        $found = false;
        if (count($player->getMetaData())) {
          foreach ($player->getMetaData() as $meta_object) {
            if ( strstr($meta_object->getMetaKey(), 'edit_lock_') !== false ) {
              if (str_replace('edit_lock_', '', $meta_object->getMetaKey()) == $userID) {
                $found = true;
                
                // same user, extend the lock
                $meta_object->setMetaValue(time());
                $meta_object->save();
              }
            }
          }
        }
        
        if( !$found ) {
          $meta_object = new FV_Player_Db_Player_Meta(null, array(
            'id_player' => $player_id,
            'meta_key' => 'edit_lock_'.$userID,
            'meta_value' => time()
          ), $FV_Player_Db);
          $meta_object->save();
        }
      }
    }

    // remove locks that are no longer being edited
    if ( !empty( $data['fv_flowplayer_edit_lock_removal'] ) && count($data['fv_flowplayer_edit_lock_removal']) ) {
      // load meta for all players to remove locks for (and to auto-cache them as well)
      new FV_Player_Db_Player_Meta(null, array('id_player' => array_keys($data['fv_flowplayer_edit_lock_removal'])), $this);
      $meta = $this->getPlayerMetaCache();
      $locks_removed = array();

      if (count($meta)) {
        foreach ( $meta as $player ) {
          foreach ($player as $meta_object) {
            if ( strstr( $meta_object->getMetaKey(), 'edit_lock_' ) !== false ) {
              if ( str_replace( 'edit_lock_', '', $meta_object->getMetaKey() ) == $userID ) {
                // correct user, delete the lock
                $meta_object->delete();
              }

              $locks_removed[$meta_object->getIdPlayer()] = 1;
            }
          }
        }

        $response['fv_flowplayer_edit_locks_removed'] = $locks_removed;
      }
    }

    return $response;
  }

  /**
   * AJAX function to return JSON-formatted export data
   * for a specific player ID.
   *
   * Works for single player only right now!
   *
   * @param null $unused        Populated by WordPress, not used in this method.
   * @param bool $output_result If true, the export data will be returned instead of outputted.
   *                            Used when cloning a player.
   *
   * @return array Returns the actual export data in an associative array, if $output_result is false.
   * @throws Exception Thrown if one of the underlying DB classes throws an exception.
   */
  public function export_player_data($unused = null, $output_result = true, $id = false ) {
    
    if( !$id && isset($_POST['playerID']) && is_numeric($_POST['playerID']) && intval($_POST['playerID']) == $_POST['playerID'] ) {
      $id  = $_POST['playerID'];
    }
    
    if( defined('DOING_AJAX') && DOING_AJAX && 
      ( empty($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'],"fv-player-db-export-".$id ) )
    ) {
      die('Security check failed');
    }

    if ( $id ) {
      // first, load the player
      $player = new FV_Player_Db_Player($id, array(), $this);
      if ($player && $player->getIsValid()) {
        $cannot_edit_other_posts = !current_user_can('edit_others_posts');
        $author_id = get_current_user_id();

        if( $cannot_edit_other_posts ) {
          if( $author_id !== $player->getAuthor() ) {
            die('You don\'t have permission to export this player.');
          }
        }

        $export_data = $player->export();

        // load player meta data
        $meta = $player->getMetaData();
        if ($meta && count($meta)) {
          $export_data['meta'] = array();

          foreach ($meta as $meta_data) {
            // don't include edit locks
            if ( strstr($meta_data->getMetaKey(), 'edit_lock_') === false ) {
              $export_data['meta'][] = $meta_data->export();
            }
          }
        }

        // load videos and meta for this player
        $videos = $player->getVideos();

        // this line will load and cache meta for all videos at once
        new FV_Player_Db_Video_Meta(null, array('id_video' => explode(',', $player->getVideoIds())), $this);

        if ($videos && count($videos)) {
          $export_data['videos'] = array();

          foreach ($videos as $video) {
            $video_export_data = $video->export();

            // load all meta data for this video
            if ($this->isVideoMetaCached($video->getId())) {
              $video_export_data['meta'] = array();

              foreach ($this->video_meta_cache[$video->getId()] as $meta) {
                $video_export_data['meta'][] = $meta->export();
              }
            }

            $export_data['videos'][] = $video_export_data;
          }
        }
      } else {
        if ($output_result) {
          die( 'invalid player ID, export unsuccessful - please use the close button and try again' );
        } else {
          return false;
        }
      }

      if ($output_result) {
        if (version_compare(phpversion(), '5.3', '<')) {
          echo json_encode($export_data);
        } else {        
          echo json_encode($export_data, true);
        }
        exit;
      } else {
        return $export_data;
      }
    } else {
      if ($output_result) {
        die( 'invalid player ID, export unsuccessful - please use the close button and try again' );
      } else {
        return false;
      }
    }
  }

  /**
   * AJAX function to import JSON-formatted export data.
   *
   * Works for single player only right now!
   *
   * @param null $unused        Populated by WordPress, not used in this method.
   * @param bool $output_result If true, the import result will be returned instead of outputted.
   *                            Used when cloning a player.
   * @param array|null $alternative_data If set, this is an alternative source of data to import.
   *                                     Used when cloning a player. This also skips the nonce check!
   *
   * @return string Returns the actual player ID, if $output_result is false.
   *
   * @throws Exception Thrown if one of the underlying DB classes throws an exception.
   */
  public function import_player_data($unused = null, $output_result = true, $alternative_data = null) {
    global $FV_Player_Db;

    if (($alternative_data !== null && $data = $alternative_data) || (isset($_POST['data']) && $data = json_decode(stripslashes($_POST['data']), true))) {
      if( defined('DOING_AJAX') && DOING_AJAX && !$alternative_data &&
        ( empty($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'],"fv-player-db-import-".get_current_user_id() ) )
      ) {
        die('Security check failed');
      }      
      
      try {
        // first, create the player
        $player_keys = $data;
        unset($player_keys['meta'], $player_keys['videos']);
        
        foreach( $player_keys AS $k => $v ) {
          if( stripos($k,'fv_wp_flowplayer_field_') === 0 ) {
            $new = str_replace( 'fv_wp_flowplayer_field_', '', $k );
            $player_keys[$new] = $v;
            unset($player_keys[$k]);
          }
        }

        $player = new FV_Player_Db_Player(null, $player_keys, $FV_Player_Db);
        $player_video_ids = array();

        // create player videos, along with meta data
        // ... don't save the player yet, as we need all video IDs to be known
        //     before doing so
        if (isset($data['videos'])) {
          foreach ($data['videos'] as $video_data) {
            
            foreach( $video_data AS $k => $v ) {
              if( stripos($k,'fv_wp_flowplayer_field_') === 0 ) {
                $new = str_replace( 'fv_wp_flowplayer_field_', '', $k );
                $video_data[$new] = $v;
                unset($video_data[$k]);
              }
            }
            
            $video_object = new FV_Player_Db_Video(null, $video_data, $FV_Player_Db);
            $id_video = $video_object->save();

            // add all meta data for this video
            if (isset($video_data['meta'])) {
              foreach ($video_data['meta'] as $video_meta_data) {
                $video_meta_object = new FV_Player_Db_Video_Meta(null, $video_meta_data, $FV_Player_Db);
                $video_meta_object->link2db($id_video, true);
                $video_meta_object->save();
              }
            }

            $player_video_ids[] = $id_video;
          }
        }

        // set video IDs for the player
        $player->setVideos(implode(',', $player_video_ids));

        // save player
        $id_player = $player->save(
          isset($data['meta']) ? $data['meta'] : array(),
          true
        );

      } catch (Exception $e) {
        if (WP_DEBUG) {
          var_dump($e);

          if (!$output_result) {
            exit;
          }
        }

        die( '0' );
      }

      if ($output_result) {
        die( (string) $id_player );
      } else {
        return (string) $id_player;
      }
    } else {
      if ($output_result) {
        die('No valid import data found, import unsuccessful');
      } else {
        return 'No valid import data found, import unsuccessful';
      }
    }
  }

  /**
   * AJAX function to remove a player from database.
   *
   * @throws Exception Thrown if one of the underlying DB classes throws an exception.
   */
  public function remove_player() {
    if (isset($_POST['playerID']) && is_numeric($_POST['playerID']) && intval($_POST['playerID']) == $_POST['playerID']) {
      if( defined('DOING_AJAX') && DOING_AJAX && 
        ( empty($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'],"fv-player-db-remove-".$_POST['playerID'] ) )
      ) {
        die('Security check failed');
      }
      
      // first, load the player
      $player = new FV_Player_Db_Player($_POST['playerID'], array(), $this);
      if ($player && $player->getIsValid()) {
        $cannot_edit_other_posts = !current_user_can('edit_others_posts');
        $author_id = get_current_user_id();

        // check if user can delete player
        if( $cannot_edit_other_posts ) {
          if( $author_id !== $player->getAuthor() ) {
            die('You don\'t have permission to delete this player.');
          }
        }

        // remove the player
        if ($player->delete()) {
          echo 1;
          exit;
        } else {
          die( 'Could not remove player' );
        }
      } else {
        die( 'Invalid player ID' );
      }
    } else {
      die( 'Invalid player ID' );
    }
  }

  /**
   * AJAX function to clone a player in the database.
   *
   * Works for single player only right now!
   *
   * @throws Exception Thrown if one of the underlying DB classes throws an exception.
   */
  public function clone_player() {
    if (isset($_POST['playerID']) && is_numeric($_POST['playerID'])) {
      $cannot_edit_other_posts = !current_user_can('edit_others_posts');
      $author_id = get_current_user_id();

      $player = new FV_Player_Db_Player( intval($_POST['playerID']), array(), $this );

      if( $cannot_edit_other_posts ) {
        if( $author_id !== $player->getAuthor() ) {
          die('You don\'t have permission to clone this player.');
        }
      }

      $export_data = $this->export_player_data(null, false);

      // do not clone information about where the player is embeded
      if (isset($export_data['meta'])) {
        foreach($export_data['meta'] as $h => $v){
          if($v['meta_key'] == 'post_id'){
            unset($export_data['meta'][$h]);
          }
        }
      }

      echo $this->import_player_data(null, false, $export_data);
      exit;
    } else {
      die('no valid player ID found, cloning unsuccessful');
    }
  }

  /**
   * AJAX method to retrieve video caption, splash screen and duration.
   * Also returns current timestamp, so we can store the last check date in DB.
   */
  public function retrieve_video_data() {

    if ( ! wp_verify_nonce( $_POST['nonce'], 'fv-player-retrieve_video_data' ) ) {
      die( 'Security check failed' );
    }

    if (!isset($_POST['video_url'])) {
      exit;
    }
    
    $url = $_POST['video_url'];

    $json_data = apply_filters('fv_player_meta_data', $url, false);
    if ($json_data !== false && is_array($json_data) ) {
      header('Content-Type: application/json');
      $json_data['ts'] = time();
      die(json_encode($json_data));
    }

    // add last update timestamp & duration
    $json_data = array(
      'ts' => time()
    );

    // was only the file path provided?
    $parsed = parse_url($url);
    if( count($parsed) == 1 && !empty($parsed['path']) ) {
      // then user the WordPress home URL
      $url = home_url($url);
      // but remove the "path" if WordPress runs in a folder
      $wordpress_home = parse_url(home_url());
      if( !empty($wordpress_home['path']) ) {
        $url = str_replace( $wordpress_home['path'], '', $url );
      }
    }

    // only run the actual check for real URLs
    if( filter_var($url, FILTER_VALIDATE_URL) ) {
      // add duration
      global $FV_Player_Checker, $fv_fp;
      if( $secured_url = $fv_fp->get_video_src( $url, array( 'dynamic' => true ) ) ) {
        $url = $secured_url;
      }
      
      $check = $FV_Player_Checker->check_mimetype(array($url), false, true);
      $json_data['error'] = $check['error'];
      $json_data['duration'] = $check['duration'];
      $json_data['is_live'] = $check['is_live'];
      $json_data['is_audio'] = $check['is_audio'];
    }

    header('Content-Type: application/json');
    die(json_encode($json_data));
  }

  /**
   * AJAX method to retrieve IDs and names of all players to be populated
   * into a dropdown in the front-end.
   */
  public function retrieve_all_players_for_dropdown() {
    $players = $this->getListPageData('date_created', 'desc', false, false);
    $json_data = array();

    foreach ($players as $player) {
      $json_data[] = array(
        'id' => $player->id,
        'name' => '#' . $player->id . ' ' . $player->player_name
      );
    }

    header('Content-Type: application/json');
    die(json_encode($json_data));
  }

  /**
   * Runs on save_post hook and it stored the post ID in player meta. It also checks any player meta which is pointing to this post and if it's no longer found in it the meta is removed.
   *
   * @param int $post_id        Populated by WordPress, the post ID
   */  
  public function store_post_ids( $post_id ) {
    global $wpdb;

    if ( wp_is_post_revision( $post_id ) ) return;
    
    $post = get_post($post_id);
    
    $matches = array();
    if( preg_match_all('~\[fvplayer.*?id=[\'"]([0-9,]+)[\'"].*?\]~', $post->post_content, $matches1 ) ) {
      $matches = array_merge( $matches, $matches1[1] );
    }

    // The [fvplayer] shortcode might be stored in plain form, or with the quotes escaped like fvplayer id=\"56\"]
    if( preg_match_all('~\[fvplayer.*?id=\\\?[\'"]([0-9,]+)~', implode( array_map( 'implode', get_post_custom($post_id) ) ), $matches2 ) ) {
      $matches = array_merge( $matches, $matches2[1] );
    }

    $ids = array();

    if( $matches ) {
      foreach( $matches AS $match ) {
        foreach( explode(',',$match) AS $match_match ) {
          $ids[] = $match_match;
        }
      }
      
      $ids = array_unique($ids);
      foreach( $ids AS $player_id ) {
        
        $player = new FV_Player_Db_Player($player_id);
        if( $player->getIsValid() ) {
          
          $add = true;
          // TODO: This seems to not work when saving with Elementor, it seems store_post_ids() runs 3 times
          // but it's never aware of the player meta added using FV_Player_Db_Player_Meta in the previous run
          $metas = $player->getMetaData();
          if( count($metas) ) {
            foreach( $metas as $meta_object ) {
              if( $meta_object->getMetaKey() == 'post_id' ) {
                if( $meta_object->getMetaValue() == $post_id ) {
                  $add = false;
                }
              }
            }
          }

          // TODO: So here's the temporary work-around which should be removed once FV_Player_Db_Player_Meta()
          // does properly register the player meta with getMetaData()
          if( $wpdb->get_var( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}fv_player_playermeta WHERE id_player = %d AND meta_key = %s AND meta_value = %d", $player_id, 'post_id', $post_id ) ) ) {
            $add = false;
          }
          
          if( $add ) {
            $meta = new FV_Player_Db_Player_Meta(null, array(
              'id_player' => $player_id,
              'meta_key' => 'post_id',
              'meta_value' => $post_id
            ) );

            $meta->save();

            // Make sure the player is no longer a Draft is used in a post
            $player->setStatus('published');
            $player->save();
          }
        }

      }
    }

    /**
     * Check if table exists before looking for FV Player that is associated in the post.
     * We do this because we would run into issues with this in WP Integration tests.
     * The database tables get created by tests like FV_Player_DBTest::setUp() but somehow 
     * wptests_fv_player_playermetas is not there
     */
    $table_name = FV_Player_Db_Player_Meta::init_db_name();

    if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
      $remove = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE meta_key = 'post_id' AND meta_value = %s ", $post_id ) );
      if( $remove ) {
        foreach( $remove AS $removal ) {
          if( !in_array($removal->id_player,$ids) ) {
            $d_meta = new FV_Player_Db_Player_Meta($removal->id);
            $d_meta->link2db( $removal->id );
            $d_meta->delete();
          }
        }
      }
    }
  }

  public static function get_player_duration( $id ) {
    global $wpdb;
    return $wpdb->get_var( "SELECT sum(vm.meta_value) FROM {$wpdb->prefix}fv_player_videometa AS vm JOIN {$wpdb->prefix}fv_player_players AS p ON FIND_IN_SET(vm.id_video, p.videos) WHERE p.id = ".intval($id)." AND vm.meta_key = 'duration'" );
    
  }

  /**
   * Searches for a player video via custom query.
   *
   * @param array $args Array with search arguments.
   *
   * @return array|bool Returns array of FV_Player_Db_Video if any data were loaded, false otherwise.
   */
  public function query_videos($args) {
    global $wpdb;

    $args =  wp_parse_args( $args,
      array( 
        'fields_to_search' => array(
          'src'
        ),
        'search_string' => '',
        'like' => false,
        'and_or' => 'OR'
      )
    );

    // assemble where part
    $where = array();

    /*
     * Inspired by core WP WP_Query::parse_search() but adjusted to make it fit our SQL query
     */
    if ( $args['like'] ) {
      $search_terms_count = 1;
      $search_terms = '';

      $args['search_string'] = stripslashes( $args['search_string'] );

      if( (substr($args['search_string'], 0,1) == "'" && substr( $args['search_string'],-1) == "'") || (substr($args['search_string'], 0,1) == '"' && substr( $args['search_string'],-1) == '"') ) { // Dont break term if in '' or ""
        $args['search_string'] = substr($args['search_string'], 1, -1);
        $search_terms = array( $args['search_string'] );
      } else {
        if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $args['search_string'], $matches ) ) {
          $search_terms_count = count( $matches[0] );
          $search_terms = self::parse_search_terms( $matches[0] );
          // If the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
          if ( empty( $search_terms ) || count( $search_terms ) > 9 ) {
            $search_terms = array( $args['search_string'] );
          }
        } else {
          $search_terms = array( $args['search_string'] );
        }
      }
      
      $search_terms_encoded = array();

      foreach( $search_terms as $term ) {
        $search_terms_encoded[] = $term; 
        $search_terms_encoded[] = urlencode($term);
        $search_terms_encoded[] = rawurlencode($term);
      }

      $search_terms = $search_terms_encoded;

      $search_terms = array_unique( $search_terms );

      unset($search_terms_encoded);

      $exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );
      
      foreach ($args['fields_to_search'] as $field_name) {
        $field_name = sanitize_key($field_name);
        $searchlike = '';
        $first = true;
        foreach ( $search_terms as $term ) {
          // If there is an $exclusion_prefix, terms prefixed with it should be excluded.
          $exclude = $exclusion_prefix && ( substr( $term, 0, 1 ) === $exclusion_prefix );
         
          if ( $exclude ) {
            $like_op  = 'NOT LIKE';
            $andor_op = ' AND ';
            $term     = substr( $term, 1 );
          } else {
            $like_op  = 'LIKE';
            $andor_op = ' OR ';
          }
          
          if( $first ) $andor_op = '';

          $like_term = '%' . $wpdb->esc_like( $term ) . '%';
          $searchlike .= $wpdb->prepare( "{$andor_op}(v.{$field_name} $like_op %s)", $like_term);

          $first = false;
        }
        $where[] = "(". $searchlike .")";
      }

    } else { // TODO same as like
      foreach ($args['fields_to_search'] as $field_name) {
        $field_name = sanitize_key($field_name);
        $where[] = "v.$field_name ='" . esc_sql($args['search_string']) . "'";
      }
    }

    $where = implode(' '.esc_sql($args['and_or']).' ', $where);

    // TODO: Sort by subtitles_count, 'chapters_count and transcript_count should be added here
    // TODO: Search the meta values too
    $video_data = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT v.* FROM `{$wpdb->prefix}fv_player_videos` AS v JOIN `{$wpdb->prefix}fv_player_players` AS p ON FIND_IN_SET(v.id, p.videos) WHERE {$where} ORDER BY v.id DESC"
      )
    );

    if (!$video_data) {
      return false;
    }

    $videos = array();

    foreach( $video_data AS $db_record ) {
      // create a new video object and populate it with DB values
      $record_id = $db_record->id;
      // if we don't unset this, we'll get warnings
      unset($db_record->id);

      $video_object = new FV_Player_Db_Video( null, get_object_vars( $db_record ), $this );
      $video_object->link2db( $record_id );

      // cache this player in DB object
      $videos[] = $video_object;
    }

    return $videos;
  }

  /**
   * Copy of core WordPress WP_Query::parse_search_terms() for our purposes without any changes
   * 
   * Check if the terms are suitable for searching.
   *
   * Uses an array of stopwords (terms) that are excluded from the separate
   * term matching when searching for posts. The list of English stopwords is
   * the approximate search engines list, and is translatable. ( from class-wp-query.php )
   *
   * @since 3.7.0
   * 
   * @param string[] $terms Array of terms to check.
   * @return string[] Terms that are not stopwords.
   */
  public function parse_search_terms( $terms ) {
    $strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
    $checked    = array();

    $stopwords = $this->get_search_stopwords();

    foreach ( $terms as $term ) {
      // Keep before/after spaces when term is for exact match.
      if ( preg_match( '/^".+"$/', $term ) ) {
        $term = trim( $term, "\"'" );
      } else {
        $term = trim( $term, "\"' " );
      }

      // Avoid single A-Z and single dashes.
      if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
        continue;
      }

      if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) ) {
        continue;
      }

      $checked[] = $term;
    }

    return $checked;
  }

  /**
   * Copy of core WordPress WP_Query::get_search_stopwords() for our purposes without any changes
   * 
   * Retrieve stopwords used when parsing search terms. ( from class-wp-query.php )
   *
   * @since 3.7.0
   *
   * @return string[] Stopwords.
   */
  public function get_search_stopwords() {
    if ( isset( $this->stopwords ) ) {
      return $this->stopwords;
    }

    /*
    * translators: This is a comma-separated list of very common words that should be excluded from a search,
    * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
    * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
    */
    $words = explode(
      ',',
      _x(
        'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
        'Comma-separated list of search stopwords in your language'
      )
    );

    $stopwords = array();
    foreach ( $words as $word ) {
      $word = trim( $word, "\r\n\t " );
      if ( $word ) {
        $stopwords[] = $word;
      }
    }

    /**
     * Filters stopwords used when parsing search terms.
     *
     * @since 3.7.0
     *
     * @param string[] $stopwords Array of stopwords.
     */
    $this->stopwords = apply_filters( 'wp_search_stopwords', $stopwords );
    return $this->stopwords;
  }

  /**
   * Sanitizes the value for DB class attributes.
   * 
   * TODO: We got a report of PHP warning where the $value was an object in FV_Player_Db_Player_Meta.
   * How could that happen and should be sanitize objects and arrays recursively?
   * 
   * @param mixed $value
   * @return mixed
   */
  public static function sanitize( $value ) {
    if ( is_string( $value ) ) {
      return stripslashes( $value );
    } else {
      return $value;
    }
  }

}
