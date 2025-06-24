import React, { useEffect, useRef } from 'react';

type Props = {
  actionUrl: string;
  csrfToken: string;
  param1: string;
};

const RedirectForm: React.FC<Props> = ({ actionUrl, csrfToken, param1 }) => {
  const formRef = useRef<HTMLFormElement>(null);

  useEffect(() => {
    if (formRef.current) {
      formRef.current.submit();
    }
  }, []);

  return (
    <div>
      <form ref={formRef} method="POST" action={actionUrl}>
        <input type="hidden" name="_token" value={csrfToken} />
        <input type="hidden" name="param1" value={param1} />
      </form>
      <p>Redirecting to payment gateway...</p>
    </div>
  );
};

export default RedirectForm;
