/**
 * Initialize common functions and event handlers.
 */
// prevent console.log errors in ie
if (!window.console) console = {log: function() {}};
// disable caching for all AJAX requests
$.ajaxSetup({cache: false});

// set global options for the jQuery validation plugin
$.validator.setDefaults({
    errorClass: 'invalid'
});
    
// add a modified version of the original phoneUS rule 
// to accept only 10-digit phone numbers
$.validator.addMethod("phoneUS", function(phone_number, element) {
    phone_number = phone_number.replace(/[-\s().]+/g, ""); 
    return this.optional(element) || phone_number.length > 9 &&
        phone_number.match(/^(\([2-9]\d{2}\)|[2-9]\d{2})[2-9]\d{2}\d{4}$/);
}, 'Please specify a valid phone number');


$(document).ready(function(){
    // Remove Facebook anchors from URL in order 
    // to prevent jquery.ui.tabs to use invalid selectors.  
    var hash = document.location.hash;
    if (hash.indexOf('.facebook') !== -1) {
      document.location.hash = '';
    }

    // initialize autocomplete
    initAutocomplete();    

    // initialize clearable fields (embedded clear button)
    // Do this before setting focus
    initClearable();
    
    initJumpMenus();
    
    initBodyClassSwitcher();
    
    // attach click event to the search help links
    /*
    $('a.searchHelp').click(function(){
        window.open(path + '/Help/Home?topic=search', 'Help', 'width=625, height=510');
        return false;
    });
    */

    // attach click event to the advanced search help links
    /*
    $('a.advsearchHelp').click(function(){
        window.open(path + '/Help/Home?topic=advsearch', 'Help', 'width=625, height=510');
        return false;
    });
    */
   
   // Init placeholder (for archaic browsers)
   $.fn.placeholder();
    
    // assign click event to "email search" links
    $('a.mailSearch').click(function(e) {
        var id = this.id.substr('mailSearch'.length);
        var $dialog = getLightbox('Search', 'Email', id, null, this.title);
        e.preventDefault();
    });

    // assign action to the "select all checkboxes" class
    $('input[type="checkbox"].selectAllCheckboxes').change(function(){
        var newValue = $(this).attr('checked') ? true : false;
        $(this.form).find('input[type="checkbox"]').attr('checked', newValue);
    });
    
    // attach mouseover event to grid view records
    $('.gridCellHover').mouseover(function() {
        $(this).addClass('gridMouseOver');
    });
    
    // attach mouseout event to grid view records
    $('.gridCellHover').mouseout(function() {
        $(this).removeClass('gridMouseOver');
    });  
    
    // assign click event to "viewCart" links
    $('a.viewCart').click(function(e) {
        var $dialog = getLightbox('Cart', 'Home', null, null, this.title, '', '', '', {viewCart:"1"});
        e.preventDefault();
    });
    
    // Print
    var url = window.location.href;
    if(url.indexOf('?' + 'print' + '=') != -1  || url.indexOf('&' + 'print' + '=') != -1) {
        $("link[media='print']").attr("media", "all");
        window.print();
    }

    // Toggle Keep filters -option

    // detect when mouse is inside search area 
    $('#searchFormContainer').hover(
        function() {
            $(this).addClass("hover");
        },
        function() {
            $(this).removeClass("hover");
        }
    );

    // show when search field is focused
    var searchInput = $('#searchForm_input');
    var prefilterMenu = $("#searchForm").find(".dropdown");


    // attach click event to the "keep filters" checkbox
    $('#searchFormKeepFilters').change(function(e) { 
        filterAll(this);
        toggleKeepFiltersOption(false); 
    });


    // Show "keep filters" checkbox when search input is focused or changed
    searchInput.focus(function(e) { 
        toggleKeepFiltersOption(true); 
    });
    searchInput.change(function(e) {
        toggleKeepFiltersOption(true);
    });
    searchInput.keydown(function(e) {
        toggleKeepFiltersOption(true);
    });

    // Hide "keep filters" checkbox when search input is blurred
    searchInput.blur(function(e) { 
        toggleKeepFiltersOption(false); 
    });
    
    
    // Show "keep filters" checkbox when prefilter -menu is opened
    prefilterMenu.bind("menuOpen", function() { 
        toggleKeepFiltersOption(true); 
    });
    // Hide "keep filters" checkbox when prefilter -menu is closed
    prefilterMenu.bind("menuClose", function() { toggleKeepFiltersOption(false); });


    // When search form is submitted, insert a hidden field for "keep filters" -value. 
    // This way the variable gets posted also when the checkbox is unchecked.
    var searchForm = $('#searchForm');
    searchForm.submit(function(e) {
        var opt = $(this).find("#searchFormKeepFilters");
        if (opt) {
            var retainFilters = opt.is(":checked") ? 1 : 0;
            $(this).append('<input type="hidden" name="retainFilters" value="' + retainFilters + '" />');            

            if (!retainFilters) {
                // Reset timerange query type
                $(this).find('input[name="search_sdaterange_mvtype"]').remove();
            }
        }
    });
});

function toggleMenu(elemId) {
    var elem = $("#"+elemId);
    if (elem.hasClass("offscreen")) {
        elem.removeClass("offscreen");
    } else {
        elem.addClass("offscreen");
    }
}

function moreFacets(name) {
    $("#more"+name).hide();
    $("#narrowGroupHidden_"+name).removeClass("offscreen");
}

function lessFacets(name) {
    $("#more"+name).show();
    $("#narrowGroupHidden_"+name).addClass("offscreen");
}

function filterAll(element, formId) {
    //  Look for filters  (specifically checkbox filters) from all search types (local, PCI)
    if (formId == null) {
        formId = "searchForm";
    }

    $("[id^=applied_filter_]").attr('checked', element.checked);

    // switch to default sort mode
    var field = $("#searchForm").find("input[name='sort']");
    field.attr("disabled", $('#searchFormKeepFilters').is(":checked") ? false : "disabled");
}

function extractParams(str) {
    var params = {};
    if (str == null) {
        return params;
    }
    var classes = str.split(/\s+/);
    var i;
    for(i = 0; i < classes.length; i++) {
        if (classes[i].indexOf(':') > 0) {
            var pair = classes[i].split(':');
            params[pair[0]] = pair[1];
        }
    }
    return params;
}

// return unique values from the given array
function uniqueValues(array) {
    var o = {}, i, l = array.length, r = [];
    for(i=0; i<l;i++) {
        o[array[i]] = array[i];
    }
    for(i in o) {
        r.push(o[i]);
    }
    return r;
}

function initAutocomplete() {
    var searchInput = $('#searchForm_input.autocomplete');
    if (searchInput.length === 0)
        return;
    var searchForm = $('#searchForm');
    var lastXhr = null;
    var params = extractParams(searchInput.attr('class'));
    var maxItems = params.maxItems > 0 ? params.maxItems : 10;
    var minLength = params.minLength > 0 ? params.minLength : 3;
    var position = 
        typeof(module) != 'undefined' && module != 'Browse' && isKeepFiltersOptionPresent() 
        ? { offset: '0 46'} : { offset: '0 6'}
    ;

    ac = searchInput.autocomplete({    
        position: position,
        minLength: minLength,
        select: function(e, ui) {
            if (e.keyCode === 13 && searchInput.val() != ui.item.label) {
                searchForm.submit();
                return false;
            }
            searchInput.val(ui.item.label);
            searchForm.submit(); 
        },
        source: function(request, response) {
            var type = params.type;
            if (!type && params.typeSelector) {
                type = $('#' + params.typeSelector).val();
            } 
            // Abort previous access if one is defined
            if (lastXhr !== null && lastXhr.hasOwnProperty("abort")) {
                lastXhr.abort();
            }
            var prefilterValue = $('select#searchForm_filter option:selected').val();
            lastXhr = $.ajax({
                cache: false,
                url: path + '/AJAX/JSON_Autocomplete',
                data: {method:'getSuggestions',type:type,q:request.term,prefilter:prefilterValue},
                dataType:'json',
                success: function(json) {
                    if (json.status == 'OK' && json.data.length > 0) {
                        response(json.data.slice(0, maxItems));
                    } else {
                        ac.autocomplete('close');
                    }
                }
                });
        },
        open: function() {
            toggleKeepFiltersOption(true);                
        }
    });

    ac.data( "autocomplete" )._renderItem = function(ul, item) {
        var label = item.label.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" 
                        + $.ui.autocomplete.escapeRegex(this.term) 
                        + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
        return $("<li></li>")
                .data("item.autocomplete", item)
                .append("<a>" + label + "</a>")
                .appendTo(ul);
    };
}

function initClearable(){
    $('.clearable').clearSearch();
    // update value
    valueContent = $(".clearable").attr("value");
    if (valueContent == null) {
      $(".clearable").val("").change();
    };
}

function initSearchInputListener() {
    var searchInput = $('#searchForm_input');
    $(window).keypress(function(e) {
        
        if (e && (!$(e.target).is('input[type="text"], textarea') && searchInput.length > 0) 
              && !$(".ui-dialog").is(":visible") 
              && (e.which !== 0 && e.charCode !== 0 && e.charCode !== 32)
              && !(e.metaKey || e.ctrlKey || e.altKey)) {
            
            var letter = String.fromCharCode(e.which);
            
            // IE 8-9
            if (typeof document.createElement("input").placeholder == 'undefined') {
                if (searchInput.val() == searchInput.attr('placeholder')) {
                  searchInput.val('');
                  searchInput.removeClass('placeholder');
                }
            }
            
            // Move cursor to the end of the input
            var tmpVal = searchInput.val();
            searchInput.val(' ').focus().val(tmpVal + letter);
            
            // Scroll to the search form
            $('html, body').animate({
                scrollTop: searchInput.offset().top - 20
            }, 150);
           
            e.preventDefault();
       }
    });
}

function initJumpMenus() {
    // support "jump menu" dropdown boxes
    $('select.jumpMenu').change(function(){ $(this).parent('form').submit(); });

    // support "jump menu to url" dropdown boxes
    $('select.jumpMenuURL').change(function(e) { window.location.href = $(e.target).val(); });
}

function htmlEncode(value){
    if (value) {
        return jQuery('<div />').text(value).html();
    } else {
        return '';
    }
}

// escape element id so that it can be used as a selector
function jqEscape(myid) {
    return String(myid).replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

function printIDs(ids) {

    var url = '';
    if(ids.length == 0) {
        return false;
    }
    if(ids.length == 1) {
            url =  path + '/Record/' + encodeURIComponent(ids[0]) + '?type=ids&print=true';
    }
    else {
        $(ids).each(function() {
           url += encodeURIComponent(this) + '+'; 
        });
        url =  path + '/Search/Results?lookfor=' + url + '&type=ids&print=true';
    }
    window.open(url);
    return true;
}
function isTouchDevice() {
    return !!('ontouchstart' in window) 
        || !!('onmsgesturechange' in window); // IE10
};


_hideKeepFiltersTimer = null;
function toggleKeepFiltersOption(mode) {    
    var obj = $("#searchForm").find(".keepFilters");
    if (mode) {        
        obj.show();
    }

    if (_hideKeepFiltersTimer) {
        window.clearTimeout(_hideKeepFiltersTimer);
    }
    
    if (mode) {
        obj.stop().fadeTo(50,1);
    } else {
        // Hide checkbox after a small delay. 
        // This way the user notices the new checkbox value before it is hidden.
        _hideKeepFiltersTimer = window.setTimeout(function() {
            obj.stop().fadeTo(400,0,function() { 
                if (!mode) { 
                    $(this).hide(); 
                }
            });
        }, 1000);        
    }
}

function isKeepFiltersOptionPresent() {
    return $('#searchFormKeepFilters').length > 0;
}

(function($) {
  $.fn.placeholder = function() {
    if(typeof document.createElement("input").placeholder == 'undefined') {
      $('[placeholder]').focus(function() {
        var input = $(this);
        if (input.val() == input.attr('placeholder')) {
          input.val('');
          input.removeClass('placeholder');
        }
      }).blur(function() {
        var input = $(this);
        if (input.val() == '' || input.val() == input.attr('placeholder')) {
          input.addClass('placeholder');
          input.val(input.attr('placeholder'));
        }
      }).blur().parents('form').submit(function() {
        $(this).find('[placeholder]').each(function() {
          var input = $(this);
          if (input.val() == input.attr('placeholder')) {
            input.val('');
          }
      })
    });
  }
}
})(jQuery);

function NDLCarousel(carouselId, itemsPerPage, scrolledItems, scrollSpeed, fixedHeight) {
    this.currentCarousel = $('#NDLCarousel-' + carouselId);
    this.carouselParent = this.currentCarousel.parent();

    this.carouselHeight, this.carouselWidth;
    
    this.carouselId = carouselId;
    this.itemsPerPage = itemsPerPage;
    this.scrolledItems = scrolledItems;
    this.scrollSpeed = scrollSpeed;
    
    var customHeight = (typeof fixedHeight !== 'undefined' && fixedHeight > 0) ?
        fixedHeight : false;

    this.horizontalPadding = 2 * parseInt(this.currentCarousel.children().first().css('margin-right'), 10);

    var self = this;

    this.render = function() {

        self.currentCarousel.trigger('destroy');

        self.carouselWidth = self.currentCarousel.parent().width();
        this.itemWidth = (self.carouselWidth - (self.itemsPerPage * self.horizontalPadding)) / self.itemsPerPage;
        self.carouselHeight = customHeight ? customHeight : 1.36 * this.itemWidth;

        self.currentCarousel.carouFredSel({
            responsive: true,
            // TODO: implement direction
            //direction:{/literal}'{$rssFeed.direction}'{literal},
            auto: 8000,
            width: "100%",
            items: self.itemsPerPage,
            height: self.carouselHeight,
            prev: '#NDLCarouselNavi-' + self.carouselId + ' .prev',
            next: '#NDLCarouselNavi-' + self.carouselId + ' .next',
            swipe: {
                onTouch: false,
                onMouse: false
            },
            scroll: {
              items: self.scrolledItems,
              duration: self.scrollSpeed,
              fx: "directscroll",
              pauseOnHover: true
            },
            onCreate: function(data) {

                this.containerWidth = self.currentCarousel.children().first().width();
                this.containerHeight = customHeight ? customHeight : 1.36 * this.containerWidth;
                this.containerRatio = this.containerWidth / this.containerHeight;

                $('#NDLCarouselNavi-' + self.carouselId + ' li').css({
                    'top'         : -(this.containerHeight / 2) - $('#NDLCarouselNavi-' + self.carouselId + ' .prev').height() / 2
                });

                self.currentCarousel.css({
                    'height'         : self.carouselHeight
                });
                self.currentCarousel.parent().css({
                    'height'         : self.carouselHeight
                });
                self.currentCarousel.children('li').css({
                    'height'         : self.carouselHeight,
                    'line-height' : self.carouselHeight + 'px'
                });
                
                var currentContainer = this;

                self.currentCarousel.filter('.includeDescription').find('img').each(function(){
                    $(this).imagesLoaded(function() {
                        $(this).css({
                            'height'      : '',
                            'width'       : '',
                            'visibility'  : 'visible'
                        });
                        var imgWidth = $(this).width();
                        var imgHeight = $(this).height();

                        var imgRatio = imgWidth / imgHeight;
                        var newWidth = 0;
                        var newHeight = 0;

                        if(currentContainer.containerRatio < imgRatio) {
                            newWidth = currentContainer.containerHeight * imgRatio;
                            newHeight = currentContainer.containerHeight;
                        } else {
                            newWidth = currentContainer.containerWidth;
                            newHeight = currentContainer.containerWidth / imgRatio;
                        }

                        var verticalPosition = (newHeight - currentContainer.containerHeight) / 2;
                        var horizontalPosition = (newWidth - currentContainer.containerWidth) / 2;

                        $(this).css({
                            'height'      : newHeight,
                            'width'       : newWidth,
                            'position'    : 'absolute',
                            'left'        : - horizontalPosition,
                            'top'         : - verticalPosition,
                            //'display'     : 'none',
                            'visibility'  : 'visible'
                        });
                    });
                });

                // Set title and text position
                self.currentCarousel.find('h4').each(function() {
                    var myHeight = $(this).find('a').height() + 12; // 6px + 6px padding
                    $(this).siblings('p').css('top', myHeight);
                });

                self.currentCarousel.css(
                    "visibility",
                    "visible"
                );
            }
        });


    };

    this.render();

    // Make individual pick-ups clickable
    this.currentCarousel.children('li').click(function() {
                var href = $(this).find('a').attr('href');
                window.location.href = href;
    });

    this.currentCarousel.filter('.includeDescription').children('li').mouseenter(function() {
        var headers = $(this).find('h4');
        var paragraphs = $(this).find('p');
        var h4Height = headers.first().height() + 12;
        headers.stop().animate({
            top: 0
         }, 50, function() {
            paragraphs.stop(true,true).delay(50).fadeIn(200);
         });

    });

    this.currentCarousel.filter('.includeDescription').children('li').mouseleave(function() {
        var headers = $(this).children('h4');
        var paragraphs = $(this).children('p');
        var h4Height = $(this).find('a').height() + 12;
        headers.stop().animate({
            top: self.carouselHeight - h4Height
        }, 100, function() {  // callback function necessary to remove css top property
            $(this).css('top', '')
        });
        paragraphs.stop(true, true).fadeOut(200);

    });

    // Function to refresh carousel when layout changes
    $(window).resize(function() {
        delay(function(){
            if(self.currentCarousel.parent().width() != self.carouselWidth) {
                self.render();
            }
        }, 250);
    });

    // Delay function to execute renderCarousel only with the last call during resize
    var delay = (function(){
      var timer = 0;
      return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
      };
    })();


}

function initBodyClassSwitcher() {
    switchBodyClass();
    
    $(window).resize(function() {
        switchBodyClass();
    });
}

function switchBodyClass() {
    var w = $('.content').width();
    if (!isNaN(w)) {
        if (w == '480') {
            $('body').removeClass('layout720').removeClass('layout960')
                .addClass('layout480');
        } else if (w == '720') {
            $('body').removeClass('layout480').removeClass('layout960')
                .addClass('layout720');
        } else if (w == '960') {
            $('body').removeClass('layout480').removeClass('layout720')
                .addClass('layout960');
        }
    }
    
}
