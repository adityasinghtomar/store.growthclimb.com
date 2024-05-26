<?php
$product 		= wc_get_product($order_bump_settings['product']);
if( $product ){
    $regular_price 	= $product->get_regular_price();
    $sale_price 	= $product->get_sale_price();
    $price 			= $product->get_price_html();


    if (isset($order_bump_settings['discountOption'])) {
        if ($order_bump_settings['discountOption'] == "discount-price" || $order_bump_settings['discountOption'] == "discount-percentage") {
            if ($order_bump_settings['discountapply'] == 'regular') {
                $price = wc_format_sale_price($regular_price, $order_bump_settings['discountPrice']);
            } else {
                $price = wc_format_sale_price($sale_price, $order_bump_settings['discountPrice']);
            }
        }
    }
?>

<div class="wpfnl-order-bump__popup-wrapper">
    <span class="close-order-bump">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11.1667 1.72266L1.72223 11.1671" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
            <path d="M1.72223 1.72266L11.1667 11.1671" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
        </svg>
    </span>

    <div class="wpfnl-order-bump__content-wrapper">
        <div class="wpfnl-order-bump__content-left">
            <h4 class="order-bump__product-title">
                <?php echo $order_bump_settings['productName']; ?>
            </h4>
            <p class="order-bump__product-detail">
                <?php echo $order_bump_settings['productDescriptionText']; ?>
            </p>

            <p class="order-bump__product-price">
                Price: <?php echo $price; ?>
            </p>

            <div class="wpfnl-order-bump__button mobile-btn">
                <label>
                    <input type="checkbox" id="wpfnl-order-bump-cb"
                           data-quantity="<?php echo $order_bump_settings['quantity']; ?>"
                           data-step="<?php echo get_the_ID(); ?>" class="wpfnl-order-bump-cb"
                           name="wpfnl-order-bump-cb" value="<?php echo $order_bump_settings["product"]; ?>">
                    <span class="wpfnl-bump-order-label"><?php echo $order_bump_settings["checkBoxLabel"]; ?></span>
                </label>
            </div>
        </div>
        <?php
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($order_bump_settings['product']), 'single-post-thumbnail');
        $img = $img[0];
        if (isset($order_bump_settings['productImage'])) {
            $img = $order_bump_settings['productImage'];
            $img_id = attachment_url_to_postid($order_bump_settings['productImage']['url']);
            if ($img_id) {
                $thumbnail = wp_get_attachment_image_src($img_id);
                $img = $thumbnail[0];
            } else {
                $img = $settings['productImage']['url'];
            }
        }
        ?>
        <div class="wpfnl-order-bump__content-right">
            <div class="wpfnl-order-bump__image-area">
                <img alt="Order bump popup image" src="<?php echo $img; ?>" class="img1">
                <!-- <img src="<?php //echo $order_bump_settings["productImage"]; ?>" class="img2" > -->
            </div>

            <div class="wpfnl-order-bump__button desktop-btn">
                <label>
                    <input type="checkbox" id="wpfnl-order-bump-cb"
                           data-quantity="<?php echo $order_bump_settings['quantity']; ?>"
                           data-step="<?php echo get_the_ID(); ?>" class="wpfnl-order-bump-cb"
                           name="wpfnl-order-bump-cb" value="<?php echo $order_bump_settings["product"]; ?>">
                    <span class="wpfnl-bump-order-label"><?php echo $order_bump_settings["checkBoxLabel"]; ?></span>
                </label>
            </div>
        </div>
        <!-- /.wpfnl-order-bump__content-right -->
    </div>
    <!-- /.wpfnl-order-bump__content-wrapper -->
</div>
<?php } ?>