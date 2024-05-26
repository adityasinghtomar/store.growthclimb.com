(function($) {
    'use strict';
    
    // ---delete promotional-banner notice permanently ------
    $(document).on("click", ".wpfnl-promotional-banner .close-promotional-banner", function(event) {
		event.preventDefault();
        $('.wpfnl-promotional-banner').css('display','none');
		wpAjaxHelperRequest("delete_promotional_banner")
	});

    // ---delete new UI notice permanently ------
    $(document).on("click", ".wpfunnels-newui-notice .close-newui-notice", function(event) {
		event.preventDefault();
        $('.wpfunnels-newui-notice.notice').css('display','none');
		wpAjaxHelperRequest("delete_new_ui_notice")
	});

})(jQuery);


