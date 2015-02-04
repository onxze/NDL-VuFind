  <dl class="narrowList navmenu{if (is_array($defaultFacets) && in_array('first_indexed', $defaultFacets))} open collapsed defaultFacet{elseif $opened} open collapsed active{/if}">
    <dt><span>{translate text='New Items in Index'}</span></dt>
      {foreach from=$newItemsLinks item=link name=links}
        {if !$smarty.foreach.links.last}
      <dd><a href="{$link.url|escape:'html'}">{$link.label}&nbsp;</a></dd>
        {else}
      <dd>
      <div class="startingfrom"><div class="left"><a class="startinglink" title="Show calendar picker">{$link.label}... </a></div><div class="right"><span id="showDate"></span><a id="newItemsFromDate" data-url="" data-urltemplate="{$link.url|escape:'html'}" data-solrdate="" href="" class="button buttonFinna searchButton right">&nbsp;</a></div></div>
      <div id="newItemsLimit" class="hasDatePick"></div></dd>
        {/if}
      {/foreach}
  </dl>
  {literal}
  <script type="text/javascript">
      initDatePicker('{/literal}{$newItemsDate}{literal}');
  </script>
  {/literal}
