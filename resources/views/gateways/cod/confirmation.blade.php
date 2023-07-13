<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>COD | {{ Config::get('app.name', 'default'); }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="row p-5">
            <div class="col">
                <form action="{{ route('callback_url') }}" method="post">
                    @csrf
                    <input type="hidden" name="redirect_url"  value="{{ $input['redirect_url'] }}">
                        <input type="hidden" name="gateway"  value="{{ $input['gateway'] }}">
                    <button type="sumit" class="btn btn-primary btn-lg">Confirm Order</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>