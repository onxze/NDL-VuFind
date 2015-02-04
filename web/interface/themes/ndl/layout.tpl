{if $smarty.request.subPage && $subTemplate}
  {include file="$module/$subTemplate"}
{else}
<!DOCTYPE html>
{* Do not use HTML comments before DOCTYPE to avoid quirks-mode in IE *} 
<!-- START of: layout.tpl -->

<html lang="{$userLang}">

{* We should hide the top search bar and breadcrumbs in some contexts - TODO, remove xmlrecord.tpl when the actual record.tpl has been taken into use: *}
{if ($module=="Search" || $module=="Summon" || $module=="PCI" || $module=="WorldCat" || $module=="Authority" || $module=="MetaLib") && $pageTemplate=="home.tpl" || $pageTemplate=="xmlrecord.tpl"}
    {assign var="showTopSearchBox" value=0}
    {assign var="showBreadcrumbs" value=0}
{else}
    {assign var="showTopSearchBox" value=1}
    {assign var="showBreadcrumbs" value=1}
{/if}

  <head>
    {if ($module == 'Search' && $action == 'Results' && $pageTemplate == 'list-none.tpl')
      || ($module == 'PCI' && $action == 'Search' && $pageTemplate == 'list-none.tpl')
      || $module == 'MetaLib'}
    <meta name="robots" content="noindex,nofollow"/>
    {/if}      
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="format-detection" content="telephone=no" />
    {include file="og-metatags.tpl"}
    {if $addHeader}{$addHeader}{/if}
    <title>{$pageTitle|truncate:64:"..."}</title>
    <link rel="shortcut icon" href="{path filename="images/favicon.ico"}" type="image/x-icon" />
    <link rel="apple-touch-icon-precomposed" href="{path filename="images/apple-touch-icon.png"}" />

    {if $module=='Record' && $hasRDF}
    <link rel="alternate" type="application/rdf+xml" title="RDF Representation" href="{$url}/Record/{$id|escape}/RDF"/>
    {/if}

    <link rel="search" type="application/opensearchdescription+xml" title="Library Catalog Search" href="{$url}/Search/OpenSearch?method=describe"/>

    {css media="screen, projection" filename="../js/jquery-ui-1.8.23.custom/css/smoothness/jquery-ui-1.8.23.custom.css"}

    {css media="screen" filename="magnific-popup.css"}

    {* Load JSTree css *}
    {css media="screen" filename="../js/jsTree/themes/apple/style.css"}

    {* Load Blueprint CSS framework *}
    {css media="screen, projection" filename="blueprint/screen.css"}

    <!--[if lt IE 8]>{css media="screen, projection" filename="blueprint/ie.css"}<![endif]-->
    {* Adjust some default Blueprint CSS styles *}
    {css media="screen, projection" filename="blueprint/blueprint-adjust.css"}

    {* Load VuFind specific stylesheets *}
    {css media="screen, projection" filename="grid.css"}
    {css media="screen" filename="ui.dynatree.css"}
    {css media="screen" filename="datatables.css"}
    {css media="screen, projection" filename="hopscotch-0.1.2.css"}
    {css media="screen, projection" filename="hopscotch-0.1.2.css"}
    {css media="screen, projection" filename="jquery.datepick.css"}
    
    {*  Set of css files based loosely on
        Less Framework 4 http://lessframework.com by Joni Korpi
        License: http://opensource.org/licenses/mit-license.php  *}
    {css media="screen, projection" filename="typography.css"}
    {css media="screen, projection" filename="default.css"}
    {css media="screen, projection" filename="layout.css"}
    
    {css media="screen, projection" filename="icons.css"}
    {css media="screen, projection" filename="home.css"}

    {css media="screen, projection" filename="breadcrumbs.css"}
    {css media="screen, projection" filename="footer.css"}
    {css media="screen, projection" filename="default_custom.css"}
    {css media="screen, projection" filename="home_custom.css"}
    
    {css media="screen, projection" filename="720tablet.css"}
    {css media="screen, projection" filename="480mobile.css"}

    {css media="screen, projection" filename="settings.css"}
    
    {css media="print" filename="print.css"}
    {css media="print" filename="print_custom.css"}
    {if $dateRangeLimit}
      {css media="screen, projection" filename="jslider/jslider.css"}
    {/if}
    {if $facetList}
      {css media="screen, projection" filename="chosen/chosen.css"}
    {/if}
    <!--[if lt IE 9]>{css media="screen, projection" filename="ie.css"}<![endif]-->
    <!--[if !IE 8]><!-->{css media="screen, projection" filename="non-ie8.css"}<!--<![endif]-->

    {* Set global javascript variables *}
    <script type="text/javascript">
    <!--//--><![CDATA[//><!--
      var path = '{$url}';
      var userLang = '{$userLang}';
      var fullPath = '{$fullPath|escape:'javascript'}';
      var module = '{$module}';
      var action = '{$action}';
      
      // String translations
      var trNext = "{translate text="Next"}";
      var trPrev = "{translate text="Prev"}";
      var trClose = "{translate text="Close"}";
      var trMore = "{translate text="More"}";
      var trLess = "{translate text="less"}";      
      var trLoading = "{translate text="Loading"}";      

      var listList = [
      {if $listList}
          {foreach from=$listList item=listItem}
              [{$listItem->id}, "{translate text=$listItem->title|escape:"html"}"],
          {/foreach}
      {/if}
      ];

    //--><!]]>
    </script>
    {* Load jQuery framework and plugins *}
    {js filename="jquery-1.8.3.min.js"}
    {js filename="jquery-ui-1.8.23.custom/js/jquery-ui-1.8.23.custom.min.js"}
    {js filename="jquery.cookie.js"}
    {js filename="jquery.form.js"}
    {js filename="jquery.metadata.js"}
    {js filename="jquery.validate.min.js"}
    {js filename="jquery.qrcode.js"}
    {js filename="jquery.labelOver.js"}
    {js filename="jquery.dataTables.min.js"}   
    {js filename="jquery.clearsearch.js"}
    {js filename="jquery.collapse.js"}
    {js filename="jquery.dynatree-1.2.2-mod.js"}
    {js filename="jquery.datepick/jquery.datepick.min.js"}
    {if $userLang == 'en-gb'}
    {js filename="jquery.datepick/jquery.datepick-en-GB.js"}
    {elseif $userLang == 'sv'}
    {js filename="jquery.datepick/jquery.datepick-sv.js"}
    {else}
    {js filename="jquery.datepick/jquery.datepick-fi.js"}
    {/if}
    {if $ratings}
    {js filename="raty/jquery.raty.min.js"}
    {/if}
    {js filename="jquery.inview.min.js"}
    {* Load custom javascript functions *}
    {js filename="custom.js"}

    {js filename="jquery.magnific-popup.min.js"}

    {* Load dynamic facets *}
    {js filename="facets.js"}

    {* Load javascript microtemplating *}
    {js filename="tmpl.js"}

    {* Load dialog/lightbox functions *}
    {js filename="lightbox.js"}

    {* Load dropdown menu modification *}
    {js filename="dropdown.js"}

    {* Load common javascript functions *}
    {js filename="common.js"}
    
    {* Load SlidesJS *}
    {js filename="slides.min.jquery.js"}
    
    {* Load carouFredSel, touchSwipe and imagesloaded *}
    {js filename="jquery.carouFredSel-6.2.0-packed.js"}
    {js filename="jquery.touchSwipe.min.js"}
    {js filename="jquery.imagesloaded.min.js"}
    
    {* Load QRCodes *}
    {js filename="qrcode.js"} 

    {* Load Hopscotch help plugin *}
    {js filename="hopscotch-0.1.2.js"} 

    {* Load Simple Date Format *}
    {js filename="simpledateformat.js"}
    
    {* Load ndl theme functions *}
    {js filename="ndl.js"}
    
    {* Load Mozilla Persona support *}
    {if $mozillaPersona}
    {js filename="persona.js"}
        <script type="text/javascript">
        {literal}
        $(document).ready(function() {        
		        $.ajax({
		            url: 'https://login.persona.org/include.js',
		            dataType: 'script',
		            success: function() {
		                {/literal}
		                mozillaPersonaSetup({if $mozillaPersonaCurrentUser}"{$mozillaPersonaCurrentUser}"{else}null{/if}, {if $mozillaPersonaAutoLogout}true{else}false{/if});
		                {literal}
		            }
		        });
        });
        {/literal}
        </script>
    {/if}

    {literal}
        <script type="text/javascript">
        // Long field truncation
        $(document).ready(function() {
            $('.truncateField').not('.recordSummary').not('#recentMetalibDatabases').collapse({maxLength: 150, more: "{/literal}{translate text="more"}{literal}", less: "{/literal}{translate text="less"}{literal}"});
            $('.recordSummary.truncateField').collapse({maxLength: 150, maxRows: 5, more: " ", less: " "});{/literal}
          {literal}
        });
        // Load child theme custom functions
        customInit();
        </script>
    {/literal}
    {* Apply labelOver placeholder for input fields *}

    <script type="text/javascript">
    {literal}
        $(function(){
            $('#searchFormLabel').labelOver('labelOver')
        });
    {/literal}
    </script>
    {php}
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if(stripos($ua, 'ipad') !== false) {
            print '<meta id="viewport" name="viewport" content="width=720px, initial-scale=1.0" />';
        } else if ((stripos($ua, 'mobile') !== false && stripos($ua, 'safari') !== false) || stripos($ua, 'android') !== false ) {
            print '<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0" />';
            print '<script type="text/javascript">
                    $(function(){
                        var ww = ( $(window).width() < window.screen.width ) ? $(window).width() : window.screen.width;
                        if (ww <= 480) {
                            $("#viewport").attr("content", "width=480px, initial-scale=0.667");
                        }
                    });
                 </script>';
        } else {
            print '<meta id="viewport" name="viewport" content="width=480px, initial-scale=1.0" />';
        }
    {/php}
    
    {* **** IE fixes **** *}
    {* Load IE CSS1 background-repeat and background-position fix *}
    <!--[if lt IE 7]>{js filename="../css/iepngfix/iepngfix_tilebg.js"}<![endif]-->
    {* Enable HTML5 in old IE - http://code.google.com/p/html5shim/
       can also use src="//html5shiv.googlecode.com/svn/trunk/html5.js" *}
    <!--[if lt IE 9]>
      {js filename="html5.js"}
    <![endif]-->

    {* NDLBlankInclude *}
    {include file='Additions/general-post-head.tpl'}
    {* /NDLBlankInclude *}

  </head>
  <body class="{foreach from=","|explode:$site.theme item=theme}theme-{$theme} {/foreach} {if $user}logged-in{/if}">
    {if !$showTopSearchBox}
        <a class="feedbackButton" href="{$path}/Feedback/Home">{translate text='Give feedback'}</a>
    {/if}
    {* Screen readers: skip to main content *}
    {if $module == 'Record'}
    <div id="skip-link">
      <a href="#resultMain" class="element-invisible">{translate text='Skip to record details'}</a>
    </div> 
    {/if}
    {* mobile device button*}
    {if $mobileViewLink}
        <div class="mobileViewLink"><a href="{$mobileViewLink|escape}">{translate text="mobile_link"}</a></div>
    {/if}
    {* End mobile device button*}

    {* LightBox *}
    <div id="lightboxLoading" style="display: none;">{translate text="Loading"}...</div>
    <div id="lightboxError" style="display: none;">{translate text="lightbox_error"}</div>
    <div id="lightbox" onclick="hideLightbox(); return false;"></div>
    <div id="popupbox" class="popupBox"><b class="btop"><b></b></b></div>
    {* End LightBox *}
    <div class="backgroundContainer background-{$bgNumber}"></div>
    <div id="page-wrapper" class="module-{$module} action-{$action}">

      {* Start BETA BANNER - Remove/comment out when not in beta anymore ===> *}
      {* <=== Remove/comment out when not in beta anymore - End BETA BANNER *}
      {if $developmentSite}
      <div class="w-i-p">{translate text="development_disclaimer"}</div>
      {/if}
      <!--[If lt IE 8]>
        <div class="ie">{translate text="ie_disclaimer"}</div>
      <![endif]-->
      <!--
      <div id="beta-wrapper">
          <a id="beta-banner" href="{$url}{if $module=='MetaLib'}/MetaLib/Home{/if}" title="{translate text="Home"}"></a>
      </div>
      -->

      <div id="nav" class="nav">
        <div class="content">
          <nav role="navigation" aria-label="{translate text='Main Navigation'}">
            <ul id="headerMenu">
              {include file="header-menu.$userLang.tpl"}
            </ul>
          </nav>
          <div class="lang">
            {if is_array($allLangs) && count($allLangs) > 1}
            <ul role="list">
              {foreach from=$allLangs key=langCode item=langName}
                {if $userLang != $langCode}
                  <li role="listitem"><a href="{$fullPath|removeURLParam:'lng'|addURLParams:"lng=$langCode"|escape:'html'}">
                    {translate text=$langName}</a>
                  </li>
                {/if}
              {/foreach}
            </ul>
            {/if}
          </div>
        </div>
      </div>

      <div id="header" class="header{if !$showTopSearchBox}-home{/if} {if $module!='Search'}header{$module}{/if} clearfix">
        <div class="header-inner">
          <div class="content">
          {include file="header.tpl"}
        </div>
      </div>
      </div>

      <div id="main" class="main{if !$showTopSearchBox}-home{/if} clearfix">
        {if $errorPage}{$errorContent}{else}
        {include file="$module/$pageTemplate"}
        {/if}
      </div>

      <div id="footer" class="clearfix">
        <div class="content">
          {include file="footer.tpl"}
        </div>
      </div>

    </div> {* End doc *}
    {* MetaLib search results are tracked in MetaLib/list-list.tpl *}
    {if !( ($module eq 'MetaLib') && ($action eq 'Search') )}
       {include file="piwik.tpl"}
    {/if}    
    {include file="AJAX/keepAlive.tpl"}

    {* NDLBlankInclude *}
    {include file='Additions/general-post-body.tpl'}
    {* /NDLBlankInclude *}

  {if !$showTopSearchBox}
    {literal}
      <script type="text/javascript">
        $(function() {
            initSearchInputListener();
        });
      </script>
    {/literal}
  {/if}
  </body>
</html>
{/if}

<!-- END of: layout.tpl -->
