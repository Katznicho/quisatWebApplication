<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 0 0 4px; }
        p { color: #6b7280; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #111827; color: #ffffff; text-align: left; padding: 5px; }
        td { border: 1px solid #e5e7eb; padding: 4px; vertical-align: top; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    @foreach ($sections as $section)
        <div class="section">
            <h2>{{ $section['title'] }}</h2>
            <p>{{ count($section['rows']) }} records</p>
            <table>
                <thead>
                    <tr>
                        @foreach ($section['headings'] as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($section['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(count($section['headings']), 1) }}">No records match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
