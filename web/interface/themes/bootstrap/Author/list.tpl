<!-- START of: Author/list.tpl -->

<div id="authorList" class="{if $sidebarOnLeft} push-3 last{/if}">
  {* Listing Options *}
  <div class="resulthead">
    <div class="floatleft small">
      {if $recordCount}
        <strong>'{$lookfor|escape}'</strong>
      {/if}
    </div>
    {include file="Search/paging.tpl" position="Top"}
    <div class="floatright small">
      <form action="{$path}/Search/SortResults" method="post">
        <label for="sort_options_1">{translate text='Sort'}</label>
        <select id="sort_options_1" name="sort" class="jumpMenu">
        {foreach from=$sortList item=sortData key=sortLabel}
          <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
        {/foreach}
        </select>
        <noscript><input type="submit" value="{translate text="Set"}" /></noscript>
      </form>
    </div>
    <div class="clear"></div>
  </div>
  {* End Listing Options *}
  
  <table class="citation authors">
    <tbody>
      <tr>
        <th>{translate text="Author"}</th><th>{translate text="Popularity"}</th>
      </tr>
      {foreach from=$recordSet item=record name="recordLoop"}
      <tr{if ($smarty.foreach.recordLoop.iteration % 2) == 0} class="alt"{/if}>
        <td><a href="{$url}/Author/Home?author={$record.0|escape:"url"}">{$record.0|escape:"html"}</a></td>
        <td>{$record.1}</td>
      </tr>
      {/foreach}
    </tbody>
  </table>
  {include file="Search/paging.tpl"}
</div>

<div id="sidebarRecommend" class="{if $sidebarOnLeft}pull-10 sidebarOnLeft{else}last{/if}">
</div>

<div class="clear"></div>

<!-- END of: Author/list.tpl -->
