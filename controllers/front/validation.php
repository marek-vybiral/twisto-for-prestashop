<?php

class TwistopaymentValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$transaction_id = $_GET['transaction_id'];
		try
		{
			$invoice = Twisto\Invoice::create($this->module->twisto, $transaction_id);
		}
		catch (Twisto\Error $e)
		{
			echo "<h1>Došlo k chybě při vytváření faktury Twisto</h1>\n";
			echo "<h2>Zkuste to prosím znovu nebo zvolte jinou platební metodu</h2>\n";
			echo "<a href='/index.php?controller=order?step=3'>Zpět na výběr platební metody</a>";
			die();
		}


		$cart = $this->context->cart;
		$customer = new Customer($cart->id_customer);
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$currency = $this->context->currency;

		$this->module->validateOrder(
			$cart->id,
			Configuration::get('TWISTO_INVOICE_CREATED_OS'),
			$total,
			'Twisto',
			NULL,
			NULL,
			(int)$currency->id,
			false,
			$customer->secure_key
		);

		$order = new Order($this->module->currentOrder);
		$db = Db::getInstance();
		$db->update('orders', array('twisto_id' => $invoice->invoice_id), "reference = '".$order->reference."'");

		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}