<!-- START of: RecordDrivers/Index/listentry.tpl -->

<div class="listentry recordId" id="record{$listId|escape}" data-recordid="{$listId|escape}"{if $listNotes} data-notes="{foreach from=$listNotes item=item}{$item|escape:'html'}{/foreach}"{/if}{if $listUsername} data-notes-user="{$listUsername|escape:'html'}"{/if}>
 <div class="checkboxFilter">
  <div class="resultCheckbox">
    <input id="checkbox_{$listId|regex_replace:'/[^a-z0-9]/':''|escape}" type="checkbox" name="ids[]" value="{$listId|escape}" class="checkbox_ui checkbox"/>
    <label for="checkbox_{$listId|regex_replace:'/[^a-z0-9]/':''|escape}" >{translate text="Select"}: {$listTitle|escape}</label>
    <input type="hidden" name="idsAll[]" value="{$listId|escape}" />
  </div>
 </div>
  
  {assign var=img_count value=$summImages|@count}
  <div class="coverDiv">
{* Multiple images *}
{if $img_count > 1}
    <div class="imagelinks">
{foreach from=$summImages item=desc name=imgLoop}
   {if $smarty.foreach.imgLoop.iteration <= 3}
      <a data-id="{$listId|escape:"url"}" class="title imagePopup" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$listId|escape:"url"}{if $listISBN}&amp;isn={$listISBN|escape:"url"}{/if}&amp;index={$smarty.foreach.imgLoop.iteration-1}&amp;size=large"  onmouseover="document.getElementById('thumbnail_{$listId|escape:"url"}').src='{$path}/thumbnail.php?id={$listId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=small'; document.getElementById('thumbnail_link_{$listId|escape:"url"}').href='{$path}/AJAX/JSON?method=getImagePopup&id={$listId|escape:"url"}&index={$smarty.foreach.imgLoop.iteration-1}&size=large'; return false;"/>
         {if $smarty.foreach.imgLoop.iteration > 2}
            &hellip;
         {else}
            {if $desc}{$desc|escape}{else}{$smarty.foreach.imgLoop.iteration + 1}{/if}
         {/if}
      </a>
   {/if}
{/foreach}
    </div>
{/if}
    <div class="resultNoImage"><p>{translate text='No image'}</p></div>
    {if $img_count > 1}
        <div class="resultImage">
            <a class="title imagePopup-trigger" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$listId|escape:"url"}{if $listISBN}&amp;isn={$listISBN|escape:"url"}{/if}&amp;index=0&amp;size=large" id="thumbnail_link_{$listId|escape:"url"}">
               <img id="thumbnail_{$listId|escape:"url"}" src="{$path}/thumbnail.php?id={$listId|escape:"url"}&size=small" class="summcover" alt="{translate text='Cover Image'}" />
            </a>
        </div>
    {elseif $img_count == 1 || $listThumb}
        <div class="resultImage">
            <a class="imagePopup" href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$listId|escape:"url"}{if $listISBN}&amp;isn={$listISBN|escape:"url"}{/if}&amp;index=0&amp;size=large" id="thumbnail_link_{$listId|escape:"url"}" data-id="{$listId|escape:"url"}"><img id="thumbnail_{$listId|escape:"url"}" src="{$listThumb|escape}" class="summcover" alt="{translate text='Cover Image'}"/></a>
        </div>    
    {/if}  
  </div>

  <div class="resultColumn2">
      <a href="{$url}/Record/{$listId|escape:"url"}" class="title">{$listTitle|escape}</a><br/>
      {if $listAuthor}
        {translate text='by'}: <a href="{$url}/Search/Results?lookfor={$listAuthor|escape:"url"}&amp;type=Author">{$listAuthor|escape}</a><br/>
      {/if}
      {* tags are disabled for now
      {if $listTags}
        <strong>{translate text='Your Tags'}:</strong>
        {foreach from=$listTags item=tag name=tagLoop}
          <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a>{if !$smarty.foreach.tagLoop.last},{/if}
        {/foreach}
        <br/>
      {/if}
      *}

      {assign var=mainFormat value=$listFormats.0} 
      {assign var=displayFormat value=$listFormats|@end} 
      <span class="iconlabel format{$mainFormat|lower|regex_replace:"/[^a-z0-9]/":""} format{$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat prefix='format_'}</span>
      {if $listEditAllowed}
        <div class="dynamicInput listEntryDescription clearfix autoSubmit">
          <strong class="subject {if !$listNotes}hiddenElement{/if}">{translate text='Description'}:</strong>
          <div data-placeholder="{translate text="Add description"}" class="transform{if !$listNotes} placeholder">{translate text="Add description"}{else}">
              {if count($listNotes) > 1}<br/>{/if}
                {foreach from=$listNotes item=note}
                  {$note|escape:"html"}<br/>
                {/foreach}
              {/if}</div>
            <a class="icon {if !$listNotes}add{else}edit{/if}" title="{if !$listNotes}{translate text="add_entry_description"}{else}{translate text="edit_entry_description"}{/if}" href="#"></a>
              <input class="hiddenElement" type="text" name="entry_description_change" value="" />
        </div>
      {/if}
  </div>

  

  <div class="clear"></div>
</div>

<!-- END of: RecordDrivers/Index/listentry.tpl -->
