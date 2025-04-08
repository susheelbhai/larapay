@extends('larapay::layouts.app')

@section('head')
    <title>Payment to {{ config('app.name') }}</title>
@endsection

@section('content')
    @php
        $api_url = 'https://test.payu.in/_payment';
        if (config('larapay.settings.payment_env') == 'production') {
            $api_url = 'https://secure.payu.in/_payment';
        }
    @endphp

    <form name="payment_form" action="{{ $api_url }}" method="post">
        <input type="hidden" name="key" value="{{ config('larapay.payu.merchant_id') }}" />
        <input type="hidden" name="txnid" value="{{ $orderData['order_id'] }}" />
        <input type="hidden" name="productinfo" value="a" />
        <input type="hidden" name="amount" value="{{ $input['amount'] }}" />
        <input type="hidden" name="email" value="{{ $input['email'] }}" />
        <input type="hidden" name="firstname" value="{{ $input['name'] }}" />
        <input type="hidden" name="lastname" value="Kumar" />
        <input type="hidden" name="surl" value="{{ route('callback_url') . "?redirect_url=" . $input['redirect_url'] . "&order_id=" . $orderData['order_id'] }}" />
        <input type="hidden" name="furl" value="{{ route('callback_url') . "?redirect_url=" . $input['redirect_url'] . "&order_id=" . $orderData['order_id'] }}" />
        <input type="hidden" name="phone" value="{{ $input['phone'] }}" />
        <input type="hidden" name="hash" value="{{ $orderData['hash'] }}" />
    </form>


    <script language='javascript'>
        document.payment_form.submit();
    </script>
@endsection
