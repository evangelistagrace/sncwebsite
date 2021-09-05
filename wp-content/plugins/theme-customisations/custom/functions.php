<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * functions.php
 * Add PHP snippets here
 */

// use bootstrap
function theme_enqueue_styles() {
	wp_enqueue_style( 'bootstrap-theme-style', '//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' );
	// wp_enqueue_script( 'bootstrap-min-script', '/wp-content/themes/' . get_stylesheet() . '/assets/js/bootstrap.min.js', array(), true );
	wp_enqueue_script('bootstrap-min-script', '//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array(), null, true);

 }
 add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

 // To change add to cart text on product archives(Collection) page
add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_custom_product_add_to_cart_text' );  

// change product button text
function woocommerce_custom_product_add_to_cart_text() {
    return __( 'Buy Now', 'woocommerce' );
}

// override product category display
function woocommerce_subcategory_thumbnail_override( $category ) {
	$small_thumbnail_size = apply_filters( 'subcategory_archive_thumbnail_size', 'shop_catalog' ); 
    $dimensions = wc_get_image_size( $small_thumbnail_size ); 
	$thumbnail_id = get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true ); 
 
    if ( $thumbnail_id ) { 
        $image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size ); 
        $image = $image[0]; 
        $image_srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $thumbnail_id, $small_thumbnail_size ) : false; 
        $image_sizes = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $thumbnail_id, $small_thumbnail_size ) : false; 
    }
	echo '
	<div class="wp-block-cover has-background-dim has-background-gradient">
		<span aria-hidden="true" class="wp-block-cover__gradient-background has-electric-grass-gradient-background"></span>
		<img loading="lazy" class="wp-block-cover__image-background wp-image-90" alt="" src="'.esc_url( $image ).'" data-object-fit="cover" srcset="'.esc_url( $image ).' 800w, '.esc_url( $image ).'-300x300.jpg 300w, '.esc_url( $image ).'-150x150.jpg 150w, '.esc_url( $image ).'-768x768.jpg 768w, '.esc_url( $image ).'-324x324.jpg 324w, '.esc_url( $image ).'-416x416.jpg 416w, '.esc_url( $image ).'-100x100.jpg 100w" sizes="(max-width: 800px) 100vw, 800px" width="800" height="800">
		<div class="wp-block-cover__inner-container">
			<a class="btn-with-arrow-animation btn btn-light btn-lg" href="/product-category/'.$category->slug.'"><span>Shop '.$category->name.'<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 36.1 25.8" enable-background="new 0 0 36.1 25.8" xml:space="preserve"><g><line fill="none" stroke="#FFFFFF" stroke-width="3" stroke-miterlimit="10" x1="0" y1="12.9" x2="34" y2="12.9"></line><polyline fill="none" stroke="#FFFFFF" stroke-width="3" stroke-miterlimit="10" points="22.2,1.1 34,12.9 22.2,24.7   "></polyline></g></svg></span></a>
		</div>
	</div>
	';
}


add_action( 'init', 'remove_hooks' ); 
add_action( 'init', 'add_hooks');
add_action( 'init', 'wpdocs_add_custom_shortcode' );

// remove hooks
function remove_hooks() {
	remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10 ); 
	remove_action( 'woocommerce_before_subcategory_title',  'woocommerce_subcategory_thumbnail',  10 ); 
	// customize header
	remove_action( 'storefront_header', 'storefront_site_branding', 20 );
	remove_action('storefront_header', 'storefront_product_search', 40);
	remove_action('storefront_header', 'storefront_primary_navigation', 50);
	remove_action('storefront_header', 'storefront_header_cart', 60);
	remove_action('storefront_footer', 'storefront_before_footer', 20);

	// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

	// if (is_shop())  {
		// Remove product images from the shop loop
		// remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	// }
}

//add hooks
function add_hooks() {
	add_action( 'woocommerce_before_subcategory_title',  'woocommerce_subcategory_thumbnail_override',  10 ); 
	
	// custom header
	add_action( 'storefront_header', 'custom_storefront_header_logo', 2 );
	add_action( 'storefront_header', 'storefront_primary_navigation', 5 );
	add_action( 'storefront_header', 'storefront_product_search', 7);
	// add_action( 'storefront_header', 'storefront_header_cart', 10);
	// add_action( 'storefront_header', 'custom_my_account', 10);
	add_action( 'storefront_header', 'custom_storefront_header_cart', 12);
	// add_action( 'woocommerce_single_product_summary', 'custom_single_product_summary', 35 ); 


}

function custom_storefront_header_logo() { ?>
<a class="navbar-title text-success" href="<?php echo site_url(); ?>">Syah&amp;Co.</a>

<?php
}

function custom_my_account() {
	?>
	<li class="menu-item menu-item-type-post_type menu-item-object-page">
		<a href="/my-account/"><i class="fa fa-user"></i>My Account</a>
	</li>
	<?php
}

function customm_storefront_header_cart() {
	?>
<div class="widget woocommerce widget_shopping_cart">
	<div class="widget_shopping_cart_content">

		<ul class="woocommerce-mini-cart cart_list product_list_widget ">
			<li class="woocommerce-mini-cart-item mini_cart_item">
			</li>
			<li class="woocommerce-mini-cart-item mini_cart_item">
			</li>
		</ul>

		<p class="woocommerce-mini-cart__total total"></p>

		<p class="woocommerce-mini-cart__buttons buttons"></p>

	</div>
</div>

<?php
}

function custom_storefront_header_cart() {
?>
	<ul class="site-header-my-account menu">
		<li>
			<a href="/my-account/"><i class="fa fa-user"></i> My Account</a>
		</li>
	</ul>	
	<ul class="site-header-cart menu">
		<li class="">
			<?php 
				custom_storefront_cart_link();
				?>
		</li>
		<li>
			<?php 
				the_widget('WC_Widget_Cart', 'title=');
				?>
		</li>
	</ul>
<?php 
}

function custom_storefront_cart_link() {
	?>
	<a href="#" class="custom-cart-content">
		<i class="fa fa-shopping-cart"></i> Cart
		<span class="badge">
		<?php echo wp_kses_data( 
			sprintf( _n( '%d', '%d', 
			WC()->cart->get_cart_contents_count(), 'storefront' ), 
			WC()->cart->get_cart_contents_count() ) ); 
		?>
		</span>
	</a>


	<?php
}

// custom shortcodes
function wpdocs_add_custom_shortcode() {
    add_shortcode( 'latest_arrival', 'latest_arrival' );
	add_shortcode( 'benefits_bar', 'benefits_bar' );
	add_shortcode( 'second_hand', 'second_hand' );
}

function latest_arrival(){
	ob_start();
	include 'latest-arrival.php';
	$output = ob_get_clean();
	return $output;
}

function benefits_bar() {
	ob_start();
	include 'benefits-bar.php';
	$output = ob_get_clean();
	return $output;
}

function second_hand() {
	ob_start();
	include 'second-hand.php';
	$output = ob_get_clean();
	return $output;
}



/**
 * Remove product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {

    // unset( $tabs['description'] );      	// Remove the description tab
    // unset( $tabs['reviews'] ); 			// Remove the reviews tab
    unset( $tabs['additional_information'] );  	// Remove the additional information tab

    return $tabs;
}

 // custom checkout script
 function custom_checkout_script() { ?>
	<script>
		let orders = [],
			i = 0;

	</script>


 <?php
	// get order details from cart item
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		$product_cat = wc_get_product_category_list( $cart_item['product_id'] );
		if(!empty($product)){
			$product_id = $product->get_id();
			$product_sku =  $product->get_sku();
			$array = $product->get_categories();
			echo $array;
			?>
			<script>
				orders[i] = {}
				let prod_id = <?php echo json_encode($product_id); ?>,
					prod_sku = <?php echo json_encode($product_sku); ?>,
					prod_name = <?php echo json_encode($product->get_name()); ?>,
					prod_price = <?php echo json_encode($product->get_price()); ?>,
					myrCurrency = new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }),
					prod_cat = <?php echo json_encode($product_cat); ?>,
					parser = new DOMParser();

				orders[i].model = prod_name;
				orders[i].slug = prod_sku.toLowerCase().split(' ').join('-');
				orders[i].price = myrCurrency.format(prod_price);
				orders[i].cat = prod_cat.match(/([A-Z])\w+/g);

				if (orders[i].cat.includes('Used')) { 
					orders[i].condition = 'Used';
				} else {
					orders[i].condition = 'New';
				}
				
			</script>
	<?php
		}
	}

	?>

    <script type="text/javascript">

        jQuery(document).on( "updated_checkout", function() {
			let redirect_phone_num = '60189686774',
				redirect_link = `https://api.whatsapp.com/send?phone=60189686774&text=*`
			
			const base_url = 'https://syah-co.local',
				ws_checkout_btn = document.getElementById('whatsapp_checkout'),
				product_url = window.location,
				name_label = 'Name',
				address_label = 'Address',
				phone_label = 'Phone',
				email_label = 'Email',
				model_label = 'Phone Model',
				storage_label = 'Storage',
				condition_label = 'Condition',
				phone_price_label = 'Phone Price',
				postage_fee_label = 'Postage Fee',
				total_label = 'Total',
				link_label = 'Link'
			

				ws_checkout_btn.addEventListener('click', () => {
					let customer = {},
						cart_items = document.querySelectorAll('.cart_item'),
						shipping_method;

					// get customer details	
					customer.first_name = document.getElementById('billing_first_name').value,
					customer.last_name = document.getElementById('billing_last_name').value,
					customer.address_1 = document.getElementById('billing_address_1').value,
					customer.address_2 = document.getElementById('billing_address_2').value,
					customer.city = document.getElementById('billing_city').value,
					customer.state = document.getElementById('billing_state').value, //translate value
					customer.postcode = document.getElementById('billing_postcode').value,
					customer.phone = document.getElementById('billing_phone').value,
					customer.email = document.getElementById('billing_email').value

					console.log(customer)

					cart_items = Array.from(cart_items)

					// get order details from order review table
					/*
						order properties => model, slug, price, cat, condition, quantity, storage

					*/
					jQuery.each(cart_items, function(i, item) {
						var quantity = item.querySelector('.product-name .product-quantity').innerText.match(/\b([0-9]|[1-9][0-9])\b/g)

						if (quantity.length >= 1) {
							orders[i].quantity = quantity[0]
						}

						orders[i].storage = document.querySelector('dd.variation-Storage p').innerText
						console.log(orders[i])
					})

					// combine details
					shipping_method = document.getElementById('shipping_method').querySelector('li input[checked="checked"]').nextSibling.innerText;

					// message template
					redirect_link += `(${shipping_method})%20Syah%26Co.%20Customer%20Detail*%0A%0AP%E2%AD%95STAGE%20Detail%20%3A%0A`;

					// add customer details
					// name
					redirect_link += `%0A${name_label}%3A%20${customer.first_name}%20${customer.last_name}`
					// address
					if (shipping_method == 'Postage' || shipping_method == 'Cash on Delivery') {
						
						redirect_link += `%0A${address_label}%3A%20${customer.address_1}`
						if (customer.address_2 != '') {
							redirect_link += `%0A${customer.address_2}`
						}
						redirect_link += `%0A${customer.postcode}%20${customer.city}%0A${customer.state}`
					}
					// phone
					redirect_link += `%0A${phone_label}%3A%20${customer.phone}`
					// email
					redirect_link += `%0A${email_label}%3A%20${customer.email}`

					// add order details
					orders.forEach(order => {
						// model
						redirect_link += `%0A%0A${model_label}%3A%20${order.model}`
						// storage
						redirect_link += `%0A${storage_label}%3A%20${order.storage}`
						// condition
						redirect_link += `%0A${condition_label}%3A%20${order.condition}`
						// link
						let prod_url = `${base_url}/product/${order.slug}`
						redirect_link += `%0A${link_label}%3A%20${prod_url}`

						// phone price
						redirect_link += `%0A%0A${phone_price_label}%3A%20${order.price}`
					}) 

					if (shipping_method == 'Postage') {
						redirect_link += `%0A${postage_fee_label}%3A%20`
					}

					redirect_link += `%0A%0A${total_label}%3A%20`

					// send order message to whatsapp
					let win = window.open(redirect_link)

					// redirect and clear cart
					window.location.href = `${base_url}?clear-cart`
					
				})
            });         

    </script>

<?php       
}
add_action( 'woocommerce_after_checkout_form', 'custom_checkout_script' );

// clear cart action
function woocommerce_clear_cart_url() {
    if ( isset( $_GET['clear-cart'] ) ) {
        global $woocommerce;
        $woocommerce->cart->empty_cart();
    }
}

add_action( 'init', 'woocommerce_clear_cart_url' );

// Add custom field to the checkout page
function custom_checkout_field($checkout) {
	echo '<div id="custom_checkout_field"><h2>' . __('New Heading') . '</h2>';
	woocommerce_form_field('custom_field_name', array(
		'type' => 'text',
		'class' => array(
		'my-field-class form-row-wide'
	) ,

	'label' => __('Custom Additional Field') ,
	'placeholder' => __('New Custom Field') ,
	) ,

	$checkout->get_value('custom_field_name'));
	echo '</div>';
}
// uncomment this if wanna add custom field
// add_action('woocommerce_after_order_notes', 'custom_checkout_field');

