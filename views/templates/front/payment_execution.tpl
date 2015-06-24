{capture name=path}Platba s Twisto{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">Váš košík je prázdný!</p>
{else}

<p style="margin-top:20px;">
	Celková hodnota objednaného zboží je
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}(s dph){/if}.
</p>

<p class="payment_module">
	<a id="twisto" href="#" style="background: url('{$this_path}views/img/twisto-logo.png') 15px center no-repeat;">
		<span id="twisto-overlay">
			<span id="twisto-overlay-message"></span>
			<svg version="1.1" 
				id="twisto-spinner" 
				xmlns="http://www.w3.org/2000/svg" 
				xmlns:xlink="http://www.w3.org/1999/xlink" 
				x="0px"
				y="0px" 
				viewBox="0 0 80 80" 
				xml:space="preserve">

			<path 
				fill="#FFF" 
				d="M10,40c0,0,0-0.4,0-1.1c0-0.3,0-0.8,0-1.3c0-0.3,0-0.5,0-0.8c0-0.3,0.1-0.6,0.1-0.9c0.1-0.6,0.1-1.4,0.2-2.1
				c0.2-0.8,0.3-1.6,0.5-2.5c0.2-0.9,0.6-1.8,0.8-2.8c0.3-1,0.8-1.9,1.2-3c0.5-1,1.1-2,1.7-3.1c0.7-1,1.4-2.1,2.2-3.1
				c1.6-2.1,3.7-3.9,6-5.6c2.3-1.7,5-3,7.9-4.1c0.7-0.2,1.5-0.4,2.2-0.7c0.7-0.3,1.5-0.3,2.3-0.5c0.8-0.2,1.5-0.3,2.3-0.4l1.2-0.1
				l0.6-0.1l0.3,0l0.1,0l0.1,0l0,0c0.1,0-0.1,0,0.1,0c1.5,0,2.9-0.1,4.5,0.2c0.8,0.1,1.6,0.1,2.4,0.3c0.8,0.2,1.5,0.3,2.3,0.5
				c3,0.8,5.9,2,8.5,3.6c2.6,1.6,4.9,3.4,6.8,5.4c1,1,1.8,2.1,2.7,3.1c0.8,1.1,1.5,2.1,2.1,3.2c0.6,1.1,1.2,2.1,1.6,3.1
				c0.4,1,0.9,2,1.2,3c0.3,1,0.6,1.9,0.8,2.7c0.2,0.9,0.3,1.6,0.5,2.4c0.1,0.4,0.1,0.7,0.2,1c0,0.3,0.1,0.6,0.1,0.9
				c0.1,0.6,0.1,1,0.1,1.4C74,39.6,74,40,74,40c0.2,2.2-1.5,4.1-3.7,4.3s-4.1-1.5-4.3-3.7c0-0.1,0-0.2,0-0.3l0-0.4c0,0,0-0.3,0-0.9
				c0-0.3,0-0.7,0-1.1c0-0.2,0-0.5,0-0.7c0-0.2-0.1-0.5-0.1-0.8c-0.1-0.6-0.1-1.2-0.2-1.9c-0.1-0.7-0.3-1.4-0.4-2.2
				c-0.2-0.8-0.5-1.6-0.7-2.4c-0.3-0.8-0.7-1.7-1.1-2.6c-0.5-0.9-0.9-1.8-1.5-2.7c-0.6-0.9-1.2-1.8-1.9-2.7c-1.4-1.8-3.2-3.4-5.2-4.9
				c-2-1.5-4.4-2.7-6.9-3.6c-0.6-0.2-1.3-0.4-1.9-0.6c-0.7-0.2-1.3-0.3-1.9-0.4c-1.2-0.3-2.8-0.4-4.2-0.5l-2,0c-0.7,0-1.4,0.1-2.1,0.1
				c-0.7,0.1-1.4,0.1-2,0.3c-0.7,0.1-1.3,0.3-2,0.4c-2.6,0.7-5.2,1.7-7.5,3.1c-2.2,1.4-4.3,2.9-6,4.7c-0.9,0.8-1.6,1.8-2.4,2.7
				c-0.7,0.9-1.3,1.9-1.9,2.8c-0.5,1-1,1.9-1.4,2.8c-0.4,0.9-0.8,1.8-1,2.6c-0.3,0.9-0.5,1.6-0.7,2.4c-0.2,0.7-0.3,1.4-0.4,2.1
				c-0.1,0.3-0.1,0.6-0.2,0.9c0,0.3-0.1,0.6-0.1,0.8c0,0.5-0.1,0.9-0.1,1.3C10,39.6,10,40,10,40z"
				>

				<animateTransform
					attributeType="xml"
					attributeName="transform"
					type="rotate"
					from="0 40 40"
					to="360 40 40"
					dur="0.6s"
					repeatCount="indefinite"
				/>
			</path>
			</svg>

			</span>
		</span>
		<span class="twisto-title">Okamžitý nákup s platbou později <span id="twisto-popover-trigger">Jak to funguje?</span></span>
		<span class="twisto-fee">Poplatek 39 Kč</span>
		<span class="twisto-checkbox-wrapper">
			<input type="checkbox" id="twisto-checkbox">
		</span>
		<span id="twisto-terms">Souhlasím s <span id="twisto-terms-href">všeobecnými obchodními podmínkami služby Twisto.cz</span> (platba první objednávky do 14 dní od doručení zboží) a se zpracováním osobních údajů pro účely této služby. Podmínkou služby je věk 18+ a převzetí zboží zákazníkem.</span>
		<span id="twisto-error-alert"></span>
		<span id="twisto-try-again-btn">Zkusit znovu</span>
	</a>
	<div class="twisto-checkbox-wrapper-new">
		<input type="checkbox" id="twisto-checkbox-new">
	</div>

	<div id="twisto-modal" style="display: none">
		<div id="twisto-page-overlay" class="modal-close"></div>
		<div id="twisto-popover">
			<h2>Zaplaťte v klidu, až na to bude čas</h2>
			<p>Nechce se vám teď vyťukávat číslo karty? Spěcháte a potřebujete nakoupit rychle? Hodilo by se
vám zaplatit později? Brnkačka. Celý nákup si můžete okamžitě objednat a zaplatit až 14 dní
po doručení zboží. Sami si pak vyberete, jak nákup zaplatíte.</p>
			<h3>Zaplaťte až 14 dní po doručení zboží:</h3>
			<img src="{$this_path}views/img/twisto-modal-icons.png">
			<div class="bottom">
				<a class="more" href="https://www.twisto.cz/" target="_blank">Více o službě Okamžitý nákup s platbou později</a>
				<span class="modal-btn modal-close">Pokračovat v nákupu</span>
			</div>
			<div class="close-cross modal-close"></div>
		</div>
	</div>
</p>

<p class="cart_navigation clearfix" id="cart_navigation">
	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" >
        <i class="icon-chevron-left"></i>Další způsoby platby
    </a>
    <button class="button btn btn-default button-medium" id="twisto-checkout-btn">
        <span>Potvrzuji objednávku<i class="icon-chevron-right right"></i></span>
    </button>
</p>

{literal}
<script type="text/javascript">
	var _twisto_config = {
		public_key: '{/literal}{$public_key}{literal}',
		script: 'https://static.twisto.cz/api/v2/twisto.js'
	};
	(function(e,g,a){function h(a){return function(){b._.push([a,arguments])}}var f=["check"],b=e||{},c=document.createElement(a);a=document.getElementsByTagName(a)[0];b._=[];for(var d=0;d<f.length;d++)b[f[d]]=h(f[d]);this[g]=b;c.type="text/javascript";c.async=!0;c.src=e.script;a.parentNode.insertBefore(c,a);delete e.script}).call(window,_twisto_config,"Twisto","script");
</script>

<script type="text/javascript">
	TwistoCheckout = {

		paymentModule: 	 document.getElementById('twisto'),
		terms: 			 document.getElementById('twisto-terms'),
		termsHref: 		 document.getElementById('twisto-terms-href'),
		termsCheckbox: 	 document.getElementById('twisto-checkbox-new'),
		overlay: 		 document.getElementById('twisto-overlay'),
		overlayMessage:  document.getElementById('twisto-overlay-message'),
		overlaySpinner:  document.getElementById('twisto-spinner'),
		errorAlert: 	 document.getElementById('twisto-error-alert'),
		tryAgainBtn: 	 document.getElementById('twisto-try-again-btn'),
		modalTrigger:	 document.getElementById('twisto-popover-trigger'),
		checkoutBtn: 	 document.getElementById('twisto-checkout-btn'),
		checkoutBtn: 	 document.getElementById('twisto-checkout-btn'),

		init: function () {
			this.reset();

			this.paymentModule.onclick = function(e) { e.preventDefault(); };

			this.termsHref.onclick = function(e) {
				var win = window.open('https://www.twisto.cz/podminky/', '_blank');
				win.focus();
			};

			this.checkoutBtn.onclick = this.tryAgainBtn.onclick = function(e) {
				if (TwistoCheckout.termsCheckbox.checked) {
					TwistoCheckout.showOverlay(true, 'Probíhá vyhodnocení objednávky');
					TwistoCheckout.checkout('{/literal}{$payload}{literal}');
				} else {
					TwistoCheckout.errorAlert.innerText = "Musíte souhlasit s podmínkami.";
					TwistoCheckout.errorAlert.style.display = 'block';
				}
			};

			this.modalTrigger.onclick = function(e) {
				TwistoCheckout.modal.style.display = 'block';
			};

			var closers = document.querySelectorAll('.modal-close');
			for (var i = closers.length - 1; i >= 0; i--) {
				closers[i].onclick = function(e) {
					TwistoCheckout.modal.style.display = 'none';
				}
			};
		},

		checkout: function (payload) {
			Twisto.check(payload, function(response) {
				if (response.status == 'accepted') {
					// platba byla schválena
					window.location = '{/literal}{$link->getModuleLink('twistopayment', 'validation', ['foo' => 'bar'], true)|unescape:'html'}{literal}&transaction_id=' + response.transaction_id;
				} else {
					var reason = response.reason !== null ? response.reason : 'Omlouváme se, platba byla systémem Twisto zamítnuta. Zvolte jinou platební metodu.';
					TwistoCheckout.showOverlay(false, reason);
				}
			}, function() {
				// došlo k chybě při odesílání požadavku, nebo je chyba v odesílaných datech
				TwistoCheckout.error();
			});
		},

		showOverlay : function(spinner, message) {
			if (spinner) {
				this.overlaySpinner.style.display = 'block';
			} else {
				this.overlaySpinner.style.display = 'none';
			}
			this.overlayMessage.innerText = message;
			this.overlay.style.display = 'block';
		},

		reset: function () {
			this.overlay.style.display = 'none';
			this.tryAgainBtn.style.display = 'none';
			this.errorAlert.style.display = 'none';
		},

		error: function (reason) {
			if(typeof(reason) == 'undefined')
				reason = 'Došlo k chybě při odesílání objednávky na platební bránu Twisto. Zkuste to prosím znovu, případně si vyberte jinou platební metodu.';
			this.errorAlert.innerText = reason;
			this.errorAlert.style.display = 'block';
			this.terms.style.display = 'none';
			document.querySelector('.twisto-checkbox-wrapper').style.display = 'none';
			document.querySelector('.twisto-checkbox-wrapper-new').style.display = 'none';
			this.tryAgainBtn.style.display = 'inline-block';
			this.overlay.style.display = 'none';
		}
	};

	TwistoCheckout.init();
</script>
{/literal}
{/if}
