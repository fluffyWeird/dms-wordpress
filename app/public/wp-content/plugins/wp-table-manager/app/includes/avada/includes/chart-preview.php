<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="wptm-chart-block-module-preview-template">
    <h4 class="fusion_module_title wptm-chart-title "><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
    <#
    var nameChart    = params.wptm_selected_chart_title;
    var tablePreview = '';

    if ( '' !== nameChart ) {
    chartPreview = jQuery( '<div></div>' ).html( nameChart ).text();
    }
    #>
    <# if ( '' !== nameChart ) { #>
    <span style="font-weight: bold">Chart Title: </span>
    <# } #>
    <span class="chart-title" style="font-style: italic"> {{{ nameChart }}} </span>

</script>
