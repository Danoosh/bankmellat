<!-- Mellat Payment Module -->
<form action="modules/bankmellat/payment.php" method="post" id="bankmellat_form" class="hidden">
    <input type="hidden" name="orderId" value="{$orderId}" />
</form>
<p class="payment_module">
    <a href="javascript:$('#bankmellat_form').submit();" title="{l s='Pay by Mellat' mod='bankmellat'}">
        <img src="modules/bankmellat/mellat.png" alt="{l s='Pay by Mellat' mod='bankmellat'}" />
		{l s='Pay by Debit/Credit card through Mellat Online Merchent.' mod='bankmellat'}
</a></p>
<!-- End of Mellat Payment Module-->