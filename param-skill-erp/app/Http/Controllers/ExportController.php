<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use App\Models\Employee;
use App\Models\Student;
use App\Services\CentreContext;
use App\Support\Format;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function centresCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->isElevated(), 403);

        $filename = 'centres-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Centre Code', 'Centre Name', 'Manager Name', 'Manager Mobile', 'District', 'Capacity', 'Hostel', 'Status', 'Created Date']);

            Centre::query()->orderBy('centre_code')->chunk(200, function ($centres) use ($handle) {
                foreach ($centres as $centre) {
                    fputcsv($handle, [
                        $centre->centre_code,
                        $centre->centre_name,
                        $centre->manager_name,
                        $centre->manager_mobile,
                        $centre->district,
                        $centre->centre_capacity,
                        $centre->hostel_available ? 'Yes' : 'No',
                        $centre->status->value,
                        Format::date($centre->created_at),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function centresPdf(): Response
    {
        abort_unless(auth()->user()?->isElevated(), 403);

        $centres = Centre::query()->orderBy('centre_code')->get();

        $pdf = Pdf::loadView('exports.centres', compact('centres'));

        return $pdf->download('centres-'.now()->format('Ymd-His').'.pdf');
    }

    public function employeesCsv(): StreamedResponse
    {
        $context = app(CentreContext::class);
        abort_unless(auth()->user()?->can('employees.view_any'), 403);

        $filename = 'employees-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($context) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Centre', 'Mobile', 'Role', 'District', 'Joining Date', 'Status']);

            $query = Employee::query()->with('centre')->orderBy('employee_code');

            $query->chunk(200, function ($employees) use ($handle, $context) {
                foreach ($employees as $employee) {
                    if (! $context->canAccessEmployee($employee)) {
                        continue;
                    }

                    fputcsv($handle, [
                        $employee->employee_code,
                        $employee->full_name,
                        $employee->centre?->centre_name,
                        $employee->mobile,
                        $employee->employee_role->value,
                        $employee->district,
                        Format::date($employee->joining_date),
                        $employee->status->value,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function employeesPdf(): Response
    {
        $context = app(CentreContext::class);
        abort_unless(auth()->user()?->can('employees.view_any'), 403);

        $employees = Employee::query()->with('centre')->orderBy('employee_code')->get()
            ->filter(fn (Employee $employee) => $context->canAccessEmployee($employee));

        $pdf = Pdf::loadView('exports.employees', ['employees' => $employees]);

        return $pdf->download('employees-'.now()->format('Ymd-His').'.pdf');
    }

    public function studentsCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('students.export'), 403);

        $filename = 'students-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Student Code', 'Student Name', 'Mobile', 'District', 'Taluka', 'Village',
                'Mobilizer', 'Centre', 'Preferred Centre', 'Admission Status',
                'Verification Status', 'Centre Visit Status', 'Hostel Required', 'Created Date',
            ]);

            Student::query()->with(['centre', 'preferredCentre', 'mobilizer'])->orderBy('student_code')->chunk(200, function ($students) use ($handle) {
                foreach ($students as $student) {
                    fputcsv($handle, [
                        $student->student_code,
                        $student->full_name,
                        $student->mobile,
                        $student->district,
                        $student->taluka,
                        $student->village,
                        $student->mobilizer?->full_name,
                        $student->centre?->centre_name,
                        $student->preferredCentre?->centre_name,
                        $student->admission_status->value,
                        $student->verification_status->value,
                        $student->centre_visit_status->value,
                        $student->hostel_required ? 'Yes' : 'No',
                        Format::date($student->created_at),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function studentsPdf(): Response
    {
        abort_unless(auth()->user()?->can('students.export'), 403);

        $students = Student::query()->with(['centre', 'preferredCentre', 'mobilizer'])->orderBy('student_code')->get();
        $pdf = Pdf::loadView('exports.students', compact('students'));

        return $pdf->download('students-'.now()->format('Ymd-His').'.pdf');
    }
}
