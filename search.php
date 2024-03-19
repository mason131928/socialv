<?php

/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package socialv
 */

namespace SocialV\Utility;

use WP_Query;
use WPML\Language\Detection\Ajax;

get_header();

$socialv_options = get_option('socialv-options');
$post_section = socialv()->post_style();
$ajax_instance = new \SocialV\Utility\Custom_Helper\Helpers\Common();


$container_class = apply_filters('content_container_class', 'container');
$row_reverse_class = esc_attr($post_section['row_reverse']);
$socialv_option = get_option('socialv-options');

echo '<div class="site-content-contain"><div id="content" class="site-content"><div id="primary" class="content-area"><main id="main" class="site-main"><div id="buddypress"><div class="' . $container_class . '"><div class="row ' . $row_reverse_class . '">';
socialv()->socialv_the_layout_class();
if (strlen($_GET['s']) > 3 && isset($_GET['ajax_search'])) {
    $variable[] = null;
    $content = $pagination = $banner = $all = [];
    $all['activity'] = $all['members'] = $all['group'] = $all['post'] = $all['page'] = $all['product'] = $all['course'] = '';
    $banner['activity'] = $banner['members'] = $banner['group'] = $banner['post'] = $banner['page'] = $banner['product'] = $banner['course'] = '';

    $count_data = 10000000;
    if ($socialv_option['display_search_pagination'] == 'yes') {
        $count_data = isset($socialv_option['searchpage_pagination_limit']) ? $socialv_option['searchpage_pagination_limit'] : '5';
    }
    if($_GET['tab'] === 'all'){
        $count_data  = isset($socialv_option['header_search_limit']) ? $socialv_option['header_search_limit'] : '5';
    }
    $search = $_GET['s'];

    //Memebrs Search
    $mem_arg = array(
        'type'      => 'active',
        'post_type' => 'member',
        'search_terms'    => $search,
        'search_columns'  => array('name'),
        'per_page'        => $count_data,
        'page' => ($_GET['tab'] === 'members') ? get_query_var('paged') : 1,
        'count_total' => true,
    );

    if (bp_has_members($mem_arg)) :
        $banner['members'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Member', 'socialv') . '</h4>';     
        $variable['members']  = '<a href="?s=' . $search . '&tab=members&ajax_search=1" id="pills-members-tab" class="nav-link" type="button" role="tab" aria-controls="pills-members" aria-selected="true">' . esc_html__('Member', 'socialv') . '</a> ';
        $all['members'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=members&ajax_search=1">' . esc_html__('View All Members', 'socialv') . '</a></div>';
        //                   
        $content['members'] = '';
        while (bp_members()) : bp_the_member();
            $members_user_id = bp_get_member_user_id();
            $content['members'] .= '<li><div class="socialv-author-heading">
                                        <div class="item-avatar">
                                            <a href="' . bp_get_member_permalink() . '">' . bp_get_member_avatar('type=full&width=70&height=70') . '</a>
                                        </div>
                                        <div class="item">
                                            <h5 class="item-title fn">
                                                <a href="' . bp_get_member_permalink() . '">' . bp_get_member_name() . '</a>'
                                                . socialv()->socialv_get_verified_badge($members_user_id) . '
                                            </h5>
                                            <div class="item-meta mt-2">' . bp_get_member_last_active() . '</div>
                                        </div>
                                    </div>
                                    </li>';
        endwhile;
        if ($_GET['tab'] === 'members') {
            if (function_exists('bp_members_pagination_links')) {
                $pagination['members']   =  bp_get_members_pagination_links();
            }
        }
        wp_reset_query();
        wp_reset_postdata();
    endif;



    //Group Search
    $grup_arg = array(
        'post_type' => 'group',
        'search_terms'    => $search,
        'type' => "alphabetical",
        'search_columns'  => array('name'),
        'paged' => ($_GET['tab'] === 'group') ? get_query_var('paged') : 1,
        'per_page' =>  $count_data,
        'count_total' => true
    );

    if (bp_has_groups($grup_arg)) :
        $banner['group']  = '<div class="search-content-data"><h4 class="title">' . esc_html__('Group', 'socialv') . '</h4>'; 
        $variable['group']  = '<a href="?s=' . $search . '&tab=group&ajax_search=1" id="pills-group-tab" class="nav-link" type="button" role="tab" aria-controls="pills-group" aria-selected="true">' . esc_html__('Group', 'socialv') . '</a> ';
        $all['group'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=group&ajax_search=1">' . esc_html__('View All Groups', 'socialv') . '</a></div>';
        $content['group'] = '';
        while (bp_groups()) : bp_the_group();
            $content['group'] .= '<li>
                    <div class="socialv-author-heading">
                        <div class="item-avatar">
                            <a href="' . bp_get_group_permalink() . '">' . bp_core_fetch_avatar(array('item_id'    => bp_get_group_id(), 'avatar_dir' => 'group-avatars', 'object'     => 'group', 'width'      => 50, 'height'     => 50, 'class' => 'rounded-circle')) . '</a>
                        </div>
                        <div class="item">
                            <h5 class="item-title fn">' . bp_get_group_link() . '</h5>
                            <div class="item-meta mt-2">' . bp_get_group_type() . '</div>
                        </div>
                    </div>
                    </li>';
        endwhile;
        if ($_GET['tab'] === 'group') {
            if (function_exists('bp_groups_pagination_links')) {
                $pagination['group']  =  bp_get_groups_pagination_links();
            }
        }
        wp_reset_query();
        wp_reset_postdata();
    endif;

     //Activity
     $act_arg =  array(
        'post_type' => 'activity',
        'search_terms'     => $search,
        'search_columns'  => array('name'),
        'type'     => 'alphabetical',
        'page' => ($_GET['tab'] === 'activty') ? get_query_var('paged') : 1,
        'per_page' =>  $count_data,
        'count_total' => true,
    );
    if (bp_has_activities($act_arg)) :
        $content['activity'] ='';
        while (bp_activities()) :   bp_the_activity();
            if (bp_get_activity_type() === 'new_blog_post') { continue;  }
            $activity_id = bp_get_activity_id();
            $activity_user_id = bp_get_activity_user_id();
            $activity_avatar_html = bp_core_fetch_avatar(array(
                'item_id' => $activity_user_id,
                'object'  => 'user',
                'type'    => 'full',
                'width'   => 70,
                'height'  => 70, // You can use different avatar types: 'thumb', 'full', etc.
            ));
            $activity = bp_activity_get_specific(array('activity_ids' => $activity_id));
            $activity_content = $activity['activities'][0]->content;
            $activity_link  = esc_url(bp_get_activity_directory_permalink() . "p/" . $activity_id);
            $content['activity']  .= '<li> <div class="socialv-author-heading">
                                        <div class="item-avatar">' . $activity_avatar_html . '</div>
                                        <div class="item">
                                            <h5 class="item-title fn">' .  bp_get_activity_action(array('no_timestamp' => true)) . '</h5>
                                            <a class="text-body mt-2" href="' .  esc_url($activity_link) . '">' . esc_html__($activity_content, 'socialv') . '</a>
                                            <div class="item-meta mt-2"> ' . bp_insert_activity_meta() . '</div>
                                        </div>
                                    </div></li>';
        endwhile;

        if (!empty($content['activity'])) {
            $banner['activity'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Activity', 'socialv') . '</h4>';     
            $all['activity'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=activity&ajax_search=1">' . esc_html__('View All Activity', 'socialv') . '</a></div>';              
            $variable['activity'] = '<a href="?s=' . $search . '&tab=activity&ajax_search=1" id="pills-activity-tab" class="nav-link" type="button" role="tab" aria-controls="pills-activity" aria-selected="true">' . esc_html__('Activity', 'socialv') . '</a> ';
        }
        if ($_GET['tab'] === 'activity') {
            if (function_exists('bp_get_activity_pagination_links')) { $pagination['activity']  =  bp_get_activity_pagination_links(); }
        }
        wp_reset_query();
        wp_reset_postdata();
    endif;

    //Post Search
    $image_url_post = '';
    $post_args = array(
        's' => $search,
        'post_type' => 'post',
        'posts_per_page' => $count_data,
        'paged' => ($_GET['tab'] === 'post') ? get_query_var('paged') : 1,
    );
    query_posts($post_args);
    if (have_posts()) :
        
        $content['post'] = '';
        while (have_posts()) : the_post();
            if (get_post_type() == 'bp-email') continue;
            $post_discription = get_the_excerpt();
           
            if (has_post_thumbnail()) :
                $image_url_post  = '<div class="item-avatar"><a href="' . get_the_permalink() . '">' . get_the_post_thumbnail(get_the_ID(), array('thumbnail', '50', ' rounded avatar-70')) . '</a></div>';
            endif;

            $content['post']  .= '<li><div class="socialv-author-heading">' . $image_url_post . '
                            <div class="item">
                            <a href="' . get_the_permalink() . '">
                                <h5 class="item-title fn">
                                    ' . get_the_title() . '
                                </h5>
                                <div class="item-meta mt-2">' . get_the_date() . '</div>                                
                                <div class="text-body mt-2">' . $post_discription . '</div>
                            </a>
                            </div>
                        </div></li>';
        endwhile;
        if (!empty( $content['post'] )) {
            $banner['post'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Post', 'socialv') . '</h4>';      
            $variable['post'] =  '<a href="?s=' . $search . '&tab=post&ajax_search=1" id="pills-post-tab" class="nav-link" type="button" role="tab" aria-controls="pills-post" aria-selected="true">' . esc_html__('Post', 'socialv') . '</a> ';
            $all['post'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=post&ajax_search=1">' . esc_html__('View All Posts', 'socialv') . '</a></div>';
        }
        if ($_GET['tab'] === 'post') {
            global $wp_query;
            $total_post_pages = $wp_query->max_num_pages;
            $pagination['post'] =  $ajax_instance->socialv_pagination($total_post_pages);
        }
        wp_reset_query();
        wp_reset_postdata();
    endif;

    //product search
    if (class_exists('WooCommerce')) {
        $image_url_product = '';
        $product_args = array(
            's' => $search,
            'search_columns'  => array('name'),
            'post_type' => 'product',
            'posts_per_page' =>  $count_data,
            'paged' => ($_GET['tab'] === 'product') ? get_query_var('paged') : 1
        );
        query_posts($product_args);

        if (have_posts()) :
            $content['product'] = '';
            while (have_posts()) : the_post();

                global $product;
                if (!$product) continue;
                if ($product->get_image_id()) :
                    $product->get_image('shop_catalog');
                    $image_product = wp_get_attachment_image_src($product->get_image_id(), "thumbnail");
                    $image_url_product  = '<div class="item-avatar"><a href="' . get_the_permalink($product->get_id()) . '"><img src="' . esc_url($image_product[0]) . '" alt="' . esc_attr('Image', 'socialv') . '" class="avatar rounded avatar-70 photo" loading="lazy"/></a></div>';
                else :
                    $image_url_product  = '<div class="item-avatar"><a href="' . get_the_permalink($product->get_id()) . '"><img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr__('Awaiting product image', 'socialv') . '" class="avatar rounded avatar-70 photo" loading="lazy"/></a></div>';
                endif;

                $content['product'] .= '<li><div class="socialv-author-heading">' . $image_url_product . '
                                <div class="item">
                                    <h5 class="item-title fn">
                                        <a href="' . get_the_permalink($product->get_id()) . '">' . esc_html($product->get_name()) . '</a>
                                    </h5>
                                    <div>' . wp_kses($product->get_price_html(), 'socialv') . '</div>
                                </div>
                            </div> </li>';
               
            endwhile;
            if (!empty($content['product'] )) {
               $banner['product'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Product', 'socialv') . '</h4>';       
                $variable['product'] = '<a href="?s=' . $search . '&tab=product&ajax_search=1" id="pills-product-tab" class="nav-link" type="button" role="tab" aria-controls="pills-product" aria-selected="true">' . esc_html__('Product', 'socialv') . '</a> ';
                $all['product'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=product&ajax_search=1">' . esc_html__('View All Products', 'socialv') . '</a></div>';
            }
            if ($_GET['tab'] === 'product') {
                global $wp_query;
                $total_product_pages = $wp_query->max_num_pages;
                $pagination['product'] = $ajax_instance->socialv_pagination($total_product_pages);
            }
        endif;
        wp_reset_query();
        wp_reset_postdata();
    }

    //Course Search
    if (class_exists('LearnPress')) {
        $image_url_course = '';
        $course_args = array(
            's' => $search,
            'post_type' => 'lp_course',
            'fields'         => 'ids',
            'posts_per_page' =>  $count_data,
            'paged' => ($_GET['tab'] === 'course') ? get_query_var('paged') : 1,
        );
        query_posts($course_args);
        if (have_posts()) :
            $content['course']  = '';
            while (have_posts()) : the_post();

                $course = learn_press_get_course(get_the_ID());
                if (!$course) continue;
                $image_url_course =    wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'thumbnail');
                if (!empty($image_url_course[0])) {
                    $image_url_course = $image_url_course[0];
                } else {
                    $image_url_course = LP()->image('no-image.png');
                }
                $content['course'] .= '<li><div class="socialv-author-heading">
                                        <div class="item-avatar"><a href="' . esc_url(get_permalink(get_the_ID())) . '"><img src="' . esc_url($image_url_course) . '" alt="' . esc_attr('Image', 'socialv') . '" class="avatar rounded avatar-70 photo" loading="lazy" /></a></div>
                                            <div class="item">
                                                <h5 class="item-title fn">
                                                    <a href="' . get_the_permalink(get_the_ID()) . '">' . esc_html(get_the_title(get_the_ID())) . '</a>
                                                </h5>   
                                                <div>' . wp_kses_post($course->get_course_price_html()) . '</div>
                                            </div>
                                        </div></li>';
                
            endwhile;
            if (!empty($content['course'])) {
                $banner['course'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Course', 'socialv') . '</h4>';    
                $variable['course']  = '<a href="?s=' . $search . '&tab=course&ajax_search=1" id="pills-course-tab" class="nav-link" type="button" role="tab" aria-controls="pills-course" aria-selected="true">' . esc_html__('Course', 'socialv') . '</a>';
                $all['course'] =  '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=course&ajax_search=1">' . esc_html__('View All Courses', 'socialv') . '</a></div>';
            }
            if ($_GET['tab'] === 'course') {
                global $wp_query;
                $total_course_pages = $wp_query->max_num_pages;
                $pagination['course']  =  $ajax_instance->socialv_pagination($total_course_pages);
            }
        endif;
        wp_reset_query();
        wp_reset_postdata();
    }

     //page search
   $image_url_page = '';
   $page_args = array(
       's' => $search,
       'post_type' => 'page',
       'posts_per_page' => $count_data,
       'post_status' => 'publish', 
       'paged' => ($_GET['tab'] === 'page') ? get_query_var('paged') : 1,
   );
   query_posts($page_args);
   if (have_posts()) :
       $content['page'] = '';
       while (have_posts()) : the_post();
       if (get_post_type() == 'bp-email') continue;
        $page_discription = get_the_excerpt();
           $content['page']  .= '<li>
                       <div class="socialv-author-heading">' . $image_url_page . '
                           <div class="item">
                           <a href="' . get_the_permalink() . '">
                               <h5 class="item-title fn">
                                   ' . get_the_title() . '
                               </h5>
                               <div class="item-meta mt-2">' . get_the_date() . '</div>
                               
                               <div class="text-body mt-2">' . $page_discription . '</div>
                               
                           </a>
                           </div>
                       </div>  
               </li>';
       endwhile;
       if (!empty( $content['page'] )) {
           $banner['page'] = '<div class="search-content-data"><h4 class="title">' . esc_html__('Page', 'socialv') . '</h4>';      
           $variable['page'] =  '<a href="?s=' . $search . '&tab=page&ajax_search=1" id="pills-page-tab" class="nav-link" type="button" role="tab" aria-controls="pills-page" aria-selected="true">' . esc_html__('Page', 'socialv') . '</a> ';
           $all['page'] = '<a class="socialv-button socialv-button-link" href="?s=' . $search . '&tab=page&ajax_search=1">' . esc_html__('View All Page', 'socialv') . '</a></div>';
       }
       if ($_GET['tab'] === 'page') {
           global $wp_query;
           $total_page_pages = $wp_query->max_num_pages;
           $pagination['page'] =  $ajax_instance->socialv_pagination($total_page_pages);
       }
       wp_reset_query();
       wp_reset_postdata();
   endif;


?>
    <!-- Tab List -->
    <div class="card-main card-space card-space-bottom">
        <div class="card-inner pt-0 pb-0 item-list-tabs no-ajax">
            <div class="socialv-subtab-lists">
                <div class="left" onclick="slide('left',event)" style="display: none;">
                    <i class="iconly-Arrow-Left-2 icli"></i>
                </div>
                <div class="right" onclick="slide('right',event)" style="display: none;">
                    <i class="iconly-Arrow-Right-2 icli"></i>
                </div>
                <div class="socialv-subtab-container custom-nav-slider">
                    <ul class="list-inline m-0" id="pills-tab" role="tablist">
                    <?php

                    $count = count($variable);
                    if ($count !== 1) {
                        echo  '<li class="nav-item" role="presentation">
                                    <a href="?s=' . $search . '&tab=all&ajax_search=1" id="pills-all-tab" class="nav-link" type="button" role="tab" aria-controls="pills-all" aria-selected="true">' . esc_html__('All', 'socialv') . '</a>   </li>';
                    }
                    foreach ($variable as $key => $value) {
                        if ($key !== 0) {
                            echo '<li class="nav-item" role="presentation">' . $value . '</li>';
                        }
                    }
                    echo '</ul></div></div></div></div>';

                    //Data list
                    echo '<div class="card-main card-space"> <div class="card-inner"><div class="tab-content socialv-member-list" id="pills-tabContent">';

                    //All Tab
                    if (isset($_GET['tab'] ) && $_GET['tab'] == 'all'){
                        echo  '<div class="tab-pane fade" id="pills-all" role="tabpanel" aria-labelledby="pills-all-tab">';
                         foreach($content as $key => $value){
                            echo wp_kses_post($banner[$key]);
                            echo '<ul class="list-inline">';
                             echo  wp_kses_post($value);
                             echo '</ul>';
                             echo  wp_kses_post($all[$key]);
                        }
                        echo '</div>';
                    }
                   else{
                    //other tabs
                        $data_key = $_GET['tab'];
                        foreach($content as $key => $value){
                            if($data_key == $key){
                                echo  '<div class="tab-pane fade" id="pills-'.$key.'" role="tabpanel" aria-labelledby="pills-'.$key.'-tab"><ul class="list-inline m-0">' . $value . '</ul></div>';     
                                echo '<div class="search-pagination">' . $pagination[$key] . '</div>';
                            }
                        }
                    }
                    echo '</div></div></div>';
                    if (empty($content)) {
                        get_template_part('template-parts/content/error');
                    }
                } elseif(strlen($_GET['s']) > 3 && isset($_GET['s'])) {
                        if (have_posts()) {
                            while (have_posts()) {
                                the_post();
                                get_template_part('template-parts/content/entry', get_post_type(), $post_section['post']);
                            }
                            if (!isset($socialv_options['display_pagination']) || $socialv_options['display_pagination'] == "yes") {                               
                                get_template_part('template-parts/content/pagination');
                            }
                        } else {
                            get_template_part('template-parts/content/error');
                        }
                    wp_reset_postdata();
                }else{
                    get_template_part('template-parts/content/error');
                }
wp_reset_postdata();
socialv()->socialv_sidebar();

echo '</div></div></div></main><!-- #primary --></div></div></div>';

get_footer();
