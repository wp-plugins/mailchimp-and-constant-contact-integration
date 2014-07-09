jQuery(document).ready(function() {
    jQuery('#mainform [id^=woocommerce_mailchimp_cc]').closest('tr').hide('fast'); //close mailchimp fields
    jQuery('#mainform [id^=woocommerce_mailchimp_mc]').closest('tr').hide('fast'); //close cc fields
    
    if (jQuery('#woocommerce_mailchimp_enabled_mc').prop('checked') == true) {
        jQuery('#mainform [id^=woocommerce_mailchimp_mc]').closest('tr').show('fast'); //show mailchimp fields
        jQuery('#woocommerce_mailchimp_enabled_cc').prop('disabled', true); // disable cc checkbox

    }
    if (jQuery('#woocommerce_mailchimp_enabled_cc').prop('checked') == true) {
        jQuery('#mainform [id^=woocommerce_mailchimp_cc]').closest('tr').show('fast');
        jQuery('#woocommerce_mailchimp_enabled_mc').prop('disabled', true);
    }
    
    jQuery('#woocommerce_mailchimp_enabled_mc').click(function() { 
        if (jQuery(this).prop('checked') == true) {
            jQuery('#mainform [id^=woocommerce_mailchimp_mc]').closest('tr').show('fast');
            jQuery('#woocommerce_mailchimp_enabled_cc').prop('disabled', true);
        } else {
            jQuery('#mainform [id^=woocommerce_mailchimp_mc]').closest('tr').hide('fast');
            jQuery('#woocommerce_mailchimp_enabled_cc').prop('disabled', false);
        }
        if (jQuery('#woocommerce_mailchimp_enabled_cc').prop('checked') == true) {
            jQuery('#mainform [id^=woocommerce_mailchimp_mc]').closest('tr').hide('fast');

        }

    });
    jQuery('#woocommerce_mailchimp_enabled_cc').click(function() {
        if (jQuery(this).prop('checked') == true) {
            jQuery('#mainform [id^=woocommerce_mailchimp_cc]').closest('tr').show('fast');
            jQuery('#woocommerce_mailchimp_enabled_mc').prop('disabled', true);
        } else {
            jQuery('#mainform [id^=woocommerce_mailchimp_cc]').closest('tr').hide('fast');
            jQuery('#woocommerce_mailchimp_enabled_mc').prop('disabled', false);
        }
        if (jQuery('#woocommerce_mailchimp_enabled_mc').prop('checked') == true) {
            jQuery('#mainform [id^=woocommerce_mailchimp_cc]').closest('tr').hide('fast');
        }

    });
});
