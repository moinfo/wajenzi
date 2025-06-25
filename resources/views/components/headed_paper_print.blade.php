@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $showPrintButton = $showPrintButton ?? true;
@endphp

<div class="print-document">
    <!-- Print Button -->
    @if($showPrintButton)
    <div class="no-print mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <a href="javascript:history.back()" class="btn font-weight-bold px-4 py-2" style="background: linear-gradient(90deg, #6c757d 0%, #868e96 100%); color: white; border: none; border-radius: 8px;">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <button onclick="window.print()" class="btn font-weight-bold px-6 py-3" style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); color: white; border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(27, 197, 189, 0.35);">
                <i class="fas fa-print mr-2"></i>Print Document
            </button>
        </div>
    </div>
    @endif

    <!-- Print Header -->
    <div class="print-header">
        <div class="company-header" style="border-bottom: 4px solid #1BC5BD; padding-bottom: 20px; margin-bottom: 30px;">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-left">
                    <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo" style="height: 100px; max-width: 100%;">
                </div>
                <div class="col-md-6 text-center">
                    <h1 class="company-name" style="font-size: 2.2rem; font-weight: 800; color: #1BC5BD; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">
                        {{settings('ORGANIZATION_NAME')}}
                    </h1>
                    <div class="company-details" style="color: #6c757d; line-height: 1.6;">
                        <p class="mb-1" style="font-size: 1rem; font-weight: 500;">
                            <i class="fas fa-map-marker-alt text-primary mr-2"></i>{{settings('COMPANY_ADDRESS_LINE_1')}}
                        </p>
                        @if(settings('COMPANY_ADDRESS_LINE_2'))
                        <p class="mb-1" style="font-size: 1rem;">{{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                        @endif
                        <p class="mb-1" style="font-size: 1rem;">
                            <i class="fas fa-phone text-primary mr-2"></i>{{settings('COMPANY_PHONE_NUMBER')}}
                        </p>
                        @if(settings('TAX_IDENTIFICATION_NUMBER'))
                        <p class="mb-0" style="font-size: 1rem;">
                            <i class="fas fa-file-invoice text-primary mr-2"></i>TIN: {{settings('TAX_IDENTIFICATION_NUMBER')}}
                        </p>
                        @endif
                    </div>
                </div>
                <div class="col-md-3 text-center text-md-right">
                    @if($title)
                    <div class="document-title" style="background: linear-gradient(135deg, #1BC5BD, #1DC9C0); color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(27, 197, 189, 0.25);">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; text-transform: uppercase;">{{ $title }}</h2>
                        @if($subtitle)
                        <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 0.9rem;">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Document Content Placeholder -->
    <!-- Content will be added after this include -->

<style>
/* Print-specific styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .print-document {
        margin: 0;
        padding: 20px;
        background: white;
    }
    
    .company-header {
        border-bottom: 4px solid #1BC5BD !important;
        page-break-inside: avoid;
    }
    
    .company-name {
        color: #1BC5BD !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .document-title {
        background: linear-gradient(135deg, #1BC5BD, #1DC9C0) !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .text-primary {
        color: #1BC5BD !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

/* Screen styles */
@media screen {
    .print-document {
        background: white;
        min-height: 100vh;
        padding: 20px;
    }
    
    .company-header {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 30px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }
}
</style>

<!-- Close print-document div after content -->