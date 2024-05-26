<?php
/**
 * Orderbump style4 template
 * 
 * @package
 */
$product 			= wc_get_product($settings['product']);
if( $product ){
    $regular_price 		= $product->get_regular_price();
    if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
        $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
        $regular_price = $regular_price + $signUpFee;
    }
    $sale_price 		= $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
    $price 				= $product->get_price_html();
    $quantity			= $settings['quantity'];

    $orderbump_color 	= isset( $settings['obPrimaryColor'] ) ? $settings['obPrimaryColor'] : '#6E42D2'; //getting order-bump color
    $orderbump_bgcolor 	= isset( $settings['obBgColor'] ) ? $settings['obBgColor'] : ''; //getting order-bump background color
    $orderbump_priceColor 	= isset( $settings['obPriceColor'] ) ? $settings['obPriceColor'] : ''; //getting order-bump price color

    $ob_title_color 	        = isset( $settings['obTitleColor'] ) ? $settings['obTitleColor'] : '#363B4E'; //getting order-bump title color
    $ob_highlight_color 	    = isset( $settings['obHighlightColor'] ) ? $settings['obHighlightColor'] : '#6E42D3'; //getting order-bump highlight color
    $ob_checkbox_title_color 	= isset( $settings['obCheckboxTitleColor'] ) ? $settings['obCheckboxTitleColor'] : '#d9d9d9'; //getting order-bump checkbox title color
    $ob_description_color 	    = isset( $settings['obDescriptionColor'] ) ? $settings['obDescriptionColor'] : '#7A8B9A'; //getting order-bump description color

    if( $product->is_on_sale() ) {
        $price = wc_format_sale_price( $regular_price * $quantity, $sale_price * $quantity );
    } else {
        $price = wc_price( $regular_price * $quantity );
    }

    if (isset($settings['discountOption'])) {
        if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
            $discount_price = preg_replace('/[^\d.]/', '', $settings['discountPrice'] );
            if ($settings['discountapply'] == 'regular') {
                $price = wc_format_sale_price( $regular_price * $quantity, $discount_price * $quantity );
            } else {
                $price = wc_format_sale_price( $sale_price * $quantity, $discount_price * $quantity );
            }
        }
    }
?>

<div class="wpfnl-reset wpfnl-order-bump__template-style4" style="background-color: <?php echo $orderbump_bgcolor; ?>">
    <div class="oderbump-loader">
        <span class="wpfnl-loader"></span>
    </div>
    
    <div class="template-preview-wrapper">
        <?php
        $img = '';
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($settings['product']), 'single-post-thumbnail');

        if (isset($img[0])) {
            $img = $img[0];
        }


        if (isset($settings['productImage'])) {
            if ($settings['productImage'] != "") {
                $img_id = attachment_url_to_postid($settings['productImage']['url']);
                if ($img_id) {
                    $thumbnail = wp_get_attachment_image_src( $img_id, 'medium' );
                    $img = $thumbnail[0];
                } else {
                    $img = $settings['productImage']['url'];
                }
            }
        }

        $rating  = $product->get_average_rating();
        $count   = $product->get_rating_count();
        $average = $product->get_average_rating();
        
        ?>
        <div class="template-img" style="background-image: url('<?php echo $img; ?>');">
            <img src="<?php echo $img; ?>" alt="" class="for-mobile">
        </div>

        <div class="template-content">
            <h5 class="template-title" style="color: <?php echo $ob_title_color ?>" ><?php echo $settings['productName'] ?></h5>
            <?php 
                if( $count > 0 ){
                    echo '<div class="rating">'.wc_get_rating_html( $rating, $count ).' '.$average.' ('.$count.')</div>'; 
                }
            ?>
            
        </div>

        <div class="template-price">
            <span class="product-price">
                <?php echo $price; ?>
            </span>
        </div>

        <div class="offer-checkbox">
            <span class="wpfnl-checkbox">
                <input
                    id="wpfnl-order-bump-cb-<?php echo $key ?>"
                    class="wpfnl-order-bump-cb"
                    type="checkbox"
                    name="wpfnl-order-bump-cb-<?php echo $key ?>"
                    data-key="<?php echo $key; ?>"
                    data-quantity="<?php echo $settings['quantity']; ?>"
                    data-replace="<?php echo $settings['isReplace']; ?>"
                    data-step="<?php echo get_the_ID(); ?>"
                    value="<?php echo $settings['product'] ?>"
                >

                <label for="wpfnl-order-bump-cb-<?php echo $key ?>" style="color: <?php echo $ob_checkbox_title_color ?>; border-color: <?php echo $ob_checkbox_title_color ?>" ><?php echo $settings['checkBoxLabel'] ?></label>
            </span>
        </div>

        <style>
            .wpfnl-order-bump__template-style4 .template-preview-wrapper .product-price span,
            .wpfnl-order-bump__template-style4 .template-preview-wrapper .product-price {
                color: <?php echo $orderbump_priceColor ?>
            }
        </style>

    </div>

    
</div>
<?php } ?>