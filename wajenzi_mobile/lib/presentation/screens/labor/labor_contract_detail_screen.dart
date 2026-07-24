import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'labor_contract_form_screen.dart';

final laborContractDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/contracts/$id');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborContractDetailScreen extends ConsumerStatefulWidget {
  final int contractId;

  const LaborContractDetailScreen({super.key, required this.contractId});

  @override
  ConsumerState<LaborContractDetailScreen> createState() =>
      _LaborContractDetailScreenState();
}

class _LaborContractDetailScreenState
    extends ConsumerState<LaborContractDetailScreen> {
  bool _busy = false;
  bool _changed = false;

  void _refresh() {
    ref.invalidate(laborContractDetailProvider(widget.contractId));
  }

  Future<void> _runAction(
    Future<Response<dynamic>> Function(ApiClient api) call, {
    required String successMessage,
  }) async {
    final isSwahili = ref.read(isSwahiliProvider);
    setState(() => _busy = true);
    try {
      final api = ref.read(apiClientProvider);
      final response = await call(api);
      final data = response.data as Map<String, dynamic>?;
      if (data != null && data['success'] == false) {
        _showMessage(data['message']?.toString() ?? 'Action failed',
            error: true);
      } else {
        _changed = true;
        _refresh();
        _showMessage(successMessage);
      }
    } on DioException catch (e) {
      _showMessage(_dioMessage(e, isSwahili), error: true);
    } catch (e) {
      _showMessage('$e', error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String _dioMessage(DioException e, bool isSwahili) {
    final data = e.response?.data;
    if (data is Map) {
      if (data['message'] != null) return data['message'].toString();
      if (data['errors'] is Map) {
        final errors = (data['errors'] as Map).values;
        if (errors.isNotEmpty && errors.first is List) {
          return (errors.first as List).first.toString();
        }
      }
    }
    return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
  }

  void _showMessage(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? AppColors.error : null,
      ),
    );
  }

  // ─── Actions ─────────────────────────────────────

  Future<void> _sign() async {
    final isSwahili = ref.read(isSwahiliProvider);
    String? filePath;
    String? fileName;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setDialog) {
            return AlertDialog(
              title: Text(isSwahili ? 'Saini Mkataba' : 'Sign Contract'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isSwahili
                        ? 'Ambatanisha faili la mkataba uliosainiwa (hiari).'
                        : 'Optionally attach a signed contract file.',
                    style: const TextStyle(fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    icon: const Icon(Icons.attach_file),
                    label: Text(
                      fileName ??
                          (isSwahili ? 'Chagua faili' : 'Choose file'),
                      overflow: TextOverflow.ellipsis,
                    ),
                    onPressed: () async {
                      final result = await FilePicker.platform.pickFiles();
                      if (result != null && result.files.single.path != null) {
                        setDialog(() {
                          filePath = result.files.single.path;
                          fileName = result.files.single.name;
                        });
                      }
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(ctx).pop(false),
                  child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                ),
                ElevatedButton(
                  onPressed: () => Navigator.of(ctx).pop(true),
                  child: Text(isSwahili ? 'Saini' : 'Sign'),
                ),
              ],
            );
          },
        );
      },
    );

    if (confirmed != true) return;

    await _runAction(
      (api) {
        final formMap = <String, dynamic>{};
        if (filePath != null) {
          formMap['contract_file'] = MultipartFile.fromFileSync(
            filePath!,
            filename: fileName,
          );
        }
        return api.uploadFile(
          '/labor/contracts/${widget.contractId}/sign',
          data: FormData.fromMap(formMap),
        );
      },
      successMessage:
          isSwahili ? 'Mkataba umesainiwa' : 'Contract signed and activated',
    );
  }

  Future<void> _hold() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Simamisha Mkataba' : 'Put On Hold'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            labelText: isSwahili ? 'Sababu' : 'Reason',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(controller.text.trim()),
            child: Text(isSwahili ? 'Simamisha' : 'Hold'),
          ),
        ],
      ),
    );
    if (reason == null) return;
    await _runAction(
      (api) => api.post(
        '/labor/contracts/${widget.contractId}/hold',
        data: {'reason': reason},
      ),
      successMessage:
          isSwahili ? 'Mkataba umesimamishwa' : 'Contract put on hold',
    );
  }

  Future<void> _resume() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Endelea na Mkataba' : 'Resume Contract'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kuendelea na mkataba huu?'
              : 'Are you sure you want to resume this contract?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Endelea' : 'Resume'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    await _runAction(
      (api) => api.post('/labor/contracts/${widget.contractId}/resume'),
      successMessage: isSwahili ? 'Mkataba umeendelea' : 'Contract resumed',
    );
  }

  Future<void> _terminate() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Sitisha Mkataba' : 'Terminate Contract'),
        content: Form(
          key: formKey,
          child: TextFormField(
            controller: controller,
            maxLines: 3,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Sababu ya kusitisha' : 'Termination reason',
              border: const OutlineInputBorder(),
            ),
            validator: (v) {
              if (v == null || v.trim().length < 10) {
                return isSwahili
                    ? 'Angalau herufi 10 zinahitajika'
                    : 'At least 10 characters required';
              }
              return null;
            },
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.of(ctx).pop(controller.text.trim());
              }
            },
            child: Text(isSwahili ? 'Sitisha' : 'Terminate'),
          ),
        ],
      ),
    );
    if (reason == null) return;
    await _runAction(
      (api) => api.post(
        '/labor/contracts/${widget.contractId}/terminate',
        data: {'termination_reason': reason},
      ),
      successMessage:
          isSwahili ? 'Mkataba umesitishwa' : 'Contract terminated',
    );
  }

  Future<void> _edit(Map<String, dynamic> contract) async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => LaborContractFormScreen(existingContract: contract),
      ),
    );
    if (result == true) {
      _changed = true;
      _refresh();
    }
  }

  Future<void> _downloadPdf() async {
    final isSwahili = ref.read(isSwahiliProvider);
    setState(() => _busy = true);
    _showMessage(isSwahili ? 'Inapakua...' : 'Downloading...');
    try {
      final api = ref.read(apiClientProvider);
      final response =
          await api.get('/labor/contracts/${widget.contractId}/pdf');
      final data = response.data['data'] as Map<String, dynamic>?;
      final base64Str = data?['pdf_base64'] as String?;
      final filename = data?['filename'] as String? ?? 'contract.pdf';
      if (base64Str == null || base64Str.isEmpty) {
        throw StateError('Empty PDF response');
      }
      final bytes = base64Decode(base64Str);
      final dir = await getTemporaryDirectory();
      final filePath = '${dir.path}/$filename';
      final file = File(filePath);
      await file.writeAsBytes(bytes);
      final uri = Uri.file(filePath);
      final opened =
          await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!opened) {
        _showMessage(
          isSwahili ? 'Imehifadhiwa: $filename' : 'Saved: $filename',
        );
      }
    } on DioException catch (e) {
      _showMessage(_dioMessage(e, isSwahili), error: true);
    } catch (e) {
      _showMessage('$e', error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  // ─── Build ───────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final detailAsync =
        ref.watch(laborContractDetailProvider(widget.contractId));

    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (didPop, result) {},
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () => Navigator.of(context).pop(_changed),
          ),
          title: Text(isSwahili ? 'Maelezo ya Mkataba' : 'Contract Detail'),
          actions: [
            IconButton(
              icon: const Icon(Icons.picture_as_pdf_outlined),
              tooltip: 'PDF',
              onPressed: _busy ? null : _downloadPdf,
            ),
          ],
        ),
        body: detailAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: _refresh,
          ),
          data: (contract) {
            return Stack(
              children: [
                RefreshIndicator(
                  onRefresh: () async => _refresh(),
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      _header(contract, isSwahili, isDarkMode),
                      const SizedBox(height: 12),
                      _amounts(contract, isSwahili, isDarkMode),
                      const SizedBox(height: 12),
                      _phasesCard(contract, isSwahili, isDarkMode),
                      const SizedBox(height: 12),
                      _workLogsCard(contract, isSwahili, isDarkMode),
                      const SizedBox(height: 12),
                      _inspectionsCard(contract, isSwahili, isDarkMode),
                      const SizedBox(height: 16),
                      _actions(contract, isSwahili),
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
                if (_busy)
                  const Positioned.fill(
                    child: ColoredBox(
                      color: Color(0x66000000),
                      child: Center(child: CircularProgressIndicator()),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _header(
      Map<String, dynamic> c, bool isSwahili, bool isDarkMode) {
    return _card(
      isDarkMode,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  c['contract_number']?.toString() ?? '-',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ),
              _statusChip(c['status']?.toString(),
                  c['status_badge_class']?.toString()),
            ],
          ),
          const SizedBox(height: 8),
          if (c['project'] != null)
            _line(Icons.folder_outlined,
                (c['project'] as Map)['project_name']?.toString() ?? '-',
                isDarkMode),
          if (c['artisan'] != null)
            _line(
              Icons.person_outline,
              '${(c['artisan'] as Map)['name'] ?? '-'}'
              '${(c['artisan'] as Map)['trade_skill'] != null ? ' · ${(c['artisan'] as Map)['trade_skill']}' : ''}',
              isDarkMode,
            ),
          if (c['supervisor'] != null)
            _line(Icons.supervisor_account_outlined,
                (c['supervisor'] as Map)['name']?.toString() ?? '-',
                isDarkMode),
          if (c['labor_request'] != null)
            _line(Icons.assignment_outlined,
                (c['labor_request'] as Map)['request_number']?.toString() ?? '-',
                isDarkMode),
          if (c['start_date'] != null)
            _line(Icons.calendar_today_outlined,
                '${c['start_date']} → ${c['end_date'] ?? '-'}', isDarkMode),
          if (c['scope_of_work'] != null) ...[
            const SizedBox(height: 8),
            Text(
              isSwahili ? 'Kazi' : 'Scope of Work',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white54 : AppColors.textHint,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              c['scope_of_work'].toString(),
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ],
          if (c['terms_conditions'] != null) ...[
            const SizedBox(height: 8),
            Text(
              isSwahili ? 'Masharti' : 'Terms & Conditions',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white54 : AppColors.textHint,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              c['terms_conditions'].toString(),
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _amounts(
      Map<String, dynamic> c, bool isSwahili, bool isDarkMode) {
    return _card(
      isDarkMode,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: _stat(isSwahili ? 'Jumla' : 'Total',
                    _formatCurrency(_toDouble(c['total_amount'])), isDarkMode),
              ),
              Expanded(
                child: _stat(isSwahili ? 'Imelipwa' : 'Paid',
                    _formatCurrency(_toDouble(c['amount_paid'])), isDarkMode,
                    highlight: true),
              ),
              Expanded(
                child: _stat(isSwahili ? 'Bakia' : 'Balance',
                    _formatCurrency(_toDouble(c['balance_amount'])), isDarkMode),
              ),
            ],
          ),
          if (c['payment_progress'] != null) ...[
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  isSwahili ? 'Maendeleo ya malipo' : 'Payment Progress',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
                Text(
                  '${_toDouble(c['payment_progress']).toStringAsFixed(1)}%',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color:
                        isDarkMode ? Colors.white70 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: (_toDouble(c['payment_progress']) / 100).clamp(0.0, 1.0),
                backgroundColor:
                    isDarkMode ? const Color(0xFF252540) : Colors.grey[200],
                valueColor: AlwaysStoppedAnimation<Color>(
                  _badgeColor(c['status_badge_class']?.toString()),
                ),
                minHeight: 6,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _phasesCard(
      Map<String, dynamic> c, bool isSwahili, bool isDarkMode) {
    final phases = (c['payment_phases'] as List? ?? const []).cast<dynamic>();
    return _card(
      isDarkMode,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle(
              isSwahili ? 'Awamu za Malipo' : 'Payment Phases', phases.length,
              isDarkMode),
          const SizedBox(height: 8),
          if (phases.isEmpty)
            _emptyLine(isSwahili ? 'Hakuna awamu' : 'No phases', isDarkMode)
          else
            ...phases.map((p) {
              final phase = Map<String, dynamic>.from(p as Map);
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${phase['phase_number']}. ${phase['phase_name'] ?? '-'}',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                          if (phase['milestone_description'] != null)
                            Text(
                              phase['milestone_description'].toString(),
                              style: TextStyle(
                                fontSize: 11,
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textHint,
                              ),
                            ),
                          Text(
                            '${_toDouble(phase['percentage']).toStringAsFixed(0)}% · ${_formatCurrency(_toDouble(phase['amount']))}',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ),
                    ),
                    _phaseStatusChip(phase['status']?.toString()),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _workLogsCard(
      Map<String, dynamic> c, bool isSwahili, bool isDarkMode) {
    final logs = (c['work_logs'] as List? ?? const []).cast<dynamic>();
    return _card(
      isDarkMode,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle(
              isSwahili ? 'Kumbukumbu za Kazi' : 'Work Logs', logs.length,
              isDarkMode),
          const SizedBox(height: 8),
          if (logs.isEmpty)
            _emptyLine(isSwahili ? 'Hakuna kumbukumbu' : 'No work logs',
                isDarkMode)
          else
            ...logs.map((l) {
              final log = Map<String, dynamic>.from(l as Map);
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.event_note_outlined,
                        size: 16,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textHint),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${log['log_date'] ?? '-'} · ${_toDouble(log['progress_percentage']).toStringAsFixed(0)}%',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                          if (log['work_done'] != null)
                            Text(
                              log['work_done'].toString(),
                              style: TextStyle(
                                fontSize: 12,
                                color: isDarkMode
                                    ? Colors.white70
                                    : AppColors.textSecondary,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _inspectionsCard(
      Map<String, dynamic> c, bool isSwahili, bool isDarkMode) {
    final inspections =
        (c['inspections'] as List? ?? const []).cast<dynamic>();
    return _card(
      isDarkMode,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle(isSwahili ? 'Ukaguzi' : 'Inspections',
              inspections.length, isDarkMode),
          const SizedBox(height: 8),
          if (inspections.isEmpty)
            _emptyLine(
                isSwahili ? 'Hakuna ukaguzi' : 'No inspections', isDarkMode)
          else
            ...inspections.map((i) {
              final ins = Map<String, dynamic>.from(i as Map);
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${ins['inspection_number'] ?? '-'} · ${ins['inspection_type'] ?? '-'}',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                          Text(
                            '${_toDouble(ins['completion_percentage']).toStringAsFixed(0)}%'
                            '${ins['result'] != null ? ' · ${ins['result']}' : ''}',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ),
                    ),
                    _phaseStatusChip(ins['status']?.toString()),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _actions(Map<String, dynamic> c, bool isSwahili) {
    final canEdit = c['can_edit'] == true;
    final canSign = c['can_sign'] == true;
    final canHold = c['can_put_on_hold'] == true;
    final canResume = c['can_resume'] == true;
    final canTerminate = c['can_terminate'] == true;

    final buttons = <Widget>[];
    if (canSign) {
      buttons.add(_actionButton(
        icon: Icons.draw_outlined,
        label: isSwahili ? 'Saini' : 'Sign',
        color: const Color(0xFF16A34A),
        onPressed: _busy ? null : _sign,
      ));
    }
    if (canEdit) {
      buttons.add(_actionButton(
        icon: Icons.edit_outlined,
        label: isSwahili ? 'Hariri' : 'Edit',
        color: const Color(0xFF2563EB),
        onPressed: _busy ? null : () => _edit(c),
      ));
    }
    if (canHold) {
      buttons.add(_actionButton(
        icon: Icons.pause_circle_outline,
        label: isSwahili ? 'Simamisha' : 'Hold',
        color: const Color(0xFFF59E0B),
        onPressed: _busy ? null : _hold,
      ));
    }
    if (canResume) {
      buttons.add(_actionButton(
        icon: Icons.play_circle_outline,
        label: isSwahili ? 'Endelea' : 'Resume',
        color: const Color(0xFF0891B2),
        onPressed: _busy ? null : _resume,
      ));
    }
    if (canTerminate) {
      buttons.add(_actionButton(
        icon: Icons.cancel_outlined,
        label: isSwahili ? 'Sitisha' : 'Terminate',
        color: AppColors.error,
        onPressed: _busy ? null : _terminate,
      ));
    }

    if (buttons.isEmpty) return const SizedBox.shrink();
    return Wrap(spacing: 10, runSpacing: 10, children: buttons);
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback? onPressed,
  }) {
    return ElevatedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 18),
      label: Text(label),
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      ),
    );
  }

  // ─── Small helpers ───────────────────────────────

  Widget _card(bool isDarkMode, {required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: child,
    );
  }

  Widget _sectionTitle(String title, int count, bool isDarkMode) {
    return Row(
      children: [
        Text(
          title,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        const SizedBox(width: 6),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(999),
          ),
          child: Text(
            '$count',
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: AppColors.primary,
            ),
          ),
        ),
      ],
    );
  }

  Widget _line(IconData icon, String text, bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon,
              size: 15,
              color: isDarkMode ? Colors.white54 : AppColors.textHint),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _stat(String label, String value, bool isDarkMode,
      {bool highlight = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: highlight
                ? const Color(0xFF16A34A)
                : (isDarkMode ? Colors.white54 : AppColors.textHint),
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: highlight
                ? const Color(0xFF16A34A)
                : (isDarkMode ? Colors.white : AppColors.textPrimary),
          ),
        ),
      ],
    );
  }

  Widget _emptyLine(String text, bool isDarkMode) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 12,
        fontStyle: FontStyle.italic,
        color: isDarkMode ? Colors.white38 : AppColors.textHint,
      ),
    );
  }

  Widget _statusChip(String? status, String? badgeClass) {
    final color = _badgeColor(badgeClass);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        (status ?? '-').toUpperCase().replaceAll('_', ' '),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  Widget _phaseStatusChip(String? status) {
    final color = _phaseColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        (status ?? '-').toUpperCase().replaceAll('_', ' '),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatCurrency(double amount) {
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

Color _badgeColor(String? badgeClass) {
  return switch (badgeClass) {
    'success' => const Color(0xFF16A34A),
    'warning' => const Color(0xFFF59E0B),
    'danger' => const Color(0xFFDC2626),
    'info' => const Color(0xFF0891B2),
    _ => const Color(0xFF6B7280),
  };
}

Color _phaseColor(String? status) {
  return switch (status) {
    'paid' => const Color(0xFF16A34A),
    'approved' => const Color(0xFF0891B2),
    'due' => const Color(0xFFF59E0B),
    'held' => const Color(0xFFDC2626),
    _ => const Color(0xFF6B7280),
  };
}
