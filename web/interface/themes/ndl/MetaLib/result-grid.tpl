<!-- START of: MetaLib/result-grid.tpl -->

<div class="gridRecordBox result recordId" id="record{$record.ID.0|escape}"{if $listNotes} data-notes="{$listNotes|escape:'html'}"{/if}{if $listUsername} data-notes-user="{$listUsername|escape:'html'}"{/if}>
  <div class="span-3 last addToFavLink">
    <a id="saveRecord{$record.ID.0|escape}" href="{$url}/MetaLib/Save?id={$record.ID.0|escape:"url"}" class="fav tool saveMetaLibRecord" title="{translate text='Add to favorites'}"></a>
  </div>      

  <div class="gridContent">
    <div class="gridTitleBox" >
      <a class="gridTitle" href="{$url}/MetaLib/Record?id={$record.ID.0|escape:"url"}" id="thumbnail_link_{$record.ID.0|escape:"url"}" >{if !$record.Title.0}{translate text='Title not available'}{else}{$record.Title.0|highlight}{/if}</a>

      <div class="gridPublished">
        {assign var=pdxml value="PublicationDate_xml"}
        {if $record.$pdxml}{translate text='Published'}: ({if $record.$pdxml.0.month}{$record.$pdxml.0.month|escape}/{/if}{if $record.$pdxml.0.day}{$record.$pdxml.0.day|escape}/{/if}{if $record.$pdxml.0.year}{$record.$pdxml.0.year|escape}){/if}{elseif $record.PublicationDate}{translate text='Published'}: {$record.PublicationDate.0|escape}{/if}
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
    <div class="resultNoImage"><p>{translate text='No image'}</p></div>
    <div class="resultImage">
      <a class="title imagePopup" data-id="{$record.ID.0|escape:"url"}" 
         href="{$path}/AJAX/JSON?method=getImagePopup&amp;id={$record.ID.0|escape:"url"}&amp;size=large" 
        id="thumbnail_link_{$record.ID.0|escape:"url"}">
        <img src="{$path}/bookcover.php?size=small{if $record.ISBN.0}&amp;isn={$record.ISBN.0|@formatISBN}{/if}{if $record.ContentType.0}&amp;contenttype={$record.ContentType.0|escape:"url"}{/if}" class="summcover" alt="{translate text="Cover Image"}"/>
      </a>
    </div>
  </div>

  <div class="clear"></div>
</div>
<span class="Z3988" title="{$record.openUrl|escape}"></span>

<!-- END of: MetaLib/result-grid.tpl -->
