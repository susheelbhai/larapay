<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Confirmation | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ config('payment.favicon') }}" type="image/x-icon">
    <style>
        body {
            text-align: center;
            padding: 40px 0;
            background: #EBF0F5;
            font-family: sans-serif;
        }

        h1 {
            color: #88B04B;
            font-weight: 900;
            font-size: 40px;
            margin-bottom: 10px;
        }

        p {
            color: #404F5E;
            font-size: 20px;
            margin: 0;
        }

        .card {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            box-shadow: 0 2px 3px #C8D0D8;
            display: inline-block;
            margin: 0 auto;
        }

        @media only screen and (min-width: 480px) {
            .card {
                padding: 2rem;
            }
        }

        .spinner-wrapper {
            border-radius: 200px;
            height: 200px;
            width: 200px;
            background: #F8FAF5;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid #e0e0e0;
            border-top: 8px solid #88B04B;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }


        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        table {
            margin: 20px auto;
            font-size: 16px;
            color: #333;
            text-align: left;
        }

        table td {
            padding: 4px 10px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
            <div class="spinner-wrapper">
                <div class="spinner" role="status" aria-label="Loading"></div>
            </div>
        </div>

        <table>
            <tr>
                <td>Payment ID</td>
                <td>: {{ $payment_data['payment_id'] }}</td>
            </tr>
            <tr>
                <td>Order ID</td>
                <td>: {{ $payment_data['order_id'] }}</td>
            </tr>
        </table>

        <h1>Processing...</h1>
        <p>We are checking your payment status.</p>
    </div>

    <!-- Success Form -->
    <form action="{{ route('paymentSuccess') }}" method="post" id="success_form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $payment_data['order_id'] }}">
        <input type="hidden" name="payment_id" value="{{ $payment_data['payment_id'] }}">
        <input type="hidden" name="redirect_url" value="{{ $payment_data['redirect_url'] }}">
    </form>

    <!-- Failed Form -->
    <form action="{{ route('paymentFailed') }}" method="post" id="payment_failed_form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $payment_data['order_id'] }}">
        <input type="hidden" name="payment_id" value="{{ $payment_data['payment_id'] }}">
        <input type="hidden" name="redirect_url" value="{{ $payment_data['redirect_url'] }}">
    </form>

    <script>
        const url = "{{ route('checkPayment') }}";
        const payload = {
            _token: "{{ csrf_token() }}",
            order_id: "{{ $payment_data['order_id'] }}",
            payment_id: "{{ $payment_data['payment_id'] }}",
            redirect_url: "{{ $payment_data['redirect_url'] }}"
        };

        setInterval(() => {
            fetch(url, {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.text())
                .then(res => {
                    if (res === 'success') {
                        document.getElementById('success_form').submit();
                    } else if (res === 'failed') {
                        document.getElementById('payment_failed_form').submit();
                    }
                    console.log(res);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 4000);
    </script>
</body>

</html>
