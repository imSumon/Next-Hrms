import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { CompanySwitcher } from '../components/CompanySwitcher';
import { PreviewMark } from '../components/PreviewMark';
import { useSession } from '../session';

export function EssShell() {
  const { person, company } = useSession();
  const navigate = useNavigate();

  if (!person || !company) {
    return null;
  }

  return (
    <div className="min-h-screen bg-[#f6f3ee] text-stone-900">
      <header className="border-b border-[#e6dfd4] bg-[#fffaf4]">
        <div className="mx-auto flex w-full max-w-3xl flex-wrap items-center gap-4 px-4 py-4">
          <div>
            <p className="text-lg font-semibold text-teal-800">Universal HRMS</p>
            <p className="text-sm text-stone-600">Employee preview</p>
          </div>
          <CompanySwitcher />
          <div className="ml-auto flex flex-wrap items-center gap-3">
            <PreviewMark name={person.name} />
            <button
              type="button"
              className="text-sm font-semibold text-teal-800"
              onClick={() => {
                navigate('/', { state: { endPreview: true } });
              }}
            >
              Leave preview
            </button>
          </div>
        </div>
        <nav className="mx-auto flex w-full max-w-3xl gap-4 px-4 pb-3 text-sm" aria-label="Employee">
          <NavLink
            to="/ess"
            end
            className={({ isActive }) => (isActive ? 'border-b-2 border-teal-700 pb-1 font-semibold text-teal-900' : 'pb-1 text-stone-600')}
          >
            {({ isActive }) => (
              <>
                My profile
                {isActive ? <span className="sr-only">Current page</span> : null}
              </>
            )}
          </NavLink>
        </nav>
      </header>
      <main className="mx-auto w-full max-w-3xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
}
