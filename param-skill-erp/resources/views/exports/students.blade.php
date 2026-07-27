<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Students Export</h2>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>District</th>
                <th>Mobilizer</th>
                <th>Centre</th>
                <th>Admission</th>
                <th>Verification</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td>{{ $student->student_code }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->mobile }}</td>
                    <td>{{ $student->district }}</td>
                    <td>{{ $student->mobilizer?->full_name }}</td>
                    <td>{{ $student->centre?->centre_name }}</td>
                    <td>{{ $student->admission_status->value }}</td>
                    <td>{{ $student->verification_status->value }}</td>
                    <td>{{ \App\Support\Format::date($student->created_at) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
