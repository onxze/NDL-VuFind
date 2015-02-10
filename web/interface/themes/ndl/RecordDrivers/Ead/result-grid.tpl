<div id="record{$summId|escape}" class="gridRecordBox recordId"{if $listNotes} data-notes="{$listNotes|escape:'html'}"{/if}{if $listUsername} data-notes-user="{$listUsername|escape:'html'}"{/if}>
  {assign var=img_count value=$summImages|@count}
  {if $img_count > 1}
    <div class="imagelinks">      
    {foreach from=$summImages item=desc name=imgLoop}
      {if $smarty.foreach.imgLoop.iteration <= 5}
        <a data-id="{$summId|escape:"url"}" class="title imagePopup"  href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$summId|escape:"url"}&amp;index={$smarty.foreach.imgLoop.iteration-1}&amp;size=large"  onmouseover="document.getElementById('thumbnail_{$summId|escape:"url"}').src='{$path}/thumbnail.php?id={$summId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=small'; document.getElementById('thumbnail_link_{$summId|escape:"url"}').href='{$path}/AJAX/JSON?method=getImagePopup&id={$summId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=large'; return false;" />
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
  <div class="addToFavLink">
    <a id="saveRecord{$summId|escape}" href="{$url}/Record/{$summId|escape:"url"}/Save" class="fav tool saveRecord" title="{translate text='Add to favorites'}"></a>
  </div>
  <div class="gridContent">
    <div class="gridTitleBox" >
      <a class="gridTitle" href="{$url}/{if $summCollection}Collection{else}Record{/if}/{$summId|escape:"url"}" >
      {if !$summTitle}{translate text='Title not available'}{elseif !empty($summHighlightedTitle)}{$summHighlightedTitle|truncate:80:"..."|highlight}{else}{$summTitle|truncate:80:"..."|escape}{/if}
      </a>
      <div class="gridPublished">
        {if $summDate}{translate text='Published'}: {$summDate.0|escape}{/if}
      </div>
      {if $listNotes}
         <div class="notes">
           <p><span class="heading">{translate text="Description"} </span>({$listUsername|escape:'html'}):</p>
           <p class="text">{$listNotes|escape:'html'}</p>
         </div>
      {/if}
    </div>
  </div>
  <div class="coverDiv">
    {if is_array($summFormats)}
      {assign var=mainFormat value=$summFormats.0} 
      {assign var=displayFormat value=$summFormats|@end} 
    {else}
      {assign var=mainFormat value=$summFormats} 
      {assign var=displayFormat value=$summFormats} 
    {/if}


 {* Cover image *}
        <div class="resultNoImage format{$mainFormat|lower|regex_replace:"/[^a-z0-9]/":""} format{$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}"></div>
    {if $img_count == 1}
        <div class="resultImage">
            <a class="title imagePopup" data-id="{$summId|escape:"url"}" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$summId|escape:"url"}&amp;index=0&amp;size=large" id="thumbnail_link_{$summId|escape:"url"}">
                <img id="thumbnail_{$summId|escape:"url"}" src="{$path}/thumbnail.php?id={$summId|escape:"url"}&size=small" class="summcover" alt="{translate text='Cover Image'}" />
            </a>
        </div>
    {elseif $img_count > 1}
    <div class="resultImage">
        <a class="title imagePopup-trigger" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$summId|escape:"url"}&amp;index=0&amp;size=large" id="thumbnail_link_{$summId|escape:"url"}">
            <img id="thumbnail_{$summId|escape:"url"}" src="{$path}/thumbnail.php?id={$summId|escape:"url"}&size=small" class="summcover" alt="{translate text='Cover Image'}" />
        </a>
    </div>    
    {/if}
  </div> 
</div>