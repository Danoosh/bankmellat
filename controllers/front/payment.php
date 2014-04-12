<?php
/*
* 2013 Presta-Shop.ir
*
*
*  @author Presta-Shop.ir - Danoosh Miralayi
*  @copyright  2013 Presta-Shop.ir
*/
class BankMellatPaymentModuleFrontController extends ModuleFrontController
{
	
	public function __construct()
	{
		//$this->auth = true;
		parent::__construct();

		$this->context = Context::getContext();
		$this->ssl = true;
	}
	
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		//$this->display_column_left = false;
		parent::initContent();
		$this->assignTpl();
	}
	
	public function postProcess()
	{
		$displayErrors = Configuration::get('Bank_Mellat_phpDisplayErrors');
		if($displayErrors)
			@ini_set('display_errors', 'on');
	} 
	
	
	
	public function assignTpl()
	{
		$return = $this->module->prePayment();
		if($return === true)
			$this->context->smarty->assign('prepay', 'true');
		else $this->errors = $return;
		return $this->setTemplate('payment.tpl');
	}
	
}