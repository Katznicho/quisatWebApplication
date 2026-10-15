<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { color: #6b7280; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #ffffff; text-align: left; padding: 6px; }
        td { border: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ count($rows) }} records · {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(count($headings), 1) }}">No records match this view.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
