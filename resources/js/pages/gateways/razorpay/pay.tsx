import { useEffect } from 'react';

interface RazorpayProps {
    props: any;
    orderData: any;
    input: Record<string, any>;
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

const RazorpayPage = ({ props, input, orderData }: RazorpayProps) => {
    console.log('RazorpayPage orderData:', orderData);
    useEffect(() => {
        const init = async () => {
            await loadRazorpayScript();

            const realOrderId = orderData.id || orderData.attributes?.id;
            console.log('Using order_id →', realOrderId);

            const options = {
                key: props.razorpayKey,
                amount: input.amount,
                currency: 'INR',
                order_id: realOrderId!,
                handler(response: any) {
                    console.log('Razorpay response:', response);

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = orderData.redirect_url;

                    // attach CSRF token…
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (token) {
                        const f = document.createElement('input');
                        f.type = 'hidden';
                        f.name = '_token';
                        f.value = token;
                        form.appendChild(f);
                    }

                    const add = (n: string, v: string) => {
                        const i = document.createElement('input');
                        i.type = 'hidden';
                        i.name = n;
                        i.value = v;
                        form.appendChild(i);
                    };

                    add('order_id', response.razorpay_order_id);
                    add('payment_id', response.razorpay_payment_id);
                    add('razorpay_signature', response.razorpay_signature);

                    Object.entries(input).forEach(([k, v]) => add(k, String(v)));

                    document.body.appendChild(form);
                    form.submit();
                },
                prefill: { name: input.name, email: input.email, contact: input.phone },
                notes: { address: input.address },
                theme: { color: '#3399cc' },
            };

            new window.Razorpay(options).open();
        };

        init().catch(console.error);
    }, []);

    return (
        <div className="flex h-screen items-center justify-center">
            <p>Redirecting to Razorpay...</p>
        </div>
    );
};

export default RazorpayPage;
