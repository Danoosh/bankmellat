{*
* 2013 Presta-Shop.ir
*
*
*  @author Presta-Shop.ir - Danoosh Miralayi
*  @copyright  2013 Presta-Shop.ir
*}
{capture name=path}{l s='پرداخت بانک ملت' mod='bankmellat'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}


<div class="block-center" id="">
<h2>{l s='پرداخت بانک ملت' mod='bankmellat'}</h2>

{include file="$tpl_dir./errors.tpl"}

{if isset($prepay) && $prepay}
	<br />
	<p>{l s='در حال اتصال به بانک...' mod='bankmellat'}</p>
	<p>{l s='چنانچه به بانک متصل نشدید روی دکمه پرداخت کلیک کنید' mod='bankmellat'}</p>
	<script type="text/javascript">
		setTimeout("document.forms.frmpayment.submit();",10);
	</script>
	<form name="frmpayment" action="{$redirect_link}" method="post">
		<input type="hidden" id="RefId" name="RefId" value="{$ref_id}" />
		<input type="submit" class="button" value="{l s='پرداخت' mod='bankmellat'}" />
	</form>
	<p></p>
{/if}
</div>