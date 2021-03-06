<div>
  {if !empty($electronic)}
    <div class="openurls">
      <span class="fulltextAvailable">{translate text="Full text available"}:</span>
      <ul>
        {foreach from=$electronic item=link}
          <li>
            {if $link.href}
              <a class="availableOpenURL" href="{$link.href|escape}" title="{$link.service_type|escape}" target="_blank">{$link.title|escape}</a> {$link.coverage|escape}
            {else}
              {$link.title|escape} {$link.coverage|escape}
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if !empty($print)}
    <div class="openurls">
      {translate text="Holdings"}
      <ul>
        {foreach from=$print item=link}
          <li>
            {if $link.href}
              <a href="{$link.href|escape}" title="{$link.service_type|escape}" target="_blank">{$link.title|escape}</a> {$link.coverage|escape}
            {else}
              {$link.title|escape} {$link.coverage|escape}
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
  {/if}
  <div class="openurls">
    <a class="openurl_more_full" href="{$openUrlBase|escape}?{$openUrl|escape}" target="_blank">{translate text="More options"}<span class="openWindow"></span></a>
    {if !empty($services)}
      <ul>
        {foreach from=$services item=link}
          {if $link.href}
            <li>
              <a href="{$link.href|escape}" title="{$link.service_type|escape}" target="_blank">{$link.title|escape}</a>
            </li>
          {/if}
        {/foreach}
      </ul>
    {/if}
  </div>
</div>
