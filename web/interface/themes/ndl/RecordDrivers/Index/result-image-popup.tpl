<div class="imagePopupHolder {$recordType}" data-type="{$recordType}" data-id="{$id}">
  <div class="imagePopupContainer">
    <div class="image">
      <span class="noimage">{translate text="No Image"}</span>    
      {if $thumbLarge}
      <img src="{$thumbLarge}" />
      {/if}
    </div>
    <div class="content">
      <h3 class="title">{$title}</h3>
      <div class="authorAndDates">
        <p>
          {if $author}{$author}{/if}
          {foreach from=$dates item=item}
      	  {$item} 
        {/foreach}
        </p>      
      </div>
      {if $building}<div class="building">{translate text=$building prefix="facet_"}</div>{/if}
      <div class="summary">
        {if $recordType == 'marc'}
        <div>
          <img src="{path filename="images/ajax_loading.gif"}" alt="{translate text='Loading data...'}"/>
        </div>
        {elseif $summary}
        {foreach from=$summary item=item}
        <p>{$item}</p>
      {/foreach}
      {/if}
      </div>
      <div class="listNotes">
        <p><span class="heading">{translate text="Description"}</span><span class="notes-user"></span>:</p>
        <p class="text">{$listNotes}</p>      
      </div>
      <div class="recordLink"><a href="{$url}">{translate text="To the record"}</a></div>
      <div class="imageRights">
        <div class="rights">
          <span>{translate text="Image Rights"}:</span> {if $copyrightLink}<a target="_blank" href="{$copyrightLink}">{/if}{$copyright}{if $copyrightLink}</a>{/if}
        </div>
        {if $copyrightDescription}
        <div class="moreLink copyrightLink"><a data-mode="1" href="">Lisää</a></div>
        <div class="copyright">
          {foreach from=$copyrightDescription item=item}
          <p>{$item}</p>
          {/foreach}
          {if $copyrightLink && !$copyright}
          <a href="{$copyrightLink}">{translate text="See also"}</a>
          {/if}
        </div>
        <div class="lessLink copyrightLink"><a data-mode="0" href="">vähemmän</a></div>
      {/if}
      </div>
      <div style="clear: both;"></div>
    </div>
    <div style="clear: both;"></div>
  </div>
</div>