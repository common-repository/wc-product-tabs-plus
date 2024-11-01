( function( $ ) {
    $( document ).ready( function() {
        $('#display-none-product-tab-radio').on('change', function(){
            if($(this).is(':checked')) {
                $("#if-show-on-products").hide();
                $("#if-show-on-categories").hide();
                $("#if-show-on-tags").hide();
            }
        });

        $('#display-all-product-tab-radio').on('change', function(){
            if($(this).is(':checked')) {
                $("#if-show-on-products").hide();
                $("#if-show-on-categories").hide();
                $("#if-show-on-tags").hide();
            }
        });

        $('#display-specific-products-product-tab-radio').on('change', function(){
            if($(this).is(':checked')) {
                $("#if-show-on-products").show();
                $("#if-show-on-categories").hide();
                $("#if-show-on-tags").hide();
            }
        });

        $('#display-specific-categories-product-tab-radio').on('change', function(){
            if($(this).is(':checked')) {
                $("#if-show-on-categories").show();
                $("#if-show-on-products").hide();
                $("#if-show-on-tags").hide();
            }
        });

        $('#display-specific-tags-tab-radio').on('change', function(){
            if($(this).is(':checked')) {
                $("#if-show-on-tags").show();
                $("#if-show-on-products").hide();
                $("#if-show-on-categories").hide();
            }
        });

        $('#show-for-duration').on('change', function(){
            if($(this).is(':checked')) {
                $(".if-show-for-duration").show();
            } else {
                $(".if-show-for-duration").hide();
            }
        });
    } );

   
}( jQuery ) );

jQuery(function($){
    $('#show_on_specific_products_select').select2();
    $('#show_on_categories_select').select2();
    $('#show_on_tags_select').select2();
});