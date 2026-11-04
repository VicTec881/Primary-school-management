<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $learner->full_name }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1b365d;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #1b365d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .school-motto {
            font-size: 10px;
            font-style: italic;
            color: #555;
            margin-bottom: 4px;
        }
        .school-contact {
            font-size: 9px;
            color: #666;
            margin-bottom: 8px;
        }
        .report-title {
            display: inline-block;
            background-color: #1b365d;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 14px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }
        .meta-table td {
            padding: 5px 8px;
            font-size: 10.5px;
        }
        .meta-label {
            color: #666;
            width: 120px;
        }
        .meta-val {
            font-weight: bold;
            color: #111;
        }
        .grading-key {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            font-size: 9px;
            background: #fdfdfd;
            border: 1px solid #e0e0e0;
        }
        .grading-key td {
            padding: 4px 6px;
            text-align: center;
        }
        .badge-ee { background: #d1e7dd; color: #0f5132; font-weight: bold; padding: 2px 4px; border-radius: 2px; }
        .badge-me { background: #cfe2ff; color: #084298; font-weight: bold; padding: 2px 4px; border-radius: 2px; }
        .badge-ae { background: #fff3cd; color: #664d03; font-weight: bold; padding: 2px 4px; border-radius: 2px; }
        .badge-be { background: #f8d7da; color: #842029; font-weight: bold; padding: 2px 4px; border-radius: 2px; }
        
        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .assessment-table th {
            background-color: #f1f3f5;
            color: #1b365d;
            border: 1px solid #ced4da;
            padding: 6px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .assessment-table td {
            border: 1px solid #dee2e6;
            padding: 5px 6px;
            font-size: 10px;
            vertical-align: middle;
        }
        .assessment-table tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .remarks-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .remarks-cell {
            width: 50%;
            border: 1px solid #dee2e6;
            padding: 8px;
            background-color: #fcfcfc;
            vertical-align: top;
        }
        .remarks-title {
            font-weight: bold;
            font-size: 10px;
            color: #1b365d;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .remarks-content {
            font-style: italic;
            color: #333;
            min-height: 40px;
            margin-bottom: 8px;
            font-size: 9.5px;
        }
        .sig-line {
            border-top: 1px dotted #888;
            padding-top: 3px;
            font-size: 9px;
            color: #666;
        }
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 8.5px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .next-term-bar {
            background-color: #e8f4fd;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 5px 8px;
            font-size: 10px;
            margin-bottom: 10px;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">{{ $school['name'] }}</div>
        <div class="school-motto">&ldquo;{{ $school['motto'] }}&rdquo;</div>
        <div class="school-contact">
            {{ $school['box'] }}, {{ $school['town'] }} | Tel: {{ $school['phone'] }} | Email: {{ $school['email'] }}
        </div>
        <div class="report-title">
            Competency-Based Education (CBE) Learner Summative Report
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Learner Name:</td>
            <td class="meta-val" style="font-size: 11px;">{{ $learner->full_name }}</td>
            <td class="meta-label">Grade & Stream:</td>
            <td class="meta-val">{{ $grade->name ?? 'Grade' }} {{ $stream->name ?? '' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Admission No:</td>
            <td class="meta-val">{{ $learner->admission_number }}</td>
            <td class="meta-label">Academic Term:</td>
            <td class="meta-val">{{ $term->name }} ({{ $year->name }})</td>
        </tr>
        <tr>
            <td class="meta-label">NEMIS / UPI No:</td>
            <td class="meta-val">{{ $learner->nemis_number ?: 'N/A' }}</td>
            <td class="meta-label">Attendance:</td>
            <td class="meta-val">
                {{ $report_card->attendance_days_present }} / {{ $report_card->attendance_days_total }} Days Present 
                ({{ $report_card->attendance_days_absent }} Absent)
            </td>
        </tr>
    </table>

    <table class="grading-key">
        <tr>
            <td style="font-weight: bold; width: 120px;">Performance Scale:</td>
            <td><span class="badge-ee">EE</span> Exceeding Expectations (80-100%)</td>
            <td><span class="badge-me">ME</span> Meeting Expectations (65-79%)</td>
            <td><span class="badge-ae">AE</span> Approaching Expectations (50-64%)</td>
            <td><span class="badge-be">BE</span> Below Expectations (0-49%)</td>
        </tr>
    </table>

    <table class="assessment-table">
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="text-align: left;">Learning Area (Subject)</th>
                <th style="width: 60px; text-align: center;">Score (%)</th>
                <th style="width: 70px; text-align: center;">Level</th>
                <th style="text-align: left;">Teacher Observations & Competency Rubric</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assessments as $idx => $assessment)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $assessment->learningArea->name }}</strong>
                    <span style="color: #666; font-size: 8.5px;">({{ $assessment->learningArea->code }})</span>
                </td>
                <td style="text-align: center; font-weight: bold;">
                    {{ $assessment->raw_score !== null ? $assessment->raw_score : '—' }}
                </td>
                <td style="text-align: center;">
                    @php
                        $lvl = $assessment->performance_level;
                        $class = match($lvl) {
                            'EE' => 'badge-ee',
                            'ME' => 'badge-me',
                            'AE' => 'badge-ae',
                            'BE' => 'badge-be',
                            default => ''
                        };
                    @endphp
                    @if($lvl)
                    <span class="{{ $class }}">{{ $lvl }}</span>
                    @else
                    —
                    @endif
                </td>
                <td>
                    {{ $assessment->rubric_comments ?: 'Consistent engagement and effort demonstrated.' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 15px; color: #888;">
                    No assessment records captured for this term.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="remarks-box">
        <tr>
            <td class="remarks-cell" style="padding-right: 10px;">
                <div class="remarks-title">Class Teacher's Remarks</div>
                <div class="remarks-content">
                    &ldquo;{{ $report_card->class_teacher_remarks ?: 'Good academic engagement and steady progress across all learning areas.' }}&rdquo;
                </div>
                <div class="sig-line">Signature: ____________________________________</div>
            </td>
            <td class="remarks-cell" style="padding-left: 10px;">
                <div class="remarks-title">Headteacher's Remarks & Stamp</div>
                <div class="remarks-content">
                    &ldquo;{{ $report_card->headteacher_remarks ?: 'Promising results. Encouraged to maintain consistency.' }}&rdquo;
                </div>
                <div class="sig-line">Signature & Stamp: _____________________________</div>
            </td>
        </tr>
    </table>

    <div class="next-term-bar">
        <strong>Next Term Commences:</strong> 
        {{ $report_card->next_term_begins ? \Carbon\Carbon::parse($report_card->next_term_begins)->format('l, d F Y') : 'To be communicated' }}
        &nbsp;|&nbsp; 
        <strong>Date Issued:</strong> {{ \Carbon\Carbon::parse($report_card->issued_at ?? now())->format('d F Y') }}
    </div>

    <div class="footer">
        Official summative assessment report generated by {{ $school['name'] }} Management System.
    </div>

</body>
</html>

