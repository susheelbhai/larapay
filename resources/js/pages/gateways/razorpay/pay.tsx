import { useEffect } from 'react';

interface RazorpayProps {
  razorpayKey: string;
  input: Record<string, any>;
  callbackUrl: string;
  logoUrl?: string;
}

declare global {
  interface Window {
    Razorpay?: any;
  }
}

const loadRazorpayScript = (): Promise<void> => {
  return new Promise((resolve, reject) => {
    if (window.Razorpay) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.onload = () => resolve();
    script.onerror = () => reject('Failed to load Razorpay script.');
    document.body.appendChild(script);
  });
};

const RazorpayPage = ({ props, input, callbackUrl }: RazorpayProps) => {
  useEffect(() => {
    const initRazorpay = async () => {
      try {
        await loadRazorpayScript();

        const options = {
          key: props.razorpayKey,
          amount: input.amount,
          currency: 'INR',
          name: input.app_name || 'App Name',
          description: input.description || 'Payment description',
          image: props.logoUrl || '',
          order_id: input.order_id,
          handler: function (response: any) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = callbackUrl;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
              const csrfInput = document.createElement('input');
              csrfInput.type = 'hidden';
              csrfInput.name = '_token';
              csrfInput.value = csrfToken;
              form.appendChild(csrfInput);
            }

            const appendField = (name: string, value: string) => {
              const inputEl = document.createElement('input');
              inputEl.type = 'hidden';
              inputEl.name = name;
              inputEl.value = value;
              form.appendChild(inputEl);
            };

            appendField('order_id', response.razorpay_order_id);
            appendField('payment_id', response.razorpay_payment_id);
            appendField('razorpay_signature', response.razorpay_signature);
            appendField('redirect_url', 'razorpay_signature');

            for (const [key, value] of Object.entries(input)) {
              appendField(key, String(value));
            }

            document.body.appendChild(form);
            form.submit();
          },
          prefill: {
            name: input.name,
            email: input.email,
            contact: input.phone,
          },
          notes: {
            address: input.address || 'Address',
          },
          theme: {
            color: '#3399cc',
          },
        };

        const rzp = new window.Razorpay(options);
        rzp.open();
      } catch (error) {
        console.error('Razorpay failed to load:', error);
      }
    };

    initRazorpay();
  }, []);

  return (
    <div className="flex items-center justify-center h-screen">
      <p>Redirecting to Razorpay...</p>
    </div>
  );
};

export default RazorpayPage;
