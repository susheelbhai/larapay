<html>	
	<head>
		<script>
			window.onload = function() {
          		document.forms['pine1'].submit();
			};
		</script>
	</head>
	
	<?php
		$formdata = $orderData['formdata'];
		$hash = $orderData['hash'];
		$ppc_DIA_SECRET_TYPE = $orderData['ppc_DIA_SECRET_TYPE'];
	 ?>
    <!-- test.pinepg.in -->
	<body>
		<form name ="pine1" action="https://uat.pinepg.in/PinePGRedirect/index"  id="pine1" method="POST">
			<div style="margin: =-20px 40px 10px 130px;text-align: center;color: blue">		
				<input type="hidden" name="ppc_MerchantID" value="{{ $formdata['ppc_MerchantID'] }}" />
				<input type="hidden" name="ppc_Amount" value="{{ $formdata['ppc_Amount'] }}" />
				<input type="hidden" name="ppc_UniqueMerchantTxnID" value="{{ $formdata['ppc_UniqueMerchantTxnID'] }}" />
				<input type="hidden" name="ppc_MerchantAccessCode" value="{{ $formdata['ppc_MerchantAccessCode'] }}" />	
				<input type="hidden" name="ppc_TransactionType" value="{{ $formdata['ppc_TransactionType']  }}" />
				<input type="hidden" name="ppc_NavigationMode" value="{{ $formdata['ppc_NavigationMode']  }}" />
				<input type="hidden" name="ppc_LPC_SEQ" value="{{ $formdata['ppc_LPC_SEQ']  }}" />
				<input type="hidden" name="ppc_MerchantReturnURL" value="{{ $formdata['ppc_MerchantReturnURL']  }}" />	
				<input type="hidden" name="ppc_DIA_SECRET" value="{{ $hash  }}" />
				<input type="hidden" name="ppc_Product_Code" value="{{ $formdata['ppc_Product_Code']   }}" />
				<input type="hidden" name="ppc_PayModeOnLandingPage" value="{{ $formdata['ppc_PayModeOnLandingPage']   }}" />
				<input type="hidden" name="ppc_DIA_SECRET_TYPE" value="{{ $ppc_DIA_SECRET_TYPE  }}" />	
			    <input type="hidden" name="ppc_MerchantProductInfo" value="{{ $formdata['ppc_MerchantProductInfo']  }}" />	
			    <input type="hidden" name="ppc_CustomerFirstName" value="{{ $formdata['ppc_CustomerFirstName'] }}" />
			    <input type="hidden" name="ppc_CustomerLastName" value="{{ $formdata['ppc_CustomerLastName'] }}" />
			    <input type="hidden" name="ppc_CustomerMobile" value="{{ $formdata['ppc_CustomerMobile'] }}" />
			    <input type="hidden" name="ppc_CustomerEmail" value="{{ $formdata['ppc_CustomerEmail'] }}" />
			    <input type="hidden" name="ppc_CustomerAddress1" value="{{ $formdata['ppc_CustomerAddress1'] }}" />
			    <input type="hidden" name="ppc_CustomerAddress2" value="{{ $formdata['ppc_CustomerAddress2'] }}" />
			    <input type="hidden" name="ppc_CustomerAddressPIN" value="{{ $formdata['ppc_CustomerAddressPIN'] }}" />
			    <input type="hidden" name="ppc_CustomerCity" value="{{ $formdata['ppc_CustomerCity'] }}" />
			    <input type="hidden" name="ppc_CustomerState" value="{{ $formdata['ppc_CustomerState'] }}" />
			    <input type="hidden" name="ppc_CustomerCountry" value="{{ $formdata['ppc_CustomerCountry'] }}" />
			    <input type="hidden" name="ppc_ShippingFirstName" value="{{ $formdata['ppc_ShippingFirstName'] }}" />
			    <input type="hidden" name="ppc_ShippingLastName" value="{{ $formdata['ppc_ShippingLastName'] }}" />
			    <input type="hidden" name="ppc_ShippingAddress1" value="{{ $formdata['ppc_ShippingAddress1'] }}" />
			    <input type="hidden" name="ppc_ShippingAddress2" value="{{ $formdata['ppc_ShippingAddress2'] }}" />
			    <input type="hidden" name="ppc_ShippingCity" value="{{ $formdata['ppc_ShippingCity'] }}" />
			    <input type="hidden" name="ppc_ShippingState" value="{{ $formdata['ppc_ShippingState'] }}" />
			    <input type="hidden" name="ppc_ShippingCountry" value="{{ $formdata['ppc_ShippingCountry'] }}" />
			    <input type="hidden" name="ppc_ShippingZipCode" value="{{ $formdata['ppc_ShippingZipCode'] }}" />
			    <input type="hidden" name="ppc_ShippingPhoneNumber" value="{{ $formdata['ppc_ShippingPhoneNumber'] }}" />      
			    <input type="hidden" name="gateway" value="{{ $formdata['gateway'] }}" />      
			</div>
		</form>
    </body>
</html>