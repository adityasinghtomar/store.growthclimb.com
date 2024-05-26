<?php
$product = wc_get_product($settings['product']);
if( $product ){
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    $price = $product->get_price_html();

    if (isset($settings['discountOption'])) {
        if ($settings['discountOption'] == "discount-price" || $settings['discountOption'] == "discount-percentage") {
            if ($settings['discountapply'] == 'regular') {
                $price = wc_format_sale_price( $regular_price, $settings['discountPrice'] );
            } else {
                $price = wc_format_sale_price( $sale_price, $settings['discountPrice'] );
            }
        }
    }
?>

<div class="wpfnl-reset wpfnl-order-bump__template-style1">
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
                    $thumbnail = wp_get_attachment_image_src($img_id);
                    $img = $thumbnail[0];
                } else {
                    $img = $settings['productImage']['url'];
                }
            }
        }

        ?>
        <div class="template-img" style="background-image: url('<?php echo $img; ?>');">
            <img src="<?php echo $settings['productImage']['url'] ?>" alt="" class="for-mobile">
        </div>

        <div class="template-content">
            <h5 class="template-title"><?php echo $settings['productName'] ?></h5>
            <h6 class="subtitle"><?php echo $settings['highLightText'] ?></h6>
            <p class="description"><?php echo $settings['productDescriptionText'] ?></p>
            <span class="product-price">
                <?php echo '<strong>Price: </strong>' . $price . ''; ?>
            </span>
        </div>
    </div>

    <div class="offer-checkbox">
        <span class="wpfnl-checkbox">
            <input type="checkbox" id="wpfnl-order-bump-cb" data-quantity="<?php echo $settings['quantity']; ?>"
                   data-step="<?php echo get_the_ID(); ?>" class="wpfnl-order-bump-cb" name="wpfnl-order-bump-cb"
                   value="<?php echo $settings['product'] ?>">

            <label for="wpfnl-order-bump-cb"><?php echo $settings['checkBoxLabel'] ?></label>
        </span>
    </div>
</div>
<?php } ?>