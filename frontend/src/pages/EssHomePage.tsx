import { useEffect } from 'react';
import { useSession } from '../session';

export function EssHomePage() {
  const { person, company, employees } = useSession();

  useEffect(() => {
    document.title = 'My profile — Universal HRMS';
  }, []);

  if (!person || !company) {
    return null;
  }

  const membership = person.memberships.find((item) => item.companyId === company.id);
  const employee = membership?.employeeId
    ? employees.find((row) => row.id === membership.employeeId && row.companyId === company.id)
    : undefined;

  return (
    <div>
      <p className="text-sm font-semibold text-teal-800">Hello</p>
      <h1 className="mt-1 text-3xl font-semibold text-stone-900">{person.name}</h1>
      <p className="mt-2 text-stone-600">Selected company: {company.name}</p>
      <section className="mt-6 rounded-2xl border border-[#e6dfd4] bg-white p-5 shadow-sm">
        <h2 className="text-lg font-semibold">Your employee identity</h2>
        {employee && employee.companyId === company.id ? (
          <dl className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <dt className="text-sm font-semibold text-stone-500">Display name</dt>
              <dd className="mt-1">{employee.displayName}</dd>
            </div>
            <div>
              <dt className="text-sm font-semibold text-stone-500">Employee code</dt>
              <dd className="mt-1">{employee.code}</dd>
            </div>
            <div>
              <dt className="text-sm font-semibold text-stone-500">Company</dt>
              <dd className="mt-1">{company.name}</dd>
            </div>
          </dl>
        ) : (
          <p className="mt-3 text-stone-600">No employee profile is stored for this person in this company.</p>
        )}
        <p className="mt-4 text-sm text-stone-500">This preview shows synthetic identity details. It does not grant access, pay, leave, or attendance.</p>
      </section>
    </div>
  );
}
