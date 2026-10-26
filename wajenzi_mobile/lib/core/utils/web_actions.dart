import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../presentation/providers/auth_provider.dart';
import 'web_action_permissions.g.dart';

/// Whether the signed-in user may use the Add / Edit / Delete control of a
/// list screen, using the same permissions the web checks around that control
/// (see `web_action_permissions.g.dart`, generated from the web templates).
///
/// [route] is the web route name of the page the screen mirrors and [action]
/// is `add`, `edit` or `delete`. Like the web, an action with no permission
/// check on its page is allowed for everyone who can open the screen.
bool canAct(BuildContext context, String route, String action) {
  final required = webActionPermissions[route]?[action];
  if (required == null || required.isEmpty) return true;

  final auth = ProviderScope.containerOf(context).read(authStateProvider).valueOrNull;
  final granted = auth?.user?.permissions;
  if (granted == null) return false;
  return required.any(granted.contains);
}
