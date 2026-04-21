import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';
import 'app_localizations_fr.dart';
import 'app_localizations_sw.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
    Locale('fr'),
    Locale('sw'),
  ];

  /// No description provided for @appName.
  ///
  /// In en, this message translates to:
  /// **'Wajenzi Professionals'**
  String get appName;

  /// No description provided for @appTitle.
  ///
  /// In en, this message translates to:
  /// **'Wajenzi Construction ERP'**
  String get appTitle;

  /// No description provided for @commonOk.
  ///
  /// In en, this message translates to:
  /// **'OK'**
  String get commonOk;

  /// No description provided for @commonCancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get commonCancel;

  /// No description provided for @commonSave.
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get commonSave;

  /// No description provided for @commonDelete.
  ///
  /// In en, this message translates to:
  /// **'Delete'**
  String get commonDelete;

  /// No description provided for @commonEdit.
  ///
  /// In en, this message translates to:
  /// **'Edit'**
  String get commonEdit;

  /// No description provided for @commonAdd.
  ///
  /// In en, this message translates to:
  /// **'Add'**
  String get commonAdd;

  /// No description provided for @commonSearch.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get commonSearch;

  /// No description provided for @commonFilter.
  ///
  /// In en, this message translates to:
  /// **'Filter'**
  String get commonFilter;

  /// No description provided for @commonLoading.
  ///
  /// In en, this message translates to:
  /// **'Loading...'**
  String get commonLoading;

  /// No description provided for @commonError.
  ///
  /// In en, this message translates to:
  /// **'Error'**
  String get commonError;

  /// No description provided for @commonSuccess.
  ///
  /// In en, this message translates to:
  /// **'Success'**
  String get commonSuccess;

  /// No description provided for @commonRetry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get commonRetry;

  /// No description provided for @commonYes.
  ///
  /// In en, this message translates to:
  /// **'Yes'**
  String get commonYes;

  /// No description provided for @commonNo.
  ///
  /// In en, this message translates to:
  /// **'No'**
  String get commonNo;

  /// No description provided for @commonClose.
  ///
  /// In en, this message translates to:
  /// **'Close'**
  String get commonClose;

  /// No description provided for @commonBack.
  ///
  /// In en, this message translates to:
  /// **'Back'**
  String get commonBack;

  /// No description provided for @commonNext.
  ///
  /// In en, this message translates to:
  /// **'Next'**
  String get commonNext;

  /// No description provided for @commonPrevious.
  ///
  /// In en, this message translates to:
  /// **'Previous'**
  String get commonPrevious;

  /// No description provided for @commonSubmit.
  ///
  /// In en, this message translates to:
  /// **'Submit'**
  String get commonSubmit;

  /// No description provided for @commonReset.
  ///
  /// In en, this message translates to:
  /// **'Reset'**
  String get commonReset;

  /// No description provided for @commonClear.
  ///
  /// In en, this message translates to:
  /// **'Clear'**
  String get commonClear;

  /// No description provided for @commonSelect.
  ///
  /// In en, this message translates to:
  /// **'Select'**
  String get commonSelect;

  /// No description provided for @commonSelected.
  ///
  /// In en, this message translates to:
  /// **'Selected'**
  String get commonSelected;

  /// No description provided for @commonAll.
  ///
  /// In en, this message translates to:
  /// **'All'**
  String get commonAll;

  /// No description provided for @commonNone.
  ///
  /// In en, this message translates to:
  /// **'None'**
  String get commonNone;

  /// No description provided for @commonTotal.
  ///
  /// In en, this message translates to:
  /// **'Total'**
  String get commonTotal;

  /// No description provided for @commonAmount.
  ///
  /// In en, this message translates to:
  /// **'Amount'**
  String get commonAmount;

  /// No description provided for @commonDate.
  ///
  /// In en, this message translates to:
  /// **'Date'**
  String get commonDate;

  /// No description provided for @commonTime.
  ///
  /// In en, this message translates to:
  /// **'Time'**
  String get commonTime;

  /// No description provided for @commonStatus.
  ///
  /// In en, this message translates to:
  /// **'Status'**
  String get commonStatus;

  /// No description provided for @commonDescription.
  ///
  /// In en, this message translates to:
  /// **'Description'**
  String get commonDescription;

  /// No description provided for @commonNotes.
  ///
  /// In en, this message translates to:
  /// **'Notes'**
  String get commonNotes;

  /// No description provided for @commonComments.
  ///
  /// In en, this message translates to:
  /// **'Comments'**
  String get commonComments;

  /// No description provided for @commonAttachments.
  ///
  /// In en, this message translates to:
  /// **'Attachments'**
  String get commonAttachments;

  /// No description provided for @commonDownload.
  ///
  /// In en, this message translates to:
  /// **'Download'**
  String get commonDownload;

  /// No description provided for @commonUpload.
  ///
  /// In en, this message translates to:
  /// **'Upload'**
  String get commonUpload;

  /// No description provided for @commonView.
  ///
  /// In en, this message translates to:
  /// **'View'**
  String get commonView;

  /// No description provided for @commonDetails.
  ///
  /// In en, this message translates to:
  /// **'Details'**
  String get commonDetails;

  /// No description provided for @commonSummary.
  ///
  /// In en, this message translates to:
  /// **'Summary'**
  String get commonSummary;

  /// No description provided for @commonReport.
  ///
  /// In en, this message translates to:
  /// **'Report'**
  String get commonReport;

  /// No description provided for @commonSettings.
  ///
  /// In en, this message translates to:
  /// **'Settings'**
  String get commonSettings;

  /// No description provided for @commonProfile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get commonProfile;

  /// No description provided for @commonLogout.
  ///
  /// In en, this message translates to:
  /// **'Logout'**
  String get commonLogout;

  /// No description provided for @commonLogin.
  ///
  /// In en, this message translates to:
  /// **'Login'**
  String get commonLogin;

  /// No description provided for @commonRegister.
  ///
  /// In en, this message translates to:
  /// **'Register'**
  String get commonRegister;

  /// No description provided for @commonForgotPassword.
  ///
  /// In en, this message translates to:
  /// **'Forgot Password'**
  String get commonForgotPassword;

  /// No description provided for @commonChangePassword.
  ///
  /// In en, this message translates to:
  /// **'Change Password'**
  String get commonChangePassword;

  /// No description provided for @commonConfirmPassword.
  ///
  /// In en, this message translates to:
  /// **'Confirm Password'**
  String get commonConfirmPassword;

  /// No description provided for @commonEmail.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get commonEmail;

  /// No description provided for @commonPassword.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get commonPassword;

  /// No description provided for @commonPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get commonPhone;

  /// No description provided for @commonName.
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get commonName;

  /// No description provided for @commonFirstName.
  ///
  /// In en, this message translates to:
  /// **'First Name'**
  String get commonFirstName;

  /// No description provided for @commonLastName.
  ///
  /// In en, this message translates to:
  /// **'Last Name'**
  String get commonLastName;

  /// No description provided for @commonUsername.
  ///
  /// In en, this message translates to:
  /// **'Username'**
  String get commonUsername;

  /// No description provided for @commonFullName.
  ///
  /// In en, this message translates to:
  /// **'Full Name'**
  String get commonFullName;

  /// No description provided for @commonAddress.
  ///
  /// In en, this message translates to:
  /// **'Address'**
  String get commonAddress;

  /// No description provided for @commonCity.
  ///
  /// In en, this message translates to:
  /// **'City'**
  String get commonCity;

  /// No description provided for @commonCountry.
  ///
  /// In en, this message translates to:
  /// **'Country'**
  String get commonCountry;

  /// No description provided for @commonLanguage.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get commonLanguage;

  /// No description provided for @commonTheme.
  ///
  /// In en, this message translates to:
  /// **'Theme'**
  String get commonTheme;

  /// No description provided for @commonDarkMode.
  ///
  /// In en, this message translates to:
  /// **'Dark Mode'**
  String get commonDarkMode;

  /// No description provided for @commonLightMode.
  ///
  /// In en, this message translates to:
  /// **'Light Mode'**
  String get commonLightMode;

  /// No description provided for @commonNotifications.
  ///
  /// In en, this message translates to:
  /// **'Notifications'**
  String get commonNotifications;

  /// No description provided for @commonCurrency.
  ///
  /// In en, this message translates to:
  /// **'Currency'**
  String get commonCurrency;

  /// No description provided for @commonTax.
  ///
  /// In en, this message translates to:
  /// **'Tax'**
  String get commonTax;

  /// No description provided for @commonVat.
  ///
  /// In en, this message translates to:
  /// **'VAT'**
  String get commonVat;

  /// No description provided for @commonDiscount.
  ///
  /// In en, this message translates to:
  /// **'Discount'**
  String get commonDiscount;

  /// No description provided for @commonSubtotal.
  ///
  /// In en, this message translates to:
  /// **'Subtotal'**
  String get commonSubtotal;

  /// No description provided for @commonGrandTotal.
  ///
  /// In en, this message translates to:
  /// **'Grand Total'**
  String get commonGrandTotal;

  /// No description provided for @navigationDashboard.
  ///
  /// In en, this message translates to:
  /// **'Dashboard'**
  String get navigationDashboard;

  /// No description provided for @navigationProjects.
  ///
  /// In en, this message translates to:
  /// **'Projects'**
  String get navigationProjects;

  /// No description provided for @navigationExpenses.
  ///
  /// In en, this message translates to:
  /// **'Expenses'**
  String get navigationExpenses;

  /// No description provided for @navigationAttendance.
  ///
  /// In en, this message translates to:
  /// **'Attendance'**
  String get navigationAttendance;

  /// No description provided for @navigationApprovals.
  ///
  /// In en, this message translates to:
  /// **'Approvals'**
  String get navigationApprovals;

  /// No description provided for @navigationBilling.
  ///
  /// In en, this message translates to:
  /// **'Billing'**
  String get navigationBilling;

  /// No description provided for @navigationMaterials.
  ///
  /// In en, this message translates to:
  /// **'Materials'**
  String get navigationMaterials;

  /// No description provided for @navigationProcurement.
  ///
  /// In en, this message translates to:
  /// **'Procurement'**
  String get navigationProcurement;

  /// No description provided for @navigationVat.
  ///
  /// In en, this message translates to:
  /// **'VAT'**
  String get navigationVat;

  /// No description provided for @navigationPayroll.
  ///
  /// In en, this message translates to:
  /// **'Payroll'**
  String get navigationPayroll;

  /// No description provided for @navigationStaff.
  ///
  /// In en, this message translates to:
  /// **'Staff'**
  String get navigationStaff;

  /// No description provided for @navigationLeave.
  ///
  /// In en, this message translates to:
  /// **'Leave'**
  String get navigationLeave;

  /// No description provided for @navigationArchitectBonus.
  ///
  /// In en, this message translates to:
  /// **'Architect Bonus'**
  String get navigationArchitectBonus;

  /// No description provided for @navigationProvisionTax.
  ///
  /// In en, this message translates to:
  /// **'Provision Tax'**
  String get navigationProvisionTax;

  /// No description provided for @navigationSettings.
  ///
  /// In en, this message translates to:
  /// **'Settings'**
  String get navigationSettings;

  /// No description provided for @navigationAbout.
  ///
  /// In en, this message translates to:
  /// **'About'**
  String get navigationAbout;

  /// No description provided for @navigationHelp.
  ///
  /// In en, this message translates to:
  /// **'Help'**
  String get navigationHelp;

  /// No description provided for @navigationSupport.
  ///
  /// In en, this message translates to:
  /// **'Support'**
  String get navigationSupport;

  /// No description provided for @expensesTitle.
  ///
  /// In en, this message translates to:
  /// **'Expenses'**
  String get expensesTitle;

  /// No description provided for @expensesProjectExpenses.
  ///
  /// In en, this message translates to:
  /// **'Project Expenses'**
  String get expensesProjectExpenses;

  /// No description provided for @expensesAddExpense.
  ///
  /// In en, this message translates to:
  /// **'Add Expense'**
  String get expensesAddExpense;

  /// No description provided for @expensesEditExpense.
  ///
  /// In en, this message translates to:
  /// **'Edit Expense'**
  String get expensesEditExpense;

  /// No description provided for @expensesExpenseDetails.
  ///
  /// In en, this message translates to:
  /// **'Expense Details'**
  String get expensesExpenseDetails;

  /// No description provided for @expensesExpenseAmount.
  ///
  /// In en, this message translates to:
  /// **'Expense Amount'**
  String get expensesExpenseAmount;

  /// No description provided for @expensesExpenseCategory.
  ///
  /// In en, this message translates to:
  /// **'Expense Category'**
  String get expensesExpenseCategory;

  /// No description provided for @expensesExpenseSubCategory.
  ///
  /// In en, this message translates to:
  /// **'Expense Sub-Category'**
  String get expensesExpenseSubCategory;

  /// No description provided for @expensesExpenseDate.
  ///
  /// In en, this message translates to:
  /// **'Expense Date'**
  String get expensesExpenseDate;

  /// No description provided for @expensesExpenseDescription.
  ///
  /// In en, this message translates to:
  /// **'Expense Description'**
  String get expensesExpenseDescription;

  /// No description provided for @expensesExpenseReceipt.
  ///
  /// In en, this message translates to:
  /// **'Expense Receipt'**
  String get expensesExpenseReceipt;

  /// No description provided for @expensesExpenseStatus.
  ///
  /// In en, this message translates to:
  /// **'Expense Status'**
  String get expensesExpenseStatus;

  /// No description provided for @expensesExpenseTotal.
  ///
  /// In en, this message translates to:
  /// **'Total Expenses'**
  String get expensesExpenseTotal;

  /// No description provided for @expensesExpenseCategories.
  ///
  /// In en, this message translates to:
  /// **'Expense Categories'**
  String get expensesExpenseCategories;

  /// No description provided for @expensesExpenseSubCategories.
  ///
  /// In en, this message translates to:
  /// **'Expense Sub-Categories'**
  String get expensesExpenseSubCategories;

  /// No description provided for @expensesSearchExpenses.
  ///
  /// In en, this message translates to:
  /// **'Search expenses...'**
  String get expensesSearchExpenses;

  /// No description provided for @expensesNoExpenses.
  ///
  /// In en, this message translates to:
  /// **'No expenses found'**
  String get expensesNoExpenses;

  /// No description provided for @expensesExpenseSaved.
  ///
  /// In en, this message translates to:
  /// **'Expense saved successfully'**
  String get expensesExpenseSaved;

  /// No description provided for @expensesExpenseDeleted.
  ///
  /// In en, this message translates to:
  /// **'Expense deleted successfully'**
  String get expensesExpenseDeleted;

  /// No description provided for @expensesExpenseApproved.
  ///
  /// In en, this message translates to:
  /// **'Expense approved'**
  String get expensesExpenseApproved;

  /// No description provided for @expensesExpenseRejected.
  ///
  /// In en, this message translates to:
  /// **'Expense rejected'**
  String get expensesExpenseRejected;

  /// No description provided for @expensesExpensePending.
  ///
  /// In en, this message translates to:
  /// **'Expense pending approval'**
  String get expensesExpensePending;

  /// No description provided for @expensesFilterByDate.
  ///
  /// In en, this message translates to:
  /// **'Filter by date'**
  String get expensesFilterByDate;

  /// No description provided for @expensesFilterByCategory.
  ///
  /// In en, this message translates to:
  /// **'Filter by category'**
  String get expensesFilterByCategory;

  /// No description provided for @expensesFilterByProject.
  ///
  /// In en, this message translates to:
  /// **'Filter by project'**
  String get expensesFilterByProject;

  /// No description provided for @expensesStartDate.
  ///
  /// In en, this message translates to:
  /// **'Start Date'**
  String get expensesStartDate;

  /// No description provided for @expensesEndDate.
  ///
  /// In en, this message translates to:
  /// **'End Date'**
  String get expensesEndDate;

  /// No description provided for @expensesSelectCategory.
  ///
  /// In en, this message translates to:
  /// **'Select Category'**
  String get expensesSelectCategory;

  /// No description provided for @expensesSelectSubCategory.
  ///
  /// In en, this message translates to:
  /// **'Select Sub-Category'**
  String get expensesSelectSubCategory;

  /// No description provided for @expensesSelectProject.
  ///
  /// In en, this message translates to:
  /// **'Select Project'**
  String get expensesSelectProject;

  /// No description provided for @expensesClearFilters.
  ///
  /// In en, this message translates to:
  /// **'Clear Filters'**
  String get expensesClearFilters;

  /// No description provided for @expensesApplyFilters.
  ///
  /// In en, this message translates to:
  /// **'Apply Filters'**
  String get expensesApplyFilters;

  /// No description provided for @expensesStatusApproved.
  ///
  /// In en, this message translates to:
  /// **'APPROVED'**
  String get expensesStatusApproved;

  /// No description provided for @expensesStatusPending.
  ///
  /// In en, this message translates to:
  /// **'PENDING'**
  String get expensesStatusPending;

  /// No description provided for @expensesStatusRejected.
  ///
  /// In en, this message translates to:
  /// **'REJECTED'**
  String get expensesStatusRejected;

  /// No description provided for @expensesStatusDraft.
  ///
  /// In en, this message translates to:
  /// **'DRAFT'**
  String get expensesStatusDraft;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en', 'fr', 'sw'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
    case 'fr':
      return AppLocalizationsFr();
    case 'sw':
      return AppLocalizationsSw();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
