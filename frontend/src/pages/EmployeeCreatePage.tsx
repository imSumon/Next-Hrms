import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { identityErrorList, type IdentityErrors } from '../lib/employeeForm';
import { readPreviewState } from '../lib/previewNavigation';
import { useSession } from '../session';

const fieldClass = 'mt-2 w-full rounded-md border px-3 py-2';

export function EmployeeCreatePage() {
  const { company, createEmployee } = useSession();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const [code, setCode] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [errors, setErrors] = useState<IdentityErrors>({});

  useEffect(() => {
    document.title = 'Add employee — Universal HRMS';
  }, []);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/employees', search: previewState.directorySearch ?? '' };
  const messages = identityErrorList(errors);

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Employees
        </Link>
        <span> / Add employee</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Add employee</h1>
      <p className="mt-1 text-stone-500">{company.name}. The company is the one selected for this preview.</p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={directoryTarget}>
          Back to directory
        </Link>
      </p>
      <p className="mt-4 max-w-3xl text-sm text-stone-600">
        Employee code and display name create the employee identity. Joining date and episode status belong to an employment episode and are not part of this form.
      </p>
      <div className="mt-4">
        <PreviewSaveNote />
      </div>

      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = createEmployee(code, displayName);
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(`/admin/employees/${result.employee.id}`, { state: { ...previewState, notice: 'saved' } });
        }}
      >
        <FormAlert errors={messages} />
        <div>
          <label className="block text-sm font-semibold" htmlFor="employee_code">
            Employee code <span className="font-normal text-stone-500">(required)</span>
          </label>
          <input
            id="employee_code"
            name="employee_code"
            value={code}
            autoComplete="off"
            aria-invalid={errors.employee_code ? true : undefined}
            aria-describedby={errors.employee_code ? 'employee_code-error' : undefined}
            onChange={(event) => setCode(event.target.value)}
            className={`${fieldClass} ${errors.employee_code ? 'border-red-600' : 'border-stone-300'}`}
          />
          {errors.employee_code ? (
            <p id="employee_code-error" className="mt-2 text-sm text-red-700">
              {errors.employee_code}
            </p>
          ) : null}
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
            className={`${fieldClass} ${errors.display_name ? 'border-red-600' : 'border-stone-300'}`}
          />
          {errors.display_name ? (
            <p id="display_name-error" className="mt-2 text-sm text-red-700">
              {errors.display_name}
            </p>
          ) : null}
        </div>
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save employee
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={directoryTarget}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
