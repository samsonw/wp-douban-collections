jQuery(document).ready(function() {
  jQuery('#load_resources_only_in_douban_collections_page').click(function() {
    if(jQuery(this).is(':checked')){
      jQuery('#douban_collections_page_names').removeAttr('disabled');
    }else{
      jQuery('#douban_collections_page_names').attr('disabled', 'disabled');
    }
  });
});