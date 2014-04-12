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
{if isset($access) && $access=='denied'}
	<br />
{else}
	<br />
	{if isset($paid) && $paid}
		<p class="success">{l s='سفارش شما با موفقیت ثبت شد' mod='bankmellat'}</p>
	{elseif !empty($reversed) && $reversed===true}
		<br />
		<p class="warning">{l s='مبلغ به حساب شما بازگشت داده شد.' mod='bankmellat'}</p>
	{/if}
	{if isset($sale_order_id)}
		<p class="required">{l s='اطلاعات پرداخت در زیر آمده است. چنانچه مایلید می توانید به جهت پیگیری آن ها را یادداشت نمایید.' mod='bankmellat'}</p>
		<br /><p><strong style="color:green;">{if isset($paid) && $paid}{l s='شناسه سفارش در فروشگاه:' mod='bankmellat'} {$order_reference}</strong><br />{/if}
		{l s='شناسه پرداخت:' mod='bankmellat'} {$sale_order_id}<br />
		{l s='کد مرجع پرداخت:' mod='bankmellat'} {$sale_refference_id}
		</p><br />
	{/if}
	{* start details *}
	<p class=""><b>{l s='جزئیات فرآیند پرداخت:' mod='bankmellat'}</b>
	{if !empty($verified) && $verified === true}
		<br />
		» {l s='مبلغ از حساب شما کسر شد.' mod='bankmellat'}
	{else}
		<br />
		» {l s='مبلغ از حساب شما کسر نشد.' mod='bankmellat'}
	{/if}
	{if !empty($settle) && $settle === true}
		<br />
		» {l s='مبلغ به حساب فروشنده واریز شده است.' mod='bankmellat'}
	{else}
		<br />
		» {l s='واریز به حساب فروشنده با مشکل مواجه شد.' mod='bankmellat'}
	{/if}
	{if !empty($reversed) && $reversed===true}
		<br />
		» {l s='مبلغ به حساب شما بازگشت داده شد.' mod='bankmellat'}
	{/if}
	{if isset($paid) && $paid}
		<br />
		» {l s='سفارش شما با موفقیت ثبت شده است.' mod='bankmellat'}
	{/if}
	{if isset($errors) && $errors}
		<br />
		» {l s='خطایی روی داده است. برای اطمینان با خدمات مشتریان تماس بگیرید.' mod='bankmellat'}
	{/if}
	</p>
	{* end details*}
	<br />
	<p class="bold"><a href="{$link->getPageLink('history', true)}">» {l s='نمایش سفارش های من' mod='bankmellat'}</a></p>
	<p>{l s='در صورتی که هرگونه سوال، نظر یا مشکلی دارید با بخش' mod='bankmellat'} <a href="{$link->getPageLink('contact', true)}"><strong>{l s='تیم پشتیبانی مشتریان تماس بگیرید' mod='bankmellat'}</strong></a>.
	</p>
{/if}
<p style="float:left; font-size:9px;color:#c4c4c4">bankmellat ver <a href="http://presta-shop.ir/" style="color:#c4c4c4">{$ver}</a></p>
</div>