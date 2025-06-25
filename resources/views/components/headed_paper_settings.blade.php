<div class="card card-custom shadow-lg border-0" style="overflow: hidden;">
    <div class="card-header" style="background: linear-gradient(135deg, #ffffff 0%, #f3f6f9 100%); border: none; padding: 0;">
        <div style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); height: 5px;"></div>
        <div class="px-4 py-4">
            <div class="row align-items-center">
                <div class="col-lg-3 text-center text-lg-left mb-3 mb-lg-0">
                    <div class="position-relative d-inline-block">
                        <img class="img-fluid" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo" style="height: 90px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15));">
                        <div class="position-absolute" style="top: -10px; right: -10px; width: 30px; height: 30px; background: linear-gradient(135deg, #1BC5BD, #1DC9C0); border-radius: 50%; opacity: 0.3;"></div>
                    </div>
                </div>
                <div class="col-lg-6 text-center mb-3 mb-lg-0">
                    <h1 class="font-weight-boldest text-dark mb-3" style="font-size: 2rem; letter-spacing: 1px; text-transform: uppercase;">{{settings('ORGANIZATION_NAME')}}</h1>
                    <div class="company-details" style="background: #f8f9fa; border-radius: 10px; padding: 15px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);">
                        <div class="d-flex flex-column align-items-center" style="gap: 8px;">
                            <div class="d-flex align-items-center">
                                <span class="svg-icon svg-icon-primary svg-icon-2x mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#1BC5BD"/>
                                    </svg>
                                </span>
                                <span class="font-size-h6 text-dark-75 font-weight-normal">{{settings('COMPANY_ADDRESS_LINE_1')}}</span>
                            </div>
                            @if(settings('COMPANY_ADDRESS_LINE_2'))
                            <span class="font-size-h6 text-dark-75 font-weight-normal ml-7">{{settings('COMPANY_ADDRESS_LINE_2')}}</span>
                            @endif
                            <div class="d-flex align-items-center">
                                <span class="svg-icon svg-icon-primary svg-icon-2x mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" fill="#1BC5BD"/>
                                    </svg>
                                </span>
                                <span class="font-size-h6 text-dark-75 font-weight-normal">{{settings('COMPANY_PHONE_NUMBER')}}</span>
                            </div>
                            @if(settings('TAX_IDENTIFICATION_NUMBER'))
                            <div class="d-flex align-items-center">
                                <span class="svg-icon svg-icon-primary svg-icon-2x mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" fill="#1BC5BD"/>
                                    </svg>
                                </span>
                                <span class="font-size-h6 text-dark-75 font-weight-medium">TIN: {{settings('TAX_IDENTIFICATION_NUMBER')}}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 text-center text-lg-right">
                    <a href="{{route('hr_settings')}}" class="btn font-weight-bold px-6 py-3" style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); color: white; border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(27, 197, 189, 0.35); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(27, 197, 189, 0.45)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(27, 197, 189, 0.35)';">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
