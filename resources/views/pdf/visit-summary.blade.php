<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Visit Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .letterhead {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .letterhead h1 {
            margin: 0;
            font-size: 22px;
            color: #1e40af;
        }
        .letterhead .practice-info {
            margin-top: 5px;
            font-size: 10px;
            color: #666;
        }
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            color: #1e40af;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 4px 0;
        }
        .info-value {
            display: table-cell;
            padding: 4px 0;
        }
        .content-block {
            margin: 10px 0;
            padding: 10px;
            background-color: #f9fafb;
            border-left: 3px solid #2563eb;
        }
        .referral-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .referral-list li {
            padding: 8px;
            margin: 5px 0;
            background-color: #fef3c7;
            border-left: 3px solid #f59e0b;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .signature-block {
            margin-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 300px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $providerName }}</h1>
        <div class="practice-info">
            {{ $providerSpecialty ?? 'Family Medicine' }}<br>
            {{ $providerLocation ?? 'Joplin, MO' }}<br>
            Phone: {{ $providerPhone ?? '(417) 555-0100' }} | Fax: (417) 555-0101
        </div>
    </div>

    <div class="document-title">Visit Summary</div>

    <div class="section">
        <div class="section-title">Patient Demographics</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Patient Name:</div>
                <div class="info-value">{{ $patientName }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Visit:</div>
                <div class="info-value">{{ $visitDate }}</div>
            </div>
            @if(isset($visitTime))
            <div class="info-row">
                <div class="info-label">Visit Time:</div>
                <div class="info-value">{{ $visitTime }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(isset($chiefComplaint))
    <div class="section">
        <div class="section-title">Chief Complaint</div>
        <div class="content-block">
            {{ $chiefComplaint }}
        </div>
    </div>
    @endif

    @if(isset($assessment))
    <div class="section">
        <div class="section-title">Assessment and Plan</div>
        <div class="content-block">
            {!! nl2br(e($assessment)) !!}
        </div>
    </div>
    @endif

    @if(isset($referrals) && count($referrals) > 0)
    <div class="section">
        <div class="section-title">Referrals and Follow-Up</div>
        <ul class="referral-list">
            @foreach($referrals as $referral)
            <li><strong>{{ $referral['specialty'] }}:</strong> {{ $referral['reason'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(isset($instructions))
    <div class="section">
        <div class="section-title">Patient Instructions</div>
        <div class="content-block">
            {!! nl2br(e($instructions)) !!}
        </div>
    </div>
    @endif

    <div class="signature-block">
        <div class="signature-line"></div>
        <div>{{ $providerName }}, {{ $providerCredentials ?? 'MD' }}</div>
        <div>{{ $visitDate }}</div>
    </div>

    <div class="footer">
        This document is an official medical record. Please keep for your records.<br>
        Generated on {{ now()->format('F d, Y \a\t g:i A') }}
    </div>
</body>
</html>
