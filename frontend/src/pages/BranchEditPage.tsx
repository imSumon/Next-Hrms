import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { BranchAuthorizationNote } from '../components/BranchAuthorizationNote';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { branchErrorList, branchStatuses, type BranchErrors } from '../lib/branchForm';
import { readPreviewState, withoutNotice } from '../lib/previewNavigation';
import { useSession } from '../session';

export function BranchEditPage() {
  const { company, branches, updateBranch } = useSession();
  const { branchId = '' } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const quietState = withoutNotice(previewState);
  const branch = company ? branches.find((row) => row.id === branchId && row.companyId === company.id) : undefined;
  const [name, setName] = useState(branch?.name ?? '');
  const [status, setStatus] = useState<string>(branch?.status ?? 'Active');
  const [errors, setErrors] = useState<BranchErrors>({});

  useEffect(() => {
    if (branch) {
      setName(branch.name);
      setStatus(branch.status);
      setErrors({});
    }
  }, [branch?.id]);

  useEffect(() => {
    document.title = branch ? `Edit ${branch.name} — Universal HRMS` : 'Edit branch — Universal HRMS';
  }, [branch]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/branches', search: previewState.directorySearch ?? '' };

  if (!branch) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Branches</p>
        <h1 className="mt-1 text-2xl font-semibold">Edit branch</h1>
        <p className="mt-4 text-stone-600">This branch is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={directoryTarget}>
            Back to directory
          </Link>
        </p>
      </div>
    );
  }

  const messages = branchErrorList(errors);
  const detailsPath = `/admin/branches/${branch.id}`;

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Branches
        </Link>
        <span> / </span>
        <Link className="text-indigo-700" to={detailsPath} state={quietState}>
          {branch.name}
        </Link>
        <span> / Edit</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Edit {branch.name}</h1>
      <p className="mt-1 text-stone-500">{company.name}. The company stays fixed for this form.</p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={detailsPath} state={quietState}>
          Back to branch
        </Link>
      </p>
      <div className="mt-4 space-y-2">
        <BranchAuthorizationNote />
        <p className="max-w-3xl text-sm text-stone-600">Withdrawal keeps the branch record. It does not delete it, and it does not assign an employee to the branch.</p>
        <PreviewSaveNote />
      </div>

      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = updateBranch(branch.id, name, status);
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(detailsPath, { state: { ...quietState, notice: 'branch-updated' } });
        }}
      >
        <FormAlert errors={messages} />
        <div>
          <p className="text-sm font-semibold text-stone-500">Branch code</p>
          <p className="mt-1">{branch.code ?? '—'}</p>
          <p className="mt-2 text-sm text-stone-600">The branch code cannot be changed.</p>
        </div>
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
            className={`mt-2 w-full rounded-md border px-3 py-2 ${errors.name ? 'border-red-600' : 'border-stone-300'}`}
          />
          {errors.name ? (
            <p id="branch_name-error" className="mt-2 text-sm text-red-700">
              {errors.name}
            </p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-semibold" htmlFor="branch_status">
            Status <span className="font-normal text-stone-500">(required)</span>
          </label>
          <select
            id="branch_status"
            name="branch_status"
            value={status}
            aria-invalid={errors.status ? true : undefined}
            aria-describedby={errors.status ? 'branch_status-error' : undefined}
            onChange={(event) => setStatus(event.target.value)}
            className={`mt-2 w-full rounded-md border bg-white px-3 py-2 ${errors.status ? 'border-red-600' : 'border-stone-300'}`}
          >
            {branchStatuses.map((value) => (
              <option key={value} value={value}>
                {value}
              </option>
            ))}
          </select>
          {errors.status ? (
            <p id="branch_status-error" className="mt-2 text-sm text-red-700">
              {errors.status}
            </p>
          ) : null}
        </div>
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save branch
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={detailsPath} state={quietState}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
