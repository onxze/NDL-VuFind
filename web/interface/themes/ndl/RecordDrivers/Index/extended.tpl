<!-- START of: RecordDrivers/Index/extended.tpl -->

{*<table cellpadding="2" cellspacing="0" border="0" class="citation" summary="{translate text='Description'}">*}
  {if !empty($extendedSummary)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedSummary">
    <th>{translate text='Summary'}: </th>
    <td>
      {foreach from=$extendedSummary item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}
  
  {if !empty($extendedDateSpan)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedDateSpan">
    <th>{translate text='Dates of Publication'}: </th>
    <td>
      {foreach from=$extendedDateSpan item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedNotes)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedNotes">
    <th>{translate text='Item Description'}: </th>
    <td>
      {foreach from=$extendedNotes item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedFrequency)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedFrequency">
    <th>{translate text='Publication Frequency'}: </th>
    <td>
      {foreach from=$extendedFrequency item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedPlayTime)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedPlayTime">
    <th>{translate text='Playing Time'}: </th>
    <td>
      {foreach from=$extendedPlayTime item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedSystem)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedSystem">
    <th>{translate text='System Details'}: </th>
    <td>
      {foreach from=$extendedSystem item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAudience)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedAudience">
    <th>{translate text='Audience'}: </th>
    <td>
      {foreach from=$extendedAudience item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAwards)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedAwards">
    <th>{translate text='Awards'}: </th>
    <td>
      {foreach from=$extendedAwards item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedCredits)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedCredits">
    <th>{translate text='Production Credits'}: </th>
    <td>
      {foreach from=$extendedCredits item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedBibliography)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedBibliography">
    <th>{translate text='Bibliography'}: </th>
    <td>
      {foreach from=$extendedBibliography item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedISBNs)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedISBNs">
    <th>{translate text='ISBN'}: </th>
    <td>
      {foreach from=$extendedISBNs item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedISSNs)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedISSNs">
    <th>{translate text='ISSN'}: </th>
    <td>
      {foreach from=$extendedISSNs item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedRelated)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedRelatedItems">
    <th>{translate text='Related Items'}: </th>
    <td>
      {foreach from=$extendedRelated item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAccess)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedAccess">
    <th>{translate text='Access restrictions'}: </th>
    <td>
      {if !empty($extendedAccessType)}
        {if !empty($extendedAccessType.link)}<a href="{$extendedAccessType.link}">{/if}{$extendedAccessType.copyright}{if !empty($extendedAccessType.link)}</a>{/if}<br/>
      {/if}
      {if !empty($extendedAccess) && (count($extendedAccess) > 1 || $extendedAccessType.copyright !== $extendedAccess[0])}
      {foreach from=$extendedAccess item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
      {/if}
    </td>
  </tr>
  {/if}

  {if !empty($extendedFindingAids)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedFindingAids">
    <th>{translate text='Finding Aid'}: </th>
    <td>
      {foreach from=$extendedFindingAids item=field name=loop}
        {$field|escape}<br/>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAuthorNotes)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedAuthorNotes">
    <th>{translate text='Author Notes'}: </th>
    <td>
      {foreach from=$extendedAuthorNotes item=providerList key=provider}
        {foreach from=$providerList item=field name=loop}
          {$field.Content}<br/>
        {/foreach}
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedVideoClips)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top" class="extendedVideoClips">
    <th>{translate text='Video Clips'}: </th>
    <td>
      {foreach from=$extendedVideoClips item=providerList key=provider}
        {foreach from=$providerList item=field name=loop}
          {$field.Content}<br/>
          {$field.Copyright}<br/>
        {/foreach}
      {/foreach}
    </td>
  </tr>
  {/if}

  {* Avoid errors if there were no rows above *}
  {if !$extendedContentDisplayed}
  {*<tr><td>&nbsp;</td></tr>*}
  {/if}
   
{*</table>*}

<!-- END of: RecordDrivers/Index/extended.tpl -->
