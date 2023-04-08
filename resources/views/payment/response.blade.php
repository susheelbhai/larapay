@extends('larapay::layouts.app')

@section('head')
    <title>Payment Form</title>
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
            padding: 60px;
            border-radius: 4px;
            box-shadow: 0 2px 3px #C8D0D8;
            display: inline-block;
            margin: 0 auto;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="card">
            <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
                <i class="checkmark">✓</i>
            </div>
            <h1>Success</h1>
            <p>We have received the payment;</p> <br>
            <table>
                <tr>
                    <td> Payment ID </td>
                    <td> : {{ $request->razorpay_payment_id ?? '' }} </td>
                </tr>
                <tr>
                    <td> Order ID </td>
                    <td> : {{ $request->razorpay_order_id ?? '' }} </td>
                </tr>
            </table>
            <p>You should be automatically redirected in <span id="seconds">7</span> seconds.
            </p>
        </div>
    
        <script>
            // Countdown timer for redirecting to another URL after several seconds
    
            var seconds = 60; // seconds for HTML
            var foo; // variable for clearInterval() function
    
            function redirect() {
                document.location.href = '{{ $request->redirect_url }}';
            }
    
            function updateSecs() {
                document.getElementById("seconds").innerHTML = seconds;
                seconds--;
                if (seconds == -1) {
                    clearInterval(foo);
                    redirect();
                }
            }
    
            function countdownTimer() {
                foo = setInterval(function() {
                    updateSecs()
                }, 1000);
            }
    
            countdownTimer();
        </script>

    </div>
@endsection
