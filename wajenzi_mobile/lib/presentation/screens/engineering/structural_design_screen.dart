// Structural Design — Engineering Design > Structural Design.
//
// Thin wrapper that delegates to the generic [DesignListScreen] for the
// structural variant. See [design_list_screen.dart] for all behaviour.

import 'package:flutter/material.dart';

import 'design_list_screen.dart';
import 'engineering_design_shared.dart';

class StructuralDesignScreen extends StatelessWidget {
  const StructuralDesignScreen({super.key});

  @override
  Widget build(BuildContext context) =>
      const DesignListScreen(kind: EngineeringDesignKind.structural);
}
