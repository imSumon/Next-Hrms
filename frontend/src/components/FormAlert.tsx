import { useEffect, useRef } from 'react';

export function FormAlert({ errors }: { errors: string[] }) {
  const ref = useRef<HTMLDivElement>(null);

  const signature = errors.join('\n');

  useEffect(() => {
    if (signature !== '') {
      ref.current?.focus();
    }
  }, [signature]);

  if (errors.length === 0) {
    return null;
  }

  return (
    <div ref={ref} tabIndex={-1} role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-950">
      <p className="font-semibold">Check the form.</p>
      <ul className="mt-2 list-disc space-y-1 pl-5">
        {errors.map((error) => (
          <li key={error}>{error}</li>
        ))}
      </ul>
    </div>
  );
}
