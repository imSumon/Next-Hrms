import { useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PublicFrame } from '../components/PublicFrame';
import { companyById, people } from '../data/fixtures';
import { useSession } from '../session';

function personaDetail(id: string, names: string[]): { membership: string; next: string } {
  if (id === 'rupa') {
    return {
      membership: `Two preview memberships: ${names.join(' and ')}.`,
      next: 'Next: the company chooser.',
    };
  }
  if (id === 'imran') {
    return {
      membership: `Preview membership: ${names[0] ?? 'North Workshop'}.`,
      next: 'Next: the North Workshop employee directory.',
    };
  }
  if (id === 'lina') {
    return {
      membership: `Preview membership: ${names[0] ?? 'South Workshop'}.`,
      next: 'Next: the South Workshop employee directory.',
    };
  }
  if (id === 'farid') {
    return {
      membership: `Preview membership: ${names[0] ?? 'Quiet Workshop'}.`,
      next: 'Next: the Quiet Workshop preview. No employee identity is stored for this persona.',
    };
  }
  const next = names.length === 1 ? `Opens ${names[0]}.` : 'Next: choose a company.';
  return { membership: `Preview memberships: ${names.join(', ')}.`, next };
}

export function DemoEntryPage() {
  const navigate = useNavigate();
  const { person, company, selectPerson } = useSession();

  useEffect(() => {
    document.title = 'Try demo — Universal HRMS';
  }, []);

  const continueTarget = person && company ? '/admin/employees' : person ? '/preview/company' : null;

  return (
    <PublicFrame>
      <main className="mx-auto grid w-full max-w-6xl gap-8 px-4 py-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-amber-800">Demo</p>
          <h1 className="mt-2 text-3xl font-semibold text-stone-900">Try the synthetic preview</h1>
          <p className="mt-3 max-w-xl text-stone-600">
            These are synthetic preview people, not accounts. Choosing one starts this browser’s preview for the companies listed below.
          </p>
          <p className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950" role="note">
            This is a demo. Selecting a persona does not authenticate a real user and does not grant production permissions.
          </p>
          {continueTarget ? (
            <div className="mt-4 rounded-xl border border-stone-200 bg-white p-4 text-sm leading-6 text-stone-700">
              <p>A preview is already open. Continue it without choosing again, or choose a persona below.</p>
              <p className="mt-2">Choosing a persona replaces the preview records from this visit with the synthetic starting records.</p>
              <p className="mt-3">
                <Link
                  className="font-semibold text-indigo-700"
                  state={company ? { previewCompanyId: company.id } : undefined}
                  to={continueTarget}
                >
                  Continue the open preview
                </Link>
              </p>
            </div>
          ) : null}
          <p className="mt-6">
            <Link className="text-sm font-semibold text-stone-700" to="/preview/sign-in">
              Back to sign in
            </Link>
          </p>
        </div>
        <ul className="grid gap-3">
          {people.map((persona) => {
            const names = persona.memberships
              .map((membership) => companyById(membership.companyId)?.name)
              .filter((name): name is string => Boolean(name));
            const detail = personaDetail(persona.id, names);
            return (
              <li key={persona.id}>
                <button
                  type="button"
                  className="flex w-full items-start justify-between gap-4 rounded-xl border border-stone-200 bg-white px-4 py-4 text-left shadow-sm hover:border-indigo-300"
                  onClick={() => {
                    const next = selectPerson(persona.id);
                    if (next === 'directory') {
                      navigate('/admin/employees', { state: { previewCompanyId: persona.memberships[0]?.companyId } });
                    } else {
                      navigate('/preview/company');
                    }
                  }}
                >
                  <span>
                    <span className="block font-semibold text-stone-900">{persona.name}</span>
                    <span className="mt-1 block text-sm text-stone-600">{detail.membership}</span>
                    <span className="mt-1 block text-sm text-stone-600">{detail.next}</span>
                  </span>
                  <span className="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-950">Demo</span>
                </button>
              </li>
            );
          })}
        </ul>
      </main>
    </PublicFrame>
  );
}
