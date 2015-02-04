/**
 * Initialize NDL theme specific functions
 */

$(document).ready(function() {
    initHeaderMenu();
    initContentMenu();
    initSidebarFacets();
    initDateVis();
    initFeedbackScroll();
    initFixedLimitSearch();
    initCustomEyeCandy();
    initScrollRecord();
    initScrollMenu();
    initContextHelp();
    initCoverImageTruncateLink();
    init480Mobile();
    initFormatSeeker();
});

// Header menu
function initHeaderMenu() {
    // Catalog account -dropdown action is handled by the surrounding <form>
    // Persona logout is handled in persona.js
    $("#headerMenu .dropdown").not(".catalogAccount").on("menuClick", function(e,url) {
        if (url && url != "") { 
            document.location = url;
        }
    });
}

// Helper function: visibility toggler
jQuery.fn.vToggle = function() {
    return this.css('visibility', function(i, visibility) {
        return (visibility == 'visible') ? 'hidden' : 'visible';
    });
};

// Date range selector 
function initDateVis() {
    if (typeof(loadVisNow) == "function") { 
        // Load date visualizer
        detector = $('.resultDates .content');
        if (detector.length > 0)
            loadVisNow();
            prevWidth = detector.width();
            $(window).resize(function(){

                // Refresh on screen resize
                if(detector.width()!=prevWidth && typeof loadVisNow != 'undefined'){
                    prevWidth = detector.width();

                    loadVisNow();
            }

        });

        $('.dateVisHandle').click(function() {
            showDateVis();
        });

        function showDateVis() {
            var dateVis = $('.resultDates');

            var dateVisHeight = !dateVis.hasClass('expanded') ? 110 : 0;
           dateVis.stop(true, true).animate({ height: dateVisHeight}, 200, function() {
               dateVis.toggleClass('expanded');

           });
           $('.resultDatesHeader').toggleClass('expanded');
               $('div.dateVisHandle').not('.visible').fadeIn(300, function() {
                   $('div.dateVisHandle.visible').fadeOut(300);
                   $('div.dateVisHandle').toggleClass('visible');
                   $('.dateVisHelp').fadeToggle(200);
               });
        }
    }
}

//Check if user is viewing single record or search results and autoscroll to wanted location
function initScrollRecord() {
	var identifier = window.location.hash;
	if (($('div').hasClass('resultHeader') === true) && (identifier === "") && ($(window).scrollTop() === 0)) {
		$('html, body').animate({
      	 		  scrollTop: $("#searchFormHeader").offset().top - 10
    	}, 200);
	}
} 


// Content pages menu 
function initContentMenu() {
    if ( $(".module-Content .main .menu").length > 0 ) {
        var menu = '<div class="content"><div class="grid_6"><ul>';

        $('.module-Content .main h2').each(function() {
            var text = $(this).text(); 
            menu += '<li>'+text+'</li>';
        });
        menu += '</ul></div></div>';

        $('.module-Content .main .menu').append(menu);
        $('.module-Content .main .menu li').click(function(event){		
            $('html,body').animate({
                scrollTop:$('h2:contains('+$(this).text()+')').offset().top - 10
            }, 500);
        });
    }
}

// Sidebar facets visibility toggler
function initSidebarFacets() {
    $('.sidegroup dl').each(function() {
        
        // Determine facet use by finding the indicator image
        if ($(this).find('img[alt="Selected"]').length > 0) {
                $(this).addClass('active');
        }

        // Mark dl collapsible
        $(this).addClass('collapsible');
    });
    
    // Toggle facet visibility by clicking its title
    $('.sidegroup dt').click(function() {
        
        // Get the facet container
        var parentDl = $(this).parent('dl');
        
        if (parentDl.hasClass('hierarcicalFacet')) {
            var target = parentDl.next('div.dynatree-facet');  
            var id = target.attr('id').substr('facet_'.length);
            enableDynatree(target, id, fullPath, action);
            parentDl.removeClass('hierarcicalFacet');
            parentDl.toggleClass('collapsed open');
        } else {
            // Do nothing if facet selected
            if (parentDl.find('img[alt="Selected"]').length > 0 || parentDl.hasClass('active')) return false;

            // Make sure this facet has options
            if (parentDl.find('dd').length > 0) {
                if (!parentDl.hasClass('timeline')) {
                // Slide them
                parentDl.children('dd').slideToggle(100);
                } else parentDl.children('dd').hide();

                // If user has clicked "more", hide the additional facets too
                if (!parentDl.next('dl').hasClass('offscreen') && parentDl.hasClass('open')) {
                    parentDl.next('dl[id*="Hidden"]').addClass('offscreen');
                    parentDl.find('dd[id*="more"]').hide();
                }
                
                // Finally, mark this facet container opened
                parentDl.toggleClass('open collapsed');
                if (!parentDl.hasClass('collapsed')) {
                    parentDl.removeClass('timeline');
                    if (parentDl.hasClass('year')) {
                        moveMainYearForm(parentDl);
                    }
                }
            }
            // Extend to dynamic facets (without dd children)
            else {
                parentDl.nextAll('div.dynatree-facet:first').slideToggle(100);
                parentDl.toggleClass('collapsed open');
            }
        }        
    });
    
    // Add clickable function for timeline icon and text
	$('.timelineview, .layout960 .year .yearLabel, .layout720 .year .yearLabel').click(function(e) {
        e.stopPropagation();
        var parentDl = $(this).closest('dl.narrowList');
        if ( !parentDl.hasClass('collapsed') ) { // if closed
            parentDl.find('> dt').trigger('click');
        }
        parentDl.toggleClass('timeline');
        moveMainYearForm(parentDl);
	});
	
    // Keep main year form visible
    moveMainYearForm = function(el) {
        if (!el.hasClass('timeline')) {
            $('.mainYearForm, .mainYearFormPCI').appendTo('.mainYearFormContainer1');
        } else {
            $('.mainYearForm, .mainYearFormPCI').appendTo('.mainYearFormContainer2');
        }
    }
    
    $('.resultDates').click(function(e) {
        e.stopPropagation();
    })
    
    // Timeline search functionality
    $(".mainYearForm").submit(function(e){
        e.preventDefault();
        // Get dates, build query
        var fromElement = $('.mainYearForm #mainYearFrom'),
            toElement = $('.mainYearForm #mainYearTo');
        var from = fromElement.val(),
            to = toElement.val(),
            action = $('.mainYearForm').attr('action');
        if (action.indexOf("?") < 0) {
            action += '?'; // No other parameters, therefore add ?
        } else {
            action += '&'; // Other parameters found, therefore add &
        }
        var type = $('.mainYearForm input[name=search_sdaterange_mvtype]:checked').val()
        var query = action + 'sdaterange[]=search_sdaterange_mv&search_sdaterange_mvtype=' + type + '&';

        fromElement.removeClass('invalidField');
        toElement.removeClass('invalidField');
      
        // Require numerical values
        if (!isNaN(from) && !isNaN(to)) {
            if (from == '' && to == '') { // both dates empty; use removal url
                query = action;
            } else if (from == '') { // only start date set
                if (type == 'within') {
                    fromElement.addClass('invalidField');
                    return false;
                }
                query += 'search_sdaterange_mvto='+padZeros(to);
            } else if (to == '')  { // only end date set
                if (type == 'within') {
                    toElement.addClass('invalidField');
                    return false;
                }
                query += 'search_sdaterange_mvfrom='+padZeros(from);
            } else { // both dates set
                query += 'search_sdaterange_mvfrom='+padZeros(from)+'&search_sdaterange_mvto='+padZeros(to);
            }
            
            // Perform the new search
            window.location = query;   
        }
    });

    // PCI Timeline search functionality
    $(".mainYearFormPCI").submit(function(e){
        e.preventDefault();
        // Get dates, build query
        var from = $('.mainYearFormPCI #mainYearFrom').val(),
            to = $('.mainYearFormPCI #mainYearTo').val(),
            action = $('.mainYearFormPCI').attr('action');

        // Store the values for timeline. See. daterange_visPCI.js
        localStorage.setItem('PCIfrom', from);
        localStorage.setItem('PCIto', to);
 
        if (action.indexOf("?") < 0) {
            action += '?'; // No other parameters, therefore add ?
        } else {
            action += '&'; // Other parameters found, therefore add &
        }
        // Check if older range exists and remove
        if (/creationdate/.test(self.location.href)) {
           var query = action.split("creationdate",1) + 'creationdate%3A';
        } else {
            var query = action + 'filterpci[]=creationdate%3A';
        }
                
        // Require numerical values
        if (!isNaN(from) && !isNaN(to)) {
            if (from == '' && to == '') { // both dates empty; use removal url
                query = 'Search?'+localStorage.getItem('lookFor');
            } else if (from == '') { // only end date set, see. daterange_visPCI.js
                query += '"['+localStorage.getItem('PCIdataMin')+' TO '+padZeros(to)+']"';
            } else if (to == '')  { // only start date set, see. daterange_visPCI.js
                query += '"['+padZeros(from)+' TO '+localStorage.getItem('PCIdataMax')+']"';
            } else { // both dates set
                query += '"['+padZeros(from)+' TO '+padZeros(to)+']"';
            }

            // Perform the new search
            window.location = query;
        }
    });


}

// Scroll the feedback button 
function initFeedbackScroll() {
   var navHeight = $('#nav').height();
   var feedbackButton = $('a.feedbackButton');
   $(window).scroll(function () { 
      if ($(document).scrollTop() >= navHeight) {
          feedbackButton.addClass('fixed');
      }
      else feedbackButton.removeClass('fixed');
   });
}

// Scroll the limit search button
// TODO: Test with Android devices and iOS < 5.0
function initFixedLimitSearch() {
   if ($('#sidebarFacets').length > 0) {
        var sidebarPos = $('#sidebarFacets').offset().top;
        var sidegroupTitleHeight = $('#sidebarFacets > .sidegroup:first-child > h4:first-child').height();
        var jumpToFacets = $('#sidebarFacets .jumpToFacets')
        
        $(window).scroll(function () { 
           sidebarPos = $('#sidebarFacets').offset().top; 
           if ($(document).scrollTop() + $(window).height() >= sidebarPos - 100) {
               jumpToFacets.fadeOut(200);
           }
           else jumpToFacets.fadeIn(200);
        });

        jumpToFacets.click( function() {
            $('html,body').animate({
             scrollTop:sidebarPos-5
                 }, 200);
        })
   }
   
}

// Padding function
function padZeros(number, length) {
    if (typeof length == 'undefined') {
        length = 4;
    }
    // Room for any leading negative sign
    var negative = false;
    if (number < 0) {
        negative = true;
        number = Math.abs(number);
    }
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return (negative ? '-' : '') + str;
}

// Custom jQuery effects
function initCustomEyeCandy() {
    
    // Slide record page thumbnail enlarge button up/down
    $('#thumbnail_link').hover(function() {
        $(this).children('span').stop().delay(100).animate({top:0}, 150);
    }, function() {
        $(this).children('span').stop().delay(100).animate({top:-50}, 150);
    });
}

// Toggle fixed menu bar
 function initScrollMenu() {
   if (document.querySelectorAll('div.menu').length >= 1) {
	$(window).on('scroll', function () {
		var menuSize	=	($(window).height()) - ($('.menu .grid_6').outerHeight()),
        scrollTop     = $(document).scrollTop(),
        elementOffset = $('.menu .content').offset().top,
        distance      = (elementOffset - scrollTop);
       if (distance < 0) {
         $('.menu .grid_6').addClass('fixed');
       }
 	   if ((distance > 0) || (menuSize < 0)) {
         $('.menu .grid_6').removeClass('fixed');
       }
    });
   }
   if (document.querySelectorAll('#myFavoritesList .bulkActionButtons').length >= 1) {
   	 $(window).on('scroll', function () {
        elementOffset2 = $('.resultOptions').offset().top,
        distance2      = (elementOffset2 - $(document).scrollTop());
        if (distance2 < 0) {
         $('.bulkActionButtons').addClass('fixed');
       }
 	   if (distance2 > 0) {
         $('.bulkActionButtons').removeClass('fixed');
       }
   	 });
   }
 }



function initContextHelp() {
    $('.showHelp').each(function() {
        var id = $(this).attr("id");
        // id is prepended with 'contextHelp_' to avoid collisions, remove it.
        var needle = "contextHelp_";
        if (id.indexOf(needle) === 0) {
            id = id.substr(needle.length);
        }
        $(this).data("contextHelp", id);
    });

    $('.showHelp').click(function(e) {        
        var id = $(e.target).data("contextHelp");
        if (!hopscotch.getCurrTour() || hopscotch.getCurrTour().id != id) {
            hopscotch.endTour();
            var success = function (data, textStatus, jqxhr) {
                if (data.length) {
                    hopscotch.startTour(eval('contextHelp'));
                }
            };
            $.getScript(path + '/AJAX/JSON?method=getContextHelp&id=' + id, success);
        }
    });

    var state = hopscotch.getState();
    if (state) {
        // tour in progress, load data and resume
        var parts = state.split(":");
        var id = parts[0];
        var page = parts[1];
        var success = function (data, textStatus, jqxhr) {
            if (data.length) {
                hopscotch.startTour(eval('contextHelp'));
            }
        };
        $.getScript(path + '/AJAX/JSON?method=getContextHelp&id=' + id, success);
    }
}

function initDatePicker(selectedNewItemsDate) {
    $("a#newItemsFromDate").click(function(event) {
        event.preventDefault();
        var dates = $(this).data('solrdate');
        if (dates==='') {
            return false;
        }
        var baseUrl = $(this).data('urltemplate');
        var location = baseUrl.replace("xxx", dates);
        window.location.href = location;
    });
    
    $('#newItemsLimit').datepick(
        {   
            maxDate: -1,
            altField: "#showDate",
            altFormat: "dd.mm.yyyy",
            onSelect: function(dates) { 
                solrDate = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'").format(new Date(dates));
                finDate = new SimpleDateFormat("d.M.yyyy").format(new Date(dates));
                $('a#newItemsFromDate').data('solrdate', solrDate);
                $('#showDate').text(finDate);
            }
        }
    );
    
    if (selectedNewItemsDate !== '') {
        $('a#newItemsFromDate').data('solrdate', selectedNewItemsDate);
        dateObj = new Date(selectedNewItemsDate);
        finDate = new SimpleDateFormat("d.M.yyyy").format(dateObj);
        $('#showDate').text(finDate);
        $("#newItemsFromDate,#newItemsLimit").show();
        $('#newItemsLimit').datepick( "setDate", finDate);
    }
    
    $("a.startinglink").click(function() {
        $("#newItemsFromDate,#newItemsLimit").slideToggle(50, function() {});
    });
}

function initCoverImageTruncateLink() {
    var moreLink = $(".coverImagesMoreLink");
    var lessLink = $(".coverImagesLessLink");
    
    moreLink.click(function(event) {
        moreLink.hide();
        lessLink.show();
        event.preventDefault();
        $('.coverImageLinks.snippet').removeClass("snippet");
    });
    lessLink.click(function(event) {
        lessLink.hide();
        moreLink.show();
        event.preventDefault();
        $('.coverImageLinks').addClass("snippet");
    });

}

// Move the Advanced Search link in narrow screens
function init480Mobile() {
    // Get the dimensions of the viewport
    var width = $(window).width();

    if (width < 731) {
        $("#480mobileFirst #move480mobile").appendTo("#480mobileSecond");
    } else {
        $("#480mobileSecond #move480mobile").appendTo("#480mobileFirst" );
    }
}

function initFormatSeeker() {
    $('.pciLink a[href$=".pdf"],.pciLink a[href*="Download"]').addClass("pdf");
    $(".pciLink a.html.fulltextlink").text("html");
    $(".pciLink a.pdf.fulltextlink").text("pdf");
    $(".openurls .availableOpenURL").text("html");
}
