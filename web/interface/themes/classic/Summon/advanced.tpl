<div id="bd">
  <form method="GET" action="{$url}/Summon/Search" id="advSearchForm" name="searchForm" class="search">
  <div id="yui-main" class="content">

    <div class="yui-b first contentbox">
      <div class="record advSearchContent">
      
          <div class="resulthead"><h3>{translate text='Advanced Search'}</h3></div>
          <div class="page">

            {if $editErr}
            {assign var=error value="advSearchError_$editErr"}
            <div class="error">{translate text=$error}</div>
            {/if}

            <div id="groupJoin" class="searchGroups">
              <div class="searchGroupDetails">
                {translate text="search_match"} : 
                <select name="join">
                  <option value="AND">{translate text="group_AND"}</option>
                  <option value="OR"{if $searchDetails}{if $searchDetails.0.join == 'OR'} selected="selected"{/if}{/if}>{translate text="group_OR"}</option>
                </select>
              </div>
              <strong>{translate text="search_groups"}</strong>:
            </div>

            {* An empty div. This is the target for the javascript that builds this screen *}
            <div id="searchHolder"></div>

            <a href="#" class="add" onclick="addGroup(); return false;">{translate text="add_search_group"}</a>
            <br /><br />
            <input type="submit" name="submit" value="{translate text="Find"}"><br><br>
            {if !empty($checkboxFilters)}
              <h3>{translate text='Limit To'}</h3><br>
              {foreach from=$checkboxFilters item=current}
              <input type="checkbox" name="filter[]" value="{$current.filter|escape}"
                {if $current.selected}checked="checked"{/if} />
              {translate text=$current.desc}<br>
              {/foreach}
              <br>
            {/if}
            {if $dateRangeLimit}
              {* Load the publication date slider UI widget *}
              {js filename="yui/slider-min.js"}
              {js filename="pubdate_slider.js"}
              <table summary="{translate text='adv_search_year'}">
                <tr>
                  <th valign="top" align="right">{translate text="adv_search_year"}:&nbsp;</th>
                  <td>
                    <input type="hidden" name="daterange[]" value="PublicationDate"/>
                    <label for="PublicationDatefrom" class='yearboxlabel'>{translate text='date_from'}:</label>
                    <input type="text" size="4" maxlength="4" class="yearbox" name="PublicationDatefrom" id="PublicationDatefrom" value="{$dateRangeLimit.0|escape}" />
                    <label for="PublicationDateto" class='yearboxlabel'>{translate text='date_to'}:</label>
                    <input type="text" size="4" maxlength="4" class="yearbox" name="PublicationDateto" id="PublicationDateto" value="{$dateRangeLimit.1|escape}" />
                    <div id="PublicationDateSlider" class="yui-h-slider dateSlider" title="{translate text='Range slider'}" style="display:none;">
                      <div id="PublicationDateslider_min_thumb" class="yui-slider-thumb"><img src="{$path}/images/yui/left-thumb.png"></div>
                      <div id="PublicationDateslider_max_thumb" class="yui-slider-thumb"><img src="{$path}/images/yui/right-thumb.png"></div>
                    </div>
                  </td>
                </tr>
              </table>
              <br/>
            {/if}
            {* Display extra submit button if we have extra controls showing: *}
            {if !empty($checkboxFilters) || $dateRangeLimit}
              <input type="submit" name="submit" value="{translate text="Find"}"><br>
            {/if}
          </div>
      </div>
    </div>
  </div>

  <div class="yui-b">
  {if $searchFilters}
    <div class="box submenu filterList">
      <h3>{translate text="adv_search_filters"}
      {* "toggle all" disabled because it does not play well with checkbox filters!
       <span>({translate text="adv_search_select_all"} <input type="checkbox" checked="checked" onclick="filterAll(this);" />)</span>
       *}
      </h3>
      {foreach from=$searchFilters item=data key=field}
      <div>
        <h4>{translate text=$field}</h4>
        <ul>
          {foreach from=$data item=value}
          <li><input type="checkbox" checked="checked" name="filter[]" value='{$value.field|escape}:"{$value.value|escape}"' /> {$value.display|escape}</li>
          {/foreach}
        </ul>
      </div>
      {/foreach}
    </div>
  {/if}
    <div class="box submenu">
      <h3>{translate text="Search Tips"}</h3>

      <a href="{$url}/Help/Home?topic=search" onClick="window.open('{$url}/Help/Home?topic=advsearch', 'Help', 'width=625, height=510'); return false;">{translate text="Help with Advanced Search"}</a><br />
      <a href="{$url}/Help/Home?topic=search" onClick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text="Help with Search Operators"}</a>
    </div>
  </div>

{if $lastSort}<input type="hidden" name="sort" value="{$lastSort|escape}" />{/if}
        </form>
</div>

{* Step 1: Define our search arrays so they are usuable in the javascript *}
<script language="JavaScript" type="text/javascript">
    var searchFields = new Array();
    {foreach from=$advSearchTypes item=searchDesc key=searchVal}
    searchFields["{$searchVal}"] = "{translate text=$searchDesc}";
    {/foreach}
    var searchJoins = new Array();
    searchJoins["AND"] = "{translate text="search_AND"}";
    searchJoins["OR"]  = "{translate text="search_OR"}";
    searchJoins["NOT"] = "{translate text="search_NOT"}";
    var addSearchString = "{translate text="add_search"}";
    var searchLabel     = "{translate text="adv_search_label"}";
    var searchFieldLabel = "{translate text="in"}";
    var deleteSearchGroupString = "{translate text="del_search"}";
    var searchMatch     = "{translate text="search_match"}";
    var searchFormId    = 'advSearchForm';
</script>
{* Step 2: Call the javascript to make use of the above *}
{js filename="advanced.js"}
{* Step 3: Build the page *}
<script language="JavaScript" type="text/javascript">
  {if $searchDetails}
    {foreach from=$searchDetails item=searchGroup}
      {foreach from=$searchGroup.group item=search name=groupLoop}
        {if $smarty.foreach.groupLoop.iteration == 1}
    var new_group = addGroup('{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}', '{$search.bool}');
        {else}
    addSearch(new_group, '{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}');
        {/if}
      {/foreach}
    {/foreach}
  {else}
    var new_group = addGroup();
    addSearch(new_group);
    addSearch(new_group);
  {/if}
</script>