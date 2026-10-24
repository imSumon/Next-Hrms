import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';

export function RealSessionLinks({ label, children }: { label: string; children?: ReactNode }) {
  return (
    <nav className="ml-auto flex flex-wrap items-center gap-4 text-sm" aria-label={label}>
      <Link className="font-semibold text-indigo-700" to="/session/employees">Employees</Link>
      <Link className="font-semibold text-indigo-700" to="/session/departments">Departments</Link>
      <Link className="font-semibold text-indigo-700" to="/session/designations">Designations</Link>
      <Link className="font-semibold text-indigo-700" to="/session/leave-types">Leave types</Link>
      <Link className="font-semibold text-indigo-700" to="/session/leave-positions">Leave positions</Link>
      <Link className="font-semibold text-indigo-700" to="/session/attendance/dashboard">Attendance</Link>
      {children}
    </nav>
  );
}
