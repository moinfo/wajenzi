import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'labor_log_form_screen.dart';

final _laborLogDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/logs/$id');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborLogDetailScreen extends ConsumerStatefulWidget {
  final int logId;

  const LaborLogDetailScreen({super.key, required this.logId});

  @override
  ConsumerState<LaborLogDetailScreen> createState() =>
      _LaborLogDetailScreenState();
}

class _LaborLogDetailScreenState extends ConsumerState<LaborLogDetailScreen> {
  bool _changed = false;
  bool _deleting = false;

  String _resolvePhotoUrl(String path) {
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    final normalized = path.startsWith('/') ? path : '/$path';
    return '${AppConfig.activePortalBaseUrl}$normalized';
  }

  Future<void> _delete(bool isSwahili) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Kumbukumbu' : 'Delete Work Log'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta kumbukumbu hii? Hatua hii haiwezi kutenduliwa.'
              : 'Are you sure you want to delete this work log? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() => _deleting = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/labor/logs/${widget.logId}');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Kumbukumbu imefutwa.' : 'Work log deleted.',
          ),
        ),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _deleting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_errorMessage(e, isSwahili))),
      );
    }
  }

  Future<void> _edit(Map<String, dynamic> log) async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => LaborLogFormScreen(existingLog: log),
      ),
    );
    if (result == true) {
      _changed = true;
      ref.invalidate(_laborLogDetailProvider(widget.logId));
    }
  }

  String _errorMessage(Object e, bool isSwahili) {
    final msg = e.toString();
    final match = RegExp(r'"message":"([^"]+)"').firstMatch(msg);
    if (match != null) return match.group(1)!;
    return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final detailAsync = ref.watch(_laborLogDetailProvider(widget.logId));

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () => Navigator.of(context).pop(_changed),
          ),
          title: Text(isSwahili ? 'Kumbukumbu ya Kazi' : 'Work Log'),
          actions: [
            detailAsync.maybeWhen(
              data: (log) {
                final canEdit = log['can_edit'] == true;
                if (!canEdit) return const SizedBox.shrink();
                return IconButton(
                  icon: const Icon(Icons.edit_outlined),
                  tooltip: isSwahili ? 'Hariri' : 'Edit',
                  onPressed: () => _edit(log),
                );
              },
              orElse: () => const SizedBox.shrink(),
            ),
            detailAsync.maybeWhen(
              data: (log) {
                final canDelete = log['can_delete'] == true;
                if (!canDelete) return const SizedBox.shrink();
                return IconButton(
                  icon: const Icon(Icons.delete_outline),
                  tooltip: isSwahili ? 'Futa' : 'Delete',
                  onPressed: _deleting ? null : () => _delete(isSwahili),
                );
              },
              orElse: () => const SizedBox.shrink(),
            ),
          ],
        ),
        body: detailAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.error_outline,
                    size: 64,
                    color: AppColors.error,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _errorMessage(error, isSwahili),
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    onPressed: () => ref.invalidate(
                      _laborLogDetailProvider(widget.logId),
                    ),
                    icon: const Icon(Icons.refresh),
                    label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
                  ),
                ],
              ),
            ),
          ),
          data: (log) => _buildContent(log, isSwahili, isDarkMode),
        ),
      ),
    );
  }

  Widget _buildContent(
    Map<String, dynamic> log,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final contract = log['contract'] as Map<String, dynamic>?;
    final logger = log['logger'] as Map<String, dynamic>?;
    final photos = (log['photos'] as List? ?? const []).cast<dynamic>();
    final materials = (log['materials_used'] as List? ?? const [])
        .cast<dynamic>();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _Card(
          isDarkMode: isDarkMode,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                log['log_date'] != null
                    ? DateFormat('EEEE, MMM d, yyyy').format(
                        DateTime.parse(log['log_date'] as String),
                      )
                    : '-',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              if (contract != null) ...[
                const SizedBox(height: 8),
                _InlineRow(
                  icon: Icons.description_outlined,
                  label: isSwahili ? 'Mkataba' : 'Contract',
                  value:
                      '${contract['contract_number'] ?? '-'}'
                      '${contract['artisan_name'] != null ? ' • ${contract['artisan_name']}' : ''}',
                  isDarkMode: isDarkMode,
                ),
                if (contract['project_name'] != null)
                  _InlineRow(
                    icon: Icons.apartment_outlined,
                    label: isSwahili ? 'Mradi' : 'Project',
                    value: contract['project_name'] as String,
                    isDarkMode: isDarkMode,
                  ),
              ],
              if (log['weather_conditions'] != null)
                _InlineRow(
                  icon: Icons.wb_cloudy_outlined,
                  label: isSwahili ? 'Hali ya Hewa' : 'Weather',
                  value: _titleCase(log['weather_conditions'] as String),
                  isDarkMode: isDarkMode,
                ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        _Card(
          isDarkMode: isDarkMode,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _SectionLabel(
                text: isSwahili ? 'Kazi Iliyofanywa' : 'Work Done',
                isDarkMode: isDarkMode,
              ),
              const SizedBox(height: 6),
              Text(
                log['work_done'] as String? ?? '-',
                style: TextStyle(
                  fontSize: 14,
                  height: 1.4,
                  color: isDarkMode
                      ? Colors.white70
                      : AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _StatBox(
                label: isSwahili ? 'Wafanyakazi' : 'Workers',
                value: '${log['workers_present'] ?? 0}',
                icon: Icons.people_outline,
                isDarkMode: isDarkMode,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _StatBox(
                label: isSwahili ? 'Masaa' : 'Hours',
                value: log['hours_worked'] != null
                    ? '${(log['hours_worked'] as num).toStringAsFixed(1)}h'
                    : '-',
                icon: Icons.access_time,
                isDarkMode: isDarkMode,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _StatBox(
                label: isSwahili ? 'Maendeleo' : 'Progress',
                value: log['progress_percentage'] != null
                    ? '${(log['progress_percentage'] as num).toStringAsFixed(0)}%'
                    : '-',
                icon: Icons.trending_up,
                isDarkMode: isDarkMode,
              ),
            ),
          ],
        ),
        if (materials.isNotEmpty) ...[
          const SizedBox(height: 12),
          _Card(
            isDarkMode: isDarkMode,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SectionLabel(
                  text: isSwahili ? 'Vifaa Vilivyotumika' : 'Materials Used',
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(height: 8),
                ...materials.map((m) {
                  final mat = Map<String, dynamic>.from(m as Map);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.inventory_2_outlined,
                          size: 16,
                          color: Color(0xFF2563EB),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            mat['name'] as String? ?? '-',
                            style: TextStyle(
                              fontSize: 13,
                              color: isDarkMode
                                  ? Colors.white70
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ),
                        if (mat['quantity'] != null)
                          Text(
                            '${mat['quantity']}',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
        if (log['challenges'] != null &&
            (log['challenges'] as String).isNotEmpty) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFDC2626).withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFFDC2626).withValues(alpha: 0.2),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(
                      Icons.warning_amber_outlined,
                      color: Color(0xFFDC2626),
                      size: 18,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      isSwahili ? 'Changamoto' : 'Challenges',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFDC2626),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  log['challenges'] as String,
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFFDC2626),
                  ),
                ),
              ],
            ),
          ),
        ],
        if (log['notes'] != null && (log['notes'] as String).isNotEmpty) ...[
          const SizedBox(height: 12),
          _Card(
            isDarkMode: isDarkMode,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SectionLabel(
                  text: isSwahili ? 'Maelezo' : 'Notes',
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(height: 6),
                Text(
                  log['notes'] as String,
                  style: TextStyle(
                    fontSize: 13,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
        if (photos.isNotEmpty) ...[
          const SizedBox(height: 12),
          _Card(
            isDarkMode: isDarkMode,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SectionLabel(
                  text:
                      '${isSwahili ? 'Picha' : 'Photos'} (${photos.length})',
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(height: 8),
                GridView.count(
                  crossAxisCount: 3,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  children: photos.map((p) {
                    final url = _resolvePhotoUrl(p.toString());
                    return GestureDetector(
                      onTap: () => _openPhoto(url),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.network(
                          url,
                          fit: BoxFit.cover,
                          errorBuilder: (_, _, _) => Container(
                            color: isDarkMode
                                ? const Color(0xFF252540)
                                : Colors.grey[200],
                            child: const Icon(
                              Icons.broken_image_outlined,
                              color: Colors.grey,
                            ),
                          ),
                          loadingBuilder: (_, child, progress) {
                            if (progress == null) return child;
                            return Container(
                              color: isDarkMode
                                  ? const Color(0xFF252540)
                                  : Colors.grey[200],
                              child: const Center(
                                child: SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 12),
        Row(
          children: [
            Icon(
              Icons.person_outline,
              size: 14,
              color: isDarkMode ? Colors.white38 : AppColors.textHint,
            ),
            const SizedBox(width: 4),
            Text(
              '${isSwahili ? 'Alirekodi' : 'Logged by'}: ${logger?['name'] ?? '-'}',
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white38 : AppColors.textHint,
              ),
            ),
          ],
        ),
        const SizedBox(height: 40),
      ],
    );
  }

  void _openPhoto(String url) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            InteractiveViewer(
              child: Image.network(
                url,
                errorBuilder: (_, _, _) => const Padding(
                  padding: EdgeInsets.all(32),
                  child: Icon(
                    Icons.broken_image_outlined,
                    color: Colors.white,
                    size: 48,
                  ),
                ),
              ),
            ),
            IconButton(
              icon: const CircleAvatar(
                backgroundColor: Colors.black54,
                child: Icon(Icons.close, color: Colors.white),
              ),
              onPressed: () => Navigator.of(ctx).pop(),
            ),
          ],
        ),
      ),
    );
  }

  String _titleCase(String s) =>
      s.isEmpty ? s : '${s[0].toUpperCase()}${s.substring(1)}';
}

class _Card extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _Card({required this.isDarkMode, required this.child});

  @override
  Widget build(BuildContext context) {
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
}

class _SectionLabel extends StatelessWidget {
  final String text;
  final bool isDarkMode;

  const _SectionLabel({required this.text, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        letterSpacing: 0.4,
        color: isDarkMode ? Colors.white54 : AppColors.textHint,
      ),
    );
  }
}

class _InlineRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isDarkMode;

  const _InlineRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            icon,
            size: 15,
            color: isDarkMode ? Colors.white54 : AppColors.textHint,
          ),
          const SizedBox(width: 6),
          Text(
            '$label: ',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatBox extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final bool isDarkMode;

  const _StatBox({
    required this.label,
    required this.value,
    required this.icon,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(
            icon,
            size: 20,
            color: isDarkMode ? Colors.white54 : AppColors.textHint,
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
        ],
      ),
    );
  }
}
