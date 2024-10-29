<?php
/*
  Plugin Name: Relevant Product Categories for Search Results Widget
  Plugin URI: https://sitegevonden.nl/wordpress-plugins/
  Description: A widget that displays all relevant product categories for a given search result. Shortcode: [relevant_categories]
  Version: 1.2.3
  Author: SiteGevonden.nl
  Author URI: https://sitegevonden.nl
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


class Relevant_Categories_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'relevant_categories_widget',
            __('Relevant Product Categories Widget', 'text_domain'),
            array(
                'description' => __('Displays relevant categories for all products included in search results', 'text_domain'),
                'classname' => 'relevant-categories-widget',
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_search()) {
            return;
        }
        $search_query = get_search_query();

        $products = new WP_Query(array(
            'post_type' => 'product',
            's' => $search_query,
            'posts_per_page' => -1
        ));

        $categories = array();

        if ($products->have_posts()) {
            while ($products->have_posts()) {
                $products->the_post();
                $terms = get_the_terms(get_the_ID(), 'product_cat');
                if ($terms) {
                    foreach ($terms as $term) {
                        $categories[$term->term_id] = $term;
                    }
                }
            }
            wp_reset_postdata();

            if ($categories) {
                $title = apply_filters('widget_title', $instance['title']);
                echo $args['before_widget'];
                if (!empty($title)) {
                    echo $args['before_title'] . esc_html($title) . $args['after_title'];
                }

                echo '<ul>';
                foreach ($categories as $category) {
                    echo '<li><a href="' . esc_url(get_term_link($category)) . '">' . esc_html($category->name) . '</a></li>';
                }
                echo '</ul>';
                echo $args['after_widget'];
            }
        }
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

function register_relevant_categories_widget() {
    global $wpdb;

    $product_taxonomy = $wpdb->get_var("SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy WHERE taxonomy LIKE 'product_cat'");

    if (!empty($product_taxonomy)) {
        register_widget('Relevant_Categories_Widget');
    }
}
add_action('widgets_init', 'register_relevant_categories_widget');

function relevant_categories_widget_enqueue_scripts() {
    wp_enqueue_style('relevant-categories-widget-style', plugins_url('relevant-categories-widget.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'relevant_categories_widget_enqueue_scripts');

function relevant_categories_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'title' => '',
    ), $atts);
    ob_start();
    the_widget('Relevant_Categories_Widget', $atts, array('before_widget' => '', 'after_widget' => ''));
    $output = ob_get_contents();
        ob_end_clean();
    return $output;
}
add_shortcode('relevant_categories', 'relevant_categories_shortcode');

?>