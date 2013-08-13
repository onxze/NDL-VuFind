$(document).ready(function() {
    checkSaveStatuses();
    // attach click event to the save record link
    $('a.saveRecord').unbind('click').click(function(e) {
        console.log("SaveRecord click");
        var id = this.id.substr('saveRecord'.length);
        var $dialog = getLightbox('Record', 'Save', id, null, this.title, 'Record', 'Save', id);
        e.preventDefault();
    });    
    $('a.saveMetaLibRecord').unbind('click').click(function(e) {
        var id = this.id.substr('saveRecord'.length);
        var $dialog = getLightbox('MetaLib', 'Save', id, null, this.title, 'MetaLib', 'Save', id);
        e.preventDefault();
    });    
    $('a.savePCIRecord').unbind('click').click(function(e) {
        var id = this.id.substr('saveRecord'.length);
        var $dialog = getLightbox('PCI', 'Save', id, null, this.title, 'PCI', 'Save', id);
        e.preventDefault();
    });    
});

function checkSaveStatuses() {
    var id = $.map($('.recordId'), function(i) {
        return $(i).attr('id').substr('record'.length);
    });
    if (id.length) {    
        $.ajax({
            dataType: 'json',
            url: path + '/AJAX/JSON?method=getSaveStatuses',
            data: {id:id},
            success: function(response) {
                if(response.status == 'OK') {
                    $('.savedLists > ul').empty();
                    $.each(response.data, function(i, result) {
                        var $container = $('#savedLists' + jqEscape(result.record_id));
                        var $ul = $container.children('ul:first');
                        if ($ul.length == 0) {
                            $container.append('<ul></ul>');
                            $ul = $container.children('ul:first');
                        }
                        var html = '<li><a href="' + path + '/MyResearch/MyList/' + result.list_id + '">' 
                                 + result.list_title + '</a></li>';
                        $ul.append(html);
                        $container.show();
                    });
                }
            }
        });
    }
}


