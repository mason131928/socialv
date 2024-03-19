<?php

/**
 * SocialV\Utility\Custom_Helper\Helpers\Ajax class
 *
 * @package socialv
 */

namespace SocialV\Utility\Custom_Helper\Helpers;

use SocialV\Utility\Custom_Helper\Component;
use function SocialV\Utility\socialv;
use function add_action;

class Ajax extends Component
{
    public $socialv_option;
    public function __construct()
    {
        $this->socialv_option = get_option('socialv-options');


        // Activity Share - Post activity share
        add_action('wp_ajax_socialv_post_share_activity', array($this, 'socialv_post_share_activity'));
        add_action('wp_ajax_nopriv_socialv_post_share_activity', array($this, 'socialv_post_share_activity'));


        // set the hide post option in activity page.
        if (isset($this->socialv_option['is_socialv_enable_hide_post']) && $this->socialv_option['is_socialv_enable_hide_post'] == '1') {
            add_action('wp_ajax_hide_activity_post', [$this, 'socialv_hide_activity_post']);
            add_action('wp_ajax_nopriv_hide_activity_post', [$this, 'socialv_hide_activity_post']);
        }

        //Search Content
        add_action('wp_ajax_ajax_search_content', [$this, 'socialv_ajax_search_content']);
        add_action('wp_ajax_nopriv_ajax_search_content', [$this, 'socialv_ajax_search_content']);
    }

    // AJAX || Post an Activity Share
    public function socialv_post_share_activity()
    {
        if (!is_user_logged_in()) {
            return;
        }
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_activity';
        $shared_activity_id = $_POST['activity_id'];
        $activity = $wpdb->get_results("SELECT user_id, primary_link FROM {$table} where id={$shared_activity_id}");
        $activity_user_id = $activity[0]->user_id;
        $current_user_id = get_current_user_id();
        if ($activity_user_id == $current_user_id) {
            $action = '<a href="' . bp_core_get_user_domain($activity_user_id) . '">' . get_the_author_meta('display_name', $activity_user_id) . '</a>' . esc_html__(' shared his post', 'socialv');
        } else {
            $action = '<a href="' . bp_core_get_user_domain($current_user_id) . '">' . get_the_author_meta('display_name', $current_user_id) . '</a> ' . sprintf(esc_html__('shared %s post', 'socialv'), '<a href="' . bp_core_get_user_domain($activity_user_id) . '">' . get_the_author_meta('display_name', $activity_user_id) . '</a>');
        }

        $wpdb->insert(
            $table,
            array(
                'user_id'       => $current_user_id,
                'component'     => 'activity',
                'type'          => 'activity_share',
                'action'        => $action,
                'content'       => '',
                'primary_link'  => $activity[0]->primary_link,
                'date_recorded' => current_time('mysql')
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        $activity_id = $wpdb->insert_id;

        bp_activity_update_meta($activity_id, 'shared_activity_id', $shared_activity_id);

        if ($activity_id) {
            $res = true;
            do_action("socialv_activity_shared", $activity_id, $shared_activity_id, $current_user_id);
        } else {
            $res = false;
        }

        wp_send_json($res, 200);
    }

    public function socialv_hide_activity_post()
    {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $meta_key = "_socialv_activity_hiden_by_user";
        $data = '';
        if (!isset($_POST["activity_id"])) {
            esc_html_e("Id not present", "socialv");
            wp_die();
        }
        $activity_id = $_POST["activity_id"];
        $hidden_activities = get_user_meta($user_id, $meta_key, true);
        if ($_POST['data_type'] == 'hide') {
            if ($hidden_activities) {
                if (in_array($activity_id, $hidden_activities)) {
                    $unset_id = array_search($activity_id, $hidden_activities);
                    unset($hidden_activities[$unset_id]);
                    if (update_user_meta($user_id, $meta_key, array_values($hidden_activities))) {
                        $data .=  esc_html__("Post is now visible", "socialv");
                    }
                } else {
                    $hidden_activities[] = $activity_id;
                    if (update_user_meta($user_id, $meta_key, $hidden_activities)) {
                        $data .=  esc_html__("Post is now hidden", "socialv");
                    }
                }
            } else {
                $hidden_activities = [];
                $hidden_activities[] = $activity_id;
                if (update_user_meta($user_id, $meta_key, $hidden_activities)) {
                    $data .= esc_html__("Post is now hidden", "socialv");
                }
            }
        } else if ($_POST['data_type'] == 'undo') {
            if ($hidden_activities && in_array($activity_id, $hidden_activities)) {
                $unset_id = array_search($activity_id, $hidden_activities);
                unset($hidden_activities[$unset_id]);
                if (update_user_meta($user_id, $meta_key, array_values($hidden_activities))) {
                    $data .= esc_html__("Post is now visible", "socialv");
                }
            } else {
                $data .= esc_html__("Post was not hidden", "socialv");
            }
        }
        wp_send_json_success($data);
        wp_die();
    }
    public function socialv_ajax_search_content()
    {   
        $data[] = null;
        $search = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $count_data = isset($this->socialv_option['header_search_limit']) ? $this->socialv_option['header_search_limit'] : '5';
        $data['content'] = $this->socialv_search($search, $count_data);
        if(!empty($data['content'])){
        $data['details'] = '<a class="btn-view-all" href="' . esc_url(home_url()) . '?s=' . $search . '&tab=all&ajax_search=1">' . esc_html__('View All', 'socialv') . '</a>';
        wp_send_json_success($data);
        }else{
            $data['content'] = '<div class="search_no_result">'.esc_html__('No Data Found', 'socialv').'</div>';
            wp_send_json_success($data);
        }
    }

    function socialv_search($search, $data_count)
    {
        $actdata =  $post_data = $data = '';
       
        //Members Search
        if (bp_has_members(array(
            'search_terms'    => $search,
            'search_columns'  => array('name'),
            'per_page'            => $data_count,
            'page' => 1,
        ))) :

            $data .= '<h6 class="socialv-header-title">' . esc_html__('Member', 'socialv') . '</h6>';
            while (bp_members()) : bp_the_member();

                $members_user_id = bp_get_member_user_id();
                $data .= '<li>
                        <div class="socialv-author-heading">
                            <div class="item-avatar">
                                <a href="' . bp_get_member_permalink() . '">' . bp_get_member_avatar('type=thumb&width=50&height=50') . '</a>
                            </div>
                            <div class="item">
                                <h6 class="item-title fn">
                                    <a href="' . bp_get_member_permalink() . '">' . bp_get_member_name() . '</a>'
                            . socialv()->socialv_get_verified_badge($members_user_id) . '
                                </h6>
                                <div class="item-meta">' . bp_get_member_last_active() . '</div>
                            </div>
                        </div>
				</li>';

            endwhile;
            $data .= '<br>';
        endif;

        //Group Search
        if (bp_has_groups(array(
            'search_terms'    => $search,
            'type' => "alphabetical",
            'search_columns'  => array('name'),
            'per_page'            => $data_count,
        ))) :
            $data .= '<h6 class="socialv-header-title">' . esc_html__('Group', 'socialv') . '</h6>';
            while (bp_groups()) : bp_the_group();

                $data .= '<li>
                        <div class="socialv-author-heading">
                            <div class="item-avatar">
                                <a href="' . bp_get_group_permalink() . '">' . bp_core_fetch_avatar(array('item_id'    => bp_get_group_id(), 'avatar_dir' => 'group-avatars', 'object'     => 'group', 'width'      => 50, 'height'     => 50, 'class' => 'rounded-circle')) . '</a>
                            </div>
                            <div class="item">
                                <h6 class="item-title fn">' . bp_get_group_link() . '</h6>
                                <div class="item-meta">' . bp_get_group_type() . '</div>
                            </div>
                      </div>
				</li>';

            endwhile;
            $data .= '<br>';
        endif;

         //Activity Search
         $act_arg = array(
            'post_type' => 'activity',
            'search_terms'    => $search,
            'per_page'          => $data_count,
            'type'     => 'alphabetical',
        );
        if (bp_has_activities($act_arg)) :
            while (bp_activities()) : bp_the_activity();
                if (bp_get_activity_type() === 'new_blog_post') {
                    continue; // Skip blog posts
                }

                $activity_id = bp_get_activity_id();
                $activity_user_id = bp_get_activity_user_id();
                $activity_avatar_html = bp_core_fetch_avatar(array(
                    'item_id' => $activity_user_id,
                    'object'  => 'user',
                    'type'    => 'thumb',
                ));
                $activity = bp_activity_get_specific(array('activity_ids' => $activity_id));
                $activity_user_field =  bp_get_activity_action(array('no_timestamp' => true)) ;
                $activity_user_field = strlen($activity_user_field) > 171 ? substr($activity_user_field, 0, 171) . '...' : $activity_user_field;
               
                $activity_content = $activity['activities'][0]->content;
                $date_recorded = $activity['activities'][0]->date_recorded;

                $truncated_content = strlen($activity_content) > 70 ? substr($activity_content, 0, 70) . '...' : $activity_content;
                $activity_link  = esc_url(bp_get_activity_directory_permalink() . "p/" . $activity_id);
                
                $actdata .= '<li>
                            <div class="socialv-author-heading">
                                <div class="item-avatar">
                                ' . $activity_avatar_html . '
                                </div>
                                <a class="search-anch" href="' .  esc_url($activity_link) . '"> </a>
                                <div class="item">
                                    <div class="socialv-activity-item item-title fn">' .  $activity_user_field . '</div>
                                    <div class="search-desc">'
                                   . $truncated_content . '
                                    </div> 
                                    <div class="item-meta mt-1 m-0"> ' . bp_core_time_since($date_recorded) . '</div>
                                </div>
                            </div>
                    </li>';

            endwhile;
            if (!empty($actdata)) {
                $data .= '<h6 class="socialv-header-title">' . esc_html__('Activity', 'socialv') . '</h6>';
                $data .= $actdata;
                $data .= '<br>';
            }
        endif;

        //Post Search
        $image_url_post = '';
        $post_args = array(
            's' => $search,
            'post_type' => 'post',
            'posts_per_page' =>  $data_count,
        );

        query_posts($post_args);
        if (have_posts()) :
            while (have_posts()) : the_post();
                 if (get_post_type() == 'bp-email') continue;
                $post_discription = get_the_excerpt();
                $post_discription = strlen($post_discription) > 125 ? substr($post_discription, 0, 125) . '...' : $post_discription;
               
                 if (has_post_thumbnail()) :
                    $image_url_post  = '<div class="item-avatar"><a href="' . get_the_permalink() . '">' . get_the_post_thumbnail(get_the_ID(), array('thumbnail', '50', ' rounded avatar-50')) . '</a></div>';
                endif;

                $post_data .= '<li>
                            <div class="socialv-author-heading">' . $image_url_post . '
                                <div class="item">
                                <a href="' . get_the_permalink() . '">
                                    <h6 class="item-title fn">
                                        ' . get_the_title() . '
                                    </h6>
                                    <div class="item-meta mt-1">' . get_the_date() . '</div>
                                    
                                    <div class="search-desc">' . $post_discription . '</div>
                                    
                                </a>
                                </div>
                            </div>
			        	</li>';

            endwhile;
            if (!empty($post_data)) {
                $data .= '<h6 class="socialv-header-title">' . esc_html__('Post', 'socialv') . '</h6>';
                $data .= $post_data;
                $data .= '<br>';
            }
            wp_reset_postdata();
            wp_reset_query();
        endif;

        //product search

        if (class_exists('WooCommerce')) {
            $image_url_product = '';
            $product_args = array(
                's' => $search,
                'search_columns'  => array('name'),
                'post_type' => 'product',
                'posts_per_page' =>  $data_count,
            );

            query_posts($product_args);
            if (have_posts()) :

                $data .= '<h6 class="socialv-header-title">' . esc_html__('Product', 'socialv') . '</h6>';
                while (have_posts()) : the_post();
                    global $product;

                    if ($product->get_image_id()) :
                        $product->get_image('shop_catalog');
                        $image_product = wp_get_attachment_image_src($product->get_image_id(), "thumbnail");
                        $image_url_product  = '<div class="item-avatar"><a href="' . get_the_permalink($product->get_id()) . '"><img src="' . esc_url($image_product[0]) . '" alt="' . esc_attr('Image', 'socialv') . '" class="avatar rounded avatar-50 photo" loading="lazy"/></a></div>';
                    else :
                        $image_url_product  = '<div class="item-avatar"><a href="' . get_the_permalink($product->get_id()) . '"><img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr__('Awaiting product image', 'socialv') . '" class="avatar rounded avatar-50 photo" loading="lazy"/></a></div>';
                    endif;
                    $data .= '<li>
                                <div class="socialv-author-heading">' . $image_url_product . '
                                    <div class="item">
                                        <h6 class="item-title fn">
                                            <a href="' . get_the_permalink($product->get_id()) . '">' . esc_html($product->get_name()) . '</a>
                                        </h6>
                                        <div class="item-meta mt-1">' . wp_kses($product->get_price_html(), 'socialv') . '</div>
                                    </div>
                                </div>
				            </li>';

                endwhile;
                wp_reset_postdata();
                $data .= '<br>';
            endif;
        }

        //Course Search
        if (class_exists('LearnPress')) {
            $image_url_course = '';
            $course_args = array(
                's' => $search,
                'post_type' => 'lp_course',
                'fields'         => 'ids',
                'posts_per_page' =>  $data_count,
            );
            query_posts($course_args);

            if (have_posts()) :
                $data .= '<h6 class="socialv-header-title">' . esc_html__('Course', 'socialv') . '</h6>';
                while (have_posts()) : the_post();

                    $course = learn_press_get_course(get_the_ID());
                    $image_url_course =    wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'thumbnail');
                    if (!empty($image_url_course[0])) {
                        $image_url_course = $image_url_course[0];
                    } else {
                        $image_url_course = LP()->image('no-image.png');
                    }
                    $data .= '<li>
                                <div class="socialv-author-heading">
                                <div class="item-avatar"><a href="' . esc_url(get_permalink(get_the_ID())) . '"><img src="' . esc_url($image_url_course) . '" alt="' . esc_attr('Image', 'socialv') . '" class="avatar rounded avatar-50 photo" loading="lazy" /></a></div>
                                    <div class="item">
                                        <h6 class="item-title fn">
                                            <a href="' . get_the_permalink(get_the_ID()) . '">' . esc_html(get_the_title(get_the_ID())) . '</a>
                                        </h6>
                                        <div class="item-meta mt-1">' . wp_kses_post($course->get_course_price_html()) . '</div>
                                    </div>
                                </div>
                            </li>';

                endwhile;
                wp_reset_postdata();
                $data .= '<br>';
            endif;
        }

            //page search
            $image_url_page = '';
            $page_args = array(
                's' => $search, 
                'post_type' => 'page',    
                'posts_per_page' =>  $data_count,
                'post_status' => 'publish' 
            );
            
            query_posts($page_args);
            if (have_posts()) :
                
                $data .= '<h6 class="socialv-header-title">' . esc_html__('Page', 'socialv') . '</h6>';
                while (have_posts()) : the_post();
                $page_discription = get_the_excerpt();
                $page_discription = strlen($page_discription) > 125 ? substr($page_discription, 0, 125) . '...' : $page_discription;
                    
                    if (has_post_thumbnail()) :
                        $image_url_page  = '<div class="item-avatar"><a href="' . get_the_permalink() . '">' . get_the_post_thumbnail(get_the_ID(), array('thumbnail', '50', ' rounded avatar-70')) . '</a></div>';
                    endif;
            
                    $data .= '<li>
                                <div class="socialv-author-heading">' . $image_url_page . '
                                    <div class="item">
                                    <a href="' . get_the_permalink() . '">
                                        <h6 class="item-title fn">
                                            ' . get_the_title() . '
                                        </h6>
                                        <div class="item-meta mt-1">' . get_the_date() . '</div>
                                        <div class="search-desc">' . $page_discription . '</div>  
                                    </a>
                                    </div>
                                </div>  
                        </li>';
                endwhile;
                wp_reset_query();
                wp_reset_postdata();
            endif;

        return $data;
    }
}
