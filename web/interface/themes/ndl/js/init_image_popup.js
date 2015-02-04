$(document).ready(function() {
    $(".imagePopup-trigger").click(function(e) {
        e.preventDefault();
        $("a[href='" + $(this).attr('href') + "']").eq(0).click();
     });    
});

$(document).ready(function() {
    $('.imagePopup').magnificPopup({
        type:'ajax',
	    tLoading: trLoading,
        ajax: {
            cursor: ''
        },
        callbacks: {
            ajaxContentAdded: function() {
                var popup = $(".imagePopupHolder");
                var type = popup.data("type");
                var id = popup.data("id");
                if (type == 'marc') {
                    // Load description                    
                    $(".imagePopupHolder .summary > div").load(path + '/AJAX/AJAX_Description?id=' + id, function(response, status, xhr) {
                        if (response.length != 0) {
                            resizeImagePopupContent();
                        }
                    });
                }

                $(".imagePopupHolder .image img").one("load", function() {
				    $(".imagePopupHolder .image").addClass('loaded');
                    resizeImagePopupContent();
                }).each(function() {
                    if(this.complete) {
                        $(this).load();
                    }
                });

                var notesHolder = $(".imagePopupHolder .content .listNotes");
                notesHolder.hide();
                
                var magnificPopup = $.magnificPopup.instance;
                var src = magnificPopup.currItem.src;
                var rec = magnificPopup.currItem.el.closest(".recordId");

                // Public list notes
                var listNotes = rec.data("notes");
                if (typeof(listNotes) != "undefined") {
                    notesHolder.show();
                    notesHolder.find(".text").html(listNotes);
                    
                    var listUser = rec.data("notes-user");
                    if (typeof(listUser) != "undefined") {
                        $(".notes-user").html(" (" + listUser + ")");
                    }
                }

                // Image copyright information
                $(".imagePopupHolder .imageRights .copyrightLink a").on("click", function() {
                    var mode = $(this).data("mode") == 1;                                      
                    
                    var moreLink = $(".imagePopupHolder .imageRights .moreLink");
                    var lessLink = $(".imagePopupHolder .imageRights .lessLink");
                    
                    moreLink.toggle(!mode);
                    lessLink.toggle(mode);
                    
                    $(".imagePopupHolder .imageRights .copyright").toggle(mode);
                    resizeImagePopupContent();
                    
                    return false;                                      
                });

                // Prevent navigation button CSS-transitions on touch-devices
                if (isTouchDevice()) {
                    $(".mfp-container .mfp-arrow-right, .mfp-container .mfp-arrow-left").addClass('touchDevice');
                }
            },
            resize: function() {
                resizeImagePopupContent();
            }
        },
        gallery: {
            enabled: true,
            preload: [0,2],
            navigateByImgClick: true,
            arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
            tPrev: trPrev,
            tNext: trNext,
            tCounter: ''
        }
	    
    });
});


function resizeImagePopupContent() {
    var content = $('.imagePopupHolder .content');
    content.css("height", "auto");
    
    if ($(window).width() > 800) {
        var h = Math.max(content.height(), $('.imagePopupHolder .image').height());        
        content.height(h);                  
    }
}
 
