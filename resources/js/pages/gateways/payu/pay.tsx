import React, { useEffect, useRef } from "react";

type PaymentFormProps = {
  orderData: {
    order_id: string;
    hash: string;
  };
  input: {
    amount: string | number;
    email: string;
    name: string;
    phone: string;
    redirect_url: string;
  };
  props: {
    paymentEnv: "production" | "sandbox";
    merchant_id: string;
    callbackUrl: string;
  };
};

export default function PaymentForm({
  orderData,
  input,
  props,
}: PaymentFormProps) {
  const formRef = useRef<HTMLFormElement>(null);

  const apiUrl =
    props.paymentEnv === "production"
      ? "https://secure.payu.in/_payment"
      : "https://test.payu.in/_payment";

  useEffect(() => {
    if (formRef.current) {
      formRef.current.submit();
    }
  }, []);
console.log(props);
  return (
    <form ref={formRef} action={apiUrl} method="post">
      <input type="hidden" name="key" value={props.merchant_id} />
      <input type="hidden" name="txnid" value={orderData.order_id} />
      <input type="hidden" name="productinfo" value="a" />
      <input type="hidden" name="amount" value={input.amount} />
      <input type="hidden" name="email" value={input.email} />
      <input type="hidden" name="firstname" value={input.name} />
      <input type="hidden" name="lastname" value="Kumar" />
      <input
        type="hidden"
        name="surl"
        value={`${props.callbackUrl}?redirect_url=${input.redirect_url}&order_id=${orderData.order_id}`}
      />
      <input
        type="hidden"
        name="furl"
        value={`${props.callbackUrl}?redirect_url=${input.redirect_url}&order_id=${orderData.order_id}`}
      />
      <input type="hidden" name="phone" value={input.phone} />
      <input type="hidden" name="hash" value={orderData.hash} />
    </form>
  );
}
