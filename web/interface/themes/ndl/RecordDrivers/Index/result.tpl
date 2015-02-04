<!-- START of: RecordDrivers/Index/result.tpl -->
<div class="result recordId" id="record{$summId|escape}"{if $listNotes} data-notes="{$listNotes|escape:'html'}"{/if}{if $listUsername} data-notes-user="{$listUsername|escape:'html'}"{/if}>

<div class="resultColumn1">

<div class="resultCheckbox">
  {if $bookBag}
  <label for="checkbox_{$summId|regex_replace:'/[^a-z0-9]/':''|escape}" class="offscreen">{translate text="Select this record"}</label>
  <input id="checkbox_{$summId|regex_replace:'/[^a-z0-9]/':''|escape}" type="checkbox" name="ids[]" value="{$summId|escape}" class="checkbox_ui"/>
  <input type="hidden" name="idsAll[]" value="{$summId|escape}" />
  {/if}
</div>

  {assign var=img_count value=$summImages|@count}
  {* Multiple images *}
  {if $img_count > 1}
    <div class="imagelinks">
  {foreach from=$summImages item=desc name=imgLoop}
    {if $smarty.foreach.imgLoop.iteration <= 5}
      <a data-id="{$summId|escape:"url"}" class="title imagePopup" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$summId|escape:"url"}&amp;isn={$summISBN|escape:"url"}&amp;index={$smarty.foreach.imgLoop.iteration-1}&amp;size=large"  onmouseover="document.getElementById('thumbnail_{$summId|escape:"url"}').src='{$path}/thumbnail.php?id={$summId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=small'; document.getElementById('thumbnail_link_{$summId|escape:"url"}').href='{$path}/AJAX/JSON?method=getImagePopup&id={$summId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=large'; return false;" />
         {if $smarty.foreach.imgLoop.iteration > 4}
            &hellip;
         {else}
            {if $desc}{$desc|escape}{else}{$smarty.foreach.imgLoop.iteration + 1}{/if}
         {/if}
      </a>
    {/if}
  {/foreach}
    </div>
  {/if}
  {if is_array($summFormats)}
    {assign var=mainFormat value=$summFormats.0} 
    {assign var=displayFormat value=$summFormats|@end} 
  {else}
    {assign var=mainFormat value=$summFormats} 
    {assign var=displayFormat value=$summFormats} 
  {/if}
  {* Cover image *}
  <div class="coverDiv">
    <div class="resultNoImage format{$mainFormat|lower|regex_replace:"/[^a-z0-9]/":""} format{$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}"></div>
  {if $summThumb}
      <div class="resultImage">
          <a class="title imagePopup" data-id="{$summId|escape:"url"}" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$summId|escape:"url"}&amp;isn={$summISBN|escape:"url"}">
              <img src="{$summThumb|escape}" class="summcover" alt="{translate text='Open Cover Image'}" />
          </a>
      </div>
  {/if}

  </div> 

</div>
    
  <div class="resultColumn2 grid_11">

    <div class="resultItemLine1">
      <h4>
        <a href="{$url}/{if $summCollection}Collection{else}Record{/if}/{$summId|escape:"url"}" class="title recordTitle">{if !empty($summHighlightedTitle)}{$summHighlightedTitle|addEllipsis:$summTitle|highlight}{elseif !$summTitle}{translate text='Title not available'}{else}{$summTitle|truncate:180:"..."|escape}{/if}</a>
      </h4>
    </div>
   
    <div class="resultItemFormat"><span class="iconlabel format{$mainFormat|lower|regex_replace:"/[^a-z0-9]/":""} format{$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat prefix='format_'}</span></div>
    <span class="rsi"></span>
    {if !empty($coreOtherLinks)}
        {assign var=prevOtherLinkHeading value=''}
        {foreach from=$coreOtherLinks item=coreOtherLink}
    <div class="resultOtherLinks">
        {if $prevOtherLinkHeading != $coreOtherLink.heading}{translate text=$coreOtherLink.heading prefix='link_'}:{else}&nbsp;{/if}
        {assign var=prevOtherLinkHeading value=$coreOtherLink.heading}
        {if $coreOtherLink.isn}
            {if $coreOtherLink.title}
              {assign var=linkTitle value=$coreOtherLink.title}
            {else}
              {assign var=linkTitle value=$coreOtherLink.isn}
            {/if}
        
            <a title="{$coreOtherLink.title|escape}" href="{$url}/Search/Results?lookfor={$coreOtherLink.isn|escape:"url"}&amp;type=ISN">
                {$linkTitle|escape}
            </a>
        {if $coreOtherLink.author}({$coreOtherLink.author|escape}){/if}
        {else}
        <a title="{$coreOtherLink.title|escape}" href="{$url}/Search/Results?lookfor=%22{$coreOtherLink.title|escape:"url"}%22&amp;type=Title">
            {$coreOtherLink.title|escape}
        </a>
        {if $coreOtherLink.author}({$coreOtherLink.author|escape}){/if}
        {/if}
    </div>    
        {/foreach}
    {/if}
    
    <div class="resultItemLine2">
      {if !empty($summAuthor)}
      {translate text='by'}:
      <a href="{$url}/Search/Results?lookfor={$summAuthorForSearch|escape:"url"}&amp;type=Author">{if !empty($summHighlightedAuthor)}{$summHighlightedAuthor|highlight}{else}{$summAuthor|escape}{/if}</a>
      {else}
        {if $summNonPresenterAuthors}
        {translate text='Authors'}:
        {foreach from=$summNonPresenterAuthors item=field name=loop}
          <a href="{$url}/Search/Results?lookfor={$field.name|escape:"url"}&amp;type=Author">{$field.name|escape}{if $field.role}, {$field.role|escape}{/if}</a>{if !$smarty.foreach.loop.last} ; {/if}
        {/foreach}
        {/if}        
      {/if}
      {if $summDate}{translate text='Published'}: {$summDate.0|escape}{/if}{if $summPublicationEndDate}&mdash;{if $summPublicationEndDate != 9999}{$summPublicationEndDate}{/if}{/if}
      {if !empty($summDateSpan)}
        <div class="resultDateSpan">
            {foreach from=$summDateSpan item=field name=loop}
              {$field|escape}<br/>
            {/foreach}
        </div>
      {/if}
      {if !empty($summClassifications)}
        <div class="resultClassification">
            {translate text='Classification'}:
            {* This is a single-line mess due to Smarty otherwise adding spaces *}
            {foreach from=$summClassifications key=class item=field name=loop}{if !$smarty.foreach.loop.first}, {/if}{foreach from=$field item=subfield name=subloop}{if !$smarty.foreach.subloop.first}, {/if}{$class|escape} {$subfield|escape}{/foreach}{/foreach}
        </div>
      {/if}
      {if $summInCollection}
        {foreach from=$summInCollection item=InCollection key=cKey}
          <div>
            {translate text="in_collection_label"}
            <a class="collectionLinkText" href="{$path}/Collection/{$summInCollectionID[$cKey]|urlencode|escape:"url"}?recordID={$summId|urlencode|escape:"url"}">
               {$InCollection}
            </a>
          </div>
        {/foreach}
      {else}
          {if !empty($summContainerTitle)}
          <div>
            {translate text='component_part_is_part_of'}:
            {if $summHierarchyParentId}
              <a href="{$url}/Record/{$summHierarchyParentId.0|escape:"url"}">{$summContainerTitle|escape}</a>
            {else}
              {$summContainerTitle|escape}
            {/if}
            {if !empty($summContainerReference)}{$summContainerReference|escape}{/if}
          </div>
          {/if}
      {/if}
    </div>

    <div class="resultItemLine3">
      {if !empty($summSnippetCaption)}
        {translate text=$summSnippetCaption}: {/if}
      {if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br/>{/if}
      <div class="summDedupData">
      {if $summDedupData}
      
        {if $summDedupData|@count gt 1} 
        <select class="dedupform" role="listbox" aria-haspopup="true">
        {foreach from=$summDedupData key=source item=dedupData name=loop}
        {if $dedupData}
        <option value="{$dedupData.id|escape:"url"}" class="dedupDataId {$source} {$dedupData.id|escape:"url"}" role="option">{translate text=$source prefix='source_'}</option>
        {/if}
        {/foreach}
        </select>
        <div id="availableHoldings{$summId|escape}" class="availableLoc availableTotals"><span class="availableNumber"></span> <span>{translate text="Available"}</span><span id="loadingIndicator{$summId|escape}" style="margin-left:5px"></span></div>
        {else}
          {foreach from=$summDedupData key=source item=dedupData name=loop}
            <strong>{translate text=$source prefix='source_'}</strong>
          {/foreach}
        {/if}
      {/if}
      <div id="callnumAndLocation{$summId|escape}">
      {if $summAjaxStatus}
        {* <strong class="hideIfDetailed{$summId|escape}">{translate text='Call Number'}:</strong> <span class="ajax_availability hide" id="callnumber{$summId|escape}">{translate text="Fetching Holdings"}</span><br class="hideIfDetailed{$summId|escape}"/> 
        <strong>{translate text='Located'}:</strong> *} <span class="ajax_availability hide" id="location{$summId|escape}">{translate text="Fetching Holdings"}</span>
        <div class="hide" id="locationDetails{$summId|escape}"></div>
      {elseif !empty($summCallNo)}
        {translate text='Call Number'}: {$summCallNo|escape}
      {/if}
      </div>
      <div id="nodata{$summId|escape}" class="noAvailabilityInfo">{translate text="No holdings available"}</div>
      <a id="moredata{$summId|escape}" class="clearfix moreDataLink" href="{$url}/{if $summCollection}Collection{else}Record{/if}/{$summId|escape:"url"}#holdingstab">{translate text="More holdings"} »</a>
    </div>
      {include file="RecordDrivers/Index/result-onlineurls.tpl"}
      
      {if $summId|substr:0:8 == 'metalib_'}
        <br/>
        <span class="metalib_link">
          <span id="metalib_link_{$summId|escape}" class="hide"><a href="{$path}/MetaLib/Home?set=_ird%3A{$summId|regex_replace:'/^.*?\./':''|escape}">{translate text='Search in this database'}</a><br/></span>
          <span id="metalib_link_na_{$summId|escape}" class="hide">{translate text='metalib_not_authorized_single'}<br/></span>
        </span>
      {/if}

      {* <br class="hideIfDetailed{$summId|escape}"/> *}

    </div>

    {if $showPreviews}
      {if (!empty($summLCCN) || !empty($summISBN) || !empty($summOCLC))}
      <div>
        {if $googleOptions}
          <div class="googlePreviewDiv__{$googleOptions}">
            <a title="{translate text='Preview from'} Google Books" class="hide previewGBS{if $summISBN} ISBN{$summISBN}{/if}{if $summLCCN} LCCN{$summLCCN}{/if}{if $summOCLC} OCLC{$summOCLC|@implode:' OCLC'}{/if}" target="_blank">
              <img src="https://www.google.com/intl/en/googlebooks/images/gbs_preview_button1.png" alt="{translate text='Preview'}"/>
            </a>
          </div>
        {/if}
        {if $olOptions}
          <div class="olPreviewDiv__{$olOptions}">
            <a title="{translate text='Preview from'} Open Library" class="hide previewOL{if $summISBN} ISBN{$summISBN}{/if}{if $summLCCN} LCCN{$summLCCN}{/if}{if $summOCLC} OCLC{$summOCLC|@implode:' OCLC'}{/if}" target="_blank">
              <img src="{$path}/images/preview_ol.gif" alt="{translate text='Preview'}"/>
            </a>
          </div>
        {/if}
        {if $hathiOptions}
          <div class="hathiPreviewDiv__{$hathiOptions}">
            <a title="{translate text='Preview from'} HathiTrust" class="hide previewHT{if $summISBN} ISBN{$summISBN}{/if}{if $summLCCN} LCCN{$summLCCN}{/if}{if $summOCLC} OCLC{$summOCLC|@implode:' OCLC'}{/if}" target="_blank">
              <img src="{$path}/images/preview_ht.gif" alt="{translate text='Preview'}"/>
            </a>
          </div>
        {/if}
        <span class="previewBibkeys{if $summISBN} ISBN{$summISBN}{/if}{if $summLCCN} LCCN{$summLCCN}{/if}{if $summOCLC} OCLC{$summOCLC|@implode:' OCLC'}{/if}"></span>
      </div>
      {/if}
    {/if}


      

    {if !$ownList}
       {* Display the lists that this record is saved to *}
       <div class="savedLists info hide" id="savedLists{$summId|escape}">
         <strong>{translate text="Saved in"}:</strong>
       </div>
       {if $summHierarchy}
         {foreach from=$summHierarchy key=hierarchyID item=hierarchyTitle}
         <div class="hierarchyTreeLink">
           <input type="hidden" value="{$hierarchyID|escape}" class="hiddenHierarchyId" />
           <a id="hierarchyTree{$summId|escape}" class="hierarchyTreeLinkText" href="{$url}/Record/{$summId|escape:"url"}/HierarchyTree?hierarchy={$hierarchyID}#tabnav" title="{if $coreShortTitle}{$coreShortTitle|truncate:150:"&nbsp;..."|urlencode}{else}{translate text="hierarchy_tree"}{/if}">
             {if count($summHierarchy) == 1}
               {translate text="hierarchy_view_context"}
             {else}
               {translate text="hierarchy_view_context"}: {$hierarchyTitle}
             {/if}
           </a>
         </div>
         {/foreach}
       {/if}
    {/if}
    {if $listNotes}
       <div class="notes">
         <p><span class="heading">{translate text="Description"} </span>({$listUsername|escape:'html'}):</p>
         <p class="text">{$listNotes|escape:'html'}</p>
       </div>
    {/if}
</div>
  <div class="last addToFavLink">
    <a id="saveRecord{$summId|escape}" href="{$url}/Record/{$summId|escape:"url"}/Save" class="fav tool saveRecord" title="{translate text='Add to favorites'}"></a>
  </div>
  <div class="clear"></div>
</div>

{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}"></span>{/if}
<!-- END of: RecordDrivers/Index/result.tpl -->
