@extends('larapay::layouts.app')

@section('head')
    <title>Payment to {{ config('app.name') }}</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
@endsection

@section('content')
<form id='paymentForm' action="{{ route('callback_url') }}" method="POST">
    @csrf
    <input type="hidden" name="payment_session_id" value="{{ $orderData['payment_session_id'] }}">
    <input type="hidden" name="order_id" value="{{ $orderData['order_id'] }}">
    @foreach ($input as $index => $i)
        <input type="hidden" name="{{ $index }}" value="{{ $i }}">
    @endforeach
</form>
@php
    if (config('larapay.settings.payment_env') == 'local') {
            $payment_env = "sandbox";
        }
        if (config('larapay.settings.payment_env') == 'production') {
            $payment_env = "production";
        }
@endphp
<script>
    const cashfree = Cashfree({
        mode: "{{ $payment_env }}",
    });

    function myFunction(e) {
        let checkoutOptions = {
            paymentSessionId: "{{ $orderData['payment_session_id'] }}",
            redirectTarget: "_modal",
        };
        cashfree.checkout(checkoutOptions).then((result) => {
            if(result.error){
                // This will be true whenever user clicks on close icon inside the modal or any error happens during the payment
                console.log("User has closed the popup or there is some payment error, Check for Payment Status");
                console.log(result.error);
            }
            if(result.redirect){
                // This will be true when the payment redirection page couldnt be opened in the same window
                // This is an exceptional case only when the page is opened inside an inAppBrowser
                // In this case the customer will be redirected to return url once payment is completed
                console.log("Payment will be redirected");
            }
            if(result.paymentDetails){
                // This will be called whenever the payment is completed irrespective of transaction status
                console.log("Payment has been completed, Check for Payment Status");
                console.log(result.paymentDetails.paymentMessage);
                document.getElementById("paymentForm").submit();
            }
        });
        }
        
</script>
@endsection
