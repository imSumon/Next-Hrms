import { useEffect } from 'react';
import { Link } from 'react-router-dom';

export function PreviewMissingPage({
  home,
  homeLabel,
  linkClassName = 'font-semibold text-indigo-700',
}: {
  home: string;
  homeLabel: string;
  linkClassName?: string;
}) {
  useEffect(() => {
    document.title = 'Page not in this preview — Universal HRMS';
  }, []);

  return (
    <div>
      <h1 className="text-2xl font-semibold text-stone-900">This page is not in this preview</h1>
      <p className="mt-3 max-w-2xl text-stone-600">
        This address does not match a screen in the current preview. The selected person, company, and preview edits are unchanged.
      </p>
      <p className="mt-5">
        <Link className={linkClassName} to={home}>
          {homeLabel}
        </Link>
      </p>
    </div>
  );
}
