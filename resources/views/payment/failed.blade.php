<html>

<head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
</head>
<style>
    body {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
    }

    h1 {
        color: #c80000;
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
        color: #c80000;
        font-size: 100px;
        line-height: 200px;
        margin-left: -15px;
    }

    .card {
        background: white;
        padding: 60px;
        border-radius: 4px;
        box-shadow: 0 2px 3px #fcbbb1;
        display: inline-block;
        margin: 0 auto;
    }
</style>

<body>
    <div class="card">
        <div style="border-radius:200px; height:200px; width:200px; background: #fcbbb1; margin:0 auto;">
            <i class="checkmark">&#9747;</i>
        </div>
        <h1>Failed</h1>
        <p>We did not receive the payment.</p> <br>
        <table>
            <tr>
                <td> Payment ID </td>
                <td> : {{ $data['payment_id'] }} </td>
            </tr>
            <tr>
                <td> Order ID </td>
                <td> : {{ $data['order_id'] }} </td>
            </tr>
        </table>
        <p>You should be automatically redirected in <span id="seconds"> {{ config('larapay.settings.redirection_waiting_time') }} </span> seconds.
        </p>
    </div>
    @php
        $redirect_url = $data['redirect_url']
    @endphp
    <script>
        // Countdown timer for redirecting to another URL after several seconds

        var seconds = "{{ config('larapay.settings.redirection_waiting_time') }}"; // seconds for HTML
        var foo; // variable for clearInterval() function

        function redirect() {
            document.location.href = '{{ $redirect_url }}';
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
</body>

</html>
