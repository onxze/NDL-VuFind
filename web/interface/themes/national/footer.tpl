<!-- START of: footer.tpl -->

<div class="grid_8">
  <h2>{translate text='navigation_aboutus'}</h2>
  <ul>
    <li><a href="{$path}/Content/Organisations">{translate text="navigation_organizations"}</a></li>
    <li><a href="{$path}/Content/about">{translate text='navigation_about_finna'}</a></li>
    <li><a href="{$path}/Content/terms_conditions">{translate text='navigation_terms_conditions'}</a></li>
    <li><a href="{$path}/Content/register_details">{translate text='navigation_register_details'}</a></li>
  </ul>
</div>

<div class="grid_8">
  <h2>{translate text='navigation_search'}</h2>
  <ul>
    <li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
    <li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
    <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
  </ul>
</div>

 <div class="grid_8">
  <h2>{translate text='navigation_help'}</h2>
  <ul>
    <li><a href="{$path}/Content/searchhelp">{translate text='Search Tips'}</a></li>
    <li><a href="{$path}/Feedback/Home">{translate text='navigation_feedback'}</a></li>
  </ul>
</div>

<div id="footerSeparator"></div>

<div class="grid_8">
   <a href="http://finna.fi" class="footerLogo">{image src="finna_logo_small.png" alt="Home"}</a>
</div>
<div class="grid_8">
  {if $userLang=='en-gb'}
    <a href="http://www.kdk.fi/en" target="_blank" class="footerLogo">{image src="kdk_logo_en.png" alt="NDL-logo"}</a>
  {/if}
  {if $userLang=='sv'}
    <a href="http://www.kdk.fi/sv" target="_blank" class="footerLogo">{image src="kdk_logo_sv.png" alt="logo"}</a>
  {/if}
  {if $userLang=='fi'}
      <a href="http://www.kdk.fi" target="_blank" class="footerLogo">{image src="kdk_logo_fi.png" alt="KDK-logo"}</a>
  {/if}
</div>
<div class="grid_8">
  <a href="http://www.vufind.org" target="_blank" class="footerLogo">{image src="vufind_logo_small.png" alt="vufind-logo"}</a>
  
  {* Comply with Serials Solutions terms of service -- this is intentionally left untranslated. *}
  {if $module == "Summon"}
  <br /><p>Powered by Summon™ from Serials Solutions, a division of ProQuest.
  </p>
  {/if}
</div>

  
{literal}
<script type="text/javascript">   
  $(document).ready(function(){
    $('.toggleHeader').parent().next().hide();
    $('.toggleHeader').click(function(){
      $(this).parent().next().toggle('fast');
      return false;
    });
  });
</script>
{/literal}

<!-- END of: footer.tpl -->
