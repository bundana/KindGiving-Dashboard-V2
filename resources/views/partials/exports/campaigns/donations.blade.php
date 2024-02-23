<!-- resources/views/exports/donations_export.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations Export</title>
    <style>
        /* Add your custom styles here */
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .campaign-info {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<h2>{{ $campaign->name }} Donations Export | {{ config('app.name') }}</h2>

<div class="campaign-info">
    <p><strong>Total Donation Amount:</strong> ₵{{ number_format($data->sum('amount'), 2) }}</p>
</div>
<br>
<table>
    <thead>
    <tr>
        <th>Donation Reference</th>
        <th>Momo Number</th>
        <th>Amount</th>
        <th>Donor Name</th>
        <th>Agent</th>
        <th>Method</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $item)
        <tr>
            <td>{{ $item->donation_ref }}</td>
            <td>{{ $item->momo_number }}</td>
            <td>{{ $item->amount }}</td>
            <td>{{ $item->donor_name }}</td>
            <td>{{ $item->user->name }}</td>
            <td>{{ $item->method }}</td>
            <td>{{ $item->status }}</td>
            <!-- Add more cells if needed -->
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
