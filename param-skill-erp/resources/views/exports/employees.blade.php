<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employees Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Employees Export</h2>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Centre</th>
                <th>Mobile</th>
                <th>Role</th>
                <th>District</th>
                <th>Joining</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_code }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->centre?->centre_name }}</td>
                    <td>{{ $employee->mobile }}</td>
                    <td>{{ $employee->employee_role->value }}</td>
                    <td>{{ $employee->district }}</td>
                    <td>{{ \App\Support\Format::date($employee->joining_date) }}</td>
                    <td>{{ $employee->status->value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
