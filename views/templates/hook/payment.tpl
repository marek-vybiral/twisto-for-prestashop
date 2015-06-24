<p class="payment_module" id="twisto">
	<a href="{$link->getModuleLink('twistopayment', 'payment')|escape:'html'}" style="background: url('{$this_path}views/img/twisto-logo.png') 15px center no-repeat;">
		<img src="{$this_path}views/img/twisto-logo.png" alt="Twisto" width="86" height="49"/>
		{$payment_method_name} <span id="twisto-popover-trigger" class="small">Jak to funguje?</span>
		<span class="twisto-opc">
			{if $fee gt 0}<span class="twisto-fee">Poplatek: {$fee_string} Kč</span>{/if}
			<input type="checkbox" id="twisto-terms-checkbox">
			<span class="twisto-terms">Souhlasím s <span class="twisto-terms-href">všeobecnými obchodními podmínkami služby Twisto.cz</span> (platba první objednávky do 14 dní od doručení zboží) a se zpracováním osobních údajů pro účely této služby. Podmínkou služby je věk 18+ a převzetí zboží zákazníkem.</span>
		</span>
	</a>
</p>

{literal}
<script type="text/javascript">
	var _twisto_config = {
		public_key: '{/literal}{$public_key}{literal}',
		script: 'https://static.twisto.cz/api/v2/twisto.js'
	};
	(function(e,g,a){function h(a){return function(){b._.push([a,arguments])}}var f=["check"],b=e||{},c=document.createElement(a);a=document.getElementsByTagName(a)[0];b._=[];for(var d=0;d<f.length;d++)b[f[d]]=h(f[d]);this[g]=b;c.type="text/javascript";c.async=!0;c.src=e.script;a.parentNode.insertBefore(c,a);delete e.script}).call(window,_twisto_config,"Twisto","script");
</script>

<script>
	if(typeof again == 'undefined') {
		var again = true; // hack to prevent multiple executions of this code
		var twistoOpc;
		var totalPriceValue = '';
		var fee = {/literal}{$fee}{literal};

		$(document.body).append('\
			<div id="twisto-modal" style="display: none">\
				<div id="twisto-page-overlay" class="modal-close"></div>\
				<div id="twisto-popover">\
					<img src="{/literal}{$this_path}{literal}views/img/twisto-modal.png" id="modal-img">\
					<div class="bottom">\
						<span class="more" id="more">Více o službě Okamžitý nákup s platbou později</span>\
						<span class="modal-btn modal-close">Pokračovat v nákupu</span>\
					</div>\
					<div class="close-cross modal-close"></div>\
				</div>\
			</div>\
		');

		$('#more').click(function(e) {
			var win = window.open('https://www.twisto.cz/', '_blank');
			win.focus();
		});

		$('.modal-close').click(function(e) {
			$('#twisto-modal').css('display', 'none');
		});

		var updatePaymentMethodsOPC = updatePaymentMethods;
		updatePaymentMethods = function(json) {
			updatePaymentMethodsOPC(json);

			twistoOpc = $('td > label > img[src="/modules/twistopayment/views/img/twisto-logo.png"]').parent().parent().parent();

			twistoOpc.find('.twisto-terms-href').click(function(e) {
				var win = window.open('https://www.twisto.cz/podminky/', '_blank');
				win.focus();
				e.stopPropagation();
			});

			twistoOpc.find('.twisto-terms').click(function(e) {
				var checkbox = twistoOpc.find('#twisto-terms-checkbox');
				checkbox.attr('checked', !checkbox.attr('checked'));
				if(checkbox.attr('checked'))
					$('input[name="id_payment_method"][value="' + twistoOpc.find('input[type="radio"]').prop('value') + '"]').prop('checked', true);
			});

			twistoOpc.find('#twisto-terms-checkbox').click(function(e) {
				if($(this).attr('checked'))
					$('input[name="id_payment_method"][value="' + twistoOpc.find('input[type="radio"]').prop('value') + '"]').prop('checked', true);
			});

			$('#twisto-popover-trigger').click(function(e) {
				$('#twisto-modal').css('display', 'block');
				var w = $('#twisto-popover').width();
				var h = $('#twisto-popover').height();
				$('#twisto-popover').css('margin-left', - w / 2);
				$('#twisto-popover').css('margin-top', - h / 1.7);
			});

			if(totalPriceValue = '') {
				totalPriceValue = $('#total_price').html();
			}
			setInterval(function() {
				if($('#total_price').html() != totalPriceValue && twistoOpc.find('input[type="radio"]').attr('checked') && $('#total_price').attr('data-twisto-fee') < 1) {
					changeTotalPrice(fee);
					$('#total_price').attr('data-twisto-fee', 1);
				}
			}, 100);

			$('#total_price').attr('data-twisto-fee', 0);
			$('#paymentMethodsTable input[type="radio"]').click(function(e) {
				if($('#total_price').attr('data-twisto-fee') > 0 && !twistoOpc.find('input[type="radio"]').attr('checked')) {
					changeTotalPrice(-fee);
					$('#total_price').attr('data-twisto-fee', 0);
				} else if($('#total_price').attr('data-twisto-fee') < 1 && twistoOpc.find('input[type="radio"]').attr('checked')) {
					changeTotalPrice(fee);
					$('#total_price').attr('data-twisto-fee', 1);
				}
			});
		}

		function changeTotalPrice(change) {
			var price = parseFloat($('#total_price').html().replace(/[^0-9\,.]+/g,"").replace(',', '.'));
			totalPriceValue = ((price + change).toFixed(2) + ' Kč').replace('.', ',');
			$('#total_price').html(totalPriceValue);
			console.log('changed from: ' + price + ' to: ' + totalPriceValue);
		}
	
 		eval("var paymentModuleConfirmOPC = " +  paymentModuleConfirm.toString());
		paymentModuleConfirm = function() {
			$('#opc_payment_methods-overlay').css('display', 'block');
			if(!twistoOpc.find('input[type="radio"]').attr('checked')) {
				paymentModuleConfirmOPC();
				return;
			}

			if($('#cgv').attr('checked') == undefined) {
				$('#opc_tos_errors').html('Musíte souhlasit s podmínkami.').slideUp('fast').slideDown('slow');
				$.fn.scrollToElement('#opc_tos_errors', 500);
				$('#opc_payment_methods-overlay').css('display', 'none');
				return;
			}

			if(!twistoOpc.find('#twisto-terms-checkbox').attr('checked')) {
				$('#opc_payment_errors').html('Musíte souhlasit s podmínkami Twisto.cz nebo zvolit jinou platební metodu.').slideUp('fast').slideDown('slow');
				$.fn.scrollToElement('#opc_payment_errors', 500);
				$('#opc_payment_methods-overlay').css('display', 'none');
				return;	
			}

			var error = function(text) {
				$('#opc_payment_errors').html(text).slideUp('fast').slideDown('slow');
				$.fn.scrollToElement('#opc_payment_errors', 500);
			}

			Twisto.check('{/literal}{$payload}{literal}', function(response) {
				if (response.status == 'accepted') {
					window.location = '{/literal}{$link->getModuleLink('twistopayment', 'validation', ['foo' => 'bar'], true)|unescape:'html'}{literal}&transaction_id=' + response.transaction_id;
				} else {
					var reason = response.reason !== null ? response.reason : 'Omlouváme se, platba byla systémem Twisto zamítnuta. Zvolte jinou platební metodu.';
					error(reason);
				}
			}, function() {
				error('Došlo k chybě při odesílání objednávky na platební bránu Twisto. Zkuste to prosím znovu, případně si vyberte jinou platební metodu.');
			});
			$('#opc_payment_methods-overlay').css('display', 'none');
		}
	}
</script>
{/literal}