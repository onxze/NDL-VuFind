{js filename="ajax_common.js"}
{js filename="search.js"}

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="record">

        <p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

        {if $parseError}
            <p class="error">{translate text='nohit_parse_error'}</p>
        {/if}

        {if $spellingSuggestions}
        <div class="correction">{translate text='nohit_spelling'}:<br/>
        {foreach from=$spellingSuggestions item=details key=term name=termLoop}
          {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
        {/foreach}
        </div>
        <br/>
        {/if}

        {* Recommendations *}
        {if $topRecommendations}
          {foreach from=$topRecommendations item="recommendations"}
            {include file=$recommendations}
          {/foreach}
        {/if}

        {if $noResultsRecommendations}
          {foreach from=$noResultsRecommendations item="recommendations" key='key' name="noResults"}
            {include file=$recommendations}
          {/foreach}
        {/if}
      </div>
    </div>
  </div>

  {* Narrow Search Options *}
  <div class="yui-b">
    {if $sideRecommendations}
      {foreach from=$sideRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
    <br />
  </div>
  {* End Narrow Search Options *}
</div>