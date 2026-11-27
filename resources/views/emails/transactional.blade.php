<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
    <h1 style="color: #e50914;">YUNFLIMS</h1>
    <p>Xin chào {{ $recipientName }},</p>
    <p>{{ $introText }}</p>
    @if ($details)
        <table cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
            @foreach ($details as $label => $value)
                <tr>
                    <th align="left" style="border-bottom: 1px solid #ddd;">{{ $label }}</th>
                    <td style="border-bottom: 1px solid #ddd;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    <p>Trân trọng,<br>YUNFLIMS Cinema</p>
</body>
</html>
