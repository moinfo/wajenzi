<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocumentSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = BillingDocumentSetting::orderBy('setting_key')->get()->groupBy('setting_type');
        
        return view('billing.settings.index', compact('settings'));
    }

    public function edit()
    {
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        return view('billing.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'invoice_prefix' => 'required|string|max:10',
            'quote_prefix' => 'required|string|max:10',
            'proforma_prefix' => 'required|string|max:10',
            'credit_note_prefix' => 'required|string|max:10',
            'receipt_prefix' => 'required|string|max:10',
            'payment_prefix' => 'required|string|max:10',
            'number_format' => 'required|string|max:20',
            'default_payment_terms' => 'required|in:immediate,net_7,net_15,net_30,net_45,net_60,net_90,custom',
            'default_currency' => 'required|string|size:3',
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_terms' => 'nullable|string',
            'invoice_footer' => 'nullable|string'
        ]);

        $settingsToUpdate = [
            'invoice_prefix' => $request->invoice_prefix,
            'quote_prefix' => $request->quote_prefix,
            'proforma_prefix' => $request->proforma_prefix,
            'credit_note_prefix' => $request->credit_note_prefix,
            'receipt_prefix' => $request->receipt_prefix,
            'payment_prefix' => $request->payment_prefix,
            'number_format' => $request->number_format,
            'default_payment_terms' => $request->default_payment_terms,
            'default_currency' => $request->default_currency,
            'default_tax_rate' => $request->default_tax_rate,
            'invoice_terms' => $request->invoice_terms ?? '',
            'invoice_footer' => $request->invoice_footer ?? ''
        ];

        foreach ($settingsToUpdate as $key => $value) {
            BillingDocumentSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return redirect()
            ->route('billing.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function reset()
    {
        $defaultSettings = [
            'invoice_prefix' => 'INV-',
            'quote_prefix' => 'QT-',
            'proforma_prefix' => 'PRO-',
            'credit_note_prefix' => 'CN-',
            'receipt_prefix' => 'RCP-',
            'payment_prefix' => 'PAY-',
            'number_format' => 'YYYY-00000',
            'default_payment_terms' => 'net_30',
            'default_currency' => 'TZS',
            'default_tax_rate' => '18',
            'invoice_terms' => 'Payment is due within the specified payment terms. Late payments may incur additional charges.',
            'invoice_footer' => 'Thank you for your business!'
        ];

        foreach ($defaultSettings as $key => $value) {
            BillingDocumentSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return redirect()
            ->route('billing.settings.index')
            ->with('success', 'Settings reset to default values.');
    }

    public function companyInfo()
    {
        $settings = BillingDocumentSetting::whereIn('setting_key', [
            'company_name', 'company_address', 'company_phone', 'company_email',
            'company_website', 'company_tax_id', 'company_logo'
        ])->pluck('setting_value', 'setting_key');

        return view('billing.settings.company-info', compact('settings'));
    }

    public function updateCompanyInfo(Request $request)
    {
        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_tax_id' => 'nullable|string|max:100',
            'company_logo' => 'nullable|image|max:2048'
        ]);

        $settingsToUpdate = [
            'company_name' => $request->company_name ?? '',
            'company_address' => $request->company_address ?? '',
            'company_phone' => $request->company_phone ?? '',
            'company_email' => $request->company_email ?? '',
            'company_website' => $request->company_website ?? '',
            'company_tax_id' => $request->company_tax_id ?? ''
        ];

        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('company', 'public');
            $settingsToUpdate['company_logo'] = $logoPath;
        }

        foreach ($settingsToUpdate as $key => $value) {
            BillingDocumentSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'setting_type' => $key === 'company_logo' ? 'file' : 'text'
                ]
            );
        }

        return redirect()
            ->route('billing.settings.company-info')
            ->with('success', 'Company information updated successfully.');
    }

    public function emailSettings()
    {
        $settings = BillingDocumentSetting::whereIn('setting_key', [
            'email_from_name', 'email_from_address', 'email_subject_invoice',
            'email_subject_quote', 'email_subject_proforma', 'email_template_invoice',
            'email_template_quote', 'email_template_proforma'
        ])->pluck('setting_value', 'setting_key');

        return view('billing.settings.email-settings', compact('settings'));
    }

    public function updateEmailSettings(Request $request)
    {
        $request->validate([
            'email_from_name' => 'nullable|string|max:255',
            'email_from_address' => 'nullable|email|max:255',
            'email_subject_invoice' => 'nullable|string|max:255',
            'email_subject_quote' => 'nullable|string|max:255',
            'email_subject_proforma' => 'nullable|string|max:255',
            'email_template_invoice' => 'nullable|string',
            'email_template_quote' => 'nullable|string',
            'email_template_proforma' => 'nullable|string'
        ]);

        $settingsToUpdate = [
            'email_from_name' => $request->email_from_name ?? '',
            'email_from_address' => $request->email_from_address ?? '',
            'email_subject_invoice' => $request->email_subject_invoice ?? 'Invoice #{document_number}',
            'email_subject_quote' => $request->email_subject_quote ?? 'Quote #{document_number}',
            'email_subject_proforma' => $request->email_subject_proforma ?? 'Proforma Invoice #{document_number}',
            'email_template_invoice' => $request->email_template_invoice ?? 'Dear {client_name},\n\nPlease find attached your invoice #{document_number}.\n\nThank you for your business.',
            'email_template_quote' => $request->email_template_quote ?? 'Dear {client_name},\n\nPlease find attached your quote #{document_number}.\n\nWe look forward to working with you.',
            'email_template_proforma' => $request->email_template_proforma ?? 'Dear {client_name},\n\nPlease find attached your proforma invoice #{document_number}.\n\nThank you for your business.'
        ];

        foreach ($settingsToUpdate as $key => $value) {
            BillingDocumentSetting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'setting_type' => in_array($key, ['email_template_invoice', 'email_template_quote', 'email_template_proforma']) ? 'textarea' : 'text'
                ]
            );
        }

        return redirect()
            ->route('billing.settings.email-settings')
            ->with('success', 'Email settings updated successfully.');
    }

    public function getSetting($key)
    {
        $setting = BillingDocumentSetting::where('setting_key', $key)->first();
        
        return $setting ? $setting->setting_value : null;
    }
}