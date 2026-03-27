import 'package:flutter/material.dart';

class PaginationListView extends StatefulWidget {
  final List<dynamic> items;
  final bool isLoading;
  final bool hasMore;
  final VoidCallback onLoadMore;
  final Widget Function(BuildContext, dynamic, int) itemBuilder;
  final Widget? loadingWidget;
  final Widget? emptyWidget;
  final EdgeInsets? padding;

  const PaginationListView({
    super.key,
    required this.items,
    required this.isLoading,
    required this.hasMore,
    required this.onLoadMore,
    required this.itemBuilder,
    this.loadingWidget,
    this.emptyWidget,
    this.padding,
  });

  @override
  State<PaginationListView> createState() => _PaginationListViewState();
}

class _PaginationListViewState extends State<PaginationListView> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels == _scrollController.position.maxScrollExtent) {
      if (!widget.isLoading && widget.hasMore) {
        widget.onLoadMore();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.items.isEmpty && !widget.isLoading) {
      return widget.emptyWidget ?? const SizedBox.shrink();
    }

    return ListView.builder(
      controller: _scrollController,
      padding: widget.padding,
      itemCount: widget.items.length + (widget.hasMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == widget.items.length) {
          return widget.loadingWidget ?? 
                 const Padding(
                   padding: EdgeInsets.all(16),
                   child: Center(child: CircularProgressIndicator()),
                 );
        }

        return widget.itemBuilder(context, widget.items[index], index);
      },
    );
  }
}
