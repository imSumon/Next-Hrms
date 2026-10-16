import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { BranchAuthorizationNote } from '../components/BranchAuthorizationNote';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { branchErrorList, type BranchErrors } from '../lib/branchForm';
import { readPreviewState } from '../lib/previewNavigation';
import { useSession } from '../session';

const fieldClass = 'mt-2 w-full rounded-md border px-3 py-2';

export function BranchCreatePage() {
  const { company, createBranch } = useSession();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const [name, setName] = useState('');
  const [code, setCode] = useState('');
  const [errors, setErrors] = useState<BranchErrors>({});

  useEffect(() => {
    document.title = 'Add branch — Universal HRMS';
  }, []);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/branches', search: previewState.directorySearch ?? '' };
  const messages = branchErrorList(errors);

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Branches
        </Link>
        <span> / Add branch</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Add branch</h1>
      <p className="mt-1 text-stone-500">{company.name}. The company stays fixed for this form.</p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={directoryTarget}>
          Back to directory
        </Link>
      </p>
      <div className="mt-4 space-y-2">
        <BranchAuthorizationNote />
        <p className="max-w-3xl text-sm text-stone-600">
          A new branch starts as Active. Saving it does not assign an employee to the branch. The branch code cannot be changed after it is saved.
        </p>
        <PreviewSaveNote />
      </div>

      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = createBranch(name, code);
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(`/admin/branches/${result.branch.id}`, { state: { ...previewState, notice: 'branch-saved' } });
        }}
      >
        <FormAlert errors={messages} />
        <div>
          <label className="block text-sm font-semibold" htmlFor="branch_name">
            Branch name <span className="font-normal text-stone-500">(required)</span>
          </label>
          <input
            id="branch_name"
            name="branch_name"
            value={name}
            autoComplete="off"
            aria-invalid={errors.name ? true : undefined}
            aria-describedby={errors.name ? 'branch_name-error' : undefined}
            onChange={(event) => setName(event.target.value)}
            className={`${fieldClass} ${errors.name ? 'border-red-600' : 'border-stone-300'}`}
          />
          {errors.name ? (
            <p id="branch_name-error" className="mt-2 text-sm text-red-700">
              {errors.name}
            </p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-semibold" htmlFor="branch_code">
            Branch code <span className="font-normal text-stone-500">(optional)</span>
          </label>
          <input
            id="branch_code"
            name="branch_code"
            value={code}
            autoComplete="off"
            aria-invalid={errors.code ? true : undefined}
            aria-describedby={errors.code ? 'branch_code-hint branch_code-error' : 'branch_code-hint'}
            onChange={(event) => setCode(event.target.value)}
            className={`${fieldClass} ${errors.code ? 'border-red-600' : 'border-stone-300'}`}
          />
          <p id="branch_code-hint" className="mt-2 text-sm text-stone-600">
            Leave this blank when the branch has no code. When a code is entered, it must be unique in {company.name}.
          </p>
          {errors.code ? (
            <p id="branch_code-error" className="mt-2 text-sm text-red-700">
              {errors.code}
            </p>
          ) : null}
        </div>
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save branch
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={directoryTarget}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
