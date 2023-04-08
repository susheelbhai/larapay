@extends('larapay::layouts.app')

@section('head')
    <title>Payment</title>
@endsection

@section('content')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <form id='razorpayform' action="{{ route('callback_url') }}" method="POST">
        @csrf
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        @foreach ($extra_input_array as $index => $i)
            <input type="hidden" name="{{ $index }}" value="{{ $i }}">
        @endforeach
        {!! $extra_input ?? '' !!}
    </form>

    <script>
        var options = {
            "key": "{{ env('RAZORPAY_KEY_ID') }}", // Enter the Key ID generated from the Dashboard
            "amount": "50000", // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
            "currency": "INR",
            "name": "{{ $input['app_name'] ?? 'Larapay ' }}",
            "description": "{{ $input['description'] ?? 'description' }}",
            "image": "https://example.com/your_logo",
            "order_id": "{{ $razorpayOrder->id }}", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
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
