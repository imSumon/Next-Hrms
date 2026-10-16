import { useEffect, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import { organizationNodes } from '../data/fixtures';
import {
  childNodes,
  detachedNodes,
  emptyOrganizationMessage,
  filterOrganizationNodes,
  nodesForCompany,
  rootNodes,
} from '../lib/organizationDirectory';
import { readPreviewState, type PreviewLocationState } from '../lib/previewNavigation';
import { useSession } from '../session';

function HierarchyList({
  nodes,
  parentId,
  linkState,
}: {
  nodes: ReturnType<typeof nodesForCompany>;
  parentId: string | null;
  linkState: PreviewLocationState;
}) {
  const children = parentId === null ? rootNodes(nodes) : childNodes(nodes, parentId);
  if (children.length === 0) {
    return null;
  }
  return (
    <ul className={parentId === null ? 'space-y-3' : 'mt-2 space-y-2 border-l border-stone-200 pl-4'}>
      {children.map((node) => (
        <li key={node.id}>
          <Link className="font-semibold text-indigo-700" state={linkState} to={`/admin/organization/${node.id}`}>
            {node.id}
          </Link>
          <HierarchyList linkState={linkState} nodes={nodes} parentId={node.id} />
        </li>
      ))}
    </ul>
  );
}

export function OrganizationPage() {
  const { company } = useSession();
  const location = useLocation();
  const [params, setParams] = useSearchParams();
  const rawQuery = params.get('q') ?? '';
  const [draft, setDraft] = useState(rawQuery);
  const previewState = readPreviewState(location.state);

  useEffect(() => {
    document.title = 'Organization — Universal HRMS';
  }, []);

  useEffect(() => {
    setDraft(rawQuery);
  }, [rawQuery]);

  if (!company) {
    return null;
  }

  const scoped = nodesForCompany(organizationNodes, company.id);
  const matched = filterOrganizationNodes(scoped, rawQuery);
  const roots = rootNodes(scoped);
  const detached = detachedNodes(scoped);
  const listState: PreviewLocationState = {
    directorySearch: location.search,
    previewCompanyId: previewState.previewCompanyId ?? company.id,
  };

  return (
    <div>
      <p className="text-sm text-stone-500">Admin / Organization</p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Organization</h1>
      <p className="mt-1 text-stone-500">{company.name}</p>
      <div className="mt-4 max-w-3xl space-y-3 text-sm leading-6 text-stone-600">
        <p>
          An organization node in this preview stores an identifier, the company, an optional parent in the same company, and an optional source identity. It does not store a name, a code, a status, or a node type.
        </p>
        <p>
          Departments, designations, and employee groups are separate records in the target specification. They are not listed here. Branches stay in Branch Management. A location record under a branch is not an organization node. This screen does not assign an employee.
        </p>
        <p>Creating, editing, moving, and deleting organization nodes are not in this preview. Recorded and updated times are not shown.</p>
      </div>

      <dl className="mt-6 grid gap-3 sm:grid-cols-2">
        <div className="rounded-xl border border-stone-200 bg-white p-4">
          <dt className="text-sm text-stone-500">Organization nodes</dt>
          <dd className="mt-1 text-2xl font-semibold text-stone-900">{scoped.length}</dd>
        </div>
        <div className="rounded-xl border border-stone-200 bg-white p-4">
          <dt className="text-sm text-stone-500">Nodes with no parent</dt>
          <dd className="mt-1 text-2xl font-semibold text-stone-900">{roots.length}</dd>
        </div>
      </dl>

      {scoped.length === 0 ? (
        <p className="mt-6 text-stone-600">No organization nodes in this company.</p>
      ) : (
        <>
          <section className="mt-8">
            <h2 className="text-lg font-semibold text-stone-900">Hierarchy</h2>
            <p className="mt-2 max-w-3xl text-sm text-stone-600">
              Parent and child links use the stored parent. Search filters the node list below and leaves this hierarchy complete.
            </p>
            <div className="mt-4 rounded-xl border border-stone-200 bg-white p-4">
              <HierarchyList linkState={listState} nodes={scoped} parentId={null} />
              {detached.length > 0 ? (
                <div className="mt-4">
                  <h3 className="text-sm font-semibold text-stone-900">Parent is not in this company</h3>
                  <ul className="mt-2 space-y-2">
                    {detached.map((node) => (
                      <li key={node.id}>
                        <Link className="font-semibold text-indigo-700" state={listState} to={`/admin/organization/${node.id}`}>
                          {node.id}
                        </Link>
                      </li>
                    ))}
                  </ul>
                </div>
              ) : null}
            </div>
          </section>

          <section className="mt-8">
            <h2 className="text-lg font-semibold text-stone-900">Node list</h2>
            <form
              className="mt-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
              onSubmit={(event) => {
                event.preventDefault();
                const next = new URLSearchParams();
                if (draft !== '') {
                  next.set('q', draft);
                }
                setParams(next);
              }}
            >
              <label className="block text-sm font-semibold" htmlFor="organization-q">
                Search by node, parent, source system, or source key
              </label>
              <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                <input
                  id="organization-q"
                  className="w-full rounded-md border border-stone-300 px-3 py-2"
                  value={draft}
                  onChange={(event) => setDraft(event.target.value)}
                />
                <button type="submit" className="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">
                  Search
                </button>
              </div>
            </form>
            {matched.length === 0 ? (
              <p className="mt-4 text-stone-600">{emptyOrganizationMessage(rawQuery)}</p>
            ) : (
              <div className="mt-4 overflow-x-auto rounded-xl border border-stone-200 bg-white">
                <table className="min-w-full text-left text-sm">
                  <thead className="border-b border-stone-200 text-stone-500">
                    <tr>
                      <th className="px-4 py-3 font-semibold" scope="col">
                        Node
                      </th>
                      <th className="px-4 py-3 font-semibold" scope="col">
                        Parent
                      </th>
                      <th className="px-4 py-3 font-semibold" scope="col">
                        Source system
                      </th>
                      <th className="px-4 py-3 font-semibold" scope="col">
                        Source key
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {matched.map((node) => (
                      <tr key={node.id} className="border-b border-stone-100 last:border-0">
                        <td className="px-4 py-3">
                          <Link className="font-semibold text-indigo-700" to={`/admin/organization/${node.id}`} state={listState}>
                            {node.id}
                          </Link>
                        </td>
                        <td className="px-4 py-3">{node.parentId ?? '—'}</td>
                        <td className="px-4 py-3">{node.sourceSystem ?? '—'}</td>
                        <td className="px-4 py-3">{node.sourceKey ?? '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </>
      )}
    </div>
  );
}
