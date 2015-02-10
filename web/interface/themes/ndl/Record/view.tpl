<!-- START of: Record/view.tpl -->

{if $bookBag}
<script type="text/javascript">
vufindString.bulk_noitems_advice = "{translate text="bulk_noitems_advice"}";
vufindString.confirmEmpty = "{translate text="bookbag_confirm_empty"}";
vufindString.viewBookBag = "{translate text="View Book Bag"}";
vufindString.addBookBag = "{translate text="Add to Book Bag"}";
vufindString.removeBookBag = "{translate text="Remove from Book Bag"}";
vufindString.itemsAddBag = "{translate text="items_added_to_bookbag"}";
vufindString.itemsInBag = "{translate text="items_already_in_bookbag"}";
vufindString.bookbagMax = "{$bookBag->getMaxSize()}";
vufindString.bookbagFull = "{translate text="bookbag_full_msg"}";
vufindString.bookbagStatusFull = "{translate text="bookbag_full"}";
</script>
{assign var=bookBagItems value=$bookBag->getItems()}
{/if}
{if isset($syndetics_plus_js)}
<script src="{$syndetics_plus_js}" type="text/javascript"></script>
{/if}
{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/300/addthis_widget.js#pub={$addThis|escape:"url"}"></script>
{/if}

{js filename="record.js"}
{js filename="check_save_statuses.js"}
{if $showPreviews}
  {js filename="preview.js"}
{/if}
{if $coreOpenURL || $holdingsOpenURL}
  {js filename="openurl.js"}
{/if}
{if $metalibEnabled}
  {js filename="metalib_links.js"}
{/if}

{* <div class="span-10{if $sidebarOnLeft} push-5 last{/if}"> *}

<div class="resultLinks">
{if $errorMsg || $infoMsg || $lastsearch || $previousRecord || $nextRecord}
  <div class="content">
  {if $errorMsg || $infoMsg}
  <div class="messages">
    {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
    {if $infoMsg}<div class="info">{$infoMsg|translate}</div>{/if}
  </div>
  {/if}

  {if !(empty($searchId))}
  {* searchId exists only after search, not when jumping from record to record 
     so we need to save the searchId to be able to reference it from other records *}
    <script type="text/javascript">
    {literal}
      $.cookie('lastSearchId', {/literal}{$searchId}{literal}, { path: '/' });
    {/literal}
    </script>
  {/if}

  {if $lastsearch}
    <div class="backToResults {if $lastsearch|strstr:'join'}grid_6{else}grid_12{/if}">
      <a href="{$lastsearch|escape}#record{$id|escape:"url"}"><div class="button buttonFinna icon"><span class="icon">&laquo;</span></div>{translate text="Back to Search Results"}</a>
    </div>
  {/if}
  {if $lastsearch|strstr:"join"}
  {* Advanced Search URL always contains substring 'join' *}
    <div class="advancedOptions grid_10">
      <a href="{$path}/Search/Advanced?edit={*$smarty.cookies.lastSearchId*}" class="editAdvancedSearch">{translate text="Edit this Advanced Search"}</a>
      <a href="{$path}/Search/Advanced" class="newAdvancedSearch">{translate text="Start a new Advanced Search"}</a>
    </div>
  {/if}
{*
  <a href="{$path}/Search/Advanced?edit={$searchId}" class="backToSearchHistory">{translate text="Search History"}</a>
*}
  {if $previousRecord || $nextRecord}
    <div class="resultscroller grid_5 {if $lastsearch|strstr:'join'}push_3{else}push_7{/if}">
    {if $previousRecord}<a href="{$url}/Record/{$previousRecord|escape:'url'}" class="prevRecord icon"><span class="resultNav">&laquo;&nbsp;{translate text="Previous Record"}</span></a>
    {else}<span class="prevRecord inactive"><span class="resultNav">&laquo;&nbsp;{translate text="Previous Record"}</span></span>{/if}
    {$currentRecordPosition} / {$resultTotal}
    {* #{$currentRecordPosition} {translate text='of'} {$resultTotal} *}
    {if $nextRecord}<a href="{$url}/Record/{$nextRecord|escape:'url'}" class="nextRecord icon"><span class="resultNav">{translate text="Next Record"}&nbsp;&raquo;</span></a>
    {else}<span class="nextRecord inactive"><span class="resultNav">{translate text="Next Record"}&nbsp;&raquo;</span></span>{/if}
	</div>
	{/if}
  </div>
{else}
  &nbsp;
{/if}

<div class="clear"></div>
</div>

<div class="record recordId" id="record{$id|escape}">

  <div class="content">
    
  <div id="resultMain">
  
  <div id="resultSide" class="grid_4">
  
    {* Display Cover Image *}
    <div class="coverImages">
    {if $coreThumbMedium}

        <div class="clear"></div>
        {assign var=img_count value=$coreImages|@count}
        {if $img_count > 1}

        <div class="coverImageLinks{if $img_count > 6} snippet{/if}">
        {foreach from=$coreImages item=desc name=imgLoop}
            <a data-id="{$id|escape:"url"}" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}{if $isbn}&amp;isn={$isbn|escape:"url"}{/if}&amp;index={$smarty.foreach.imgLoop.iteration-1}&amp;size=large" class="title imagePopup" onmouseover="document.getElementById('thumbnail').src='{$url}/thumbnail.php?id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=medium'; document.getElementById('thumbnail_link').href='{$path}/AJAX/JSON?method=getImagePopup&id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=large'; return false;" style="background-image:url('{$path}/thumbnail.php?id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=small');" rel="{$id|escape:"url"}"><span></span>
            </a>
          {/foreach}
          </div>
        {/if}
        {if $img_count > 6}
          <div class="moreLink coverImagesMoreLink">
            <a href="#">{translate text="more"}</a>
            <span>({$img_count})</span>
          </div>
          <div class="lessLink coverImagesLessLink">
            <a href="#">{translate text="less"}</a>
          </div>
        {/if}
        
        {if $coreThumbLarge}{if $img_count > 1}<a class="title imagePopup-trigger" id="thumbnail_link" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}{if $isbn}&amp;isn={$isbn|escape:"url"}{/if}&amp;index=0&amp;size=large"><span></span>{else}<a data-id="{$id|escape:"url"}" class="imagePopup" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}{if $isbn}&amp;isn={$isbn|escape:"url"}{/if}&amp;index=0&amp;size=large">{/if}{/if}
        <span></span><img id="thumbnail" alt="{translate text="Cover Image"}" class="recordcover" src="{$coreThumbMedium|escape}" style="padding:0" />
        {if $coreThumbLarge}</a>
        {js filename="init_image_popup.js"}
        {/if}
        
        {else}
        {* <img src="{$path}/bookcover.php" alt="{translate text='No Cover Image'}"> *}
    {/if}
    </div>
    {* End Cover Image *}
  
    <div id="resultToolbar" class="toolbar">
      <ul>
        <li id="saveLink"><a href="{$url}/Record/{$id|escape:"url"}/Save" class="saveRecord fav" id="saveRecord{$id|escape}" title="{translate text="Add to favorites"}">{translate text="Add to favorites"}</a></li>
        
        {* SMS commented out for now
        <li><a href="{$url}/Record/{$id|escape:"url"}/SMS" class="smsRecord sms" id="smsRecord{$id|escape}" title="{translate text="Text this"}">{translate text="Text this"}</a></li>
        *}
        {* Citation commented out for now
        <li><a href="{$url}/Record/{$id|escape:"url"}/Cite" class="citeRecord cite" id="citeRecord{$id|escape}" title="{translate text="Cite this"}">{translate text="Cite this"}</a></li> *}
        <li><a href="{$url}/Record/{$id|escape:"url"}/Email" class="mailRecord mail" id="mailRecord{$id|escape}" title="{translate text="Email Record"}">{translate text="Email Record"}</a></li>
        <li><a href="javascript:window.print()" class="printRecord print" id="printRecord{$id|escape}" title="{translate text="Print Record"}">{translate text="Print Record"}</a></li>
        {if $bookBag}
        <li><a id="recordCart" class="{if in_array($id|escape, $bookBagItems)}bookbagDelete{else}bookbagAdd{/if} offscreen" href="">{translate text="Add to Book Bag"}</a></li>
        {/if}
        {if is_array($exportFormats) && count($exportFormats) > 0}
        <li>
          <a href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export exportMenu" title="{translate text="Export Record"}">{translate text="Export Record"}</a>
          <ul class="menu offscreen" id="exportMenu">
          {foreach from=$exportFormats item=exportFormat}
            <li><a {if $exportFormat=="RefWorks"}target="{$exportFormat}Main" {/if}href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
          {/foreach}
          <li>
            <div id="qrcode"><span class="overlay"></span></div>
            {js filename="qrcodeNDL.js"}
          </li>
          </ul>
        </li>
        {/if}
        {* AddThis-Bookmark commented out
        {if !empty($addThis)}
        <li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text="Bookmark"}</a></li>
        {/if} *}
        {* AddThis for social sharing START *}
        {if !empty($addThis)}
      	  <li id="addThis">
          <div class="addthis_toolbox addthis_default_style ">
            <a class="icon addthis_button_facebook"></a>
            <a class="icon addthis_button_twitter"></a>
            <a class="icon addthis_button_google_plusone_share"></a>
          </div>
        </li>
        {/if}
        {* Addthis for social sharing END *}
        <li>
          <div id="recordProvidedBy">
            <label for="deduprecordMenu">{translate text='Record Provided By'}</label>
            {if $coreMergedRecordData.dedup_data}
            <select id="deduprecordMenu" name="deduprecordMenu" class="dropdown dropdownTruncate jumpMenuURL">
              {foreach from=$coreMergedRecordData.dedup_data key=source item=dedupData name=loop}
              <option value="{$url}/Record/{$dedupData.id|escape:"url"}"{if $dedupData.id == $id} selected="selected"{/if}>{translate text=$source prefix='source_'}</option>
            {/foreach}
            </select>
            {else}
            <div class="recordProvidedByOrganization">{translate text=$coreSource prefix='source_'}</div>
            {/if}
          </div>
        </li>
        <div id="recordFeedback">
          <li>
            <a href="{$url}/Record/{$id|escape:"url"}/Feedback" class="feedbackRecord mail" id="feedbackRecord{$id|escape}" title="{translate text="Send Feedback"}">{translate text="Send Feedback"}</a>
          </li>
        </div>
        {if $ratings && $recordRating}
        <li class="recordAverageRating">
            <div id="averageRating" data-score="{if $recordRating.ratingAverage}{$recordRating.ratingAverage}{else}0{/if}"></div><span id="ratingCount">({$recordRating.ratingCount})</span>
        </li>
        {/if}        
      </ul>
      
      {if $bookBag}
      <div class="cartSummary">
      <form method="post" name="addForm" action="{$url}/Cart/Home">
        <input id="cartId" type="hidden" name="ids[]" value="{$id|escape}" />
        <noscript>
          {if in_array($id|escape, $bookBagItems)}
          <input id="cartId" type="hidden" name="ids[]" value="{$id|escape}" />
          <input type="submit" class="button cart bookbagDelete" name="delete" value="{translate text="Remove from Book Bag"}"/>
          {else}
          <input type="submit" class="button bookbagAdd" name="add" value="{translate text="Add to Book Bag"}"/>
          {/if}
        </noscript>
      </form>
      </div>
      {/if}
      
      <div class="clear"></div>

      {* NDLBlankInclude *}
      {include file='Additions/record-post-toolbar.tpl'}
      {* /NDLBlankInclude *}

      <div class="clear"></div>
    </div>
      <div class="clear"></div>
      {* <div class="qr_wrapper">
      <div id="qrcode"><!-- span class="overlay"></span --></div>
      {js filename="qrcodeNDL.js"}
      </div> *}
      
  </div>
  <div class="grid_12 prefix_1">
    {include file=$coreMetadata}

    {* NDLBlankInclude *}
    {include file='Additions/record-post-metadata.tpl'}
    {* /NDLBlankInclude *}

  </div>
  
   
  <div id="resultSidebar" class="{if $sidebarOnLeft}pull-10 sidebarOnLeft{else}last{/if} grid_6 prefix_1">

    {* NDLBlankInclude *}
    {include file='Additions/record-pre-recommendations.tpl'}
    {* /NDLBlankInclude *}

    <div class="similarItems" id="similarItems{$id}"><div class="sidegroup">{image src="ajax_loading.gif" width="16" height="16" alt="Loading data..."}</div></div>
    
    {if $bXEnabled}
      {include file="Record/bx.tpl"}
    {/if}

    {* NDLBlankInclude *}
    {include file='Additions/record-post-recommendations.tpl'}
    {* /NDLBlankInclude *}

  </div>
  </div>
  </div>
  

   
  <a name="tabnav"></a>
    <div id="{if $dynamicTabs}dyn{/if}tabnav">
    {if !$dynamicTabs || ($tab != 'Hold' && $tab != 'CallSlip' && $tab != 'UBRequest')}
      <ul class="content">
        {if $hasHoldings}
        <li{if $tab == 'Holdings' || $tab == 'Hold' || $tab == 'CallSlip' || $tab == 'UBRequest'} class="active"{/if}>
          <a id="holdingstab" href="{$url}/Record/{$id|escape:"url"}/Holdings{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Holdings'}</a>
        </li>
        {/if}
        {* Description moved to RecordDrivers/Index/core.tpl 
        <li id="description"{if $tab == 'Description'} class="active"{/if}>
          <a id="descriptiontab" href="{$url}/Record/{$id|escape:"url"}/Description{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Description'}</a>
        </li> *}
        {* TOC moved to core.tpl
        {if $hasTOC}
        <li id="toc{if $tab == 'TOC'} class="active"{/if}>
          <a id="toctab" href="{$url}/Record/{$id|escape:"url"}/TOC{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Table of Contents'}</a>
        </li>
        {/if}
        *}
        {if $hasContainedComponentParts}
        <li{if $tab == 'ComponentParts'} class="active"{/if}>
          <a id="componentstab" href="{$url}/Record/{$id|escape:"url"}/ComponentParts{if $dynamicTabs}?subPage=1{/if}#tabnav" class="first"><span></span>{translate text='Contents/Parts'}</a>
        </li>
        {/if}
		{if $hasHierarchyTree}
          <li{if $tab == 'Hierarchytree'} class="active"{/if}>
            <a id="hierarchytab" href="{$url}/Record/{$id|escape:"url"}/HierarchyTree{if $dynamicTabs}?subPage=1{/if}#tabnav" class="first"><span></span>{translate text='hierarchy_tree'}</a>
          </li>
        {/if}
        {if $userCommentsEnabled}
        <li{if $tab == 'UserComments'} class="active"{/if}>
          <a id="commentstab" href="{$url}/Record/{$id|escape:"url"}/UserComments{if $dynamicTabs}?subPage=1{/if}#tabnav">{if $ratings}{translate text='Ratings'}{else}{translate text='Comments'}{/if} (<span id="commentCount">{$commentCount}</span>)</a>
        </li>
        {/if}
        {if $hasReviews}
        <li{if $tab == 'Reviews'} class="active"{/if}>
          <a id="reviewstab" href="{$url}/Record/{$id|escape:"url"}/Reviews{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Reviews'}</a>
        </li>
        {/if}
        {if $hasExcerpt}
        <li{if $tab == 'Excerpt'} class="active"{/if}>
          <a id="excerpttab" href="{$url}/Record/{$id|escape:"url"}/Excerpt{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Excerpt'}</a>
        </li>
        {/if}
        {if $hasMap}
          <li{if $tab == 'Map'} class="active"{/if}>
            <a id="maptab" href="{$url}/Record/{$id|escape:"url"}/Map{if $dynamicTabs}?subPage=1{/if}#tabnav" class="first"><span></span>{translate text='Map View'}</a>
          </li>
        {/if}
        <li{if $tab == 'Details'} class="active"{/if}>
          <a id="detailstab" href="{$url}/Record/{$id|escape:"url"}/Details{if $dynamicTabs}?subPage=1{/if}#tabnav">{translate text='Staff View'}</a>
        </li>
      </ul>
    {/if}
      <div class="clear"></div>
    </div>
  
    {if $dynamicTabs && $tab != 'Hold' && $tab != 'CallSlip' && $tab != 'UBRequest'}
    <div class="recordsubcontent">
          {include file="Record/view-dynamic-tabs.tpl"}
    </div>
    {else}
    <div class="recordsubcontent">
        <div class="content">
          {include file="Record/$subTemplate"}
        </div>
    </div>
    {/if}
  
    {* Add COINS *}
    <span class="Z3988" title="{$openURL|escape}"></span>
  
  <div class="clear"></div>
</div>
<script type="text/javascript">
{literal}
  $(document).ready(function() {
    var icons = {starHalf    : path+'/interface/themes/ndl/images/raty/star-half.png',
                 starOff     : path+'/interface/themes/ndl/images/raty/star-off.png',
                 starOn      : path+'/interface/themes/ndl/images/raty/star-on.png'
                };
    var searchID = $.cookie('lastSearchId');
    var editURL = $('.editAdvancedSearch').attr('href') + searchID;
    $('.editAdvancedSearch').attr('href', editURL);
    {/literal}{if $ratings && $recordRating}{literal}
    $('#averageRating').raty($.extend(icons, {readOnly : true, half: true, score : $('div#averageRating').attr('data-score')}));
    {/literal}{/if}{literal}
  });
{/literal}
</script>

<!-- END of: Record/view.tpl -->
