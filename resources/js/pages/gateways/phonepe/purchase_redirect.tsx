import React, { useEffect, useRef } from 'react';

type Props = {
    orderData: {
        action_url: string;
    };
    props: {
        csrfToken: string;
    };
};

const RedirectForm: React.FC<Props> = ({ orderData, props }) => {
    const formRef = useRef<HTMLFormElement>(null);
    console.log('RedirectForm props:', orderData.action_url);
    useEffect(() => {
        if (formRef.current) {
            formRef.current.submit();
        }
    }, []);

    return (
        <div>
            <form ref={formRef} method="POST" action={orderData.action_url}>
                <input type="hidden" name="_token" value={props.csrfToken} />
            </form>
            <p>Redirecting to payment gateway...</p>
        </div>
    );
};

export default RedirectForm;
