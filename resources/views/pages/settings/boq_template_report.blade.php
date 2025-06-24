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
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
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
            border: none;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            line-height: 1.5;
        }
        
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .stage-cell {
            font-weight: bold;
            background-color: #e8f4fd !important;
            border-left: 5px solid #4A90E2;
            color: #2c3e50;
            font-size: 15px;
        }
        
        .activity-cell {
            background-color: #fce4ec !important;
            font-weight: 600;
            color: #2c3e50;
            border-left: 3px solid #e91e63;
        }
        
        .sub-activity-cell {
            background-color: #fff8e1 !important;
            padding-left: 25px;
            color: #2c3e50;
            border-left: 3px solid #ff9800;
        }
        
        .materials-cell {
            background-color: #e8f5e9 !important;
            border-left: 3px solid #4caf50;
        }
        
        .duration-cell {
            text-align: center;
            font-weight: 500;
            background-color: #f3f4f6 !important;
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
        
        .material-item {
            display: inline-block;
            margin: 3px 2px;
            padding: 6px 12px;
            background-color: #4CAF50;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .quantity-badge {
            display: inline-block;
            margin: 3px 2px;
            padding: 6px 12px;
            background-color: #FF9800;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
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
            line-height: 1.6;
            margin: 0;
            font-size: 15px;
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background-color: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }
        
        .empty-cell {
            color: #999;
            font-style: italic;
            text-align: center;
        }
        
        @media print {
            body {
                margin: 0;
                background-color: white;
            }
            
            .print-btn {
                display: none;
            }
            
            .header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .stats-bar {
                page-break-inside: avoid;
            }
            
            .table-container {
                page-break-inside: avoid;
            }
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
                    <th style="width: 20%;">Construction Stages</th>
                    <th style="width: 25%;">Activities</th>
                    <th style="width: 25%;">Sub-Activities</th>
                    <th style="width: 20%;">Materials</th>
                    <th style="width: 10%;">Time Duration</th>
                </tr>
            </thead>
                    <tbody>
                        @forelse($template->templateStages->sortBy('sort_order') as $stageIndex => $stage)
                            @php
                                $stageActivities = $stage->templateActivities->sortBy('sort_order');
                                $stageRowspan = $stageActivities->sum(function($activity) {
                                    $subActivities = $activity->templateSubActivities->sortBy('sort_order');
                                    return $subActivities->sum(function($subActivity) {
                                        return max(1, $subActivity->subActivity->materials->count());
                                    });
                                });
                                $stageRowspan = max(1, $stageRowspan);
                            @endphp
                            
                            @if($stageActivities->count() > 0)
                                @foreach($stageActivities as $activityIndex => $activity)
                                    @php
                                        $subActivities = $activity->templateSubActivities->sortBy('sort_order');
                                        $activityRowspan = $subActivities->sum(function($subActivity) {
                                            return max(1, $subActivity->subActivity->materials->count());
                                        });
                                        $activityRowspan = max(1, $activityRowspan);
                                    @endphp
                                    
                                    @if($subActivities->count() > 0)
                                        @foreach($subActivities as $subActivityIndex => $subActivity)
                                            @php
                                                $materials = $subActivity->subActivity->materials;
                                                $materialCount = max(1, $materials->count());
                                            @endphp
                                            
                                            @if($materials->count() > 0)
                                                @foreach($materials as $materialIndex => $material)
                                                    <tr>
                                                        @if($stageIndex == 0 && $activityIndex == 0 && $subActivityIndex == 0 && $materialIndex == 0)
                                                            <td rowspan="{{ $stageRowspan }}" class="stage-cell">
                                                                {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                                            </td>
                                                        @endif
                                                        
                                                        @if($activityIndex == 0 && $subActivityIndex == 0 && $materialIndex == 0)
                                                            <td rowspan="{{ $activityRowspan }}" class="activity-cell">
                                                                {{ $activity->activity->name ?? 'Unknown Activity' }}
                                                            </td>
                                                        @endif
                                                        
                                                        @if($subActivityIndex == 0 && $materialIndex == 0)
                                                            <td rowspan="{{ $materialCount }}" class="sub-activity-cell">
                                                                {{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}
                                                            </td>
                                                        @endif
                                                        
                                                        <td class="materials-cell">
                                                            <span class="material-item">{{ $material->boqItem->name ?? 'Unknown Material' }}</span>
                                                            <br>
                                                            <span class="quantity-badge">{{ $material->quantity }} {{ $material->boqItem->unit ?? 'pcs' }}</span>
                                                        </td>
                                                        
                                                        @if($subActivityIndex == 0 && $materialIndex == 0)
                                                            <td rowspan="{{ $materialCount }}" class="duration-cell">
                                                                @if($subActivity->subActivity->estimated_duration_hours)
                                                                    <span class="duration-badge">
                                                                        {{ $subActivity->subActivity->estimated_duration_hours }} {{ $subActivity->subActivity->duration_unit ?? 'hours' }}
                                                                    </span>
                                                                @else
                                                                    <span class="empty-cell">Not specified</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    @if($stageIndex == 0 && $activityIndex == 0 && $subActivityIndex == 0)
                                                        <td rowspan="{{ $stageRowspan }}" class="stage-cell">
                                                            {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                                        </td>
                                                    @endif
                                                    
                                                    @if($activityIndex == 0 && $subActivityIndex == 0)
                                                        <td rowspan="{{ $activityRowspan }}" class="activity-cell">
                                                            {{ $activity->activity->name ?? 'Unknown Activity' }}
                                                        </td>
                                                    @endif
                                                    
                                                    <td class="sub-activity-cell">
                                                        {{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}
                                                    </td>
                                                    
                                                    <td class="materials-cell">
                                                        <span class="empty-cell">No materials assigned</span>
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
                                            @endif
                                        @endforeach
                                    @else
                                        <tr>
                                            @if($stageIndex == 0 && $activityIndex == 0)
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