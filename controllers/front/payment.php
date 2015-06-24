<?php

class TwistopaymentPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left;

	public function __construct()
	{
		parent::__construct();
		$this->display_column_left = false;
	}


	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;
		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
			'public_key' => $this->module->TWISTO_PUBLIC_KEY,
			'payload' => $this->module->getPayload(),
			'fee' => $this->fee_customer,
		));

		$this->setTemplate('payment_execution.tpl');
	}
}