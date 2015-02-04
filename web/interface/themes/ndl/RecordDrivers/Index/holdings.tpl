<!-- START of: RecordDrivers/Index/holdings.tpl -->

<div class="holdingsHeader clearfix">
    {if is_array($recordFormat)}
      {assign var=format value=$recordFormat.0}
    {else}
      {assign var=format value=$recordFormat}
    {/if}
    <div class="holdingsHoldingURLs">
    {if (!empty($holdingURLs) || $holdingsOpenURL)}
      <h5>{translate text="available_online"}</h5>
      <ul class="holdingsOnline">
      {if !empty($holdingURLs)}
        {foreach from=$holdingURLs item=desc key=currentUrl name=loop}
          <li><a href="{$currentUrl|proxify|escape}" target="_blank">{$desc|translate_prefix:'link_'|escape}</a></li>
        {/foreach}
      {/if}
      {if $holdingsOpenURL}
        {include file="Search/openurl.tpl" openUrl=$holdingsOpenURL}
      {/if}
      </ul>
    {/if}
    </div>
    <div class="holdingsHeaderLinks">
      {if $id|substr:0:7 == 'helmet.'}
        <span class="native_link">
          <a href="http://haku.helmet.fi/iii/encore/record/C|R{$id|substr:7|escape}" target="_blank">{translate text='Holdings details from'} HelMet</a><br/>
        </span>
      {/if}
    </div>
  {if $patronFunctions}
    <div class="holdingsPlaceHold">
    {assign var="ublink" value=false}
        {foreach from=$holdings item=holding}
          {if !$ublink}
            {foreach from=$holding item=row}
              {if !$ublink && $row.UBRequestLink}
                <a class="UBRequestPlace checkUBRequest button buttonFinna" href="{$row.UBRequestLink|escape}"><span>{translate text="ub_request_check"}</span></a>
                {assign var="ublink" value=true}
              {/if}
            {/foreach}
          {/if}
        {/foreach}
      {if !$hideLogin && $offlineMode != "ils-offline"}
        {if ($driverMode  && !empty($holdings)) || $titleDriverMode}
          {if $showLoginMsg || $showTitleLoginMsg}
              <a class="button buttonFinna" href="{$path}/MyResearch/Home?followup=true&followupModule=Record&followupAction={$id|escape}">{translate text="Login"}</a> {translate text="hold_login"}
          {/if}
          {if count($catalogAccounts) > 1}
            {include file="MyResearch/select-card.tpl"}
          {/if}
          {if $user && !$user->cat_username}
              <a class="button buttonFinna" href="{$path}/MyResearch/Profile">{translate text="Add an account to place holds"}</a>
          {/if}
        {/if}
      {/if}
      {if $holdingTitleHold && $holdingTitleHold != 'block'}
          <a class="button buttonFinna holdPlace" href="{$holdingTitleHold|escape}">{translate text="title_hold_place"}</a>
      {/if}
      {if $catalogAccounts && !$ublink && !$holdingTitleHold}
        {if $driver != 'AxiellWebServices'}
          <span>{translate text="title_cant_place_hold"}</span>
        {else}
          {assign var="holdLink" value=false}
          {foreach from=$holdings item=holding}
            {if !$holdLink}
              {if $holding.0.link}
                {assign var="holdLink" value=true}
              {else}
                {foreach from=$holding.holdings item=singleHolding}
                  {if $singleHolding.0.link}
                    {assign var="holdLink" value=true}
                  {/if}
                {/foreach}
              {/if} 
            {/if}
          {/foreach}
          {if !$holdLink}
            <span>{translate text="title_cant_place_hold"}</span>
          {/if}
        {/if}
      {/if}
      {if $holdingTitleHold == 'block'}
          {translate text="hold_error_blocked"}
      {/if}
    </div>
  {/if}
</div>
{if $driver != 'AxiellWebServices'}
  <div class="holdingsContainerHeader">
    {if $coreMergedRecordData.dedup_data}
    <div class="menuHolder">
      <select id="dedupRecordHoldingsMenu" name="deduprecordHoldingsMenu" class="dropdown jumpMenuURL">
        {foreach from=$coreMergedRecordData.dedup_data key=source item=dedupData name=loop}
        <option value="{$url}/Record/{$dedupData.id|escape:"url"}#holdingstab"{if $dedupData.id == $id} selected="selected"{/if}>{translate text=$source prefix='source_'}</option>
        {/foreach}
      </select>
    </div>
    {else}
      <h5>{translate text=$source prefix='source_'}</h5>
    {/if}
  </div>
  <div class="holdingsContainer clearfix driver-{$driver}">
    {if !$holdings}
       <h5>{translate text="No holdings information available"}</h5>
    {else} 
      <div>
         {* Display link to access rights for records from fennica, viola and vaari *} 
        {if $coreSource == 'fennica' || $coreSource == 'viola' || $coreSource == 'vaari'}  
        <p class="accessRights"><a href="
        {if $coreSource == 'fennica' || $coreSource == 'viola'}{if $userLang == 'fi'}http://www.kansalliskirjasto.fi/kokoelmatjapalvelut/lainaus/kansalliskokoelmankaytosta.html{elseif $userLang == 'sv'}http://www.nationalbiblioteket.fi/tjanster/lainaus/kansalliskokoelmankaytosta.html{else}http://www.nationallibrary.fi/services/lainaus/kansalliskokoelmankaytosta.html{/if}
        {elseif $coreSource == 'vaari'}http://www.varastokirjasto.fi/{if $userLang == 'fi'}kaukolainaus/varastokirjaston-palvelut-henkiloasiakkaille/{elseif $userLang == 'sv'}utlaning/service-for-privatkunder/{else}loans-and-requests/ill-services-for-private-customers/{/if}
        {/if}
        " target="_blank">{translate text="Record access rights"}</a></p>
        {/if}
      </div>
    {/if}
    {foreach from=$holdings item=holding key=location name=holdings}
      {assign var=prevGroupId value=''}
      {assign var="copyCount" value="0"}
      {foreach from=$holding item=row name=items}
        {assign var="itemsIteration" value=$smarty.foreach.items.iteration}
        {if isset($row.mfhd_id)}
          {assign var="holdingsGroupId" value=$row.mfhd_id}
        {else}
          {if isset($row.callnumber)}
            {assign var="holdingsGroupId" value=$row.callnumber|replace:'.':'_'}
          {else}
            {assign var="holdingsGroupId" value=$location|replace:'.':'_'}
          {/if}
        {/if}
        {if $prevGroupId != $holdingsGroupId}
          {assign var="showCopyTitle" value=1}
          
          {if $prevGroupId}
            <tr class="avail"><td>{$availCount}</td></tr>
            </table>
          {/if}
          {assign var=prevGroupId value=$holdingsGroupId}
          <table class="holdingsContainerHolding  {if $smarty.foreach.items.first && $smarty.foreach.holdings.first}active{/if}" id="holding_{$prevGroupId}_{$itemsIteration}" cellpadding="2" cellspacing="0" border="0" class="citation" summary="{translate text='Holdings details from'} {translate text=$location}">
          <tr class="holdingsContainerHeading">
            <th colspan="2" class="location"><span class="arrowIndicator"></span>{$location|translate|escape}</th>
            <th colspan="2" class="holdingDetails">
              <span class="availability">{translate text="Available"}</span>
            </th>
            <th class="locationLink">
              {if $locationServiceUrl}
                <a class="openLocationService" href="{$locationServiceUrl}&callno={$row.callnumber|escape:'url'}">
                {$row.callnumber|escape}</a>
              {else}
                {$row.callnumber|escape}
              {/if}
            </th>
          </tr>
          <tr class="mobileLocation">          
            <td colspan="5" class="locationLink">
              {if $locationServiceUrl}
                <a class="openLocationService" href="{$locationServiceUrl}&callno={$row.callnumber|escape:'url'}">
                {$row.callnumber|escape}</a>
              {else}
                {$row.callnumber|escape}
              {/if}
            </td>
          </tr>
            {assign var="availCount" value=0}
            {if $row.summary}
              <tr class="rowSummary format-{$format}">
                <td class="copyTitle">{translate text="Volume Holdings"}: </td>
                <td colspan="4">
                  {foreach from=$row.summary item=summary}
                    {$summary|escape}<br>
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $row.purchase_history}
              <tr>
                <td class="copyTitle">{translate text="Most Recent Received Issues"}: </td>
                <td colspan="4">
                  {foreach from=$row.purchase_history item=data}
                    {$data.issue|escape}<br>
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $row.notes}
              <tr>
                <td class="copyTitle">{translate text="Notes"}: </td>
                <td colspan="4">
                  {foreach from=$row.notes item=data}
                    {$data|escape}<br>
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $row.supplements}
              <tr>
                <td class="copyTitle">{translate text="Supplements"}: </td>
                <td colspan="4">
                  {foreach from=$row.supplements item=supplement}
                    {$supplement|escape}<br>
                  {/foreach}
                </td>
              </tr>
            {/if}
            {if $row.indexes}
              <tr>
                <td class="copyTitle">{translate text="Indexes"}: </td>
                <td colspan="4">
                  {foreach from=$row.indexes item=index}
                    {$index|escape}<br>
                  {/foreach}
                </td>
              </tr>
            {/if}
          {/if}
          {if $row.item_id}
            {if $copyCount == 12}<tr class="toggleCopyDetails"><td class="copyTitle"></td><td colspan="2"><strong><a href="#">{translate text="More Results"}</a></strong></td></tr>{/if}
            <tr class="copyDetails {if $showCopyTitle}{counter start=0 assign=copyCount}first{/if} {if $copyCount >= 12}hidden{/if}">
              {counter assign=copyCount}
              {if $showCopyTitle}<td class="copyTitle">{translate text="Copies"}{assign var="showCopyTitle" value=0}</td>
              {else}<td></td>{/if}
              <td class="copyNumber">
                {if $row.itemSummary}
                  {$row.itemSummary}
                {else}
                  {if $row.number|trim == false}{translate text="Copy"}{/if}
                  {$row.number|escape}
                {/if}
              </td>
              <td colspan="3" class="copyInfo">
                {if $row.reserve == "Y"}
                  {translate text="On Reserve - Ask at Circulation Desk"}
                {elseif $row.use_unknown_message}
                  <span class="unknown">{translate text="status_unknown_message"}</span>
                {else}
                  {if $row.availability}
                    {assign var=availCount value=$availCount+1}
                    {* Begin Available Items (Holds) *}
                    <span class="available">{translate text="Available"}</span>
                    {if $patronFunctions && $row.link}
                      <a class="holdPlace{if $row.check} checkRequest{/if}" href="{$row.link|escape}"><span>{if !$row.check}{translate text="Place a Hold"}{else}{translate text="Check Hold"}{/if}</span></a>
                    {/if}
                    {if $patronFunctions && $row.callSlipLink}
                      <a class="callSlipPlace{if $row.checkCallSlip} checkCallSlipRequest{/if}" href="{$row.callSlipLink|escape}"><span>{if !$row.checkCallSlip}{translate text="call_slip_place_text"}{else}{translate text="Check Call Slip Request"}{/if}</span></a>
                    {/if}
                  {else}
                  {* Begin Unavailable Items (Recalls) *}
                    {if is_null($row.availability)}
                    <span class="availabilityUnknown" data-status="{$row.status}">{translate text=$row.status prefix='status_'}</span>
                    {else}
                    <span class="checkedout">{translate text=$row.status prefix='status_'}</span>
                    {/if}
                    {if $row.returnDate} <span class="statusExtra"><span class="returnDate">{$row.returnDate|escape}</span></span>
                    {/if}
                    {if $row.duedate}
                    {* N.B. The "returnDate duedate" classes on the next line are needed for the JS below to work properly *}
                    <span class="statusExtra">{translate text="Due"}: <span class="returnDate duedate">{$row.duedate|escape}</span></span>

                    {/if}
                    {if $row.requests_placed > 0}
                      <span>{translate text="Requests"}: {$row.requests_placed|escape}</span>
                    {/if}
                    {if $patronFunctions && $row.link}
                      <a class="holdPlace{if $row.check} checkRequest{/if}" href="{$row.link|escape}"><span>{if !$row.check}{translate text="Recall This"}{else}{translate text="Check Recall"}{/if}</span></a>
                    {/if}
                  {/if}
                {/if}
              </td>
            </tr>
          {/if}
        {/foreach}
        <tr class="avail"><td>{$availCount}</td></tr>
        </table>
    {/foreach}
  </div>
{else}
  <div class="holdingsContainerHeader">
    <div class="menuHolder">
    {if $coreMergedRecordData.dedup_data}
      <select id="dedupRecordHoldingsMenu" name="deduprecordHoldingsMenu" class="dropdown jumpMenuURL">
        {foreach from=$coreMergedRecordData.dedup_data key=source item=dedupData name=loop}
        <option value="{$url}/Record/{$dedupData.id|escape:"url"}#holdingstab"{if $dedupData.id == $id} selected="selected"{/if}>{translate text=$source prefix='source_'}</option>
        {/foreach}
      </select>
    {else}
      <div class="singleItem">{translate text=$source prefix='source_'}</div>
    {/if}
    {assign var="journal" value="0"}
    {foreach from=$holdings item=location}
      {if $location.0.journal}
        {assign var="journal" value="1"}
      {/if}
    {/foreach}
    {if $holdings && !$journal}
      <div class="holdingTotals">
        <span class="requestCount">{translate text="Requests"}: {$requestCount}</span>
        <span class="holdingCount">{translate text="Total number of items"}: {$itemCount}</span>
      </div>
    {/if}
    </div>
  </div>
  <div class="holdingsContainer clearfix driver-{$driver}">
    {if !$holdings}
       <h5>{translate text="No holdings information available"}</h5>
    {/if}
    {foreach from=$holdings item=location name=locations}
      {if $location.0.status.text == "Available"}
        {assign var="availableLoc" value="1"}
      {else}
        {assign var="availableLoc" value="0"}
      {/if}
      {assign var="status" value=$location.0.status.text}
      <table class="holdingsContainerHolding  {if $smarty.foreach.locations.first}active{/if}">
        <tr class="holdingsContainerHeading">
          <th class="location" colspan="2"><span class="arrowIndicator"></span>{$location.0.title}</th>
          <th class="holdingDetails" colspan="2">
            <span class="availability {if $availableLoc || $location.0.status.text=="On Reference Desk"}available{else}checkedout{/if}">
              {if $availableLoc}
                {translate text="axiell_available_from"} {$location.0.status.availableCount} {translate text="axiell_branches"}
              {elseif $status=="Closest due"}
                {translate text=$status} {$location.0.status.closestDueDate}
              {else}
                {translate text="status_`$status`"}
              {/if}
            </span>
            {if $journal}
              {assign var=commonReservations value=$location.0.status.reservations}
              {assign var=maxReservations value=0}
              {foreach from=$location.0.holdings item=holding}
                {if $holding.reservations != $commonReservations}
                  {assign var=commonReservations value=false}
                {/if}
                {if $holding.reservations > $maxReservations}
                  {assign var=maxReservations value=$holding.reservations}
                {/if}
              {/foreach}
              {if $location.0.status.reservations || $maxReservations}
                <span class="requestCount">({translate text="Requests"}: {if $location.0.status.reservations}{$location.0.status.reservations}{else}{$maxReservations}{/if})</span>
              {/if}
            {/if}
          </th>
          <th class="locationLink">{$location.0.callnumber}
            {if $holdingTitleHold != 'block' && $location.0.link}
              <a class="button buttonFinna holdPlace" href="{$location.0.link|escape}" data-title="{translate text="Place a Hold"} - {$location.0.title|escape}">{translate text="Place a Hold"}</a>
            {/if}
          </th>
        </tr>
        {foreach from=$location.0.holdings item=holding name=holding}
          <tr class="copyDetails {if $smarty.foreach.holding.first}first{/if}">
            <td colspan="2" class="copyTitle"><span class="holdingOrganisation">{if $journal}{$holding.organisation}, {/if}{$holding.branch}</span><span class="holdingDepartment">, {$holding.department}{if $holding.location}, {$holding.location}{/if}</span></td>
            <td class="copyInfo" colspan="2">
              <span class="{if $holding.availability || $holding.status=="On Reference Desk"}available{else}checkedout{/if}">
              {if $holding.availability}
                {capture assign="avail" name="avail"}{translate text="axiell_available"}{/capture}{$avail|@ucfirst}
              {else}
                {translate text="status_`$holding.status`"}
              {/if}
              </span>
              {if $holding.duedate != '' && !$holding.availability}
                <span class="statusExtra">{translate text="Due Date"}: <span class="returnDate">{$holding.duedate}</span></span>
              {/if}
            </td>
            <td class="availableOfTotal">
              {if $holding.ordered > 0}
                {translate text="status_Ordered"}: {$holding.ordered}<br/>
              {/if}
              {if $holding.reservations && $commonReservations === false}
                {translate text="Requests"}: {$holding.reservations}<br/>
              {/if}
              {translate text="Available items"}: {$holding.available} / {$holding.total}
              {if !$location.0.link & $holdingTitleHold != 'block' && $holding.link}
                <a class="holdPlace" href="{$holding.link|escape}" title="{translate text="Place a Hold"} - {$location.0.title|escape}">{translate text="Place a Hold"}</a>
              {/if}
            </td>
          </tr>
        {/foreach}
      </table>
    {/foreach}
  </div>
{/if}
{literal}
<script type="text/javascript">
$(document).ready(function() {

    // IE8 compatible Date.now()
    Date.now = Date.now || function() { return +new Date; };

    /* Open volume details by clicking on the heading */
    $('.holdingsContainerHeading').click(function() {
      $(this).closest('table').toggleClass('active');
    })

    /* Show more/less copy information */
    $('.toggleCopyDetails a').click(function(e) {
      e.preventDefault();
      $(this).toggleClass('active');
      var $parentTr = $(this).closest('tr'),
          $copyDetails = $parentTr.siblings('.copyDetails'),
          openText = '{/literal}{translate text="More Results"}{literal}',
          closeText = '{/literal}{translate text="Fewer Results"}{literal}';

      // Change .hidden to .visible
      $copyDetails.each(function() {
        if ($(this).is('.hidden')) {
          $(this).removeClass('hidden').addClass('visible');
        } else if ($(this).is('.visible')) {
          $(this).removeClass('visible').addClass('hidden');
        }
      });

      // Move the toggler and change its content accordingly
      if ($(this).is('.active')) {
        $(this).text(closeText);
        $parentTr.insertAfter($copyDetails.last());
      } else {
        $(this).text(openText);
      }
    });

    {/literal}{if $driver != 'AxiellWebServices'}{literal}

    /* Show copy details in the heading */
    $('.holdingsContainerHolding').each(function() {
      var availableCount = $(this).find('.avail td').text();
          $headingAvail = $(this).find('.holdingsContainerHeading .availability');
          iBlock = {'display': 'inline-block'},
          msg = '{/literal}{translate text="Available"}{literal}',
          due = '{/literal}{translate text="Closest due"}{literal}';
      /* If there are available copies, show the number */
      if (availableCount > 0) {
        $headingAvail.addClass('available');
        $headingAvail.text(availableCount + ' ' + $headingAvail.text().toLowerCase()).show();
      } else { /* If no available copies, get return dates */
        var prevDate, firstDate, firstDateText;
        $(this).find('.copyDetails .returnDate.duedate').each(function() {
          var returnDateSplit = $(this).text().split('.'); /* Split the date and build it anew */
          var returnDate = new Date(returnDateSplit[2], returnDateSplit[1] - 1, returnDateSplit[0]);
          if (typeof prevDate == 'undefined' || returnDate < prevDate) { 
            firstDate = returnDate; /* Get the closest date to today */
          }
          prevDate = firstDate;
        })
          
        /* If a return date exists */
        if (typeof firstDate != 'undefined') {
          var $dateTarget = $(this).find('.holdingDetails .returnDate');

          $headingAvail.addClass('checkedout').text(due + ' ' + ' ' + firstDate.getDate() + '.' + 
            (firstDate.getMonth() + 1) + '.' +firstDate.getFullYear()).css(iBlock);

        } else { /* Without a date, only show the checkedout message */
          var uniqueStatuses = [];
          var fullStatus = '';
          $(this).find('.checkedout').each(function(index, elem) {
              var status = $(elem).text();
              if ((jQuery.inArray(status, uniqueStatuses)) == -1) {
                  uniqueStatuses.push(status);
                  fullStatus += status + ' ';
              }
          });        
          if (fullStatus !== '') {
            $headingAvail.addClass('checkedout').text(fullStatus).css(iBlock);
          } else { /* If no message found, print the default one */
            $headingAvail.addClass('available').text(msg).css(iBlock);
          }
        }
      }
    });

  {/literal}{/if}{literal}

  $('a.holdPlace,a.callSlipPlace,a.UBRequestPlace').click(function() {
    var id = {/literal}'{$id}'{literal};
    var href = $(this).attr('href');
    var hashPos = href.indexOf('#');
    if (hashPos >= 0) {
      href = href.substring(0, hashPos);      
    }
    var title = $(this).data('title') ? $(this).data('title') : $(this).text();
    var $dialog = getPageInLightbox(href + '&lightbox=1', title, 'Record', '', id);
    return false;
  });

  createDropdowns();

  initDropdowns();

  initJumpMenus();
  
  initScrollAfterLoading();
  
});
</script>
{/literal}
<!-- END of: RecordDrivers/Index/holdings.tpl -->