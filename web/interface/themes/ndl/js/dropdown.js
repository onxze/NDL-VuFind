// Original idea by Janko Jovanovic
// http://www.jankoatwarpspeed.com/post/2009/07/28/reinventing-drop-down-with-css-jquery.aspx
//
// Modifications by NDL

$(document).ready(function() {
    createDropdowns();
    initDropdowns();
});


function initDropdowns() {
    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        $(".dropdown").trigger("toggle", [false]);
    });
    
    $(".dropdown dt a").not(".initDone").on('click', function(e) {
        $(".dropdown").each(function(ind,o) { 
            var parObj = $(e.target).parent().parent();
            if ($(this).get(0) != parObj.get(0)) {
                $(this).trigger("toggle", [false]);
            }
        });
        
        var dropdown = $(this).closest('dl.dropdown');
        dropdown.trigger("toggle", [!dropdown.data("menuOpen")]);

        // prevent event from bubbling to document.click
        return false;
    });

    $(".dropdown dd ul li a").not(".initDone").on('click', function(e) {
        var dropdown = $(this).closest('dl.dropdown');
        if (!dropdown.hasClass("dropdownStatic")) {
            var text = $(this).html();
            dropdown.find('dt a').html(text);
        }
        dropdown.find('dd ul').fadeOut(100);
        

        dropdown.trigger("truncate");

        // Get id of the hidden select element
        var source = dropdown.next('select');
        
        source.find('option').removeAttr('selected');
        var val = $(this).find("span.value").text();
        // attempt to find option-elements
        source.find('option[value="'+val+'"]').attr('selected', 'selected').change();
        dropdown.trigger("menuClick", val);

        e.preventDefault();
    });

    $(".dropdown").not(".initDone").data("menuOpen", 0);

    $(".dropdown").not(".initDone").on("toggle", function(e, mode) {
        var currentMode = $(this).data("menuOpen");
        if (currentMode == mode) {
          return;
        }

        var menu = $(this).find('dd ul');
        menu.stop().fadeTo(100, mode ? 1 : 0, function() { if (!mode) { menu.hide(); } });

        // send menuOpen/menuClose events
        var event = mode ? 'menuOpen' : 'menuClose';
        $(this).trigger(event);
        
        if (mode) {
            // Reposition dropdown if it does not fit on the screen.
            var ul = $(this).find('ul');
            if (ul.offset().left < 10) {
                var w = ul.width();
                $(this).offset({left: ul.width() + 10});
            }
        }

        // save state
        $(this).data("menuOpen", mode ? 1 : 0);
    });

    $(".dropdown").not(".initDone").on("truncate", function(e) {
        var a = $(this).find('dt a');
        var txt = a.html();
        if (txt) {
            var pos = txt.indexOf("…");
            if (pos != -1) {
                // already truncated, abort
                return;
            }

            // link text is followed by a hidden <span> with the actual url, ignore this
            pos = txt.indexOf("<span");
            if (pos != -1) {
                txt = txt.substring(0,pos);
            }
            
            
            var truncated = txt;
            if ($(this).hasClass("dropdownTruncate")) {
                // truncate label
                a.html(".");
                var singleLineHeight = a.height();
                var txtLen = txt.length;
                var ind = 0;
                while(ind <= txtLen) {
                    truncated = txt.substring(0,ind++);
                    a.html(truncated + " &nbsp;");
                    if (a.height() > singleLineHeight) {
                        // leave space for icon
                        ind = ind-6;
                        if (ind < 0) {
                            ind = 0;
                        }                    
                        truncated = txt.substring(0,ind) + '&hellip;';
                        break;
                    }
                }
            }
            a.html(truncated);
        }
    });


    $(".dropdown").trigger("truncate");
    $(".dropdown dt a").removeClass("initDone").addClass("initDone");
    $(".dropdown dd ul li a").removeClass("initDone").addClass("initDone");
    $(".dropdown").removeClass("initDone").addClass("initDone");
}

// Function for creating dropdowns
function createDropdowns(){
    var counter = 0;
    $('.styledDropdowns, .searchForm_styled, .jumpMenu, .jumpMenuURL, .resultOptionLimit select').not('.stylingDone').each(function() { 
        var source = $(this);
        var selected = source.find("option:selected");
        var options = $("option", source);
        var idName = $(this).attr("id");
        var target = 'styled_'+idName+counter;
        counter++;
        
        var classNames = "dropdown";
        // truncate label?
        if ($(this).hasClass("dropdownTruncate")) {
            classNames += " dropdownTruncate";
        }
        // keep label fixed regardless of selected option
        var staticLabel = $(this).hasClass("dropdownStatic");
        if (staticLabel) {
            classNames += " dropdownStatic";
        }

        var html = '<dl id="'+target+'" class="' + classNames + ' ' + idName + '">';
        html += '<dt><a href="#">' + selected.text() + 
                             '<span class="value">' + selected.val() + 
                             '</span></a></dt>';
        html += '<dd><ul id="owned_'+idName+'" role="menu" tabindex="0" aria-haspopup="true">';
        
        options.each(function(){
            html += $(this).text().trim()
                ? '<li role="menuitem" tabindex="-1"><a href="#" class="big">' + 
                                              $(this).text() + '<span class="value">' + 
                                              $(this).val() + '</span></a></li>'
                : '<li style="height: 2px"><hr/></li>'
            ;
        });

        html += '</ul></dd></dl>';

        $(this).hide().addClass('stylingDone').before(html);

    })
}
