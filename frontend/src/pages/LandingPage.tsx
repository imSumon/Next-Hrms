import { History, Network, UserRound, Users } from 'lucide-react';
import { useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { PublicFrame } from '../components/PublicFrame';
import { useSession } from '../session';

const features = [
  {
    title: 'Employee Management',
    icon: Users,
    text: 'A company-scoped employee directory and identity record. The employee code stays with that identity inside the company.',
    state: 'In this preview',
  },
  {
    title: 'Employment History',
    icon: History,
    text: 'Employment episodes keep joining, inactive, separated, and rejoin history apart from the employee identity.',
    state: 'In this preview',
  },
  {
    title: 'Organization Structure',
    icon: Network,
    text: 'A read-only view of organization nodes and the parent link stored for each node. Departments, designations, and employee groups are separate specified records and are not edited here.',
    state: 'In this preview, read only',
  },
  {
    title: 'Employee Self-Service',
    icon: UserRound,
    text: 'A person can open their own profile for the selected company. The employee preview does not include administration.',
    state: 'In this preview',
  },
] as const;

export function LandingPage() {
  const { person, company, leavePreview } = useSession();
  const location = useLocation();
  const navigate = useNavigate();
  const ending = Boolean(location.state && typeof location.state === 'object' && 'endPreview' in location.state && (location.state as { endPreview?: boolean }).endPreview);

  useEffect(() => {
    document.title = 'Universal HRMS';
  }, []);

  useEffect(() => {
    if (!ending) {
      return;
    }
    leavePreview();
    navigate('/', { replace: true });
  }, [ending, leavePreview, navigate]);

  const continueTarget = !ending && person && company ? '/admin/employees' : !ending && person ? '/preview/company' : null;

  return (
    <PublicFrame>
      <main>
        <section className="border-b border-stone-200 bg-[#111827] text-white">
          <div className="mx-auto grid w-full max-w-6xl gap-8 px-4 py-16 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.8fr)] lg:items-end">
            <div>
              <p className="text-sm font-semibold uppercase tracking-wide text-indigo-200">Universal HRMS</p>
              <h1 className="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                Workforce and organization records, held in the company that owns them.
              </h1>
              <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-200">
                Universal HRMS is one product for employee identity, employment history, organization structure, and employee self-service. Each company keeps its own records.
              </p>
              <div className="mt-8 flex flex-wrap gap-3">
                <Link className="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-[#111827]" to="/preview/sign-in">
                  Sign in to Preview
                </Link>
                <Link className="rounded-md border border-white/30 px-4 py-2.5 text-sm font-semibold text-white" to="/session/employees">
                  Laravel session directory
                </Link>
                <a className="rounded-md border border-white/30 px-4 py-2.5 text-sm font-semibold text-white" href="#features">
                  Explore Features
                </a>
              </div>
              <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-300">
                The Laravel session directory uses the existing Laravel sign-in. Choosing a demo persona does not open it.
              </p>
            </div>
            <div className="rounded-xl border border-white/10 bg-white/5 p-5 text-sm leading-6 text-slate-200">
              <p className="font-semibold text-white">What this site is</p>
              <p className="mt-2">A preview you can open without a password. It is not a production sign-in, and it does not grant access.</p>
              {continueTarget ? (
                <p className="mt-4">
                  <Link
                    className="font-semibold text-white underline decoration-white/40 underline-offset-4"
                    to={continueTarget}
                    state={company ? { previewCompanyId: company.id } : undefined}
                  >
                    Continue the open preview
                  </Link>
                </p>
              ) : null}
            </div>
          </div>
        </section>

        <section className="mx-auto w-full max-w-6xl scroll-mt-24 px-4 py-16" id="features">
          <h2 className="text-2xl font-semibold text-stone-900">Features</h2>
          <p className="mt-3 max-w-3xl text-stone-600">
            The highlights below are the areas represented in this preview. Later product areas are named separately so they are not mistaken for screens you can use today.
          </p>
          <ul className="mt-8 grid gap-4 md:grid-cols-2">
            {features.map((feature) => {
              const Icon = feature.icon;
              return (
                <li key={feature.title} className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                  <div className="flex items-start gap-3">
                    <Icon className="mt-0.5 text-indigo-700" size={22} aria-hidden="true" />
                    <div>
                      <h3 className="text-lg font-semibold text-stone-900">{feature.title}</h3>
                      <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-700">{feature.state}</p>
                      <p className="mt-3 text-sm leading-6 text-stone-600">{feature.text}</p>
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        </section>

        <section className="border-t border-stone-200 bg-white">
          <div className="mx-auto grid w-full max-w-6xl gap-8 px-4 py-14 md:grid-cols-2">
            <div>
              <h2 className="text-xl font-semibold text-stone-900">In this preview</h2>
              <ul className="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-stone-600">
                <li>Employee directory, profile, and preview edits for the selected company.</li>
                <li>Employment episodes, including rejoin as a new episode.</li>
                <li>Branch records, kept separate from organization nodes.</li>
                <li>Read-only organization nodes and their parent links.</li>
                <li>An employee profile in the employee preview.</li>
              </ul>
            </div>
            <div>
              <h2 className="text-xl font-semibold text-stone-900">Not in this preview</h2>
              <ul className="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-stone-600">
                <li>Real authentication, passwords, and production permissions.</li>
                <li>Creating, editing, moving, or deleting organization nodes.</li>
                <li>Employee assignment to a branch, department, or organization node.</li>
                <li>Attendance, leave, and payroll.</li>
              </ul>
            </div>
          </div>
        </section>
      </main>
    </PublicFrame>
  );
}
