import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { identityErrorList, type IdentityErrors } from '../lib/employeeForm';
import { readPreviewState, withoutNotice } from '../lib/previewNavigation';
import { useSession } from '../session';

export function EmployeeEditPage() {
  const { company, employees, updateEmployeeName } = useSession();
  const { employeeId = '' } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const quietState = withoutNotice(previewState);
  const employee = company ? employees.find((row) => row.id === employeeId && row.companyId === company.id) : undefined;
  const [displayName, setDisplayName] = useState(employee?.displayName ?? '');
  const [errors, setErrors] = useState<IdentityErrors>({});

  useEffect(() => {
    if (employee) {
      setDisplayName(employee.displayName);
    }
  }, [employee?.id, employee?.displayName]);

  useEffect(() => {
    document.title = employee ? `Edit ${employee.displayName} — Universal HRMS` : 'Edit employee — Universal HRMS';
  }, [employee]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/employees', search: previewState.directorySearch ?? '' };

  if (!employee) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Employees</p>
        <h1 className="mt-1 text-2xl font-semibold">Edit employee</h1>
        <p className="mt-4 text-stone-600">This employee is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={directoryTarget}>
            Back to directory
          </Link>
        </p>
      </div>
    );
  }

  const messages = identityErrorList(errors);
  const profileTarget = { pathname: `/admin/employees/${employee.id}`, state: quietState };

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Employees
        </Link>
        <span> / </span>
        <Link className="text-indigo-700" to={profileTarget.pathname} state={quietState}>
          {employee.displayName}
        </Link>
        <span> / Edit</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Edit {employee.displayName}</h1>
      <p className="mt-1 text-stone-500">
        {company.name}. Employee code {employee.code} stays on this record.
      </p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={profileTarget.pathname} state={quietState}>
          Back to profile
        </Link>
      </p>

      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = updateEmployeeName(employee.id, displayName);
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(`/admin/employees/${employee.id}`, { state: { ...quietState, notice: 'updated' } });
        }}
      >
        <FormAlert errors={messages} />
        <div>
          <p className="text-sm font-semibold text-stone-500">Employee code</p>
          <p className="mt-1">{employee.code}</p>
          <p className="mt-2 text-sm text-stone-600">The employee code cannot be changed.</p>
        </div>
        <div>
          <label className="block text-sm font-semibold" htmlFor="display_name">
            Display name <span className="font-normal text-stone-500">(required)</span>
          </label>
          <input
            id="display_name"
            name="display_name"
            value={displayName}
            autoComplete="off"
            aria-invalid={errors.display_name ? true : undefined}
            aria-describedby={errors.display_name ? 'display_name-error' : undefined}
            onChange={(event) => setDisplayName(event.target.value)}
            className={`mt-2 w-full rounded-md border px-3 py-2 ${errors.display_name ? 'border-red-600' : 'border-stone-300'}`}
          />
          {errors.display_name ? (
            <p id="display_name-error" className="mt-2 text-sm text-red-700">
              {errors.display_name}
            </p>
          ) : null}
        </div>
        <PreviewSaveNote />
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={profileTarget.pathname} state={quietState}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
