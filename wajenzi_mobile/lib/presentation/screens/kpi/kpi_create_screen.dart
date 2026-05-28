import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/kpi_api.dart';
import '../../../data/models/kpi_create_info.dart';
import '../../providers/kpi_provider.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

class KpiCreateScreen extends ConsumerStatefulWidget {
  const KpiCreateScreen({super.key});

  @override
  ConsumerState<KpiCreateScreen> createState() => _KpiCreateScreenState();
}

class _KpiCreateScreenState extends ConsumerState<KpiCreateScreen> {
  final _dateFmt = DateFormat('yyyy-MM-dd');
  final _periodController = TextEditingController();

  int? _templateId;
  DateTime? _periodStart;
  DateTime? _periodEnd;
  bool _submitting = false;
  bool _initialized = false;

  @override
  void dispose() {
    _periodController.dispose();
    super.dispose();
  }

  void _applyDefaults(KpiCreateInfo info) {
    if (_initialized) return;
    _initialized = true;
    _periodController.text = info.defaultPeriodLabel;
    _templateId = info.autoTemplate?.id ??
        (info.templates.isNotEmpty ? info.templates.first.id : null);
    _periodStart = _tryParse(info.defaultPeriodStart);
    _periodEnd = _tryParse(info.defaultPeriodEnd);
  }

  DateTime? _tryParse(String? raw) {
    if (raw == null || raw.isEmpty) return null;
    return DateTime.tryParse(raw);
  }

  Future<void> _pickDate(bool isStart) async {
    final now = DateTime.now();
    final initial = (isStart ? _periodStart : _periodEnd) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 5),
      lastDate: DateTime(now.year + 5),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _periodStart = picked;
        } else {
          _periodEnd = picked;
        }
      });
    }
  }

  Future<void> _create() async {
    final messenger = ScaffoldMessenger.of(context);
    if (_templateId == null) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Please select a template.')),
      );
      return;
    }
    if (_periodController.text.trim().isEmpty) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Please enter a period label.')),
      );
      return;
    }
    if (_periodStart == null || _periodEnd == null) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Please pick the period dates.')),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      final id = await ref.read(kpiApiProvider).createReview(
            kpiTemplateId: _templateId!,
            periodLabel: _periodController.text.trim(),
            periodStart: _dateFmt.format(_periodStart!),
            periodEnd: _dateFmt.format(_periodEnd!),
          );
      // Refresh the Mine list so the new review shows up.
      ref.read(kpiListProvider(KpiTab.mine).notifier).refresh();
      if (!mounted) return;
      // Replace create with the self-assessment for the new review.
      context.pushReplacement('/performance/$id/self');
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text('Failed to create review: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final infoAsync = ref.watch(kpiCreateInfoProvider);

    return Scaffold(
      appBar: kpiAppBar(context: context, ref: ref, title: 'New Review'),
      body: infoAsync.when(
        loading: () => const LoadingWidget(message: 'Loading...'),
        error: (e, _) => CustomErrorWidget(
          message: 'Could not load creation info.\n$e',
          onRetry: () => ref.refresh(kpiCreateInfoProvider),
        ),
        data: (info) {
          _applyDefaults(info);
          if (!info.canCreate || !info.hasSupervisor) {
            return _BlockingNotice(info: info);
          }
          return _buildForm(info);
        },
      ),
    );
  }

  Widget _buildForm(KpiCreateInfo info) {
    final hasMultipleTemplates = info.templates.length > 1;
    final templateName = info.autoTemplate?.name ??
        (info.templates.isNotEmpty ? info.templates.first.name : '—');

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _sectionLabel('Template'),
        const SizedBox(height: 6),
        if (hasMultipleTemplates)
          DropdownButtonFormField<int>(
            initialValue: _templateId,
            isExpanded: true,
            decoration: const InputDecoration(
              prefixIcon: Icon(Icons.description_outlined),
            ),
            items: [
              for (final t in info.templates)
                DropdownMenuItem(value: t.id, child: Text(t.name)),
            ],
            onChanged: (v) => setState(() => _templateId = v),
          )
        else
          _readOnlyField(Icons.description_outlined, templateName),
        const SizedBox(height: 16),
        _sectionLabel('Period Label'),
        const SizedBox(height: 6),
        TextField(
          controller: _periodController,
          decoration: const InputDecoration(
            hintText: 'e.g. Q1 2026',
            prefixIcon: Icon(Icons.label_outline),
          ),
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _datePickerTile(
                'Period Start',
                _periodStart,
                () => _pickDate(true),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _datePickerTile(
                'Period End',
                _periodEnd,
                () => _pickDate(false),
              ),
            ),
          ],
        ),
        const SizedBox(height: 28),
        SizedBox(
          height: 50,
          child: ElevatedButton.icon(
            onPressed: _submitting ? null : _create,
            icon: _submitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.check),
            label: Text(_submitting ? 'Creating...' : 'Create & Start'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.brandGreen,
            ),
          ),
        ),
      ],
    );
  }

  Widget _sectionLabel(String text) => Text(
        text.toUpperCase(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 1,
          color: Theme.of(context)
              .textTheme
              .bodyMedium
              ?.color
              ?.withValues(alpha: 0.7),
        ),
      );

  Widget _readOnlyField(IconData icon, String value) {
    return InputDecorator(
      decoration: InputDecoration(prefixIcon: Icon(icon)),
      child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
    );
  }

  Widget _datePickerTile(String label, DateTime? value, VoidCallback onTap) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionLabel(label),
        const SizedBox(height: 6),
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          child: InputDecorator(
            decoration: const InputDecoration(
              prefixIcon: Icon(Icons.calendar_today_outlined),
            ),
            child: Text(
              value == null ? 'Select' : _dateFmt.format(value),
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: value == null
                    ? Theme.of(context).hintColor
                    : Theme.of(context).textTheme.bodyLarge?.color,
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _BlockingNotice extends StatelessWidget {
  final KpiCreateInfo info;

  const _BlockingNotice({required this.info});

  @override
  Widget build(BuildContext context) {
    final reason = info.reason ??
        (!info.hasSupervisor
            ? 'You do not have a supervisor assigned. A supervisor is '
                'required before you can start a performance review. Please '
                'contact HR.'
            : 'You are not able to create a performance review right now.');
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.lock_outline,
                size: 64, color: AppColors.brandYellow),
            const SizedBox(height: 16),
            Text(
              'Cannot Start a Review',
              style: AppType.display(18),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              reason,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: Theme.of(context)
                    .textTheme
                    .bodyMedium
                    ?.color
                    ?.withValues(alpha: 0.8),
              ),
            ),
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: () => Navigator.of(context).maybePop(),
              icon: const Icon(Icons.arrow_back),
              label: const Text('Go Back'),
            ),
          ],
        ),
      ),
    );
  }
}
