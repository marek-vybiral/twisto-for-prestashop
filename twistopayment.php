<?php

if (!defined('_PS_VERSION_'))
	exit;

require_once __DIR__.'vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

class TwistoPayment extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	public $TWISTO_PUBLIC_KEY;
	public $TWISTO_SECRET_KEY;

	public $twisto;

	public function __construct()
	{
		$this->name = 'twistopayment';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'Twisto';
		$this->is_eu_compatible = 1;
		$this->bootstrap = true;
		$this->displayName = 'Twisto platby';
		$this->description = 'Modul umožnující platby přes Twisto.';
		$this->confirmUninstall = 'Jste si jisti, že chcete odstranit nastavení tohoto modulu?';
		if (!isset($this->TWISTO_PUBLIC_KEY) || !isset($this->TWISTO_SECRET_KEY))
			$this->warning = 'Veřejný a tajný klíč musí být nastaven.';

		$config = Configuration::getMultiple([
			'TWISTO_PUBLIC_KEY',
			'TWISTO_SECRET_KEY',
			'TWISTO_FEE_CUSTOMER',
			'TWISTO_VAT',
			'TWISTO_PAYMENT_METHOD_NAME'
		]);

		if (!empty($config['TWISTO_PUBLIC_KEY']))
			$this->TWISTO_PUBLIC_KEY = $config['TWISTO_PUBLIC_KEY'];
		if (!empty($config['TWISTO_SECRET_KEY']))
			$this->TWISTO_SECRET_KEY = $config['TWISTO_SECRET_KEY'];
		if (!empty($config['TWISTO_VAT']))
			$this->twisto_vat = $config['TWISTO_VAT'];
		if (!empty($config['TWISTO_PAYMENT_METHOD_NAME']))
			$this->payment_method_name = $config['TWISTO_PAYMENT_METHOD_NAME'];
		if (isset($config['TWISTO_FEE_CUSTOMER']))
			$this->fee_customer = $config['TWISTO_FEE_CUSTOMER'];
		
		parent::__construct();

		$this->twisto = new Twisto\Twisto();
		$this->twisto->setPublicKey($this->TWISTO_PUBLIC_KEY);
		$this->twisto->setSecretKey($this->TWISTO_SECRET_KEY);
	}

	public function install()
	{
		$missing_libs = [];
		if (!function_exists('curl_version'))
			$missing_libs[] = 'cURL';
		if (!function_exists('gzcompress'))
			$missing_libs[] = 'zlib';
		if (!function_exists("mcrypt_encrypt"))
			$missing_libs[] = 'MCrypt';

		if (count($missing_libs) > 0)
		{
			$this->_errors[] = 'Některé knihovny vyžadovné pro instalaci toho modulu nebyly nalezeny: ' . implode(', ', $missing_libs);
			return false;
		}

		if  (!parent::install() ||
				!$this->registerHook('header') ||
				!$this->registerHook('payment') ||
				!$this->registerHook('displayPaymentEU') ||
				!$this->registerHook('displayPaymentReturn') ||
				!$this->registerHook('actionOrderStatusUpdate') ||
				!$this->registerHook('displayPDFInvoice') ||
				!$this->_createAuthorizationOrderStates()
			)
		{
			return false;
		}

		Configuration::updateValue('TWISTO_FEE_CUSTOMER', 39.0);
		Configuration::updateValue('TWISTO_VAT', 21);
		Configuration::updateValue('TWISTO_PAYMENT_METHOD_NAME', 'Okamžitý nákup s platbou později');

		$db = Db::getInstance();
		$db->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'orders ADD `twisto_id` INT');

		return true;
	}

	private function _createAuthorizationOrderStates()
	{
		return ($this->_createAuthorizationOrderState('TWISTO_INVOICE_CREATED_OS', 'Twisto - faktura zatím neaktivována', '#2296D9') &&
				$this->_createAuthorizationOrderState('TWISTO_INVOICE_ACTIVATED_OS', 'Twisto - faktura potvrzena', '#32CD32', true) );
	}

	private function _createAuthorizationOrderState($name, $display_name, $color, $invoice = false)
	{
		if (!Configuration::get($name))
		{
			$os = new OrderState();
			$os->name = [];
			foreach (Language::getLanguages(false) as $language)
					$os->name[(int)$language['id_lang']] = $display_name;
			$os->color = $color;
			$os->hidden = false;
			$os->send_email = false;
			$os->delivery = false;
			$os->logable = false;
			$os->invoice = $invoice;
			if ($os->add())
			{
				Configuration::updateValue($name, $os->id);
				copy(dirname(__FILE__).'/os-logo.gif', dirname(__FILE__).'/../../img/os/'.(int)$os->id.'.gif');
			}
			else
				return false;
		}
		return true;
	}

	public function uninstall()
	{
		$db = Db::getInstance();
		$db->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'orders DROP `twisto_id`');

		if (!Configuration::deleteByName('TWISTO_PUBLIC_KEY') ||
			!Configuration::deleteByName('TWISTO_SECRET_KEY') ||
			!Configuration::deleteByName('TWISTO_FEE_CUSTOMER') ||
			!Configuration::deleteByName('TWISTO_VAT') ||
			!Configuration::deleteByName('TWISTO_PAYMENT_METHOD_NAME')
			!parent::uninstall()) 
		{
			return false;
		}
		return true;
	}

	public function hookHeader($params) {
		$this->context->controller->addCSS($this->_path.'views/css/twisto.css', 'all');
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;

		global $cookie;

		$customer = new Customer($cookie->id_customer);
		$cart = new Cart($cookie->id_cart);

		$this->context->smarty->assign(array(
			'public_key' => $this->TWISTO_PUBLIC_KEY,
			'payload' => $this->_composePayload($customer, $cart),
			'payment_method_name' => $this->payment_method_name,
			'fee' => $this->fee_customer,
			'fee_string' => number_format($this->fee_customer, 2, ',', ''),
			'this_path' => $this->getPathUri(),
		));

		return $this->display(__FILE__, $this->_getTemplate('hook', 'payment.tpl'));
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		$customer = new Customer($cookie->id_customer);
		$cart = new Cart($cookie->id_cart);

		$this->context->smarty->assign(array(
			'public_key' => $this->TWISTO_PUBLIC_KEY,
			'payload' => $this->_composePayload($customer, $cart),
			'payment_method_name' => $this->payment_method_name,
			'fee' => $this->fee_customer,
			'fee_string' => number_format($this->fee_customer, 2, ',', ''),
			'this_path' => $this->getPathUri(),
		));

		return $this->display(__FILE__, $this->_getTemplate('front', 'payment_execution.tpl'));
	}

	public function hookDisplayPaymentReturn($params)
	{
		$this->context->smarty->assign(array(
			'this_path' => $this->getPathUri(),
		));

		return $this->display(__FILE__, $this->_getTemplate('front', 'payment_return.tpl'));
	}

	public function hookActionOrderStatusUpdate($params)
	{
		$newOrderStatus = $params['newOrderStatus'];
		if ($newOrderStatus->id == Configuration::get('TWISTO_INVOICE_ACTIVATED_OS'))
		{
			$order = new Order($params['id_order']);
			$db = Db::getInstance();
			$row = $db->getRow('SELECT `twisto_id` FROM ' . _DB_PREFIX_ . "orders WHERE `reference` = '" . $order->reference . "'");
			$invoice_id = $row['twisto_id'];

			$invoice = new Twisto\Invoice($this->twisto, $invoice_id);
			$invoice->activate();

			file_put_contents(dirname(__FILE__).'/invoices/'.$order->reference.'.pdf', fopen($invoice->pdf_url, 'r'));
		}
	}

	public function hookDisplayPDFInvoice($params)
	{
		$invoice = $params['object'];
		$order = new Order($invoice->id_order);
		if($order->module == 'twistopayment')
		{
			header('Location: /modules/twistopayment/invoices/'.$order->reference.'.pdf');
		}
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';

		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => 'Nastavení Vašeho Twisto účtu',
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => 'Veřejný klíč',
						'name' => 'TWISTO_PUBLIC_KEY',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => 'Tajný klíč',
						'name' => 'TWISTO_SECRET_KEY',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => 'Zobrazovaný název platební metody',
						'name' => 'TWISTO_PAYMENT_METHOD_NAME',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => 'Poplatek pro zákazníka včetně DPH (Kč)',
						'name' => 'TWISTO_FEE_CUSTOMER',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => 'Výše DPH z poplatku',
						'name' => 'TWISTO_VAT',
						'required' => true
					),
				),
				'submit' => array(
					'title' => 'Uložit',
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->default_form_language = $lang;
		$helper->allow_employee_form_lang = $lang;
		$this->fields_form = [];
		$helper->show_toolbar = true;
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'TWISTO_PUBLIC_KEY' => Tools::getValue('TWISTO_PUBLIC_KEY', Configuration::get('TWISTO_PUBLIC_KEY')),
			'TWISTO_SECRET_KEY' => Tools::getValue('TWISTO_SECRET_KEY', Configuration::get('TWISTO_SECRET_KEY')),
			'TWISTO_FEE_CUSTOMER' => Tools::getValue('TWISTO_FEE_CUSTOMER', number_format(Configuration::get('TWISTO_FEE_CUSTOMER'), 2, ',', '')),
			'TWISTO_VAT' => Tools::getValue('TWISTO_VAT', Configuration::get('TWISTO_VAT')),
			'TWISTO_PAYMENT_METHOD_NAME' => Tools::getValue('TWISTO_PAYMENT_METHOD_NAME', Configuration::get('TWISTO_PAYMENT_METHOD_NAME')),
		);
	}

	private function _composePayload($presta_customer, $cart)
	{
		$customer = new Twisto\Customer($presta_customer->email, $presta_customer->firstname.' '.$presta_customer->lastname);
		$billing_address = $this->_convertToTwistoAddress(new Address($cart->id_address_invoice));
		$delivery_address = $this->_convertToTwistoAddress(new Address($cart->id_address_delivery));


		$order_items = $this->_convertCartToTwistoItems($cart);

		$order_items[] = new Twisto\Item(
			Twisto\Item::TYPE_PAYMENT,
			'Twisto Faktura – platím až po vyzkoušení',
			'payment',
			1,
			$this->fee_customer,
			$this->twisto_vat
		);

		$carrier = new Carrier($cart->id_carrier);
		$order_items[] = new Twisto\Item(
			Twisto\Item::TYPE_SHIPMENT,
			'Doprava '.$carrier->name,
			'shipment',
			1,
			round($cart->getTotalShippingCost(), 2),
			$carrier->getTaxesRate(new Address($cart->id_address_delivery))
		);

		$order = new Twisto\Order(
			new DateTime(),
			$billing_address,
			$delivery_address,
			$cart->getOrderTotal(true) + $this->fee_customer,
			$order_items
		);

		$previous_orders = $this->_getPreviousOrders($presta_customer);

		return $this->twisto->getCheckPayload($customer, $order, $previous_orders);
	}

	private function _getPreviousOrders($customer)
	{
		$db = Db::getInstance();

		$orders = array();
		if ($results = Db::getInstance()->ExecuteS('SELECT `id_order` FROM ' . _DB_PREFIX_ . "orders WHERE `id_customer` = '" . $customer->id . "'"))
		{
			foreach ($results as $row)
			{
				$order = new Order($row['id_order']);
				$state = new OrderState($order->current_state);
				if($state->paid && $order->module != 'twistopayment')
					$orders[] = $order;
			}
		}

		$twisto_orders = array();
		foreach ($orders as $order)
		{
			$cart = new Cart($order->id_cart);
			$presta_customer = new Customer($order->id_customer);

			$customer = new Twisto\Customer($presta_customer->email, $presta_customer->firstname.' '.$presta_customer->lastname);
			$billing_address = $this->_convertToTwistoAddress(new Address($order->id_address_invoice));
			$delivery_address = $this->_convertToTwistoAddress(new Address($order->id_address_delivery));

			$twisto_items = $this->_convertCartToTwistoItems($cart);
			$totoal_price = 0;
			foreach ($twisto_items as $twisto_item)
			{
				$totoal_price += $twisto_item->price_vat;
			}
		
			$twisto_orders[] = new Twisto\Order(
				new DateTime(),
				$billing_address,
				$delivery_address,
				$totoal_price,
				$twisto_items
			);
		}

		return $twisto_orders;
	}

	private function _convertToTwistoAddress($address)
	{
		if (empty($address->address2))
			$street = $address->address1;
		else
			$street = $address->address1.', '.$address->address2;

		$country = CountryCore::getIsoById($address->id_country);

		if (!empty($address->phone_mobile))
			$phone = $address->phone_mobile;
		elseif (!empty($address->phone))
			$phone = $address->phone;
		else
			$phone = '';

		return new Twisto\Address(
			$address->firstname.' '.$address->lastname,
			$street,
			$address->city,
			preg_replace('/\s+/', '', $address->postcode),
			$country,
			$phone
		);
	}

	private function _convertCartToTwistoItems($cart)
	{
		$twisto_items = [];
		$products_in_cart = $cart->getProducts();
		foreach ($products_in_cart as $product)
		{
			$duplicated = false;
			foreach ($twisto_items as $order_item)
			{
				if ($order_item->product_id == $product['id_product'])
				{
					$duplicated = true;
					$order_item->quantity += $product['quantity'];
					$order_item->price_vat += round($product['quantity'] * $product['price_wt'], 2);
				}
			}

			if (!$duplicated)
			{
				$twisto_items[] =  new Twisto\Item(
					Twisto\Item::TYPE_DEFAULT,
					$product['name'],
					$product['id_product'],
					$product['quantity'],
					round($product['price_wt'] * $product['quantity'], 2),
					$product['rate']
				);
			}
		}

		return $twisto_items;
	}

	private function _getTemplate($area, $file)
	{
		return 'views/templates/'.$area.'/'.$file;
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('TWISTO_PUBLIC_KEY'))
				$this->_postErrors[] = 'Zadejte Váš veřejný klíč.';
			elseif (!Tools::getValue('TWISTO_SECRET_KEY'))
				$this->_postErrors[] = 'Zadejte Váš tajný klíč.';
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$price = (float) str_replace(',', '.', Tools::getValue('TWISTO_FEE_CUSTOMER'));
			Configuration::updateValue('TWISTO_PUBLIC_KEY', Tools::getValue('TWISTO_PUBLIC_KEY'));
			Configuration::updateValue('TWISTO_SECRET_KEY', Tools::getValue('TWISTO_SECRET_KEY'));
			Configuration::updateValue('TWISTO_FEE_CUSTOMER', $price);
			Configuration::updateValue('TWISTO_PAYMENT_METHOD_NAME', Tools::getValue('TWISTO_PAYMENT_METHOD_NAME'));
			Configuration::updateValue('TWISTO_VAT', Tools::getValue('TWISTO_VAT'));
		}
		$this->_html .= $this->displayConfirmation('Nastavení bylo upraveno');
	}
}
