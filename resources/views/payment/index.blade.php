@extends('larapay::layouts.app')

@section('head')
    <title>Payment to {{ config('app.name') }}</title>
@endsection

@section('content')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <form id='razorpayform' action="{{ route('callback_url') }}" method="POST">
        @csrf
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        @foreach ($input as $index => $i)
            <input type="hidden" name="{{ $index }}" value="{{ $i }}">
        @endforeach
    </form>

    <script>
        var options = {
            "key": "{{ config('larapay.razorpay.razorpay_key_id') }}", // Enter the Key ID generated from the Dashboard
            "amount": "50000", // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
            "currency": "INR",
            "name": "{{ $input['app_name'] ?? config('app.name')}}",
            "description": "{{ $input['description'] ?? 'description' }}",
            "image": "{{ config('app.logo_light') }}",
            "order_id": "{{ $orderData->id }}", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
            "handler": function(response) {
                document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('razorpayform').submit();
            },
            // "callback_url": "{{ route('callback_url') }}",
            "prefill": {
                "name": "{{ $input['name'] }}",
                "email": "{{ $input['email'] }}",
                "contact": "{{ $input['phone'] }}"
            },
            "notes": {
                "address": "{{ $input['address'] ?? 'address' }}"
            },
            "theme": {
                "color": "#3399cc"
            }
        };
        var rzp1 = new Razorpay(options);

        function myFunction(e) {
            rzp1.open();
            e.preventDefault();
        }
    </script>
@endsection
