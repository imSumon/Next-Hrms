import { useEffect } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { BranchAuthorizationNote } from '../components/BranchAuthorizationNote';
import { BranchStatusBadge } from '../components/BranchStatusBadge';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { readPreviewState, withoutNotice, type PreviewNotice } from '../lib/previewNavigation';
import { useSession } from '../session';

function noticeText(notice: PreviewNotice | undefined): string | null {
  if (notice === 'branch-saved') {
    return 'Branch saved in this preview.';
  }
  if (notice === 'branch-updated') {
    return 'Branch updated in this preview.';
  }
  return null;
}

export function BranchDetailsPage() {
  const { company, branches } = useSession();
  const { branchId = '' } = useParams();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const branch = company ? branches.find((row) => row.id === branchId && row.companyId === company.id) : undefined;
  const message = noticeText(previewState.notice);

  useEffect(() => {
    document.title = branch ? `${branch.name} — Universal HRMS` : 'Branch — Universal HRMS';
  }, [branch]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/branches', search: previewState.directorySearch ?? '' };

  if (!branch) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Branches</p>
        <h1 className="mt-1 text-2xl font-semibold">Branch</h1>
        <p className="mt-4 text-stone-600">This branch is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={directoryTarget}>
            Back to directory
          </Link>
        </p>
      </div>
    );
  }

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Branches
        </Link>
        <span> / {branch.name}</span>
      </p>
      <div className="mt-1 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold text-stone-900">{branch.name}</h1>
          <p className="mt-1 text-stone-500">{company.name}</p>
        </div>
        <Link
          className="inline-flex min-h-10 items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-800"
          to={`/admin/branches/${branch.id}/edit`}
          state={withoutNotice(previewState)}
        >
          Edit
        </Link>
      </div>

      {message ? (
        <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950" role="status">
          <p className="font-semibold">{message}</p>
          <div className="mt-2">
            <PreviewSaveNote />
          </div>
        </div>
      ) : null}

      <div className="mt-5 space-y-2">
        <BranchAuthorizationNote />
        <p className="max-w-3xl text-sm text-stone-600">This branch belongs to {company.name}. It does not assign an employee to the branch.</p>
      </div>

      <dl className="mt-5 max-w-2xl divide-y divide-stone-200 rounded-xl border border-stone-200 bg-white shadow-sm">
        <div className="grid gap-1 px-4 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
          <dt className="text-sm font-semibold text-stone-500">Branch name</dt>
          <dd>{branch.name}</dd>
        </div>
        <div className="grid gap-1 px-4 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
          <dt className="text-sm font-semibold text-stone-500">Branch code</dt>
          <dd>{branch.code ?? '—'}</dd>
        </div>
        <div className="grid gap-1 px-4 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
          <dt className="text-sm font-semibold text-stone-500">Status</dt>
          <dd>
            <BranchStatusBadge status={branch.status} />
          </dd>
        </div>
        <div className="grid gap-1 px-4 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
          <dt className="text-sm font-semibold text-stone-500">Company</dt>
          <dd>{company.name}</dd>
        </div>
      </dl>

      <p className="mt-5">
        <Link className="font-semibold text-indigo-700" to={directoryTarget}>
          Back to directory
        </Link>
      </p>
    </div>
  );
}
