// Success.jsx
import React from 'react';
import { usePage } from '@inertiajs/react';

export default function Success() {
  const { data } = usePage().props;

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold text-green-600">success from larapay package</h1>
      <pre className="mt-4 bg-gray-100 text-black p-4 rounded">{JSON.stringify(data, null, 2)}</pre>
    </div>
  );
}
