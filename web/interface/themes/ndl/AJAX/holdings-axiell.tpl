<!-- START of: AJAX/holdings-axiell.tpl -->

{if $locationThreshold < $locationCount}
  {assign var="collapseLocations" value=true}
{/if}

{if $branchThreshold < $branchCount}
  {assign var="collapseBranch" value=true}
{/if}

<table class="resultHoldingsContainer">
  {if !$holdings}
    <tr><td><span>{translate text="No holdings information available"}</span></tr></td>
  {else}
    <tr class="resultHoldingsHeading">
      <th{if $collapseLocations} class="collapsible collapsed"{/if}>
        {if $availableLocationCount > 0}
          <span class="availability available">
              {if $journal}
                {if $userLang == 'fi'}
                  {translate text="axiell_available"} {$availableCount} {translate text="axiell_issues"}
                {else}
                  {$availableCount} {translate text="axiell_issues"} {translate text="axiell_available"}
                {/if}
              {else} 
                {translate text="axiell_available_from"} {$availableCount} {translate text="axiell_branches_alt"}
              {/if}
          </span>
        {else}
          {if $closestDueDate != ''}
            <span class="availability checkedout">{translate text="status_Charged"}
              <span class="duedate">{translate text="Due Date"} {$closestDueDate}</span>
            </span>
          {elseif $itemStatusText == $referenceDeskStatus}
            <span class="availability available">{translate text="status_`$itemStatusText`"}</span>
          {else}
            <span class="availability checkedout">{translate text="status_`$itemStatusText`"}</span>
          {/if}
        {/if}
        {if $collapseLocations}<span class="arrowIndicator collapsed"></span>{/if}
      </th>
      {if !$holdings.0.journal}
      <th>{translate text="Requests"} {$requestCount}, {translate text="axiell_items"} {$itemCount}</th>
      {else}
      <th></th>
      {/if}
    </tr>
    {foreach from=$holdings item=location name=locations}
      <tr{if $collapseLocations} class="collapsed"{/if}>
        <td colspan="2">
          <table>
            <tr>
              <th{if $collapseBranch} class="collapsible collapsed"{/if}><span class="indent">{if $collapseBranch}<span class="arrowIndicator collapsed"></span>{/if}
                {$location.title} 
                {if $collapseBranch}
                  {if $location.status.availableCount > 0}
                     ({translate text="axiell_available_from"} {$location.status.availableCount} {translate text="axiell_branches_alt"})
                  {elseif $location.status.text=="Closest due"}
                    ({translate text=$location.status.text} {$location.status.closestDueDate})
                  {else}
                    ({translate text="status_`$location.status.text`"})
                  {/if}
                {/if}
              </span></th>
              <th>{$location.callnumber}</th>
            </tr>
            {foreach from=$location.holdings item=holding name=holding}
              <tr{if $collapseBranch} class="collapsed"{/if}>
                <td class="copyNumber">
                  <span class="holdingText indent {if $holding.availability || $holding.status==$referenceDeskStatus}available{else}checkedout{/if}">
                    {if $journal}{$holding.organisation}, {/if}{$holding.branch}, {$holding.department}{if $holding.location}, {$holding.location}{/if}
                  </span>
                </td>
                <td> 
                  {if $holding.available > 0}
                    {translate text="Available items"}: {$holding.available}
                  {else}
                    {if $holding.duedate != '' && !$holding.availability}
                      {translate text="Closest due"} {$holding.duedate}
                    {elseif $holding.ordered > 0}
                      {translate text="status_Ordered"} {$holding.ordered}
                    {else}
                      {translate text="status_`$holding.status`"}
                    {/if}
                  {/if}
                </td>
              </tr>
            {/foreach}
          </table>
        </td>
      </tr>
    {/foreach}
    {if $patronFunctions}
      <tr class="static">
        <td colspan="2">
          <div class="holdingsPlaceHold axiell">
            {if $isHoldable}
              {if !$user}
                {if $driverMode || $titleDriverMode}
                    <a class="button buttonFinna" href="{$path}/MyResearch/Home?followup=true&followupModule=Record&followupAction={$id|escape}%23tabnav">{translate text="title_hold_place"}</a>
                {else}
                  <span>{translate text="title_cant_place_hold"}</span>
                {/if}
              {else}
                {if !$user->cat_username}
                  <a class="button buttonFinna" href="{$path}/MyResearch/Profile">{translate text="Add an account to place holds"}</a>
                {else}
                  {if $holdingTitleHold && $holdingTitleHold != 'block'}
                    {if $holdings.0.journal}
                      <a class="button buttonFinna" href="{$path}/Record/{$id|escape}#tabnav">{translate text="title_hold_place"}</a>
                    {else}
                      <a class="button buttonFinna holdPlace" href="{$holdingTitleHold|escape}">{translate text="title_hold_place"}</a>
                    {/if}
                  {elseif $holdingTitleHold == 'block'}
                      {translate text="hold_error_blocked"}
                  {elseif !$holdingTitleHold}
                      <span>{translate text="title_cant_place_hold"}</span>
                  {/if}
                {/if}
              {/if}
            {else}
              <span>{translate text="title_cant_place_hold"}</span>
            {/if}
          </div>
        </td>
      </tr>
    {/if}
    <tr class="static">
      <td colspan="2">
          <a class="moreHoldings" href="{$path}/Record/{$id|escape}#holdingstab">{translate text="axiell_see_all_holdings"} »</a>
      </td>
    </tr>
  {/if}
</table>

<!-- END of: AJAX/holdings-axiell.tpl -->