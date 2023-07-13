@extends('larapay::layouts.app')

@section('head')
    <title>Payment Form | Larapay</title>
@endsection

@section('content')

    <div class="container">

        <h1 class="text-center p-5"> Larapay Testing Environment </h1>
        <div class="card">
            <div class="card-header">
                Payment Detail
            </div>
            <div class="card-body">
                <form class="row g-3 needs-validation" action="{{ route('payment_form') }}" method="post">
                    @csrf
                    <div class="col-md-6">
                        <label for="name" class="form-label"> Name</label>
                        <input type="text" class="form-control" name="name" id="name" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="redirect_url" class="form-label"> Redirect URL</label>
                        <input type="url" class="form-control" name="redirect_url" id="redirect_url"  value="http://localhost/larapay/public/pay" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label"> Email</label>
                        <input type="text" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label"> Phone</label>
                        <input type="text" class="form-control" name="phone" id="phone" required>
                    </div>
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" id="amount" required>
                    </div>
                    <div class="col-md-6">
                        <label for="gateway" class="form-label">Payment Gateway</label>
                        <select  class="form-control" name="gateway" id="gateway">
                            @foreach ($gateways as $i)
                                <option value="{{ $i->id }}"> {{ $i->name }} </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 text-center">
                        <button class="btn btn-primary " type="submit">Submit form</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
