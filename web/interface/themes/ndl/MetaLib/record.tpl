<!-- START of: MetaLib/record.tpl -->

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
{if $record.openUrl}
  {js filename="openurl.js"}
{/if}
{if $metalibEnabled}
  {js filename="metalib_links.js"}
{/if}

<div class="resultLinks">
  {if $errorMsg || $infoMsg || $lastsearch || $previousRecord || $nextRecord}
    <div class="content">
      {if $errorMsg || $infoMsg}
    <div class="messages">
      {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
      {if $infoMsg}<div class="info">{$infoMsg|translate}</div>{/if}
    </div>
  {/if}
  {if $lastsearch}
    <div class="backToResults grid_12">
        <a href="{$lastsearch|escape}#record{$id|escape:"url"}"><div class="button buttonFinna icon"><span class="icon">&laquo</span></div>{translate text="Back to Search Results"}</a>
    </div>
  {/if}
  {if $previousRecord || $nextRecord}
    <div class="resultscroller grid_5 push_7">
      {if $previousRecord}<a href="{$url}/Record/{$previousRecord}" class="prevRecord icon"><span class="resultNav">&laquo;&nbsp;{translate text="Previous Record"}</span></a>
      {else}<span class="prevRecord inactive"><span class="resultNav">&laquo;&nbsp;{translate text="Previous Record"}</span></span>{/if}
      {$currentRecordPosition} / {$resultTotal}
      {* #{$currentRecordPosition} {translate text='of'} {$resultTotal} *}
      {if $nextRecord}<a href="{$url}/Record/{$nextRecord}" class="nextRecord icon"><span class="resultNav">{translate text="Next Record"}&nbsp;&raquo;</span></a>
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
                  <a data-id="{$id|escape:"url"}" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}&amp;index={$smarty.foreach.imgLoop.iteration-1}&amp;size=large" class="title imagePopup" onmouseover="document.getElementById('thumbnail').src='{$url}/thumbnail.php?id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=medium'; document.getElementById('thumbnail_link').href='{$path}/AJAX/JSON?method=getImagePopup&id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=large'; return false;" style="background-image:url('{$path}/thumbnail.php?id={$id|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=small');" rel="{$id|escape:"url"}"><span></span>
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
            {if $coreThumbLarge}{if $img_count > 1}<a class="title imagePopup-trigger" id="thumbnail_link" data-id="{$id|escape:"url"}" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}&amp;index=0&amp;size=large"><span></span>{else}<a data-id="{$id|escape:"url"}" class="imagePopup" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$id|escape:"url"}&amp;index=0&amp;size=large">{/if}{/if}
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
            <li id="saveLink"><a href="{$url}/MetaLib/Save?id={$id|escape:"url"}" class="saveMetaLibRecord metalibRecord fav" id="saveRecord{$id|escape}" title="{translate text="Add to favorites"}">{translate text="Add to favorites"}</a></li>
            {* AddThis for social sharing START *}
            {if !empty($addThis)}
              <li id="addThis">
                <div class="addthis_toolbox addthis_default_style ">
                  <a href="{$url}/MetaLib/{$id|escape:"url"}/Email" class="mailRecord mailMetaLib mail" id="mailRecord{$id|escape}" title="{translate text="Email this"}"></a>
                  <a class="icon addthis_button_facebook"></a>
                  <a class="icon addthis_button_twitter"></a>
                  <a class="icon addthis_button_google_plusone_share"></a>
                </div>
              </li>
            {/if}
          {if $record.Source}
            <div id="recordProvidedBy">
              <label for="deduprecordMenu">{translate text='Record Provided By'}</label>
              <div class="recordProvidedByOrganization">{$record.Source.0|escape}</div>
            </div>
          {/if}  
          <div id="recordFeedback">
            <li>
              <a href="{$url}/MetaLib/Feedback?id={$id|escape:"url"}" class="feedbackMetaLib mail" id="feedbackMetaLib{$id|escape}" title="{translate text="Send Feedback"}">{translate text="Send Feedback"}</a>
            </li>
          </div>
          </ul>
          <div class="clear"></div>
          {* NDLBlankInclude *}
          {include file='Additions/record-post-toolbar.tpl'}
          {* /NDLBlankInclude *}
          <div class="clear"></div>
        </div>
      </div>
      <div class="grid_12 prefix_1">
        <div id="recordMetadata">
          <h1 class="recordTitle">{$record.Title.0|escape}</h1>

          {* Display Abstract/Snippet *}
          {if $record.Abstract}
            <p class="snippet">{$record.Abstract.0|escape}</p>
          {elseif $record.Snippet.0 != ""}
            <blockquote>
              <span class="quotestart">&#8220;</span>{$record.Snippet.0|escape}<span class="quoteend">&#8221;</span>
            </blockquote>
          {/if}

          {* Display Main Details *}
          <table cellpadding="2" cellspacing="0" border="0" class="citation">
            
            {if $record.Author || $record.AdditionalAuthors}
              <tr valign="top">
                <th>{translate text='Authors'}: </th>
                <td>
                  <div class="truncateField">
                    {if $record.Author}
                      {foreach from=$record.Author item="author" name="loop"}
                        <a href="{$url}/MetaLib/Search?lookfor={$author|escape:"url"}">{$author|escape}</a>{if !$smarty.foreach.loop.last} ; {/if} 
                      {/foreach}
                    {/if}
                    {if $record.AdditionalAuthors && $record.Author} ; {/if}
                    {if $record.AdditionalAuthors}
                      {if !$smarty.foreach.loop.last} ; {/if} 
                      {foreach from=$record.AdditionalAuthors item="author" name="loop"}
                        <a href="{$url}/MetaLib/Search?lookfor={$author|escape:"url"}">{$author|escape}</a>{if !$smarty.foreach.loop.last} ; {/if}  
                      {/foreach}
                    {/if}
                  </div>
                  {*  Statement of responsibility *}
                  {if $coreTitleStatement}
                    <a href="" class="info_more" id="titleStatement">{translate text='Additional information'}</a>
                    <div class="additionalInformation hide">{$coreTitleStatement|escape}</div>
                  {/if}
                  {* End statement of responsibility *}
                </td>
              </tr>
            {/if}
            {if $record.PublicationTitle}
              <tr valign="top">
                <th>{translate text='Publication'}: </th>
                <td>{$record.PublicationTitle.0|escape}</td>
              </tr>
            {/if}

            {assign var=pdxml value="PublicationDate_xml"}
            {if $record.$pdxml || $record.PublicationDate}
              <tr valign="top">
                <th>{translate text='Published'}: </th>
                <td>
                  {if $record.$pdxml}
                    {if $record.$pdxml.0.month}{$record.$pdxml.0.month|escape}/{/if}{if $record.$pdxml.0.day}{$record.$pdxml.0.day|escape}/{/if}{if $record.$pdxml.0.year}{$record.$pdxml.0.year|escape}{/if}
                  {else}
                    {$record.PublicationDate.0|escape}
                  {/if}
                </td>
              </tr>
            {/if}
            
            {if $record.ISSN}
              <tr valign="top">
                <th>{translate text='ISSN'}: </th>
                <td>
                {foreach from=$record.ISSN item="value"}
                  {$value|escape}<br/>
                {/foreach}
                </td>
              </tr>
            {/if}
            {if $record.RelatedAuthor}
              <tr valign="top">
                <th>{translate text='Related Author'}: </th>
                <td>
                  {foreach from=$record.RelatedAuthor item="author"}
                    {$author|escape}
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $record.Language}
              <tr valign="top" class="recordLanguage">
                <th>{translate text='Language'}: </th>
                <td>{foreach from=$record.Language item=lang}{translate text=$lang prefix='facet_'}<br/>{/foreach}</td>
              </tr>
            {/if}
            {if !empty($coreSubjects)}
              <tr valign="top" class="recordSubjects">
                <th>{translate text='Subjects'}: </th>
                <td>
                  <div class="truncateField">
                    {foreach from=$coreSubjects item=field name=loop}
                      <div class="subjectLine">
                        {assign var=subject value=""}
                        {foreach from=$field item=subfield name=subloop}
                          {if !$smarty.foreach.subloop.first} &#8594; {/if}
                          {if $subject}
                            {assign var=subject value="$subject $subfield"}
                          {else}
                            {assign var=subject value="$subfield"}
                          {/if}
                          <a title="{$subject|escape}" href="{$url}/MetaLib/Search?lookfor={$subject|escape:"url"}" class="subjectHeading">{$subfield|escape}</a>
                        {/foreach}
                      </div>
                    {/foreach}
                  </div>
                </td>
              </tr>
            {/if}
            {foreach from=$record.Notes item=field name=loop}
              <tr valign="top">
                <th>{if $smarty.foreach.loop.first}{translate text='Notes'}:{/if}</th>
                <td>{$field|escape}</td>
              </tr>
            {/foreach}
            {foreach from=$record.url key=recordurl item=urldesc}
              <tr valign="top">
                <th>{translate text='Source'}:</th>
                <td><a href="{if $record.proxy}{$recordurl|proxify|escape}{else}{$recordurl|escape}{/if}" class="fulltext" target="_blank">{$urldesc|translate_prefix:'link_'|escape}</a></td>
              </tr>
            {/foreach}
            {if $openUrlBase && $record.openUrl}
              <tr valign="top">
                <th></th>
                <td>{include file="Search/openurl.tpl" openUrl=$record.openUrl}</td>
              </tr>
              {include file="Search/rsi.tpl"}
              {include file="Search/openurl_autocheck.tpl"}
            {/if}
          </table>
          {* End Main Details *}

          {* Display the lists that this record is saved to *}
          <div class="savedLists info hide" id="savedLists{$id|escape}">
            <strong>{translate text="Saved in"}:</strong>
          </div>
        </div>
    {* End Record *} 

    {* Add COINS *}  
        <span class="Z3988" title="{$record.openUrl|escape}"></span>
      </div>
    </div>
  </div>
</div>
<div class="clear"></div>
{literal}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#titleStatement').click(function(event) {
                event.preventDefault();
                var div = $(this).siblings('.additionalInformation');
                $(this).toggleClass('expanded');
                div.slideToggle(150);
                return false;
            });
        });
    </script>
{/literal}

<!-- END of: MetaLib/record.tpl -->
