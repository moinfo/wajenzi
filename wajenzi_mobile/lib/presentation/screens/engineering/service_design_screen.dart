// Service Design — Engineering Design > Service Design.
//
// Thin wrapper that delegates to the generic [DesignListScreen] for the
// service variant (Electrical, FADS, ICT, HVAC stages).

import 'package:flutter/material.dart';

import 'design_list_screen.dart';
import 'engineering_design_shared.dart';

class ServiceDesignScreen extends StatelessWidget {
  const ServiceDesignScreen({super.key});

  @override
  Widget build(BuildContext context) =>
      const DesignListScreen(kind: EngineeringDesignKind.service);
}
