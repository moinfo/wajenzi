<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOQ Template Report - {{ $template->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header {
            background-color: #4A90E2;
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 15px 0 10px 0;
            font-size: 24px;
            font-weight: normal;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stats-bar {
            display: flex;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            flex: 1;
            padding: 20px;
            text-align: center;
            border-right: 1px solid #ddd;
            background-color: white;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-number {
            font-size: 42px;
            color: #4A90E2;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: visible;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            max-width: 100%;
        }
        
        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            table-layout: fixed;
        }
        
        th {
            background-color: #2c3e50;
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.8px;
            border: 1px solid #34495e;
            border-bottom: 2px solid #34495e;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        th:first-child {
            border-left: none;
        }
        
        th:last-child {
            border-right: none;
        }
        
        td {
            padding: 12px 15px;
            border: 1px solid #eee;
            vertical-align: top;
            line-height: 1.6;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .stage-cell {
            font-weight: bold;
            background-color: #e8f4fd !important;
            border-left: 5px solid #4A90E2 !important;
            color: #2c3e50;
            font-size: 14px;
            width: 18%;
            max-width: 18%;
            text-align: left;
            vertical-align: middle;
        }
        
        .activity-cell {
            background-color: #fce4ec !important;
            font-weight: 600;
            color: #2c3e50;
            border-left: 3px solid #e91e63 !important;
            width: 22%;
            max-width: 22%;
            text-align: left;
            vertical-align: middle;
        }
        
        .sub-activity-cell {
            background-color: #fff8e1 !important;
            color: #2c3e50;
            border-left: 3px solid #ff9800 !important;
            width: 22%;
            max-width: 22%;
            text-align: left;
            vertical-align: middle;
        }
        
        .materials-cell {
            background-color: #e8f5e9 !important;
            border-left: 3px solid #4caf50 !important;
            width: 35%;
            max-width: 35%;
            text-align: left;
            vertical-align: top;
            padding: 10px 15px;
            min-width: 300px;
        }
        
        .duration-cell {
            text-align: center;
            font-weight: 500;
            background-color: #f3f4f6 !important;
            width: 12%;
            max-width: 12%;
            vertical-align: middle;
        }
        
        .duration-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #4A90E2;
            color: white;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .materials-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .materials-table th {
            background-color: #f1f3f4;
            color: #202124;
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #dadce0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .materials-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
            line-height: 1.4;
        }
        
        .materials-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .material-name {
            font-weight: 600;
            color: #202124;
            font-size: 12px;
        }
        
        .material-quantity {
            font-weight: 700;
            color: #000000;
            text-align: center;
            font-size: 14px;
            white-space: nowrap;
            background-color: #f0f8ff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #1BC5BD;
        }
        
        .material-unit {
            color: #333333;
            font-size: 12px;
            margin-left: 4px;
            font-weight: 600;
        }
        
        .materials-summary {
            margin-top: 8px;
            padding: 6px 8px;
            background-color: #e8f4fd;
            border-radius: 4px;
            font-size: 10px;
            color: #1a73e8;
            font-weight: 500;
            text-align: center;
        }
        
        .description-section {
            margin-top: 30px;
            padding: 25px;
            background-color: white;
            border-left: 5px solid #4A90E2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 0 8px 8px 0;
        }
        
        .description-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .description-section p {
            color: #666;
        }
        
        /* Responsive Design */
        @media screen and (max-width: 1200px) {
            .table-container {
                overflow-x: scroll;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 1000px;
            }
        }
        
        @media screen and (max-width: 768px) {
            body {
                margin: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .header h2 {
                font-size: 18px;
            }
            
            .stats-bar {
                flex-direction: column;
            }
            
            .stat-item {
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
            
            .stat-item:last-child {
                border-bottom: none;
            }
        }
        
        /* Print Styles */
        @media print {
            body {
                margin: 0;
                background-color: white;
            }
            
            .header {
                background-color: #4A90E2 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .table-container {
                overflow: visible;
                box-shadow: none;
            }
            
            table {
                min-width: auto;
                width: 100%;
            }
            
            .stage-cell,
            .activity-cell,
            .sub-activity-cell,
            .materials-cell,
            .duration-cell {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .materials-table {
                border: 1px solid #ccc !important;
            }
            
            .materials-table th,
            .materials-table td {
                border: 1px solid #ddd !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .materials-table th {
                background-color: #f1f3f4 !important;
            }
            
            .materials-summary {
                background-color: #e8f4fd !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .duration-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background-color: #4A90E2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background-color: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 144, 226, 0.4);
        }
        
        @media print {
            .print-btn {
                display: none;
            }
        }
        
        .empty-cell {
            color: #999;
            font-style: italic;
            text-align: center;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        🖨️ Print Report
    </button>

    <div class="header">
        <h1>BOQ Template Report</h1>
        <h2>{{ $template->name }}</h2>
        <p>
            @if($template->buildingType)
                <strong>Building Type:</strong> {{ $template->buildingType->name }} | 
            @endif
            <strong>Generated:</strong> {{ now()->format('F d, Y \a\t H:i A') }}
        </p>
    </div>
    
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-number">{{ $template->templateStages->count() }}</div>
            <div class="stat-label">Construction Stages</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">
                {{ $template->templateStages->sum(function($stage) {
                    return $stage->templateActivities->count();
                }) }}
            </div>
            <div class="stat-label">Activities</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">
                {{ $template->templateStages->sum(function($stage) {
                    return $stage->templateActivities->sum(function($activity) {
                        return $activity->templateSubActivities->count();
                    });
                }) }}
            </div>
            <div class="stat-label">Sub-Activities</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">
                {{ $template->templateStages->sum(function($stage) {
                    return $stage->templateActivities->sum(function($activity) {
                        return $activity->templateSubActivities->sum(function($subActivity) {
                            return $subActivity->subActivity->materials->count();
                        });
                    });
                }) }}
            </div>
            <div class="stat-label">Material Items</div>
        </div>
    </div>

    <!-- Detailed Report Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 16%;">Construction Stages</th>
                    <th style="width: 20%;">Activities</th>
                    <th style="width: 20%;">Sub-Activities</th>
                    <th style="width: 35%;">Materials & Quantities</th>
                    <th style="width: 9%;">Time Duration</th>
                </tr>
            </thead>
                    <tbody>
                        @forelse($template->templateStages->sortBy('sort_order') as $stageIndex => $stage)
                            @php
                                // Calculate how many rows this stage will span
                                // Now each sub-activity gets exactly one row (not one per material)
                                $stageActivities = $stage->templateActivities->sortBy('sort_order');
                                $stageRowspan = $stageActivities->sum(function($activity) {
                                    $subActivities = $activity->templateSubActivities->sortBy('sort_order');
                                    return max(1, $subActivities->count()); // One row per sub-activity
                                });
                                $stageRowspan = max(1, $stageRowspan);
                            @endphp
                            
                            @if($stageActivities->count() > 0)
                                @foreach($stageActivities as $activityIndex => $activity)
                                    @php
                                        // Calculate how many rows this activity will span
                                        // Now each activity spans across its sub-activities (one row per sub-activity)
                                        $subActivities = $activity->templateSubActivities->sortBy('sort_order');
                                        $activityRowspan = max(1, $subActivities->count()); // One row per sub-activity
                                    @endphp
                                    
                                    @if($subActivities->count() > 0)
                                        @foreach($subActivities as $subActivityIndex => $subActivity)
                                            @php
                                                $materials = $subActivity->subActivity->materials;
                                            @endphp
                                            
                                            <tr>
                                                @if($activityIndex == 0 && $subActivityIndex == 0)
                                                    <td rowspan="{{ $stageRowspan }}" class="stage-cell">
                                                        {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                                    </td>
                                                @endif
                                                
                                                @if($subActivityIndex == 0)
                                                    <td rowspan="{{ $activityRowspan }}" class="activity-cell">
                                                        {{ $activity->activity->name ?? 'Unknown Activity' }}
                                                    </td>
                                                @endif
                                                
                                                <td class="sub-activity-cell">
                                                    {{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}
                                                </td>
                                                
                                                <td class="materials-cell">
                                                    @if($materials->count() > 0)
                                                        <div style="background: white; border: 2px solid #1BC5BD; border-radius: 6px; padding: 10px;">
                                                            <div style="background: #1BC5BD; color: white; padding: 5px 10px; margin: -10px -10px 10px -10px; font-weight: bold; font-size: 12px;">
                                                                📦 MATERIALS LIST
                                                            </div>
                                                            @foreach($materials as $material)
                                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #eee;">
                                                                    <div style="flex: 1; font-weight: 600; color: #333; font-size: 13px;">
                                                                        {{ $material->boqItem->name ?? 'Unknown Material' }}
                                                                    </div>
                                                                    <div style="background: #FF9800; color: white; padding: 4px 12px; border-radius: 15px; font-weight: bold; font-size: 14px; min-width: 80px; text-align: center;">
                                                                        {{ $material->quantity }} {{ $material->boqItem->unit ?? 'pcs' }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            <div style="margin-top: 8px; text-align: center; background: #e8f4fd; padding: 4px; border-radius: 4px; font-size: 11px; color: #1a73e8; font-weight: 600;">
                                                                TOTAL: {{ $materials->count() }} material{{ $materials->count() > 1 ? 's' : '' }}
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="empty-cell">No materials assigned</span>
                                                    @endif
                                                </td>
                                                
                                                <td class="duration-cell">
                                                    @if($subActivity->subActivity->estimated_duration_hours)
                                                        <span class="duration-badge">
                                                            {{ $subActivity->subActivity->estimated_duration_hours }} {{ $subActivity->subActivity->duration_unit ?? 'hours' }}
                                                        </span>
                                                    @else
                                                        <span class="empty-cell">Not specified</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            @if($activityIndex == 0)
                                                <td rowspan="{{ $stageRowspan }}" class="stage-cell">
                                                    {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                                </td>
                                            @endif
                                            
                                            <td class="activity-cell">
                                                {{ $activity->activity->name ?? 'Unknown Activity' }}
                                            </td>
                                            
                                            <td class="sub-activity-cell">
                                                <span class="empty-cell">No sub-activities</span>
                                            </td>
                                            
                                            <td class="materials-cell">
                                                <span class="empty-cell">No materials</span>
                                            </td>
                                            
                                            <td class="duration-cell">
                                                <span class="empty-cell">-</span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td class="stage-cell">
                                        {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                    </td>
                                    <td class="activity-cell">
                                        <span class="empty-cell">No activities</span>
                                    </td>
                                    <td class="sub-activity-cell">
                                        <span class="empty-cell">No sub-activities</span>
                                    </td>
                                    <td class="materials-cell">
                                        <span class="empty-cell">No materials</span>
                                    </td>
                                    <td class="duration-cell">
                                        <span class="empty-cell">-</span>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="empty-cell" style="text-align: center; padding: 40px;">
                                    No template structure configured
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
        </table>
    </div>

    @if($template->description)
        <div class="description-section">
            <h3>Template Description</h3>
            <p>{{ $template->description }}</p>
        </div>
    @endif
</body>
</html>