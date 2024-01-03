function wptm_frameload() {
    jQuery("#wptm_loader").hide();
    jQuery("#wptmmodalframe").css('visibility', "visible");
    jQuery("#wptmmodalframe").show();
}

jQuery(document).ready(function ($) {
    $('#wptmlaunch').wptm_leanModal({
        top: 20, beforeShow: function () {
            $("#wptmmodal").css("height", "90%");
            $("#wptmmodalframe").css('visibility', 'hidden');
            $("#wptmmodalframe").attr('src', $("#wptmmodalframe").attr('src'));
            $("#wptm_loader").show();
        }
    });
    $('body').append('<div id="wptmmodal"><img src="' + wptmVars.adminurl + '/images/spinner-2x.gif" width="32" id="wptm_loader" /> <iframe id="wptmmodalframe" onload="wptm_frameload()"  width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" src="' + wptmVars.adminurl + 'admin.php?page=wptm&noheader=1&caninsert=1" /><button id="wptm-close-modal" onclick="jQuery(\'#lean_overlay\',window.parent.document).fadeOut(300);jQuery(\'#wptmmodal\',window.parent.document).fadeOut(300);" style="position: absolute; right: -23px;">x</button></div>');

    return false;
});
