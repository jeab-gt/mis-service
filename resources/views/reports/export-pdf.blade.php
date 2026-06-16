<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; }
h1 { font-size: 16px; color: #4f46e5; margin-bottom: 4px; }
.meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th { background: #4f46e5; color: white; padding: 6px 8px; text-align: left; font-size: 9px; }
td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
tr:nth-child(even) { background: #f9fafb; }
.badge { padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
.badge-submitted  { background: #dbeafe; color: #1d4ed8; }
.badge-in_review  { background: #fef3c7; color: #b45309; }
.badge-approved   { background: #d1fae5; color: #065f46; }
.badge-rejected   { background: #fee2e2; color: #991b1b; }
.badge-closed     { background: #ede9fe; color: #5b21b6; }
.footer { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: right; }
</style>
</head>
<body>
<h1>IT MIS System — Submission Report</h1>
<p class="meta">
    Period: {{ $from }} to {{ $to }} &nbsp;|&nbsp;
    Total: {{ $submissions->count() }} records &nbsp;|&nbsp;
    Generated: {{ now()->format('d/m/Y H:i') }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>App / Type</th>
            <th>Submitter</th>
            <th>Assignee</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Created</th>
            <th>Closed</th>
        </tr>
    </thead>
    <tbody>
        @foreach($submissions as $s)
        <tr>
            <td>{{ $s->id }}</td>
            <td>{{ $s->app?->name ?? '-' }}</td>
            <td>{{ $s->submitter?->name ?? '-' }}</td>
            <td>{{ $s->latestAssignment?->assignee?->name ?? '-' }}</td>
            <td><span class="badge badge-{{ $s->status }}">{{ $s->status }}</span></td>
            <td>{{ $s->progress }}%</td>
            <td>{{ $s->created_at->format('d/m/Y') }}</td>
            <td>{{ $s->closed_at?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">IT MIS System — Confidential</div>
</body>
</html>
