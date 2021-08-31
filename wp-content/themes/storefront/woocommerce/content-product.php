<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<!-- <li <?php wc_product_class( '', $product ); ?>>
	<?php
	/**
	 * Hook: woocommerce_before_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_open - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item' );

	/**
	 * Hook: woocommerce_before_shop_loop_item_title.
	 *
	 * @hooked woocommerce_show_product_loop_sale_flash - 10
	 * @hooked woocommerce_template_loop_product_thumbnail - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_shop_loop_item_title.
	 *
	 * @hooked woocommerce_template_loop_product_title - 10
	 */
	do_action( 'woocommerce_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_after_shop_loop_item_title.
	 *
	 * @hooked woocommerce_template_loop_rating - 5
	 * @hooked woocommerce_template_loop_price - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_after_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_close - 5
	 * @hooked woocommerce_template_loop_add_to_cart - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item' );
	?>
</li> -->


<a class="card" href="<?php echo the_permalink(get_the_ID()); ?>">
    <?php
        $is_used=false;
        $term_list = wp_get_post_terms(get_the_ID(),'product_cat');

        for ($i=0; $i<count($term_list); $i++) {
            foreach ($term_list[$i] as $key=>$value) {
                if ($key == 'name') {
                    if ($value == 'Second Hand') {
                        $is_used = true;
                    }
                }
            }               
        }
        
    ?>
    <div class="ribbon-wrapper">
        <div class="ribbon green">
            <?php 
                if ($is_used) {
                    echo 'Used';
                } else {
                    echo 'New';
                }
            ?>
        </div>
    </div>
    <img src="<?php if (has_post_thumbnail( get_the_ID() )) echo get_the_post_thumbnail_url(get_the_ID());
                        else echo woocommerce_placeholder_img_src();?>" class="card-img-top" alt="...">
    <div class="card-body text-center">
        <h5 class="card-title"><?php the_title(); ?></h5>
        <p class="card-text"><?php echo $product->get_price_html() ?></p>
    </div>
    <!-- <div class="card-footer">
        <button class="btn btn-outline-primary" href="<?php echo get_permalink(get_the_ID()) ?>">View</button>
        <button class="btn btn-primary" href="<?php echo esc_url( $product->add_to_cart_url() ) ?>">Add To Cart</button>
    </div> -->
</a>