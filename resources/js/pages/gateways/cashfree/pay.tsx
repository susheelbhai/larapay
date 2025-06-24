import React, { useEffect, useRef } from 'react';

type Props = {
  input: Record<string, string>;
  orderData: {
    cashfree_data: {
      payment_session_id: string;
      order_id: string;
    };
  };
  props: {
    csrfToken: string;
    callbackUrl: string;
    source: string;
    logoUrl: string;
    paymentEnv: 'sandbox' | 'production';
  };
};

const CashfreePayment: React.FC<Props> = ({ input, orderData, props }) => {
  const formRef = useRef<HTMLFormElement>(null);

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
    script.onload = () => {
      // @ts-ignore: Cashfree is added globally by the script
      const cashfree = window.Cashfree({ mode: props.paymentEnv });

      const checkoutOptions = {
        paymentSessionId: orderData.cashfree_data.payment_session_id,
        redirectTarget: '_modal',
      };

      cashfree.checkout(checkoutOptions).then((result: any) => {
        if (result.error) {
          console.error('Cashfree Error:', result.error);
        }
        if (result.redirect) {
          console.log('Cashfree is redirecting externally.');
        }
        if (result.paymentDetails) {
          console.log('Cashfree payment completed. Submitting form...');
          formRef.current?.submit();
        }
      });
    };

    document.body.appendChild(script);
  }, [orderData.cashfree_data.payment_session_id, props.paymentEnv]);

  return (
    <div>
      <form ref={formRef} action={props.callbackUrl} method="POST">
        <input type="hidden" name="_token" value={props.csrfToken} />
        <input type="hidden" name="payment_session_id" value={orderData.cashfree_data.payment_session_id} />
        <input type="hidden" name="order_id" value={orderData.cashfree_data.order_id} />
        {Object.entries(input).map(([key, value]) => (
          <input key={key} type="hidden" name={key} value={value} />
        ))}
      </form>
      <p>Launching Cashfree payment modal...</p>
    </div>
  );
};

export default CashfreePayment;
