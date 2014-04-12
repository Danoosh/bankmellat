<?php 
/*
* 2013 Presta-Shop.ir
*
* Do not edit or remove author copyright
* if you have any problem contact us at presta-shop.ir/forum/Thread-2487.html
*
*  @author Danoosh Miralayi @ Presta-Shop.ir <support@presta-shop.ir>
*  @copyright  2013 Presta-Shop.ir
*  نکته مهم:
*  حذف یا تغییر این اطلاعات به هر شکلی ممنوع بوده و پیگرد قانونی دارد
*/

class BankMellat extends PaymentModule
{  
	private $_html = '';

	private  $_webservice = 'https://pgws.bpm.bankmellat.ir/pgwchannel/services/pgw?wsdl';
	private  $_new_webservice = 'https://pgwsf.bpm.bankmellat.ir:1443/pgwchannel/services/pgw?wsdl';
    private  $_shaparak_webservice = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    private  $_startPay = 'https://pgw.bpm.bankmellat.ir/pgwchannel/startpay.mellat';
    private  $_startPay_shaparak = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';
    private  $_postErrors = array();
	private  $_namespace = 'http://interfaces.core.sw.bps.com/';

	public function __construct(){  
		$this->name = 'bankmellat';  
		$this->tab = 'payments_gateways';
		$this->version = '2.8.2';
		$this->author = 'Danoosh @ Presta-Shop.IR';

		$this->currencies = true;
  		$this->currencies_mode = 'checkbox';

		parent::__construct();  		
		$this->context = Context::getContext();
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Mellat Payment');  
		$this->description = $this->l('A free module to pay online for Mellat.');  
		$this->confirmUninstall = $this->l('Are you sure, you want to delete your details?');
		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');
		$config = Configuration::getMultiple(array('Bank_Mellat_TerminalId', ''));			
		if (!isset($config['Bank_Mellat_TerminalId']))
			$this->warning = $this->l('Your Mellat TerminalId must be configured in order to use this module');
		$config = Configuration::getMultiple(array('Bank_Mellat_UserName', ''));			
		if (!isset($config['Bank_Mellat_UserName']))
			$this->warning = $this->l('Your Mellat username must be configured in order to use this module');

			$config = Configuration::getMultiple(array('Bank_Mellat_UserPassword', ''));			
		if (!isset($config['Bank_Mellat_UserPassword']))
			$this->warning = $this->l('Your Mellat password must be configured in order to use this module');

		if ($_SERVER['SERVER_NAME'] == 'localhost')
			$this->warning = $this->l('Your are in localhost, Mellat Payment can\'t validate order');
        $use_new_webservise = Configuration::get('Bank_Mellat_newWebservice');
        $use_shaparak = Configuration::get('Bank_Mellat_SHAPARAK');
        if($use_shaparak)
        {
            $this->webservice = $this->_shaparak_webservice;
            $this->link = $this->_startPay_shaparak;
        }
        else
        {
            $this->webservice = ($use_new_webservise ? $this->_new_webservice : $this->_webservice);
            $this->link = $this->_startPay;
        }
	}  
	public function install(){
		if (!parent::install()
	    	OR !Configuration::updateValue('Bank_Mellat_TerminalId', '')
	    	OR !Configuration::updateValue('Bank_Mellat_UserName', '')
			OR !Configuration::updateValue('Bank_Mellat_UserPassword', '')
			OR !Configuration::updateValue('Bank_Mellat_BP_Test_Payment', 0)
			OR !Configuration::updateValue('Bank_Mellat_newWebservice', 0)
            OR !Configuration::updateValue('Bank_Mellat_SHAPARAK', 1)
            OR !Configuration::updateValue('Bank_Mellat_phpDisplayErrors', 0)
	      	OR !$this->registerHook('payment')
	      	OR !$this->registerHook('paymentReturn')){
			    return false;
		}else{
		    return true;
		}
	}
	public function uninstall(){
		if (!Configuration::deleteByName('Bank_Mellat_TerminalId') 
			OR !Configuration::deleteByName('Bank_Mellat_UserName') 
			OR !Configuration::deleteByName('Bank_Mellat_UserPassword')
			OR !Configuration::deleteByName('Bank_Mellat_newWebservice')
            OR !Configuration::deleteByName('Bank_Mellat_SHAPARAK')
            OR !Configuration::deleteByName('Bank_Mellat_BP_Test_Payment')
			OR !Configuration::deleteByName('Bank_Mellat_phpDisplayErrors')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function displayFormSettings()
	{
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('Settings').'</legend>
				<label>'.$this->l('terminalId').'</label>
				<div class="margin-form"><input type="text" size="30" name="terminalId" value="'.Configuration::get('Bank_Mellat_TerminalId').'" /></div>
				<label>'.$this->l('userName').'</label>
				<div class="margin-form"><input type="text" size="30" name="userName" value="'.Configuration::get('Bank_Mellat_UserName').'" /></div>
				<label>'.$this->l('userPassword').'</label>
				<div class="margin-form"><input type="password" size="30" name="userPassword" value="'.Configuration::get('Bank_Mellat_UserPassword').'" /></div>

				<label>'.$this->l('وب سرویس شاپرک').'</label>
				<div class="margin-form"><input type="radio" value="1" name="shaparak" '.(Configuration::get('Bank_Mellat_SHAPARAK')=='1' ? "checked" : "").' /> <span>'.$this->l('Yes').'</span>
				<input type="radio" value="0" name="shaparak" '.(Configuration::get('Bank_Mellat_SHAPARAK')=='0' ? "checked" : "").' /> <span>'.$this->l('No').'</span><p class="span" name="help_box">از دی ماه 1392 بانک های کشور از سیستم شاپرک استفاده می کنند. در صورت نیاز و یا تغییر در آینده می توانید با غیرفعال سازی شاپرک از وب سرویس های قدیمی استفاده کنید.</p></div>

				<div style=" '.(Configuration::get('Bank_Mellat_SHAPARAK')=='0' ? "" : "display:none;").' "><label>'.$this->l('وب سرویس').'</label>
				<div class="margin-form"><input type="radio" value="1" name="newWebservice" '.(Configuration::get('Bank_Mellat_newWebservice')=='1' ? "checked" : "").' /> <span>'.$this->l('دوم').'</span>
				<input type="radio" value="0" name="newWebservice" '.(Configuration::get('Bank_Mellat_newWebservice')=='0' ? "checked" : "").' /> <span>'.$this->l('اول').'</span><span class="hint" name="help_box">این وب سرویس مناسب هاست های خارج کشور است. فقط در صورتی که در اتصال به بانک مشکل دارید فعال کنید. قبل از فعال سازی از تست پایین صفحه استفاده کنید.</span></div></div>
				<label>'.$this->l('تست متد reversal').'</label>
				<div class="margin-form"><input value="1" type="radio" name="bpTestPayment" '.(Configuration::get('Bank_Mellat_BP_Test_Payment')=='1' ? "checked" : "").' /> <span>'.$this->l('Yes').'</span>
				<input type="radio" value="0" name="bpTestPayment" '.(Configuration::get('Bank_Mellat_BP_Test_Payment')=='0' ? "checked" : "").' /> <span>'.$this->l('No').'</span><span class="hint" name="help_box">جهت تست تابع Reversal در هنگام پرداخت. این گزینه فقط به درخواست شرکت به پرداخت باید فعال شود.</span></div>
				<label>'.$this->l('خطایابی PHP').'</label>
				<div class="margin-form"><input type="radio" value="1" name="phpDisplayErrors" '.(Configuration::get('Bank_Mellat_phpDisplayErrors')=='1' ? "checked" : "").' /> <span>'.$this->l('Yes').'</span>
				<input type="radio" value="0" name="phpDisplayErrors" '.(Configuration::get('Bank_Mellat_phpDisplayErrors')=='0' ? "checked" : "").' /> <span>'.$this->l('No').'</span><span class="hint" name="help_box">جهت کشف خطاهای سرور و یا پرستاشاپ مناسب است. فقط در صورتی که در اتصال به بانک مشکل دارید فعال کنید. فراموش نکنید بعد از رفع مشکل آن را غیرفعال کنید.</span></div>
				<center><input type="submit" name="submitMellat" value="'.$this->l('Update Settings').'" class="button" /></center>			
			</fieldset>
		</form>';
	}

	public function checkWebservices()
	{
		$this->_html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><br />
		<fieldset>		
		<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('بررسی وب سرویس').'</legend>
		<p>نکته: این تست اعتبار کامل ندارد و ممکن است در عمل نتیجه متفاوتی بگیرید.</p><p>';
		if (Tools::getValue('submitCheck'))
		{
			$connection = @fsockopen('pgws.bpm.bankmellat.ir', '443');
			if (is_resource($connection))
				$this->_html .= 'وب سرویس قدیمی: بله</p><p>';
			else $this->_html .= 'وب سرویس قدیمی: خیر</p><p>';
			$connection = @fsockopen('pgwsf.bpm.bankmellat.ir', '1443');
			if (is_resource($connection))
				$this->_html .= 'وب سرویس جدید: بله</p>';
			else $this->_html .= 'وب سرویس جدید: خیر</p>';
		}

		$this->_html .= '

		<center><input type="submit" name="submitCheck" value="'.$this->l('بررسی امکان اتصال به وب سرویس').'" class="button" />
		<p style="text-align:center;">این عمل ممکن است مدتی طول بکشد. شکیبا باشید.</p></center>
		</fieldset></form>
		<p></p>
		<fieldset>		
		<legend>اطلاعات</legend>
		<p><a href="http://presta-shop.ir/forum/Thread-2487.html"> + پشتیبانی در انجمن</a></p>
		<p> + کپی رایت : <a href="http://presta-shop.ir">پرستاشاپ پارسی</a></p>
		<p> + نویسنده: دانوش میرعلایی مطلق</p>
		</fieldset>
		';
	}
	public function displayConf()
	{

		$this->_html .= '<div class="conf confirm"> '.$this->l('Settings updated').'</div>';
	}

	public function displayErrors()
	{
		foreach ($this->_postErrors AS $err)
		$this->_html .= '<div class="alert error">'. $err .'</div>';
	}

    public function getContent()
	{
		$this->_html = '<h2>'.$this->l('Mellat Payment').'</h2>';
		if (isset($_POST['submitMellat']))
		{
				if (empty($_POST['terminalId']))
				$this->_postErrors[] = $this->l('Mellat TerminalId is required.');

			if (empty($_POST['userName']))
				$this->_postErrors[] = $this->l('Your username is required.');

			if (empty($_POST['userPassword']))
				$this->_postErrors[] = $this->l('Your password is required.');
			if (!sizeof($this->_postErrors))
			{

				Configuration::updateValue('Bank_Mellat_TerminalId', $_POST['terminalId']);
				Configuration::updateValue('Bank_Mellat_UserName', $_POST['userName']);
				Configuration::updateValue('Bank_Mellat_UserPassword', $_POST['userPassword']);
				Configuration::updateValue('Bank_Mellat_newWebservice', $_POST['newWebservice']);
                Configuration::updateValue('Bank_Mellat_SHAPARAK', $_POST['shaparak']);
                Configuration::updateValue('Bank_Mellat_BP_Test_Payment', $_POST['bpTestPayment']);
				Configuration::updateValue('Bank_Mellat_phpDisplayErrors', $_POST['phpDisplayErrors']);
				$this->displayConf();
			}
			else
				$this->displayErrors();
		}
		$this->displayFormSettings();
		//$this->checkWebservices();
		return $this->_html;
	}
	public function prePayment()
	{
		include_once('lib/nusoap.php');

		$soapclient = new nusoap_client($this->webservice,'wsdl');

		if (!$err = $soapclient->getError())
			$soapProxy = $soapclient->getProxy() ;
		if ( (!$soapclient) OR $err ) {
				$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			   	return $this->_postErrors;
  		}
		else
		{			
			$purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
			$current_currency = new Currency($this->context->cookie->id_currency);			
			if($current_currency->id == $purchase_currency->id)
				$PurchaseAmount= number_format($this->context->cart->getOrderTotal(true, 3), 0, '', '');		 
			else
				$PurchaseAmount= number_format($this->convertPriceFull($this->context->cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');

			$additionalData = "Cart Number: ".$this->context->cart->id." Customer ID: ".$this->context->cart->id_customer;
		    $params = array(
						'terminalId' =>  Configuration::get('Bank_Mellat_TerminalId'),
						'userName' => Configuration::get('Bank_Mellat_UserName'),
						'userPassword' => Configuration::get('Bank_Mellat_UserPassword'),
						'orderId' => ($this->context->cart->id).date('YmdHis'),
		                'amount' => (int)$PurchaseAmount,
						'callBackUrl' => $this->context->link->getModuleLink('bankmellat', 'validation'),
						'localDate' => date('Ymd'),
						'localTime' => date("His"),
						'additionalData' => $additionalData,
						'payerId' => 0
		              );

			$res = $soapclient->call('bpPayRequest', $params, $this->_namespace);

			if ($soapclient->fault OR $err = $soapclient->getError())
			{
				$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			   	$this->displayErrors();
				return $this->_postErrors;
			} 
			else
			{
				// Display the result
				if (is_array($res))
					$ress = explode (',',$res['return']);
				else
					$ress = explode (',',$res);
				$ResCode = $ress[0];
				$RefId     = $ress[1];
				if ($ResCode == "0")
				{
					$this->context->cookie->__set("RefId", $RefId);
					$this->context->cookie->__set("amount", (int)$PurchaseAmount);

					$this->context->smarty->assign(array(
						'redirect_link' => $this->link,
						'ref_id' => $RefId
					));
					return true;
				} 
				else {
					$this->showMessages($ResCode);
					return $this->_postErrors;
				}

			}

        }

	}

	public function verify($saleOrderId,$saleReferenceId,$soapclient = NULL)
	{
		if(!$soapclient)
		{	
			include_once('lib/nusoap.php');
			$soapclient = new nusoap_client($this->webservice,'wsdl');
		}

		if (!$soapclient)
		{
			$this->_postErrors[] = $this->l('اتصال به بانک برقرار نشد');
			// if(!empty($err))
				// $this->_postErrors[] = $err;
			return $this->_postErrors;
			// return $return;
		}

		// Params For Verify
		$params = array(
			'terminalId' =>  Configuration::get('Bank_Mellat_TerminalId'),
			'userName' => Configuration::get('Bank_Mellat_UserName'),
			'userPassword' => Configuration::get('Bank_Mellat_UserPassword'),
			'orderId' => ($this->context->cart->id).date('YmdHis'),
			'saleOrderId' => $saleOrderId,
			'saleReferenceId' => $saleReferenceId
		);

		$result = $soapclient->call('bpVerifyRequest', $params, $this->_namespace);

		if ($soapclient->fault OR $err = $soapclient->getError())
		{
			$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			return $this->_postErrors;
		} 
		if ($result['return'] != "0"){
			$this->showMessages($result['return']);
			return $this->_postErrors;
		}
		return true;
	}

	public function settle($saleOrderId,$saleReferenceId, $soapclient = NULL)
	{
		if(!$soapclient)
		{	
			include_once('lib/nusoap.php');
			$soapclient = new nusoap_client($this->webservice,'wsdl');
		}

		if (!$soapclient)
		{
			$this->_postErrors[] = $this->l('اتصال به بانک برقرار نشد');
			// if(!empty($err))
				// $this->_postErrors[] = $err;
			return $this->_postErrors;
			// return $return;
		}

		//Params for settle
		$params = array(
			'terminalId' =>  Configuration::get('Bank_Mellat_TerminalId'),
			'userName' => Configuration::get('Bank_Mellat_UserName'),
			'userPassword' => Configuration::get('Bank_Mellat_UserPassword'),
			'orderId' => ($this->context->cart->id).date('YmdHis'),
			'saleOrderId' => $saleOrderId,
			'saleReferenceId' => $saleReferenceId
		);

		$result = $soapclient->call('bpSettleRequest', $params, $this->_namespace);
		if ($soapclient->fault OR $err = $soapclient->getError())
		{
			$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			return $this->_postErrors;
		} 


		if ($result['return'] != "0"){
			$this->showMessages($result['return']);
			return $this->_postErrors;
			//return $return;
		}
		return true;
	}

	public function inquiry($saleOrderId,$saleReferenceId, $soapclient =NULL)
	{
		if(!$soapclient)
		{	include_once('lib/nusoap.php');
			$soapclient = new nusoap_client($this->webservice,'wsdl');
		}

		if (!$soapclient)
		{
			$this->_postErrors[] = $this->l('اتصال به بانک برقرار نشد');
			// if(!empty($err))
				// $this->_postErrors[] = $err;
			return $this->_postErrors;
			// return $return;
		}

		//Params for inquiry
		$params = array(
			'terminalId' =>  Configuration::get('Bank_Mellat_TerminalId'),
			'userName' => Configuration::get('Bank_Mellat_UserName'),
			'userPassword' => Configuration::get('Bank_Mellat_UserPassword'),
			'orderId' => ($this->context->cart->id).date('YmdHis'),
			'saleOrderId' => $saleOrderId,
			'saleReferenceId' => $saleReferenceId
		);

		$result = $soapclient->call('bpInquiryRequest', $params, $this->_namespace);
		if ($soapclient->fault OR $err = $soapclient->getError())
		{
			$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			return $this->_postErrors;
		} 

		if ($result['return'] != "0"){
			$this->showMessages($result['return']);
			return $this->_postErrors;
		}
		return true;
	}

	public function reverse($saleOrderId,$saleReferenceId, $soapclient = NULL)
	{
		if(!$soapclient)
		{	include_once('lib/nusoap.php');
			$soapclient = new nusoap_client($this->webservice,'wsdl');
		}

		if (!$soapclient)
		{
			$this->_postErrors[] = $this->l('اتصال به بانک برقرار نشد');
			// if(!empty($err))
				// $this->_postErrors[] = $err;
			return $this->_postErrors;
			// return $return;
		}

		//Params for reversal
		$params = array(
			'terminalId' =>  Configuration::get('Bank_Mellat_TerminalId'),
			'userName' => Configuration::get('Bank_Mellat_UserName'),
			'userPassword' => Configuration::get('Bank_Mellat_UserPassword'),
			'orderId' => ($this->context->cart->id).date('YmdHis'),
			'saleOrderId' => $saleOrderId,
			'saleReferenceId' => $saleReferenceId
		);

		$result = $soapclient->call('bpReversalRequest', $params, $this->_namespace);
		if ($soapclient->fault OR $err = $soapclient->getError())
		{
			$this->_postErrors[] = $this->l('Could not connect to bank or service.');
			return $this->_postErrors;
		} 

		if ($result['return'] != "0"){
			$this->showMessages($result['return']);
			return $this->_postErrors;
		}
		return true;
	}

	public function showMessages($result)
	{                
		switch($result)
		{ 
			case 0:  $this->_postErrors[]=$this->l('تراکنش با موفقیت انحام شد'); break;
			case 11: $this->_postErrors[]=$this->l('شماره کارت نامعتبر است'); break;
			case 12: $this->_postErrors[]=$this->l('موجودی کافی نیست'); break;
			case 13: $this->_postErrors[]=$this->l('رمز نادرست است'); break;  
			case 14: $this->_postErrors[]=$this->l('تعداد دفعات وارد کردن رمز بیش از حد مجاز است'); break;    
			case 15: $this->_postErrors[]=$this->l('کارت نامعتبر است'); break;
			case 16: $this->_postErrors[]=$this->l('دفعات برداشت وجه بیش از حد مجاز است'); break;
			case 17: $this->_postErrors[]=$this->l('کاربر از انجام تراکنش منصرف شده است'); break;
			case 18: $this->_postErrors[]=$this->l('تاریخ انقضای کارت گذشته است'); break;
			case 19: $this->_postErrors[]=$this->l('مبلغ برداشت وجه بیش از حد مجاز است'); break;
			case 111: $this->_postErrors[]=$this->l('صادر کننده کارت نامعتبر است'); break;
			case 112: $this->_postErrors[]=$this->l('خطای سوییچ صادر کننده کارت'); break;
			case 113: $this->_postErrors[]=$this->l('پاسخی از صادر کننده کارت دریافت نشد'); break;
			case 114: $this->_postErrors[]=$this->l('دارنده کارت مجاز به انجام این تراکنش نیست'); break;
			case 21: $this->_postErrors[]=$this->l('پذیرنده نامعتبر است'); break;
			case 23: $this->_postErrors[]=$this->l('خطای امنیتی رخ داده است'); break;
			case 24: $this->_postErrors[]=$this->l('اطلاعات کاربری پذیرنده نامعتبر است'); break;
			case 25: $this->_postErrors[]=$this->l('مبلغ نامعتبر است'); break;
			case 31: $this->_postErrors[]=$this->l('پاسخ نامعتبر است'); break;
			case 32: $this->_postErrors[]=$this->l('فرمت اطلاعات وارد شده صحیح نمی باشد'); break;
			case 33: $this->_postErrors[]=$this->l('حساب نامعتبر است'); break;
			case 34: $this->_postErrors[]=$this->l('خطای سیستمی'); break;
			case 35: $this->_postErrors[]=$this->l('تاریخ نامعتبر است'); break;
			case 41: $this->_postErrors[]=$this->l('شماره درخواست تکراری است'); break;
			case 42: $this->_postErrors[]=$this->l('تراکنش Sale یافت نشد'); break;
			case 43: $this->_postErrors[]=$this->l('قبلا درخواست Verify داده شده است'); break;
			case 44: $this->_postErrors[]=$this->l('درخواست Verify یافت نشد'); break;
			case 45: $this->_postErrors[]=$this->l('تراکنش Settle شده است'); break;
			case 46: $this->_postErrors[]=$this->l('تراکنش Settle نشده است'); break;
			case 47: $this->_postErrors[]=$this->l('تراکنش Settle یافت نشد'); break;
			case 48: $this->_postErrors[]=$this->l('تراکنش Reverse شده است'); break;
			case 49: $this->_postErrors[]=$this->l('تراکنش Refund یافت شند'); break;
			case 412: $this->_postErrors[]=$this->l('شناسه قبض نادرست است'); break;
			case 413: $this->_postErrors[]=$this->l('شناسه پرداخت نادرست است'); break;
			case 414: $this->_postErrors[]=$this->l('سازمان صادر کننده قبض نامعتبر است'); break;
			case 415: $this->_postErrors[]=$this->l('زمان جلسه کاری به پایان رسیده است'); break;
			case 416: $this->_postErrors[]=$this->l('خطا در ثبت اطلاعات'); break;
			case 417: $this->_postErrors[]=$this->l('شناسه پرداخت کننده نامعتبر است'); break;
			case 418: $this->_postErrors[]=$this->l('اشکال در تعریف اطلاعات مشتری'); break;
			case 419: $this->_postErrors[]=$this->l('تعداد دفعات ورود اطلاعات از حد مجاز گذشته است'); break;
			case 421: $this->_postErrors[]=$this->l('IP نامعتبر است'); break;
			case 51: $this->_postErrors[]=$this->l('تراکنش تکراری است'); break;
			case 54: $this->_postErrors[]=$this->l('تراکنش مرجع موجود نیست'); break;
			case 55: $this->_postErrors[]=$this->l('تراکنش نامعتبر است'); break;
			case 61: $this->_postErrors[]=$this->l('خطا در واریز'); break;
			}
		return $this->_postErrors;
	}

	// to show only one error
	public function showErrorMessages($result)
	{
		$Message = $this->showMessages($result);
		$this->_html = '';
		$this->_postErrors = array();
		return $Message;
	}

	public function hookPayment($params){
		if (!$this->active)
			return ;
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn($params)
	{
		return ;
	}

	/**
	 *
	 * @return float converted amount from a currency to an other currency
	 * @param float $amount
	 * @param Currency $currency_from if null we used the default currency
	 * @param Currency $currency_to if null we used the default currency
	 */
	public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null)
	{
		if ($currency_from === $currency_to)
			return $amount;
		if ($currency_from === null)
			$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_to === null)
			$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
			$amount *= $currency_to->conversion_rate;
		else
		{
            $conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
			// Convert amount to default currency (using the old currency rate)
			$amount = Tools::ps_round($amount / $conversion_rate, 2);
			// Convert to new currency
			$amount *= $currency_to->conversion_rate;
		}
		return Tools::ps_round($amount, 2);
	}
}