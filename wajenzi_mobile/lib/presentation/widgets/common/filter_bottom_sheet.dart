import 'package:flutter/material.dart';

class FilterBottomSheet extends StatefulWidget {
  final String title;
  final Map<String, dynamic> filters;
  final Map<String, Map<String, dynamic>> options;
  final Function(Map<String, dynamic>) onApply;
  final VoidCallback onReset;

  const FilterBottomSheet({
    super.key,
    required this.title,
    required this.filters,
    required this.options,
    required this.onApply,
    required this.onReset,
  });

  @override
  State<FilterBottomSheet> createState() => _FilterBottomSheetState();
}

class _FilterBottomSheetState extends State<FilterBottomSheet> {
  late Map<String, dynamic> _filters;
  final _formKey = GlobalKey<FormState>();

  @override
  void initState() {
    super.initState();
    _filters = Map.from(widget.filters);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.8,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    widget.title,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: widget.onReset,
                  child: const Text('Reset'),
                ),
              ],
            ),
          ),
          
          const Divider(height: 1),
          
          // Form content
          Expanded(
            child: Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: widget.options.entries.map((entry) {
                    return _buildFilterField(entry.key, entry.value);
                  }).toList(),
                ),
              ),
            ),
          ),
          
          // Apply button
          Padding(
            padding: const EdgeInsets.all(20),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  if (_formKey.currentState?.validate() ?? false) {
                    widget.onApply(_filters);
                  }
                },
                child: const Text('Apply Filters'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterField(String key, Map<String, dynamic> option) {
    final label = option['label'] as String;
    final type = option['type'] as String;
    
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 8),
          
          if (type == 'select')
            _buildSelectField(key, option)
          else if (type == 'date')
            _buildDateField(key, option)
          else if (type == 'text')
            _buildTextField(key, option)
          else if (type == 'number')
            _buildNumberField(key, option)
          else
            const SizedBox(), // Unsupported type
        ],
      ),
    );
  }

  Widget _buildSelectField(String key, Map<String, dynamic> option) {
    final options = option['options'] as List<dynamic>;
    String? currentValue = _filters[key]?.toString();
    
    return DropdownButtonFormField<String>(
      value: currentValue,
      decoration: const InputDecoration(
        border: OutlineInputBorder(),
        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      ),
      items: [
        const DropdownMenuItem(
          value: '',
          child: Text('Select option'),
        ),
        ...options.map((opt) {
          return DropdownMenuItem(
            value: opt['value'].toString(),
            child: Text(opt['label'].toString()),
          );
        }),
      ],
      onChanged: (value) {
        setState(() {
          if (value == null || value.isEmpty) {
            _filters.remove(key);
          } else {
            _filters[key] = value;
          }
        });
      },
    );
  }

  Widget _buildDateField(String key, Map<String, dynamic> option) {
    String? currentValue = _filters[key]?.toString();
    
    return TextFormField(
      initialValue: currentValue,
      decoration: InputDecoration(
        border: const OutlineInputBorder(),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        suffixIcon: const Icon(Icons.calendar_today),
        hintText: 'Select date',
      ),
      readOnly: true,
      onTap: () async {
        final date = await showDatePicker(
          context: context,
          initialDate: currentValue != null
              ? DateTime.tryParse(currentValue)
              : DateTime.now(),
          firstDate: DateTime(2020),
          lastDate: DateTime(2030),
        );
        
        if (date != null) {
          setState(() {
            _filters[key] = date.toIso8601String().split('T')[0]; // YYYY-MM-DD format
          });
        }
      },
    );
  }

  Widget _buildTextField(String key, Map<String, dynamic> option) {
    return TextFormField(
      initialValue: _filters[key]?.toString(),
      decoration: const InputDecoration(
        border: OutlineInputBorder(),
        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      ),
      onChanged: (value) {
        setState(() {
          if (value.isEmpty) {
            _filters.remove(key);
          } else {
            _filters[key] = value;
          }
        });
      },
    );
  }

  Widget _buildNumberField(String key, Map<String, dynamic> option) {
    return TextFormField(
      initialValue: _filters[key]?.toString(),
      decoration: const InputDecoration(
        border: OutlineInputBorder(),
        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      ),
      keyboardType: TextInputType.number,
      onChanged: (value) {
        setState(() {
          if (value.isEmpty) {
            _filters.remove(key);
          } else {
            final number = double.tryParse(value);
            if (number != null) {
              _filters[key] = number;
            }
          }
        });
      },
    );
  }
}
