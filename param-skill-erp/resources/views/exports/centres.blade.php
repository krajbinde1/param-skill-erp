<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Centres Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Centres Export</h2>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Manager</th>
                <th>Mobile</th>
                <th>District</th>
                <th>Capacity</th>
                <th>Hostel</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($centres as $centre)
                <tr>
                    <td>{{ $centre->centre_code }}</td>
                    <td>{{ $centre->centre_name }}</td>
                    <td>{{ $centre->manager_name }}</td>
                    <td>{{ $centre->manager_mobile }}</td>
                    <td>{{ $centre->district }}</td>
                    <td>{{ $centre->centre_capacity }}</td>
                    <td>{{ $centre->hostel_available ? 'Yes' : 'No' }}</td>
                    <td>{{ $centre->status->value }}</td>
                    <td>{{ \App\Support\Format::date($centre->created_at) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
