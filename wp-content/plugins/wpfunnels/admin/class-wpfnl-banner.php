<?php
namespace WPFunnels\Admin\Banner;

/**
 * SpecialOccasionBanner Class
 *
 * This class is responsible for displaying a special occasion banner in the WordPress admin.
 *
 * @package WPFunnels\Admin\Banner
 */
class SpecialOccasionBanner {

    /**
     * The occasion identifier.
     *
     * @var string
     */
    private $occasion;
    
    /**
     * The button link.
     *
     * @var string
     */
    private $btn_link;

    /**
     * The start date and time for displaying the banner.
     *
     * @var int
     */
    private $start_date;

    /**
     * The end date and time for displaying the banner.
     *
     * @var int
     */
    private $end_date;

    /**
     * Constructor method for SpecialOccasionBanner class.
     *
     * @param string $occasion   The occasion identifier.
     * @param string $start_date The start date and time for displaying the banner.
     * @param string $end_date   The end date and time for displaying the banner.
     */
    public function __construct($occasion, $start_date, $end_date, $btn_link = '#' ) {
        $this->occasion     = $occasion;
        $this->btn_link     = $btn_link;
        $this->start_date   = strtotime($start_date);
        $this->end_date     = strtotime($end_date);

        if ( !defined('WPFNL_PRO_VERSION') && 'yes' === get_option( '_is_wpfnl_eid_al_fitr_promotion', 'yes' )) {
            
            // Hook into the admin_notices action to display the banner
            add_action('admin_notices', [$this, 'display_banner']);

            // Add styles
            add_action('admin_head', array($this, 'add_styles'));
        }

        if ( 'yes' === get_option( '_is_wpfnl_new_ui_notices', 'yes' )) {
            // Hook into the admin_notices action to display the new UI coming soon notice
            // add_action('admin_notices', [$this, 'display_new_ui_notice']);

            // Add styles for new UI coming soon notice
            // add_action('admin_head', array($this, 'add_new_ui_notice_styles'));
        }
        
    }


    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_banner() {
        $screen                     = get_current_screen();
        $promotional_notice_pages   = ['dashboard', 'plugins', 'toplevel_page_wp_funnels', 'wp-funnels_page_wpfnl_settings'];
        $current_date_time          = current_time('timestamp');

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }

        if ( $current_date_time < $this->start_date || $current_date_time > $this->end_date ) {
            return;
        }
        // Calculate the time remaining in seconds
        $time_remaining = $this->end_date - $current_date_time;

        ?>
        <div class="<?php echo esc_attr($this->occasion); ?>-banner notice">
            <div class="wpfnl-promotional-banner">
                <div class="banner-overflow">
                    
                    <div class="wpfnl-promotional-banner-wrapper">
                        <div class="wpfunnel-promotional-banner-eid-symbol wpfunnel-flex">
                            <figure class="wpfunnel-eid-symbol-with-mosque">
                                <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/eid-symbol-img.webp' ); ?>" alt="Eid Symbol With Mosque" />
                            </figure>

                            <figure class="wpfunnel-eid-mubarak">
                                <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/eid-mubarak-img.webp' ); ?>" alt="Image of Eid Mubarak" />
                            </figure>
                        </div>

                        <div class="wpfunnel-promotional-banner-discount-section wpfunnel-flex">
                            <figure class="wpfunnel-eid-celebration">
                                <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/celebrate-eid-img.webp' ); ?>" alt="Eid Celebration" />
                            </figure>

                            <figure class="wpfunnel-eid-20-percent">
                                <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/eid-20-percent-discount-img.webp' ); ?>" alt="Eid Celebration 20% off" />
                            </figure>
                        </div>

                        <!-- <div class="promotional-counter">
                            <ul class="countdown" id="wpfnl_countdown">
                                <li><span id="wpfnl_days">00</span> days</li>
                                <li><span id="wpfnl_hours">00</span> hours</li>
                                <li><span id="wpfnl_minutes">00</span> mins</li>
                                <li><span id="seconds">59</span> seconds</li>
                            </ul>
                        </div> -->

                        <div class="wpfunnel-promotional-banner-button-image-wrapper wpfunnel-flex">
                            <button class="get-plugin-btn">
                                <a href="<?php echo esc_url($this->btn_link); ?>" target="_blank">
                                Get <span>20%</span> OFF
                                </a>
                            </button>

                            <figure class="mosque-image">
                                <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/eid-symbol-with-mosque-img.webp' ); ?>" alt="Mosque Image" />
                            </figure>
                        </div>
                    </div>
                </div>

                <button class="close-promotional-banner" type="button" aria-label="close banner">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.97L1 11.96m0-9.99l10 9.99"/></svg>
                </button>
            </div>
        </div>

        <script>
            // var timeRemaining = <?php 
            // echo esc_js($time_remaining); 
            ?>;

            // Update the countdown every second
            // setInterval(function() {
            //     // var countdownElement    = document.getElementById('wpfnl_countdown');
            //     // var daysElement         = document.getElementById('wpfnl_days');
            //     // var hoursElement        = document.getElementById('wpfnl_hours');
            //     // var minutesElement      = document.getElementById('wpfnl_minutes');

            //     // Decrease the remaining time
            //     timeRemaining--;

            //     // Calculate new days, hours, and minutes
            //     var days = Math.floor(timeRemaining / (60 * 60 * 24));
            //     var hours = Math.floor((timeRemaining % (60 * 60 * 24)) / (60 * 60));
            //     var minutes = Math.floor((timeRemaining % (60 * 60)) / 60);


            //     // Format values with leading zeros
            //     days = (days < 10) ? '0' + days : days;
            //     hours = (hours < 10) ? '0' + hours : hours;
            //     minutes = (minutes < 10) ? '0' + minutes : minutes;

            //     // Update the HTML
            //     // daysElement.textContent = days;
            //     // hoursElement.textContent = hours;
            //     // minutesElement.textContent = minutes;

            //     // Check if the countdown has ended
            //     if (timeRemaining <= 0) {
            //         countdownElement.innerHTML = 'Campaign Ended';
            //     }
            // }, 1000); // Update every second
        </script>
        <?php
    }

    /**
     * Adds internal CSS styles for the special occasion banners.
     */
    public function add_styles() {
        ?>
        <style type="text/css">
            @font-face {
                font-family: "Circular Std Bold";
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/circularstd-bold.woff2'; ?>) format("woff2"),
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/circularstd-bold.woff'; ?>) format("woff");
                font-weight: 700;
                font-style: normal;
                font-display: swap; 
            }

            @font-face {
                font-family: "Circular Std Book";
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/CircularStd-Book.woff2'; ?>) format("woff2"),
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/CircularStd-Book.woff'; ?>) format("woff");
                font-weight: normal;
                font-style: normal;
                font-display: swap; 
            }

            @font-face {
                font-family: "Inter";
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/Inter-Bold.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/Inter-Bold.woff'; ?>) format("woff");
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Lexend Deca';
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-SemiBold.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-SemiBold.woff'; ?>) format("woff");
                font-weight: 600;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'Lexend Deca';
                src: url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-Bold.woff2'; ?>) format("woff2"), 
                url(<?php echo plugin_dir_url(__FILE__).'assets/fonts/LexendDeca-Bold.woff'; ?>) format("woff");
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }

            .wpfnl-promotional-banner, 
            .wpfnl-promotional-banner * {
                box-sizing: border-box;
            }
            
            .wp-funnels_page_wpfnl_settings .wpfnl-promotional-banner,
            .toplevel_page_wp_funnels .wpfnl-promotional-banner {
                margin: 20px 0;
                width: calc(100% - 20px);
            }

            .wp-funnels_page_wpfnl_settings .eid-ul-fitr-banner.notice,
            .toplevel_page_wp_funnels .eid-ul-fitr-banner.notice {
                margin: 0;
            }
            
            .eid-ul-fitr-banner.notice {
                border: none;
                padding: 0;
                display: block;
                background: transparent;
            }

            .wpfnl-promotional-banner {
                background-color: #224215;
                width: 100%;
                background-image: url(<?php echo WPFNL_URL.'admin/assets/images/eid-banner-bg.webp'; ?>);
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                position: relative;
                border: none;
                box-shadow: none;
                display: block;
            }

            .wpfnl-promotional-banner .banner-overflow {
                overflow: hidden;
                position: relative;
                width: 100%;
            }

            .wpfnl-promotional-banner figure {
                margin: 0;
            }

            .wpfnl-promotional-banner-wrapper {
                display: flex;
                flex-flow: row wrap;
                align-items: center;
                justify-content: space-between;
                gap: 30px;
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 15px;
                z-index: 1;
                position: relative;
                height: 100%;
            }

            .wpfnl-promotional-banner-wrapper .promotional-content {
                max-width: 420px;
            }

            .wpfnl-promotional-banner-wrapper .promotional-logo img {
                max-width: 293px;
                position: relative;
                top: 7px;
                display: block;
            }

            .wpfnl-promotional-banner-wrapper .promotional-content h4 {
                margin: 0;
                text-transform: none;
                color: #ED8136;
                font-family: 'Lexend Deca';
                font-size: 30px;
                font-style: normal;
                font-weight: 700;
                line-height: 1.37;
            }

            .wpfnl-promotional-banner-wrapper .promotional-content h4 span {
                display: block;
                color: #BEB4F4;
                font-family: 'Lexend Deca';
                font-size: 24px;
                font-style: normal;
                font-weight: 600;
                line-height: 1.1;
                letter-spacing: 1.68px;
                text-transform: uppercase;
            }

            .wpfnl-promotional-banner-wrapper .promotional-discount img {
                max-width: 345px;
                display: block;
            }

            .wpfnl-promotional-banner-wrapper .countdown {
                position: relative;
                top: 3px;
            }
            .wpfnl-promotional-banner-wrapper .countdown {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin: 0;
                padding: 0;
            }

            .wpfnl-promotional-banner-wrapper .countdown li {
                display: flex;
                flex-direction: column;
                text-align: center;
                width: 68.5px;
                font-size: 16px;
                list-style-type: none;
                font-family: "Circular Std Book";
                line-height: 1.2;
                font-weight: 500;
                letter-spacing: 1.6px;
                text-transform: uppercase;
                text-align: center;
                color: #A89CC3;
                margin: 0;
            }

            .wpfnl-promotional-banner-wrapper .countdown li span {
                font-size: 44px;
                font-family: 'Inter', sans-serif;
                font-weight: 700;
                line-height: 1;
                color: #fff;
                text-align: center;
                margin-bottom: 10px;
                padding: 4px 2px;

                border-radius: 10px;
                border: 1px solid #FF0083;
                background: linear-gradient(148deg, #2A0856 21.92%, #140102 80.41%);
                box-shadow: 0px 5px 0px 0px #C90369;

            }

            .wpfnl-promotional-banner-wrapper .get-plugin-btn a {
                text-decoration: none;
                padding: 18px 29px 16px;
                border-radius: 15px;
                background: linear-gradient(90deg, rgba(91,38,198,1) 0%, rgba(105,27,212,1) 50%, rgba(191,67,192,1) 100%);
                color: #FFF;
                text-align: center;
                font-family: 'Lexend Deca';
                font-size: 20px;
                font-weight: 700;
                line-height: 1;
                display: block;
                outline: none;
                box-shadow: none;
            }
            .wpfnl-promotional-banner-wrapper .get-plugin-btn a:focus {
                outline: none;
                box-shadow: none;
            }

            .wpfnl-promotional-banner-wrapper .get-plugin-btn span {
                font-family: 'Lexend Deca';
                font-size: 26px;
                font-weight: 700;
                line-height: 1;
            }

            .wpfnl-promotional-banner .close-promotional-banner {
                position: absolute;
                top: -10px;
                right: -9px;
                background: #fff;
                border: none;
                padding: 8px 9px;
                border-radius: 50%;
                cursor: pointer;
                z-index: 9;
            }

            .wpfnl-promotional-banner .close-promotional-banner svg {
                display: block;
                width: 10px;
            }

            .wpfunnel-flex {
                display: flex;
                align-items: center;
                gap: 40px;
            }

            .wpfunnel-flex figure{
                margin-bottom: -9px !important;
            }

            .wpfunnel-flex figure img{
                width: 100% ;
            }

            .wpfunnel-eid-symbol-with-mosque img{
                max-width: 108px;
            }

            .wpfunnel-eid-mubarak img{
                max-width: 192px;
            }
            .wpfunnel-flex .wpfunnel-eid-celebration{
                margin-bottom: -4px !important;
            }
            .wpfunnel-eid-celebration img{
                max-width: 255px;
            }
            .wpfunnel-eid-20-percent {
                margin-bottom: -5px !important;
            }
            .wpfunnel-eid-20-percent img{
                max-width: 362px; 
            }
            .mosque-image img{
                max-width: 78px;
            }

            .wpfunnel-promotional-banner-button-image-wrapper .get-plugin-btn{
                position: relative;
                background: none;
                border: none;
            }
            .wpfunnel-promotional-banner-button-image-wrapper .get-plugin-btn:before {
                position: absolute;
                content: url(<?php echo esc_url( WPFNL_URL.'admin/assets/images/button-image.png' )?>);
                top: -12px;
                right: -22px;
            }

            @media only screen and (min-width: 1600px) {
                .wpfnl-promotional-banner-wrapper{
                    max-width: 1990px;
                }
            }

            @media only screen and (max-width: 1599px) {
                .wpfunnel-flex figure{
                    margin-bottom: -4px !important;
                }

                .wpfunnel-eid-symbol-with-mosque img{
                    max-width: 70px;
                }

                .wpfunnel-eid-mubarak img{
                    max-width: 130px;
                }
                .wpfunnel-flex .wpfunnel-eid-celebration{
                    margin-bottom: -19px !important;
                }
                .wpfunnel-eid-celebration img{
                    max-width: 180px;
                }
                .wpfunnel-flex.wpfunnel-promotional-banner-discount-section .wpfunnel-eid-20-percent {
                    margin-bottom: -35px !important;
                }
                .wpfunnel-eid-20-percent img{
                    max-width: 250px; 
                }
                .mosque-image img{
                    max-width: 75px;
                }
            }

            @media only screen and (max-width: 1350px) {
                .wpfunnel-flex{
                    gap: 20px;
                }

                .wpfunnel-eid-symbol-with-mosque img{
                    max-width: 45px;
                }

                .wpfunnel-eid-mubarak img{
                    max-width: 80px;
                }

                .wpfunnel-eid-celebration img{
                    max-width: 130px;
                }
                .wpfunnel-eid-20-percent img{
                    max-width: 180px; 
                }
                .mosque-image img{
                    max-width: 45px;
                }

                .wpfunnel-promotional-banner-wrapper .get-plugin-btn a{
                    padding: 14px 15px;
                }
                .wpfunnel-flex .wpfunnel-eid-celebration{
                    margin-bottom: -4px !important;
                }
                .wpfunnel-flex.wpfunnel-promotional-banner-discount-section .wpfunnel-eid-20-percent{
                    margin-bottom: -12px !important;
                }
                .wpfunnel-promotional-banner-wrapper .get-plugin-btn:before{
                    content: ""
                }
            }

            @media only screen and (max-width: 975px) {

                .wpfunnel-eid-celebration img{
                    max-width: 100px;
                }
                .wpfunnel-eid-20-percent img{
                    max-width: 140px; 
                }

                .wpfunnel-promotional-banner-wrapper .get-plugin-btn a{
                    padding: 12px;
                    font-size: 12px;
                }
                .wpfunnel-flex .wpfunnel-eid-celebration{
                    margin-bottom: -25px !important;
                }
                .wpfunnel-flex.wpfunnel-promotional-banner-discount-section .wpfunnel-eid-20-percent{
                    margin-bottom: -30px !important;
                }
            }

            @media only screen and (max-width: 700px) {
                .wpfunnel-promotional-banner-wrapper{
                    flex-direction: column;
                    padding: 15px;
                }
            }

            @media only screen and (max-width: 1399px) {
                .wpfnl-promotional-banner-wrapper .promotional-logo img {
                    max-width: 243px;
                }
                .wpfnl-promotional-banner .promotional-discount img {
                    max-width: 285px;
                }

                .wpfnl-promotional-banner-wrapper .promotional-content h4 {
                    font-size: 26px;
                }
                .wpfnl-promotional-banner-wrapper .promotional-content h4 span {
                    font-size: 20px;
                }

                .wpfnl-promotional-banner-wrapper .get-plugin-btn span {
                    font-size: 22px;
                }

                .wpfnl-promotional-banner-wrapper .countdown {
                    gap: 10px;
                }
                .wpfnl-promotional-banner-wrapper .countdown li {
                    width: 58px;
                    font-size: 14px;
                }
                .wpfnl-promotional-banner-wrapper .countdown li span {
                    font-size: 36px;
                    box-shadow: 0px 3px 0px 0px #C90369;
                }

                .wpfnl-promotional-banner-wrapper .get-plugin-btn a {
                    padding: 14px 18px 14px;
                    font-size: 18px;
                    font-weight: 600;
                    border-radius: 10px;
                }
            }

            @media only screen and (max-width: 1199px) {
                .wpfnl-promotional-banner-wrapper {
                    gap: 24px;
                }

                .wpfnl-promotional-banner-wrapper .promotional-content h4 {
                    font-size: 22px;
                }
                .wpfnl-promotional-banner-wrapper .promotional-content h4 span {
                    font-size: 17px;
                }

                .wpfnl-promotional-banner-wrapper .countdown li {
                    width: 47px;
                    font-size: 11px;
                }
                .wpfnl-promotional-banner-wrapper .countdown li span {
                    font-size: 26px;
                }

                .wpfnl-promotional-banner-wrapper .get-plugin-btn a {
                    padding: 14px 18px 14px;
                    font-size: 16px;
                    border-radius: 10px;
                    position: relative;
                    top: 2px;
                }
                .wpfnl-promotional-banner-wrapper .get-plugin-btn span {
                    font-size: 18px;
                }
                
            }

            @media only screen and (max-width: 991px) {
                .wpfnl-promotional-banner-wrapper .promotional-logo img {
                    max-width: 213px;
                }
                .wpfnl-promotional-banner .promotional-discount img {
                    max-width: 265px;
                }
                .wpfnl-promotional-banner-wrapper .get-plugin-btn a {
                    top: 0;
                }
                
            }
        </style>
        <?php
    }


    /**
     * Displays the special occasion banner if the current date and time are within the specified range.
     */
    public function display_new_ui_notice(){
        $screen                     = get_current_screen();
        $promotional_notice_pages   = ['dashboard', 'plugins', 'toplevel_page_wp_funnels', 'wp-funnels_page_wpfnl_settings'];

        if (!in_array($screen->id, $promotional_notice_pages)) {
            return;
        }
        ?>
        <div class="wpfunnels-newui-notice notice">
            <a href="https://youtu.be/OrDQg-XcOLY" target="_blank">
                <div class="newui-notice-wrapper">
                    <figure class="newui-template-img">
                        <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/newui-template-img-2x.webp' ); ?>" alt="newui-template-img" />
                    </figure>

                    <h4 class="newui-notice-title">
                        <span class="highlighted">WPFunnels 3.0 Is Here!</span>

                        <figure class="newui-version">
                            <img src="<?php echo esc_url( WPFNL_URL.'admin/assets/images/wpfunnel-version.svg' ); ?>" alt="wpfunnel-version" />
                        </figure>
                    </h4>
                    <p class="newui-notice-description">Now experience a better funnel-building experience with a better and more intuitive canvas for designing your funnel journey easily.</p>
                </div>
            </a>

            <button class="close-newui-notice" type="button" aria-label="close banner">
                <svg width="20" height="20" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="9.5" fill="#fff" stroke="#FE9A1B"/><path stroke="#FE9A1B" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.5 7.917l-5 5m0-5l5 5"/></svg>
            </button>
        </div>
        <?php
    }


    /**
     * Adds internal CSS styles for new ui notice.
     */
    public function add_new_ui_notice_styles() {
        ?>
        <style type="text/css">
            .wpfunnels-newui-notice * {
                box-sizing: border-box;
            }

            .wpfunnels-newui-notice {
                position: relative;
                border-radius: 5px;
                padding: 0;
                border: none;
                border-left: 3px solid #6E42D3;
                background: #ffffff;
                box-shadow: 0px 1px 2px 0px rgba(39, 25, 72, 0.10);
                box-sizing: border-box;
                background-image: url(<?php echo WPFNL_URL.'admin/assets/images/new-ui-notice-bg.svg'; ?>);
                background-repeat: no-repeat;
                background-size: cover;
                background-position: right center;
            }

            .wpfunnels-newui-notice.notice {
                display: block;
            }

            .wp-funnels_page_wpfnl_settings .wpfunnels-newui-notice,
            .toplevel_page_wp_funnels .wpfunnels-newui-notice {
                margin: 20px 0;
                width: calc(100% - 20px);
            }

            .wpfunnels-newui-notice a {
                text-decoration: none;
            }

            .wpfunnels-newui-notice .newui-notice-wrapper {
                padding: 24px 40px;
                position: relative;
                overflow: hidden;
                border-radius: 5px;
            }

            .wpfunnels-newui-notice .newui-template-img {
                position: absolute;
                right: 0;
                top: 0;
                display: block;
                margin: 0;
            }
            .wpfunnels-newui-notice figure.newui-template-img img {
                max-width: 482px;
                margin: 0;
                display: block;
            }

            .wpfunnels-newui-notice .newui-notice-title {
                margin: 0;
                color: #363B4E;
                font-size: 20px;
                font-weight: 500;
                font-family: "Roboto", sans-serif;
                position: relative;
                display: inline-block;
                z-index: 1;
            }

            .wpfunnels-newui-notice .newui-version {
                position: absolute;
                top: -25px;
                left: calc(100% + 30px);
                margin: 0;
                display: block;
            }

            .wpfunnels-newui-notice .newui-version img {
                display: block;
            }

            .wpfunnels-newui-notice .highlighted {
                color: #6E42D3;
                font-weight: 600;
            }
            
            .wpfunnels-newui-notice .newui-notice-description {
                color: #7A8B9A;
                font-size: 14px;
                font-weight: 400;
                font-family: "Roboto", sans-serif;
                line-height: 1.5;
                max-width: 632px;
                margin: 12px 0 0;
                position: relative;
                z-index: 1;
                padding: 0;
            }

            .wpfunnels-newui-notice .close-newui-notice {
                border: none;
                padding: 0;
                background: transparent;
                display: block;
                line-height: 1;
                cursor: pointer;
                box-shadow: none;
                outline: none;
                position: absolute;
                top: -6px;
                right: -6px;
            }


            @media only screen and (max-width: 1399px) {
                .wpfunnels-newui-notice .newui-template-img {
                    right: -100px;
                }

                .wpfunnels-newui-notice .newui-notice-description {
                    max-width: 592px;
                }

            }

            @media only screen and (max-width: 1199px) {
                .wpfunnels-newui-notice .newui-notice-wrapper {
                    padding: 24px 24px;
                }
                .wpfunnels-newui-notice .newui-notice-description {
                    max-width: 532px;
                }
                .wpfunnels-newui-notice .newui-template-img {
                    right: -226px;
                }
            }
        </style>
        <?php
    }


}