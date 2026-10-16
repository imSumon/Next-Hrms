import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { EpisodeFields } from '../components/EpisodeFields';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { episodeErrorList, pendingEpisodePolicyNote, rejoinGuidance, statusesForEdit, type EpisodeErrors } from '../lib/episodeForm';
import { readPreviewState, withoutNotice } from '../lib/previewNavigation';
import { useSession } from '../session';

export function EpisodeCreatePage() {
  const { company, employees, createEpisode } = useSession();
  const { employeeId = '' } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const quietState = withoutNotice(previewState);
  const employee = company ? employees.find((row) => row.id === employeeId && row.companyId === company.id) : undefined;
  const [joiningDate, setJoiningDate] = useState('');
  const [lastWorkingDate, setLastWorkingDate] = useState('');
  const [status, setStatus] = useState('Open');
  const [errors, setErrors] = useState<EpisodeErrors>({});

  useEffect(() => {
    document.title = 'Add employment episode — Universal HRMS';
  }, []);

  if (!company) {
    return null;
  }

  const profileTarget = { pathname: `/admin/employees/${employeeId}`, state: quietState };

  if (!employee) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Employees</p>
        <h1 className="mt-1 text-2xl font-semibold">Add employment episode</h1>
        <p className="mt-4 text-stone-600">This employee is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={{ pathname: '/admin/employees', search: previewState.directorySearch ?? '' }}>
            Back to directory
          </Link>
        </p>
      </div>
    );
  }

  const messages = episodeErrorList(errors);

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={{ pathname: '/admin/employees', search: previewState.directorySearch ?? '' }}>
          Employees
        </Link>
        <span> / </span>
        <Link className="text-indigo-700" to={profileTarget.pathname} state={quietState}>
          {employee.displayName}
        </Link>
        <span> / Add employment episode</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Add employment episode</h1>
      <p className="mt-1 text-stone-500">
        {employee.displayName} · {employee.code}
      </p>
      <p className="mt-1 text-stone-500">{company.name}. The employee identity and company stay fixed for this form.</p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={profileTarget.pathname} state={quietState}>
          Back to profile
        </Link>
      </p>
      <p className="mt-4 max-w-3xl text-sm text-stone-600">{pendingEpisodePolicyNote}</p>
      <p className="mt-2 max-w-3xl text-sm text-stone-600">{rejoinGuidance}</p>
      <div className="mt-4">
        <PreviewSaveNote />
      </div>
      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = createEpisode(employee.id, { joiningDate, lastWorkingDate, status });
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(`/admin/employees/${employee.id}`, { state: { ...quietState, notice: 'episode-saved' } });
        }}
      >
        <FormAlert errors={messages} />
        <EpisodeFields
          joiningDate={joiningDate}
          lastWorkingDate={lastWorkingDate}
          status={status}
          statuses={statusesForEdit(null)}
          errors={errors}
          onJoiningDate={setJoiningDate}
          onLastWorkingDate={setLastWorkingDate}
          onStatus={setStatus}
        />
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save episode
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={profileTarget.pathname} state={quietState}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
