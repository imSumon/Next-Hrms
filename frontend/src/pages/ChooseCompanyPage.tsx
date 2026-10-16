import { useEffect } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { companyById } from '../data/fixtures';
import { useSession } from '../session';

export function ChooseCompanyPage() {
  const { person, selectCompany } = useSession();
  const navigate = useNavigate();

  useEffect(() => {
    document.title = 'Choose a company — Universal HRMS';
  }, []);

  if (!person) {
    return <Navigate to="/preview/demo" replace />;
  }

  const memberships = person.memberships.flatMap((membership) => {
    const company = companyById(membership.companyId);
    return company ? [company] : [];
  });

  return (
    <main className="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center px-4 py-10">
      <p className="text-sm font-semibold text-amber-800">Preview — not an authorized role</p>
      <h1 className="mt-2 text-3xl font-semibold">Choose a company</h1>
      <p className="mt-3 text-stone-600">Only {person.name}&apos;s preview memberships are listed.</p>
      <form
        className="mt-6 rounded-xl border border-stone-200 bg-white p-4"
        onSubmit={(event) => {
          event.preventDefault();
          const data = new FormData(event.currentTarget);
          const companyId = String(data.get('company_id') ?? '');
          if (!memberships.some((company) => company.id === companyId)) {
            return;
          }
          selectCompany(companyId);
          navigate('/admin/employees', { state: { previewCompanyId: companyId } });
        }}
      >
        <label className="block text-sm font-semibold" htmlFor="company_id">
          Company
        </label>
        <select id="company_id" name="company_id" className="mt-2 w-full rounded-md border border-stone-300 px-3 py-2" required defaultValue={memberships[0]?.id}>
          {memberships.map((company) => (
            <option key={company.id} value={company.id}>
              {company.name}
            </option>
          ))}
        </select>
        <button type="submit" className="mt-4 rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
          Continue preview
        </button>
      </form>
    </main>
  );
}
