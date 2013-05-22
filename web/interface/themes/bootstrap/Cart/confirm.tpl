<div class="well-small alignleft">
  {if $listName}
  <h3>"{$listName|escape}"</h3>
  {/if}  

  {if $infoMsg || $errorMsg} 
  <div class="messages">
  {if $errorMsg}<div class="alert alert-error">{$errorMsg|translate}</div>{/if}
  {if $infoMsg}<div class="alert alert-warning">{$infoMsg|translate}</div>{/if}
  </div>
  {/if}

  <div id="popupMessages"></div>
  <div id="popupDetails"> 

    <form action="{$url}/Cart/Home" method="post">
    <input type="hidden" name="confirm" value="1" />
    {if $origin}
    <input type="hidden" name="origin" value="{$origin|escape}" />
    {/if}
    {if $listID}
    <input type="hidden" name="listID" value="{$listID|escape}" />
    {/if}
    <input type="hidden" name="{$confirmAction|escape}" />
    <input type="submit" class="btn btn-success" name="confirmAction" value="{translate text='confirm_dialog_yes'}" /> <input type="submit" class="btn btn-warning" name="cancelAction" value="{translate text='confirm_dialog_no'}" />
    </form>

  </div>
</div>

