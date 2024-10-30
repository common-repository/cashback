jQuery(document).ready(function () {
    jQuery('.c247-clickout-link').click(function(){
        var object = jQuery(this);
        var id = object.attr('data-id');
        var type = object.attr('data-type');
        var src = object.attr('href');
        if(src == undefined){
            src = object.attr('data-href');
        }
        if(type == undefined){
            type = 'offer';
        }
        window.open(src);
        location.href = 'https://www.247discount.nl/clickout/'+type+'/'+id+'/';
        return false;
    });
});