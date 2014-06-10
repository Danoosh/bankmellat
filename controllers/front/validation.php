<?php
/*
* 2013 Presta-Shop.ir
*
*
*  @author Presta-Shop.ir - Danoosh Miralayi
*  @copyright  2013 Presta-Shop.ir
*/
class BankMellatValidationModuleFrontController extends ModuleFrontController
{
	private  $_webservice = 'https://pgws.bpm.bankmellat.ir/pgwchannel/services/pgw?wsdl';
	private  $_new_webservice = 'https://pgwsf.bpm.bankmellat.ir:1443/pgwchannel/services/pgw?wsdl';
	private  $_shaparak_webservice = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
	
	public function __construct()
	{
		//$this->auth = true;
		parent::__construct();

		$this->context = Context::getContext();
		$this->ssl = true;
		$use_new_webservise = Configuration::get('Bank_Mellat_newWebservice');
		$use_shaparak = Configuration::get('Bank_Mellat_SHAPARAK');
		if($use_shaparak)
			$this->webservice = $this->_shaparak_webservice;
		else
			$this->webservice = ($use_new_webservise ? $this->_new_webservice : $this->_webservice);

	}
	
	public function postProcess()
	{
		$displayErrors = Configuration::get('Bank_Mellat_phpDisplayErrors');
		if($displayErrors)
			@ini_set('display_errors', 'on');
		$this->RefId = Tools::getValue('RefId');
		$this->ResCode = Tools::getValue('ResCode');
		$this->saleOrderId = Tools::getValue('SaleOrderId');
		$this->saleReferenceId = Tools::getValue('SaleReferenceId');
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();
		
		if (!empty($this->ResCode) && $this->ResCode != "0")
		{
			$this->errors = $this->module->showMessages($this->ResCode);
			
		}
		elseif(empty($this->saleOrderId) || empty($this->saleReferenceId) || empty($this->RefId))
			$this->errors[] = $this->module->l('اطلاعات پرداخت صحیح نیست.');
		elseif(empty($this->context->cart->id))
			$this->errors[] = $this->module->l('سبد خرید شما خالی است.');
		if(!count($this->errors))
		{
			$this->validate = $this->validate();
			$OrderAmount = $this->context->cart->getOrderTotal(true, 3);
			$amount = (int)$this->context->cookie->__get('amount');
			$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
			if($this->context->currency->id != $purchase_currency->id)
				$amount = number_format($this->module->convertPriceFull($amount, $purchase_currency, $this->context->currency), 0, '', '');
			$message = $this->module->l('شناسه تراکنش:').' '.$this->saleOrderId.' '.$this->module->l('کد مرجع بانک:').' '.$this->saleReferenceId;
			if($amount != (int)$OrderAmount)
			{
				$OrderAmount = (int)$amount;
				$message .= ' <br />'.$this->module->l('هشدار: پرداخت مشکوک به تقلب است');
			}
			if(empty($this->RefId) || $this->context->cookie->__get('RefId') != $this->RefId)
			{
				$message .= ' <br />'.$this->module->l('هشدار: پرداخت مشکوک به تقلب است');
			}
			if($this->validate === true)
				$this->paid = $this->module->validateOrder((int)$this->context->cart->id, _PS_OS_PAYMENT_, (int)$OrderAmount, $this->module->displayName, $message , array(),(int)$this->context->currency->id, false, $this->context->customer->secure_key);
			if(!isset($this->paid) || !$this->paid)
				$this->errors[] = $this->module->l('خطایی در ثبت سفارش روی داد.');
			$this->context->cookie->__unset("RefId");
			$this->context->cookie->__unset("amount");
		}
		$this->assignTpl();
	}
	
	public function validate()
	{
		include_once($this->module->getLocalPath().'lib/nusoap.php');
		$webservice = $this->webservice;
		$this->soapclient = new nusoap_client($webservice,'wsdl');
		
		if ( (!$this->soapclient) OR ($err = $this->soapclient->getError()) )
		{
			$this->errors[] = $this->l('اتصال به بانک برقرار نشد');
			 if(!empty($err))
				 $this->_postErrors[] = $err;
			return false;
		}
		
		$this->verify = $this->module->verify($this->saleOrderId,$this->saleReferenceId,$this->soapclient);
		if($this->verify !== true)
		{
			foreach($this->verify as $err)
				$this->errors[] = $err;
			$this->inquiry = $this->module->inquiry($this->saleOrderId,$this->saleReferenceId,$this->soapclient);
		}
		$bpTestPayment = Configuration::get('Bank_Mellat_BP_Test_Payment');
		if(empty($bpTestPayment) && ($this->verify === true || $this->inquiry === true))
		{	
			$this->errors = array();
			$settle = $this->module->settle($this->saleOrderId,$this->saleReferenceId,$this->soapclient);
			if($settle === true)
				return true;
			else foreach($settle as $err)
				$this->errors[] = $err;
		}
		elseif(isset($this->inquiry))
		{
			$this->errors = array();
			foreach($this->inquiry as $err)
				$this->errors[] = $err;
		}
		$this->reverse = $this->module->reverse($this->saleOrderId,$this->saleReferenceId,$this->soapclient);
		if($this->reverse !==true)
		{
			$this->errors = array();
			foreach($this->reverse as $err)
				$this->errors[] = $err;
		}
		return false;
	}
	
	
	
	
	public function assignTpl()
	{
		if(!isset($this->validate))
			$this->context->smarty->assign(array(
				'access' => 'denied',
				'ver' => $this->module->version
			));
		else
			$this->context->smarty->assign(array(
				'sale_order_id' => $this->saleOrderId,
				'sale_refference_id' => $this->saleReferenceId,
				'verified' => isset($this->inquiry) ? $this->inquiry : $this->verify,
				'settle' => isset($this->validate) ? $this->validate : false,
				'paid' => isset($this->paid) ? $this->paid : false,
				'reversed' => isset($this->reverse) ? $this->reverse : false,
				'order_reference' => $this->module->currentOrderReference,
				'ver' => $this->module->version
			));
		return $this->setTemplate('validation.tpl');
	}
	
}
