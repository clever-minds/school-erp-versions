<!DOCTYPE html>
<html>
<head>
    <title>School Audit Report</title>
</head>
<body>
    <h2>Audit Report for {{ $audit->school ? $audit->school->name : 'School' }}</h2>
    <p>Dear School Management,</p>
    <p>Please find attached the latest audit report for your school.</p>
    <ul>
        <li><strong>Audit Type:</strong> {{ $audit->audit_type ?? 'N/A' }}</li>
        <li><strong>Audit Date:</strong> {{ date('d M, Y', strtotime($audit->audit_date)) }}</li>
        <li><strong>Overall Score:</strong> {{ number_format($audit->percentage_score, 2) }}%</li>
    </ul>
    <p>Thank you,</p>
    <p>System Administrator</p>
</body>
</html>
