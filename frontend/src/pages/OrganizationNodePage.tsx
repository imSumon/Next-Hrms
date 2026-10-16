import { useEffect } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { organizationNodes } from '../data/fixtures';
import { childNodes, nodesForCompany } from '../lib/organizationDirectory';
import { readPreviewState } from '../lib/previewNavigation';
import { useSession } from '../session';

function Field({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-sm text-stone-500">{label}</dt>
      <dd className="mt-1 text-stone-900">{value}</dd>
    </div>
  );
}

export function OrganizationNodePage() {
  const { company } = useSession();
  const { nodeId = '' } = useParams();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const scoped = company ? nodesForCompany(organizationNodes, company.id) : [];
  const node = scoped.find((row) => row.id === nodeId);

  useEffect(() => {
    document.title = node ? `${node.id} — Universal HRMS` : 'Organization node — Universal HRMS';
  }, [node]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/organization', search: previewState.directorySearch ?? '' };

  if (!node) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Organization</p>
        <h1 className="mt-1 text-2xl font-semibold text-stone-900">Organization node</h1>
        <p className="mt-4 text-stone-600">This organization node is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={directoryTarget}>
            Back to organization
          </Link>
        </p>
      </div>
    );
  }

  const children = childNodes(scoped, node.id);
  const parent = node.parentId ? scoped.find((row) => row.id === node.parentId) : undefined;

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Organization
        </Link>
        <span> / {node.id}</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">{node.id}</h1>
      <p className="mt-1 text-stone-500">{company.name}</p>
      <div className="mt-4 max-w-3xl space-y-2 text-sm leading-6 text-stone-600">
        <p>This node has no stored name, code, status, or type. Recorded and updated times are not shown.</p>
        <p>It is not a branch, and it does not assign an employee. Creating, editing, moving, and deleting nodes are not in this preview.</p>
      </div>
      <dl className="mt-6 grid max-w-3xl gap-4 rounded-xl border border-stone-200 bg-white p-5 sm:grid-cols-2">
        <Field label="Node" value={node.id} />
        <Field label="Company" value={company.name} />
        <div>
          <dt className="text-sm text-stone-500">Parent</dt>
          <dd className="mt-1 text-stone-900">
            {node.parentId === null ? (
              'No parent'
            ) : parent ? (
              <Link className="font-semibold text-indigo-700" to={`/admin/organization/${parent.id}`} state={previewState}>
                {parent.id}
              </Link>
            ) : (
              node.parentId
            )}
          </dd>
        </div>
        <Field label="Source system" value={node.sourceSystem ?? '—'} />
        <Field label="Source key" value={node.sourceKey ?? '—'} />
      </dl>
      <section className="mt-6">
        <h2 className="text-lg font-semibold text-stone-900">Child nodes</h2>
        {children.length === 0 ? (
          <p className="mt-2 text-stone-600">No child nodes.</p>
        ) : (
          <ul className="mt-3 space-y-2">
            {children.map((child) => (
              <li key={child.id}>
                <Link className="font-semibold text-indigo-700" to={`/admin/organization/${child.id}`} state={previewState}>
                  {child.id}
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>
      <p className="mt-6">
        <Link className="font-semibold text-indigo-700" to={directoryTarget}>
          Back to organization
        </Link>
      </p>
    </div>
  );
}
