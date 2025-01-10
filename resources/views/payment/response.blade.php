<html>

<head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
    <script src="{{asset('js/jquery-3.7.1.min.js')}}"></script>
    <link rel="stylesheet"
        href="{{asset('css/bootstrap.min.css')}}">
        <title>Payment Confirmation | {{ config('app.name') }}</title>
        <link rel="shortcut icon" href="{{ config('payment.favicon') }}" type="image/x-icon">
</head>
<style>
    body {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
    }

    h1 {
        color: #88B04B;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-weight: 900;
        font-size: 40px;
        margin-bottom: 10px;
    }

    p {
        color: #404F5E;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-size: 20px;
        margin: 0;
    }

    i {
        color: #9ABC66;
        font-size: 100px;
        line-height: 200px;
        margin-left: -15px;
    }

    .card {
        background: white;
        padding: 1rem;
        border-radius: 4px;
        box-shadow: 0 2px 3px #C8D0D8;
        display: inline-block;
        margin: 0 auto;
    }

    @media only screen and (min-width:480px) {
        .card {
            padding: 2rem;
        }
    }
</style>

<body>
    <div class="card">
        <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
            <i class="checkmark">
                <div class="spinner-grow" style="width: 120px; height: 120px; margin:40px 60px" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </i>
        </div>

        <table>
            <tr>
                <td> Payment ID </td>
                <td> : {{ $payment_data['payment_id'] }} </td>
            </tr>
            <tr>
                <td> Order ID </td>
                <td> : {{ $payment_data['order_id'] }} </td>
            </tr>
        </table>
        <h1>Processing...</h1>
        <p>We are checking your payment status.</p> <br>

        </p>
    </div>

    <form action="{{ route('paymentSuccess') }}" method="post" id="success_form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $payment_data['order_id'] }}">
        <input type="hidden" name="payment_id" value="{{ $payment_data['payment_id'] }}">
        <input type="hidden" name="redirect_url" value="{{ $payment_data['redirect_url'] }}">
    </form>
    
    <form action="{{ route('paymentFailed') }}" method="post" id="payment_failed_form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $payment_data['order_id'] }}">
        <input type="hidden" name="payment_id" value="{{ $payment_data['payment_id'] }}">
        <input type="hidden" name="redirect_url" value="{{ $payment_data['redirect_url'] }}">
    </form>

    <script>
        const url = "{{ route('checkPayment') }}"

        setInterval(() => {
            $.ajax({
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    order_id: "{{ $payment_data['order_id'] }}",
                    redirect_url: "{{ $payment_data['redirect_url'] }}",
                    payment_id: "{{ $payment_data['payment_id'] }}",
                },
                url: url,
                success: function(res) {
                    if (res == 'success') {
                        $("#success_form").submit();
                    }
                    if (res == 'failed') {
                        $("#payment_failed_form").submit();
                    }
                    console.log(res);
                    return res;
                },
                error: function(errors) {
                    console.log(errors.responseJSON.message);
                }
            });
        }, 4000);
    </script>

</body>

</html>
