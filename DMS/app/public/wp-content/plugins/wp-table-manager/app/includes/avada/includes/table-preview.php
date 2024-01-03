<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="wptm-table-block-module-preview-template">
    <#
    var elementContent = params.type_table;
    #>
    <h4 class="fusion_module_title wptm-table-title {{{ elementContent }}}"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
    <#
    var nameTable    = params.wptm_selected_table_title;
    var tablePreview = '';

    if ( '' !== nameTable ) {
    tablePreview = jQuery( '<div></div>' ).html( nameTable ).text();
    }
    #>
    <# if ( '' !== nameTable ) { #>
    <span style="font-weight: bold">Table Title: </span>
    <# } #>
    <span class="table-title" style="font-style: italic"> {{{ nameTable }}} </span>

</script>
