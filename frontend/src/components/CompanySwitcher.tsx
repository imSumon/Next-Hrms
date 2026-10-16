import { useLocation, useNavigate } from 'react-router-dom';
import { companyById } from '../data/fixtures';
import { useSession } from '../session';

export function CompanySwitcher() {
  const { person, company, selectCompany } = useSession();
  const navigate = useNavigate();
  const location = useLocation();

  if (!person || !company) {
    return null;
  }

  const memberships = person.memberships.flatMap((membership) => {
    const match = companyById(membership.companyId);
    return match ? [match] : [];
  });

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
      }}
    >
      <label className="block text-xs font-semibold text-stone-500" htmlFor="company_id">
        Company
      </label>
      <select
        id="company_id"
        className="mt-1 min-w-44 rounded-md border border-stone-300 bg-white px-2 py-1.5 text-sm text-stone-900"
        value={company.id}
        onChange={(event) => {
          const nextCompanyId = event.target.value;
          selectCompany(nextCompanyId);
          const pathname = location.pathname.startsWith('/ess')
            ? '/ess'
            : location.pathname.startsWith('/admin/branches')
              ? '/admin/branches'
              : location.pathname.startsWith('/admin/organization')
                ? '/admin/organization'
                : '/admin/employees';
          navigate(pathname, { state: { previewCompanyId: nextCompanyId } });
        }}
      >
        {memberships.map((membership) => (
          <option key={membership.id} value={membership.id}>
            {membership.name}
          </option>
        ))}
      </select>
    </form>
  );
}
