import { Navigate, Route, Routes } from 'react-router-dom';
import { CompanyHistory } from './components/CompanyHistory';
import { RequirePreview } from './components/RequirePreview';
import { BranchCreatePage } from './pages/BranchCreatePage';
import { BranchDetailsPage } from './pages/BranchDetailsPage';
import { BranchDirectoryPage } from './pages/BranchDirectoryPage';
import { BranchEditPage } from './pages/BranchEditPage';
import { ChooseCompanyPage } from './pages/ChooseCompanyPage';
import { DemoEntryPage } from './pages/DemoEntryPage';
import { EmployeeCreatePage } from './pages/EmployeeCreatePage';
import { EmployeeDirectoryPage } from './pages/EmployeeDirectoryPage';
import { EmployeeEditPage } from './pages/EmployeeEditPage';
import { EmployeeProfilePage } from './pages/EmployeeProfilePage';
import { EpisodeCreatePage } from './pages/EpisodeCreatePage';
import { EpisodeEditPage } from './pages/EpisodeEditPage';
import { EssHomePage } from './pages/EssHomePage';
import { LandingPage } from './pages/LandingPage';
import { OrganizationNodePage } from './pages/OrganizationNodePage';
import { OrganizationPage } from './pages/OrganizationPage';
import { PreviewMissingPage } from './pages/PreviewMissingPage';
import { RealSessionDirectoryPage } from './pages/RealSessionDirectoryPage';
import { RealSessionDesignationsPage, RealSessionDepartmentsPage } from './pages/RealSessionMasterPage';
import { RealSessionProfilePage } from './pages/RealSessionProfilePage';
import { SignInPage } from './pages/SignInPage';
import { AdminShell } from './shells/AdminShell';
import { EssShell } from './shells/EssShell';

export function App() {
  return (
    <>
      <CompanyHistory />
      <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/session/employees" element={<RealSessionDirectoryPage />} />
      <Route path="/session/employees/:employeeId" element={<RealSessionProfilePage />} />
      <Route path="/session/departments" element={<RealSessionDepartmentsPage />} />
      <Route path="/session/departments/:recordId" element={<RealSessionDepartmentsPage />} />
      <Route path="/session/designations" element={<RealSessionDesignationsPage />} />
      <Route path="/session/designations/:recordId" element={<RealSessionDesignationsPage />} />
      <Route path="/preview/sign-in" element={<SignInPage />} />
      <Route path="/preview/demo" element={<DemoEntryPage />} />
      <Route path="/preview/company" element={<ChooseCompanyPage />} />
      <Route
        path="/admin"
        element={
          <RequirePreview>
            <AdminShell />
          </RequirePreview>
        }
      >
        <Route index element={<Navigate to="employees" replace />} />
        <Route path="employees" element={<EmployeeDirectoryPage />} />
        <Route path="employees/new" element={<EmployeeCreatePage />} />
        <Route path="employees/:employeeId/episodes/new" element={<EpisodeCreatePage />} />
        <Route path="employees/:employeeId/episodes/:episodeId/edit" element={<EpisodeEditPage />} />
        <Route path="employees/:employeeId" element={<EmployeeProfilePage />} />
        <Route path="employees/:employeeId/edit" element={<EmployeeEditPage />} />
        <Route path="branches" element={<BranchDirectoryPage />} />
        <Route path="branches/new" element={<BranchCreatePage />} />
        <Route path="branches/:branchId" element={<BranchDetailsPage />} />
        <Route path="branches/:branchId/edit" element={<BranchEditPage />} />
        <Route path="organization" element={<OrganizationPage />} />
        <Route path="organization/:nodeId" element={<OrganizationNodePage />} />
        <Route path="*" element={<PreviewMissingPage home="/admin/employees" homeLabel="Back to employees" />} />
      </Route>
      <Route
        path="/ess"
        element={
          <RequirePreview>
            <EssShell />
          </RequirePreview>
        }
      >
        <Route index element={<EssHomePage />} />
        <Route
          path="*"
          element={<PreviewMissingPage home="/ess" homeLabel="Back to my profile" linkClassName="font-semibold text-teal-800" />}
        />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
    </>
  );
}
