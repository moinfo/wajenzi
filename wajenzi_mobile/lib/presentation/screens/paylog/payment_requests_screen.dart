import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/paylog/paylog_models.dart';
import '../../../data/repositories/paylog_repository.dart';
import 'paylog_shared.dart';
import 'payment_request_detail_screen.dart';

final _prRefProvider = FutureProvider.autoDispose<PaylogReferenceData>((ref) {
  return ref.watch(paylogRepositoryProvider).referenceData();
});

const List<String> _statusOptions = [
  'PENDING',
  'APPROVED',
  'PAID',
  'REJECTED',
];

class PaymentRequestsScreen extends ConsumerStatefulWidget {
  const PaymentRequestsScreen({super.key});

  @override
  ConsumerState<PaymentRequestsScreen> createState() =>
      _PaymentRequestsScreenState();
}

class _PaymentRequestsScreenState extends ConsumerState<PaymentRequestsScreen> {
  final ScrollController _scroll = ScrollController();
  final List<SitePaymentRequestDto> _items = [];

  int? _siteId;
  String? _status;
  int _page = 1;
  int _lastPage = 1;
  bool _loading = false;
  bool _initialLoaded = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _load(reset: true));
  }

  @override
  void dispose() {
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scroll.position.pixels >=
            _scroll.position.maxScrollExtent - 300 &&
        !_loading &&
        _page < _lastPage) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) {
        _error = null;
        _page = 1;
        _items.clear();
      }
    });
    try {
      final result = await ref.read(paylogRepositoryProvider).requestsList(
            siteId: _siteId,
            status: _status,
            page: _page,
          );
      if (!mounted) return;
      setState(() {
        _items.addAll(result.items);
        _lastPage = result.lastPage;
        if (result.currentPage < result.lastPage) {
          _page = result.currentPage + 1;
        } else {
          _page = result.currentPage;
        }
        _initialLoaded = true;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _applyFilters({int? siteId, String? status, bool clearSite = false}) {
    setState(() {
      if (clearSite) {
        _siteId = null;
      } else if (siteId != null) {
        _siteId = siteId;
      }
      _status = status;
    });
    _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final refAsync = ref.watch(_prRefProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Payment Requests'),
      ),
      body: Column(
        children: [
          refAsync.maybeWhen(
            data: (refData) => _buildFilters(refData),
            orElse: () => const SizedBox.shrink(),
          ),
          Expanded(child: _buildList()),
        ],
      ),
    );
  }

  Widget _buildFilters(PaylogReferenceData refData) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      child: Row(
        children: [
          Expanded(
            child: DropdownButtonFormField<int?>(
              initialValue: _siteId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Site',
                isDense: true,
                border: OutlineInputBorder(),
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('All sites'),
                ),
                ...refData.sites.map(
                  (s) => DropdownMenuItem<int?>(
                    value: s.id,
                    child: Text(s.name, overflow: TextOverflow.ellipsis),
                  ),
                ),
              ],
              onChanged: (v) => _applyFilters(
                siteId: v,
                status: _status,
                clearSite: v == null,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: DropdownButtonFormField<String?>(
              initialValue: _status,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Status',
                isDense: true,
                border: OutlineInputBorder(),
              ),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text('All'),
                ),
                ..._statusOptions.map(
                  (s) => DropdownMenuItem<String?>(value: s, child: Text(s)),
                ),
              ],
              onChanged: (v) => _applyFilters(
                siteId: _siteId,
                status: v,
                clearSite: _siteId == null,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList() {
    if (_error != null && _items.isEmpty) {
      return _ErrorView(error: _error!, onRetry: () => _load(reset: true));
    }
    if (!_initialLoaded && _loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_items.isEmpty) {
      return _EmptyView(
        icon: Icons.receipt_long_outlined,
        label: 'No payment requests match these filters.',
      );
    }
    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
        itemCount: _items.length + ((_page < _lastPage) ? 1 : 0),
        itemBuilder: (context, i) {
          if (i >= _items.length) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          final r = _items[i];
          return _RequestTile(
            request: r,
            onTap: () => _openDetail(r.id),
          );
        },
      ),
    );
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PaymentRequestDetailScreen(requestId: id),
      ),
    );
    _load(reset: true);
  }
}

class _RequestTile extends StatelessWidget {
  final SitePaymentRequestDto request;
  final VoidCallback onTap;
  const _RequestTile({required this.request, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final subtitle = [request.siteName, request.projectName, request.creatorName]
        .whereType<String>()
        .where((v) => v.isNotEmpty)
        .join(' • ');
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 5),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      request.requestNumber,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  PaylogStatusChip(
                    label: request.displayStatus,
                    colorToken: request.statusColor,
                  ),
                ],
              ),
              if (subtitle.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: Theme.of(context).textTheme.bodySmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 8),
              Row(
                children: [
                  Text(
                    'TZS ${fmtMoney(request.totalAmount)}',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const Spacer(),
                  if (request.paymentDate != null)
                    Text(
                      fmtDate(request.paymentDate),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String label;
  const _EmptyView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 64, color: Theme.of(context).hintColor),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(color: Theme.of(context).hintColor),
            ),
          ),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;
  const _ErrorView({required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.error_outline_rounded,
            size: 56, color: Colors.red.withValues(alpha: 0.7)),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child:
                Text(paylogErrorMessage(error), textAlign: TextAlign.center),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child:
              OutlinedButton(onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}
