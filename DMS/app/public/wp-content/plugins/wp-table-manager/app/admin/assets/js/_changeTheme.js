import alternating from "./_alternating";
import tableFunction from "./_functions";
import selectOption from "./_toolbarOptions"

//change default theme to table, get ajax
function change_theme(ret, id, cellsData) {
    var $ = window.jquery;
    var $jform_css = $('#jform_css');
    $('.full_image').remove();

    tableFunction.wptmBootbox('', wptmText.WARNING_CHANGE_THEME, true, true,() => {
        Wptm.container.handsontable("selectCell", 0,0,0,0);

        $.ajax({
            url: wptm_ajaxurl + "view=style&format=json&id=" + id +"&id-table=" + Wptm.id,
            type: 'POST',
            dataType: 'json',
            data: {}
        }).done(function (data) {
            $('#wptm_popup').find('.colose_popup').trigger('click');
            if (typeof (data) === 'object') {
                if (data.response) {
                    location.reload();
                }
            } else {
                alert(data, wptmText.Ok);
            }
        });
    });
}

export default change_theme
