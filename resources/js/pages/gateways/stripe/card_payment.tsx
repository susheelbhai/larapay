import React, { useEffect, useRef, useState } from 'react';

interface Props {
  input: Record<string, any>;
  orderData: Record<string, any>;
  props: {
    csrfToken: string;
    callbackUrl: string;
    logoUrl: string;
    source: string;
  };
}

declare global {
  interface Window {
    Stripe: any;
  }
}

const StripePayment: React.FC<Props> = ({ input, props }) => {
  const formRef = useRef<HTMLFormElement>(null);
  const [error, setError] = useState<string | null>(null);

  const publishableKey = input?.stripe_key || import.meta.env.VITE_STRIPE_KEY;

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v2/';
    script.async = true;
    script.onload = () => {
      if (window.Stripe) {
        window.Stripe.setPublishableKey(publishableKey);
      }
    };
    document.body.appendChild(script);
  }, [publishableKey]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const form = formRef.current;
    if (!form || !window.Stripe) return;

    const name = form['name'].value;
    const number = form['card_number'].value;
    const cvc = form['card_cvc'].value;
    const exp_month = form['exp_month'].value;
    const exp_year = form['exp_year'].value;

    window.Stripe.createToken(
      { number, cvc, exp_month, exp_year, name },
      (status: number, response: any) => {
        if (response.error) {
          setError(response.error.message);
        } else {
          const token = response.id;
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'stripeToken';
          hiddenInput.value = token;
          form.appendChild(hiddenInput);
          form.submit();
        }
      }
    );
  };

  return (
    <div className="container max-w-xl mx-auto my-12">
      <h1 className="text-center text-2xl font-bold mb-6">Stripe Payment</h1>

      <form
        ref={formRef}
        onSubmit={handleSubmit}
        method="POST"
        action={props.callbackUrl}
        className="space-y-4 bg-white p-6 rounded shadow"
      >
        <input type="hidden" name="_token" value={props.csrfToken} />
        <input type="hidden" name="redirect_url" value={input.redirect_url} />
        <input type="hidden" name="amount" value={input.amount} />
        <input type="hidden" name="currency" value="inr" />
        <input type="hidden" name="description" value="Payment from larapay" />

        {error && <div className="text-red-600 font-medium">{error}</div>}

        <div>
          <label className="block font-semibold">Name on Card</label>
          <input
            type="text"
            name="name"
            defaultValue={input.name}
            className="w-full border px-3 py-2 rounded"
          />
        </div>

        <div>
          <label className="block font-semibold">Card Number</label>
          <input
            type="text"
            name="card_number"
            defaultValue="4242424242424242"
            className="w-full border px-3 py-2 rounded"
          />
        </div>

        <div className="flex space-x-4">
          <div>
            <label className="block font-semibold">CVC</label>
            <input
              type="text"
              name="card_cvc"
              defaultValue="123"
              className="w-full border px-3 py-2 rounded"
            />
          </div>

          <div>
            <label className="block font-semibold">Exp. Month</label>
            <input
              type="text"
              name="exp_month"
              defaultValue="12"
              className="w-full border px-3 py-2 rounded"
            />
          </div>

          <div>
            <label className="block font-semibold">Exp. Year</label>
            <input
              type="text"
              name="exp_year"
              defaultValue="25"
              className="w-full border px-3 py-2 rounded"
            />
          </div>
        </div>

        <button
          type="submit"
          className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"
        >
          Pay Now ₹{input.amount}
        </button>
      </form>
    </div>
  );
};

export default StripePayment;
