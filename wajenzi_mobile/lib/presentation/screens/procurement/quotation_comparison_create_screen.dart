import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../data/models/procurement/quotation_models.dart';
import '../../../data/repositories/quotations_repository.dart';
import '../../providers/settings_provider.dart';
import 'procurement_shared.dart';

final _eligibilityProvider = FutureProvider.autoDispose
    .family<ComparisonEligibilityDto, int>((ref, materialRequestId) async {
  return ref.read(quotationsRepositoryProvider).createEligibility(materialRequestId);
});

class QuotationComparisonCreateScreen extends ConsumerStatefulWidget {
  final int materialRequestId;
  const QuotationComparisonCreateScreen({
    super.key,
    required this.materialRequestId,
  });

  @override
  ConsumerState<QuotationComparisonCreateScreen> createState() =>
      _QuotationComparisonCreateScreenState();
}

class _QuotationComparisonCreateScreenState
    extends ConsumerState<QuotationComparisonCreateScreen> {
  int? _selectedQuotationId;
  final _reasonCtrl = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _reasonCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final async = ref.watch(_eligibilityProvider(widget.materialRequestId));

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Tengeneza Ulinganisho' : 'Create Comparison'),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          isSwahili: isSwahili,
          onRetry: () =>
              ref.invalidate(_eligibilityProvider(widget.materialRequestId)),
        ),
        data: (elig) => _body(elig, isSwahili, isDarkMode),
      ),
    );
  }

  Widget _body(
      ComparisonEligibilityDto elig, bool isSwahili, bool isDarkMode) {
    if (!elig.canCreate) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.block, size: 56, color: AppColors.warning),
              const SizedBox(height: 12),
              Text(
                elig.blockReason ??
                    (isSwahili
                        ? 'Haiwezi kutengeneza ulinganisho.'
                        : 'Cannot create a comparison.'),
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
      );
    }

    final reasonValid = _reasonCtrl.text.trim().length >= 10;
    final canSubmit = _selectedQuotationId != null && reasonValid;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1E1E32) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey.withValues(alpha: 0.18)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(elig.materialRequestNumber,
                  style: const TextStyle(
                      fontSize: 15, fontWeight: FontWeight.w700)),
              if (elig.projectName.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(elig.projectName,
                      style: TextStyle(
                          fontSize: 12, color: AppColors.textSecondary)),
                ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 16,
                runSpacing: 4,
                children: [
                  _stat(isSwahili ? 'Nukuu' : 'Quotes',
                      elig.quotationCount.toString()),
                  _stat(isSwahili ? 'Chini' : 'Lowest',
                      fmtMoney(elig.analysisLowest)),
                  _stat(isSwahili ? 'Juu' : 'Highest',
                      fmtMoney(elig.analysisHighest)),
                  _stat(isSwahili ? 'Wastani' : 'Average',
                      fmtMoney(elig.analysisAverage)),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Chagua Mshindi' : 'Select Winner',
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
        const SizedBox(height: 4),
        Text(
          isSwahili
              ? 'Bofya redio kwenye msambazaji unayemchagua.'
              : 'Tap the radio for the supplier you are selecting.',
          style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
        const SizedBox(height: 10),
        _MatrixTable(
          elig: elig,
          selectedQuotationId: _selectedQuotationId,
          isDarkMode: isDarkMode,
          isSwahili: isSwahili,
          onSelect: (id) => setState(() => _selectedQuotationId = id),
        ),
        const SizedBox(height: 20),
        TextField(
          controller: _reasonCtrl,
          maxLines: 3,
          onChanged: (_) => setState(() {}),
          decoration: InputDecoration(
            labelText: isSwahili
                ? 'Sababu ya mapendekezo (angalau herufi 10)'
                : 'Recommendation reason (min 10 chars)',
            border: const OutlineInputBorder(),
            helperText: _reasonCtrl.text.trim().isEmpty
                ? null
                : (reasonValid
                    ? null
                    : (isSwahili ? 'Fupi mno' : 'Too short')),
          ),
        ),
        const SizedBox(height: 20),
        SizedBox(
          height: 48,
          child: ElevatedButton(
            onPressed: (!canSubmit || _submitting)
                ? null
                : () => _submit(isSwahili),
            child: _submitting
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(isSwahili
                    ? 'Tuma kwa Idhini'
                    : 'Submit for Approval'),
          ),
        ),
      ],
    );
  }

  Widget _stat(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
        Text(value,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
      ],
    );
  }

  Future<void> _submit(bool isSwahili) async {
    if (_selectedQuotationId == null) return;
    setState(() => _submitting = true);
    try {
      await ref.read(quotationsRepositoryProvider).createComparison(
            materialRequestId: widget.materialRequestId,
            selectedQuotationId: _selectedQuotationId!,
            recommendationReason: _reasonCtrl.text.trim(),
          );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili
                ? 'Ulinganisho umetumwa kwa idhini'
                : 'Comparison submitted for approval'),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _submitting = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _MatrixTable extends StatelessWidget {
  final ComparisonEligibilityDto elig;
  final int? selectedQuotationId;
  final bool isDarkMode;
  final bool isSwahili;
  final ValueChanged<int> onSelect;

  const _MatrixTable({
    required this.elig,
    required this.selectedQuotationId,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final quotations = elig.quotations;
    final headerColor = isDarkMode ? const Color(0xFF25253C) : Colors.grey[100];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowColor: WidgetStateProperty.all(headerColor),
        headingRowHeight: 92,
        columnSpacing: 18,
        columns: [
          DataColumn(label: Text(isSwahili ? 'Bidhaa' : 'Item')),
          ...quotations.map((q) => DataColumn(
                label: _ColumnHeader(
                  quotation: q,
                  selected: selectedQuotationId == q.id,
                  onSelect: () => onSelect(q.id),
                  isSwahili: isSwahili,
                ),
                numeric: true,
              )),
        ],
        rows: [
          ...elig.items.map((item) {
            final row = elig.itemPriceMatrix[item.materialRequestItemId] ??
                const <int, double>{};
            final values =
                quotations.map((q) => row[q.id]).whereType<double>().toList();
            final lowest = values.isEmpty
                ? null
                : values.reduce((a, b) => a < b ? a : b);
            return DataRow(cells: [
              DataCell(SizedBox(
                width: 120,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      item.description.isEmpty
                          ? (isSwahili ? 'Bidhaa' : 'Item')
                          : item.description,
                      overflow: TextOverflow.ellipsis,
                      maxLines: 2,
                      style: const TextStyle(fontSize: 12.5),
                    ),
                    Text('${fmtQty(item.quantity)} ${item.unit ?? ''}',
                        style: TextStyle(
                            fontSize: 10.5, color: AppColors.textSecondary)),
                  ],
                ),
              )),
              ...quotations.map((q) {
                final v = row[q.id];
                final isLow = v != null && lowest != null && v <= lowest;
                return DataCell(Text(
                  v == null ? '-' : fmtMoney(v),
                  style: TextStyle(
                    color: isLow ? AppColors.success : null,
                    fontWeight: isLow ? FontWeight.w800 : FontWeight.w500,
                  ),
                ));
              }),
            ]);
          }),
          DataRow(
            color: WidgetStateProperty.all(headerColor),
            cells: [
              DataCell(Text(isSwahili ? 'Jumla kuu' : 'Grand total',
                  style: const TextStyle(fontWeight: FontWeight.w800))),
              ...quotations.map((q) => DataCell(Text(
                    fmtMoney(q.grandTotal),
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: selectedQuotationId == q.id
                          ? AppColors.success
                          : null,
                    ),
                  ))),
            ],
          ),
        ],
      ),
    );
  }
}

class _ColumnHeader extends StatelessWidget {
  final EligQuotationDto quotation;
  final bool selected;
  final VoidCallback onSelect;
  final bool isSwahili;

  const _ColumnHeader({
    required this.quotation,
    required this.selected,
    required this.onSelect,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onSelect,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                selected
                    ? Icons.radio_button_checked
                    : Icons.radio_button_unchecked,
                size: 20,
                color: selected ? AppColors.success : AppColors.textSecondary,
              ),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  quotation.supplierName.isEmpty
                      ? quotation.quotationNumber
                      : quotation.supplierName,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: selected ? AppColors.success : null,
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.only(left: 4),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(fmtMoney(quotation.grandTotal),
                    style: TextStyle(
                        fontSize: 11, color: AppColors.textSecondary)),
                if (quotation.isExpired) ...[
                  const SizedBox(width: 4),
                  Text(isSwahili ? 'IMEISHA' : 'EXP',
                      style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                          color: AppColors.error)),
                ],
              ],
            ),
          ),
        ],
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
      children: [
        const SizedBox(height: 120),
        Center(child: Icon(Icons.error_outline, size: 56, color: AppColors.error)),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Text(
            procurementErrorMessage(error),
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.textSecondary),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton(
            onPressed: onRetry,
            child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}
