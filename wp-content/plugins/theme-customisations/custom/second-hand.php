<div class="row--latest-arrival row bg-light">
    <h2 class="has-text-align-center text-primary">Second Hand</h2>
    <div class="products--list">
        <div class="card-group">
            <?php
                $args = array( 'post_type' => 'product', 'per_page' => 4, 'product_cat' => 'second-hand');
                $loop = new WP_Query( $args );
                $count = 0;
                while ( $loop->have_posts() ) : $loop->the_post(); global $product; 
                    if ($count != 4) {
                        ?>
                        <a class="card" href="<?php echo get_permalink( $loop->post->ID ) ?>">
                            <?php
                                $is_used=false;
                                $term_list = wp_get_post_terms($loop->post->ID,'product_cat');
            
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
                            <img src="<?php if (has_post_thumbnail( $loop->post->ID )) echo get_the_post_thumbnail_url($loop->post->ID);
                                        else echo woocommerce_placeholder_img_src();?>" class="card-img-top" alt="...">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php the_title(); ?></h5>
                                <p class="card-text"><?php echo $product->get_price_html() ?></p>
                                <!-- <?php woocommerce_template_loop_add_to_cart( $loop->post, $product ); ?> -->
                            </div>
                            <!-- <div class="card-footer">
                                <a class="btn btn-primary btn-lg" href="<?php echo get_permalink( $loop->post->ID ) ?>">Buy Now</a> -->
                                <!-- <a class="btn btn-primary btn-lg" href="<?php echo esc_url( $product->add_to_cart_url() ) ?>">Add To Cart</a> -->
                            <!-- </div> -->
                        </a>

                        <?php 
                    }
                $count++;
            ?>
            <?php endwhile;
                wp_reset_query(); 
            ?>
        </div>
    </div>
    <div class="row mt-4 mb-4 justify-content-center">
        <div class="col-md-12 mr-4 text-center">
            <a class="btn btn-lg btn-outline-success" href="/product-category/second-hand/">View More</a>
        </div>
    </div>
</div>