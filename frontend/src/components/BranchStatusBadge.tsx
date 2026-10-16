import type { BranchStatus } from '../data/fixtures';

export function BranchStatusBadge({ status }: { status: BranchStatus }) {
  const tone =
    status === 'Active'
      ? 'bg-emerald-50 text-emerald-950 ring-emerald-200'
      : 'bg-amber-50 text-amber-950 ring-amber-200';

  return <span className={`inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1 ring-inset ${tone}`}>{status}</span>;
}
