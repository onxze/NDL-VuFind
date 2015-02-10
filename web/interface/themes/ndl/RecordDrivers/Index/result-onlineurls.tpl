
      {if $summOpenUrl || !empty($summURLs) || !empty($summOnlineURLs)}
        {if $summOnlineURLs}
        <div>
          {if $sumOnlineURLs|@count > 2}
          <p class="resultContentToggle"><a href="#" class="toggleHeader">{translate text='available_online'}<img src="{path filename="images/down.png"}" width="11" height="6" alt="" /></a></p>
          {else}
          <p class="resultContentToggle">{translate text='available_online'}<img src="{path filename="images/down.png"}" width="11" height="6" alt="" /></p>
          {/if}
          <div class="resultContentList">
          <ul>
          {foreach from=$summOnlineURLs item=urldesc}
            <li><a href="{$urldesc.url|proxify|escape}" class="fulltext" target="_blank" title="{$urldesc.url|escape}">{if $urldesc.text}{$urldesc.text|translate_prefix:'link_'|escape}{else}{$urldesc.url|truncate_url|escape}{/if}</a>{if $urldesc.source} ({if is_array($urldesc.source)}{translate text='Multiple Organisations'}{else}{$urldesc.source|translate_prefix:'source_'}{/if}){/if}</li>
          {/foreach}
          </ul>
	        {if $summOpenUrl}
	          {include file="Search/openurl.tpl" openUrl=$summOpenUrl}
	        {/if}
          </div>
        </div>
        {elseif $summURLs}
        <div>
          {if $summURLs|@count > 2}
          <p class="resultContentToggle"><a href="#" class="toggleHeader">{translate text='available_online'}<img src="{path filename="images/down.png"}" width="11" height="6" alt="" /></a></p>
          {else}
          <p class="resultContentToggle">{translate text='available_online'}<img src="{path filename="images/down.png"}" width="11" height="6" alt="" /></p>
          {/if}
          <div class="resultContentList">
          <ul>
          {foreach from=$summURLs key=recordurl item=urldesc}
            <li><a href="{$recordurl|proxify|escape}" class="fulltext" target="_blank" title="{$recordurl|escape}">{if $recordurl == $urldesc}{$recordurl|truncate_url|escape}{else}{$urldesc|translate_prefix:'link_'|escape}{/if}</a></li>
          {/foreach}
          </ul>
	        {if $summOpenUrl}
	          {include file="Search/openurl.tpl" openUrl=$summOpenUrl}
	        {/if}
          </div>
        </div>
        {else}
	        {if $summOpenUrl}
	          {include file="Search/openurl.tpl" openUrl=$summOpenUrl}
	        {/if}
        {/if}
      {/if}
