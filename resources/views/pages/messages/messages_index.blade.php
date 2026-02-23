@extends('layouts.backend')

@section('content')

    <div class="sms-dashboard">
        <!-- Header -->
        <div class="sms-header">
            <div class="sms-header-content">
                <div class="sms-header-text">
                    <h1><i class="fa fa-envelope"></i> SMS Messaging</h1>
                    <p>Send messages and track delivery across your organization</p>
                </div>
                <div class="sms-header-actions">
                    @can('Add Message')
                        <button type="button"
                                onclick="loadFormModal('message_form', {className: 'Message'}, 'Send New Message', 'modal-md');"
                                class="sms-btn sms-btn-primary">
                            <i class="fa fa-paper-plane"></i> New Message
                        </button>
                        <button type="button"
                                onclick="loadFormModal('bulk_message_form', {className: 'Message'}, 'Send Bulk SMS', 'modal-md');"
                                class="sms-btn sms-btn-secondary">
                            <i class="fa fa-users"></i> Bulk SMS
                        </button>
                    @endcan
                    <button type="button" onclick="loadBirthdays()" class="sms-btn sms-btn-birthday">
                        <i class="fa fa-birthday-cake"></i> Birthdays
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="sms-stats-grid">
            <div class="sms-stat-card balance">
                <div class="sms-stat-icon">
                    <i class="fa fa-coins"></i>
                </div>
                <div class="sms-stat-info">
                    <span class="sms-stat-label">SMS Balance</span>
                    <span class="sms-stat-value">{{ $smsBalance !== null ? number_format($smsBalance) : '--' }}</span>
                    <span class="sms-stat-sub">Credits remaining</span>
                </div>
            </div>

            <div class="sms-stat-card today">
                <div class="sms-stat-icon">
                    <i class="fa fa-calendar-day"></i>
                </div>
                <div class="sms-stat-info">
                    <span class="sms-stat-label">Sent Today</span>
                    <span class="sms-stat-value">{{ number_format($todayMessages) }}</span>
                    <span class="sms-stat-sub">{{ now()->format('d M Y') }}</span>
                </div>
            </div>

            <div class="sms-stat-card week">
                <div class="sms-stat-icon">
                    <i class="fa fa-calendar-week"></i>
                </div>
                <div class="sms-stat-info">
                    <span class="sms-stat-label">This Week</span>
                    <span class="sms-stat-value">{{ number_format($thisWeekMessages) }}</span>
                    <span class="sms-stat-sub">{{ now()->startOfWeek()->format('d M') }} - {{ now()->endOfWeek()->format('d M') }}</span>
                </div>
            </div>

            <div class="sms-stat-card total">
                <div class="sms-stat-icon">
                    <i class="fa fa-chart-bar"></i>
                </div>
                <div class="sms-stat-info">
                    <span class="sms-stat-label">Total Messages</span>
                    <span class="sms-stat-value">{{ number_format($totalMessages) }}</span>
                    <span class="sms-stat-sub">All time</span>
                </div>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="sms-table-card">
            <div class="sms-table-header">
                <h3><i class="fa fa-list"></i> Message History</h3>
                <span class="sms-table-badge">{{ $thisMonthMessages }} this month</span>
            </div>
            <div class="table-responsive">
                <table id="js-dataTable-full" class="table table-bordered table-striped table-vcenter js-dataTable-full">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;">#</th>
                        <th>Recipient</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Sent</th>
                        <th class="text-center" style="width: 100px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($messages as $message)
                        <tr id="message-tr-{{$message->id}}">
                            <td class="text-center">{{ $loop->index + 1 }}</td>
                            <td class="font-w600">{{ $message->name ?? '-' }}</td>
                            <td>
                                <span class="sms-phone-badge">
                                    <i class="fa fa-phone fa-xs"></i> {{ $message->phone }}
                                </span>
                            </td>
                            <td>
                                <span title="{{ $message->message }}" data-toggle="tooltip" data-placement="top">
                                    {{ Str::limit($message->message, 60) }}
                                </span>
                            </td>
                            <td>
                                @if($message->created_at)
                                    <span title="{{ $message->created_at->format('d M Y, H:i') }}" data-toggle="tooltip">
                                        {{ $message->created_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    @can('Edit Message')
                                        <button type="button"
                                                onclick="loadFormModal('message_form', {className: 'Message', id: {{$message->id}}}, 'Edit Message', 'modal-md');"
                                                class="btn btn-sm btn-primary js-tooltip-enabled"
                                                data-toggle="tooltip" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                    @endcan
                                    @can('Delete Message')
                                        <button type="button"
                                                onclick="deleteModelItem('Message', {{$message->id}}, 'message-tr-{{$message->id}}');"
                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                data-toggle="tooltip" title="Delete">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .sms-dashboard {
            padding: 1.5rem;
            background: #F8FAFC;
            min-height: calc(100vh - 140px);
        }

        /* Header */
        .sms-header {
            background: linear-gradient(135deg, #2563EB 0%, #22C55E 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .sms-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .sms-header::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
        }
        .sms-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .sms-header-text h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
        }
        .sms-header-text h1 i {
            margin-right: 0.5rem;
            opacity: 0.9;
        }
        .sms-header-text p {
            margin: 0;
            opacity: 0.85;
            font-size: 0.95rem;
        }
        .sms-header-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Buttons */
        .sms-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sms-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .sms-btn-primary { background: white; color: #1D4ED8; }
        .sms-btn-secondary { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .sms-btn-secondary:hover { background: rgba(255,255,255,0.25); }

        /* Stats Grid */
        .sms-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .sms-stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            transition: all 0.2s;
        }
        .sms-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .sms-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .sms-stat-card.balance .sms-stat-icon { background: linear-gradient(135deg, #1D4ED8, #2563EB); color: white; }
        .sms-stat-card.today .sms-stat-icon { background: linear-gradient(135deg, #16A34A, #22C55E); color: white; }
        .sms-stat-card.week .sms-stat-icon { background: linear-gradient(135deg, #D97706, #F59E0B); color: white; }
        .sms-stat-card.total .sms-stat-icon { background: linear-gradient(135deg, #334155, #475569); color: white; }

        .sms-stat-info {
            display: flex;
            flex-direction: column;
        }
        .sms-stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748B;
        }
        .sms-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.2;
        }
        .sms-stat-sub {
            font-size: 0.78rem;
            color: #94A3B8;
            margin-top: 2px;
        }

        /* Table Card */
        .sms-table-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .sms-table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sms-table-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1E293B;
        }
        .sms-table-header h3 i {
            margin-right: 0.5rem;
            color: #1D4ED8;
        }
        .sms-table-badge {
            background: #DCFCE7;
            color: #16A34A;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .sms-table-card .table-responsive {
            padding: 0 1rem 1rem;
        }
        .sms-phone-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #F1F5F9;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.88rem;
            color: #475569;
            font-family: monospace;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sms-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .sms-header-content { flex-direction: column; text-align: center; gap: 1rem; }
        }
        @media (max-width: 575px) {
            .sms-dashboard { padding: 1rem; }
            .sms-stats-grid { grid-template-columns: 1fr; }
            .sms-header { padding: 1.25rem; }
            .sms-header-text h1 { font-size: 1.35rem; }
            .sms-header-actions { flex-direction: column; width: 100%; }
            .sms-btn { justify-content: center; }
        }
        .sms-btn-birthday {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .sms-btn-birthday:hover { background: rgba(255,255,255,0.25); }

        .birthday-today { background: #F0FDF4 !important; }
        .birthday-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .birthday-badge.today { background: #DCFCE7; color: #16A34A; }
        .birthday-badge.upcoming { background: #FEF3C7; color: #D97706; }
    </style>

    <!-- Birthday Modal -->
    <div class="modal fade" id="birthdayModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #16A34A, #22C55E); color: white;">
                    <h5 class="modal-title"><i class="fa fa-birthday-cake"></i> Employee Birthdays</h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                </div>
                <div class="modal-body" id="birthdayModalBody">
                    <div class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading birthdays...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadBirthdays() {
            $('#birthdayModal').modal('show');
            $('#birthdayModalBody').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading birthdays...</p></div>');

            $.get('{{ route("eSMS.birthdays") }}', function(data) {
                if (data.length === 0) {
                    $('#birthdayModalBody').html('<div class="text-center py-4"><i class="fa fa-calendar-times fa-3x text-muted mb-3"></i><p class="text-muted">No employees have a date of birth set.</p></div>');
                    return;
                }

                var html = '<table class="table table-bordered table-striped"><thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Birthday</th><th>Status</th></tr></thead><tbody>';
                data.forEach(function(item, index) {
                    var rowClass = item.is_today ? 'birthday-today' : '';
                    var status = item.is_today
                        ? '<span class="birthday-badge today"><i class="fa fa-star"></i> Today!</span>'
                        : '<span class="birthday-badge upcoming">in ' + item.days_until + ' day' + (item.days_until > 1 ? 's' : '') + '</span>';

                    html += '<tr class="' + rowClass + '">';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td class="font-w600">' + item.name + '</td>';
                    html += '<td><span class="sms-phone-badge"><i class="fa fa-phone fa-xs"></i> ' + (item.phone_number || '-') + '</span></td>';
                    html += '<td>' + item.dob_formatted + '</td>';
                    html += '<td>' + status + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';

                $('#birthdayModalBody').html(html);
            }).fail(function() {
                $('#birthdayModalBody').html('<div class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle fa-2x"></i><p class="mt-2">Failed to load birthdays.</p></div>');
            });
        }
    </script>

@endsection
