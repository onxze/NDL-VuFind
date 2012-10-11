<!-- START of: Summon/searchbox.tpl -->

<div class="searchform">
  {if $searchType == 'SummonAdvanced'}
    <a href="{$path}/Summon/Advanced?edit={$searchId}" class="small">{translate text="Edit this Advanced Search"}</a> |
    <a href="{$path}/Summon/Advanced" class="small">{translate text="Start a new Advanced Search"}</a> |
    <a href="{$path}/Summon/Home" class="small">{translate text="Start a new Basic Search"}</a>
    <br/>{translate text="Your search terms"} : "<strong>{$lookfor|escape:"html"}</strong>"
  {else}
    <form method="get" action="{$path}/Summon/Search" name="searchForm" id="searchForm" class="search">
      <label for="searchForm_input" class="offscreen">{translate text="Search Terms"}</label>
      <input id="searchForm_input" type="text" name="lookfor" size="40" value="{$lookfor|escape:"html"}"/>
      <label for="searchForm_type" class="offscreen">{translate text="Search Type"}</label>
  {if $prefilterList}
      <div class="styled_select">
        <select id="searchForm_filter" class="searchForm_styled" name="prefilter">
    {foreach from=$prefilterList item=searchDesc key=searchVal}
          <option value="{$searchVal|escape}"{if $searchVal == $activePrefilter}selected="selected"{/if}>{translate text=$searchDesc}</option>
    {/foreach}
        </select>
      </div>
  {/if}
      <input id="searchForm_searchButton" type="submit" name="submit" value="{translate text="Find"}"/>
      <div class="advanced-link-wrapper clear">
      <a href="{$path}/Summon/Advanced" class="small">{translate text="Advanced"}</a>
      </div>

      {* Do we have any checkbox filters? *}
      {assign var="hasCheckboxFilters" value="0"}
      {if isset($checkboxFilters) && count($checkboxFilters) > 0}
        {foreach from=$checkboxFilters item=current}
          {if $current.selected}
            {assign var="hasCheckboxFilters" value="1"}
          {/if}
        {/foreach}
      {/if}
      {if $filterList || $hasCheckboxFilters}
        <div class="keepFilters">
          <input type="checkbox" {if $retainFiltersByDefault}checked="checked" {/if} id="searchFormKeepFilters"/> <label for="searchFormKeepFilters">{translate text="basic_search_keep_filters"}</label>
          <div class="offscreen">
            {foreach from=$filterList item=data key=field}
              {foreach from=$data item=value}
                <input type="checkbox" {if $retainFiltersByDefault}checked="checked" {/if} name="filter[]" value='{$value.field|escape}:"{$value.value|escape}"' />
              {/foreach}
            {/foreach}
            {foreach from=$checkboxFilters item=current}
              {if $current.selected}
                <input type="checkbox" {if $retainFiltersByDefault}checked="checked" {/if} name="filter[]" value="{$current.filter|escape}" />
              {/if}
            {/foreach}
          </div>
        </div>
      {/if}
      {if $lastSort}<input type="hidden" name="sort" value="{$lastSort|escape}" />{/if}
    </form>
    <script type="text/javascript">$("#searchForm_lookfor").focus()</script>
    <script type="text/javascript" src="{$url}/interface/themes/institution/js/dropdown.js"></script>
  {/if}
</div>

<!-- END of: Summon/searchbox.tpl -->
