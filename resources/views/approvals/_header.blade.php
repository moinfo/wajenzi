<div class="page-header">
    <div class="header-content">

        <div class="card-box">
            <div class="row" style="margin-bottom: 10px;">
                <!-- Logo Section -->
                <div class="col-md-1">
                    <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo" height="80">
                </div>

                <!-- Address Section -->
                <div class="col-md-8">
                    <p style="margin-bottom: 3px; font-weight: bold;">{{settings('ORGANIZATION_NAME')}}</p>
                    <p style="margin-bottom: 3px;">{{settings('COMPANY_ADDRESS_LINE_1')}}</p>
                    <p style="margin-bottom: 3px;">Tel: {{settings('COMPANY_PHONE_NUMBER')}}</p>
                    <p style="margin-bottom: 3px;">Mob: </p>
                    <p style="margin-bottom: 3px;">P. O. Box {{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                </div>

                <!-- Title Section -->
                <div class="col-md-3 text-right">
                    <h2 style="font-weight: bold; margin-bottom: 15px;">{{$page_name}}</h2>
                    <?php
                        if($approval_data->document_number){
                            ?>
                    <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                        <p style="margin-bottom: 0;"><b>No. {{$approval_data->document_number}}</b></p>
                    </div>
                    <?php
                        }
                        ?>

                </div>
            </div>

            <!-- Request Information -->
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-6">
                    <p style="margin-bottom: 5px;">Requested by : {{$requestedBy ?? 'System Admin'}}</p>
                    <p style="margin-bottom: 5px;">Address : {{settings('COMPANY_ADDRESS_LINE_1')}}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p style="margin-bottom: 5px;">Created Time : {{$approval_data->created_at}}</p>
                </div>
            </div>
        </div>

        <h1 class="page-title"></h1>
        <div class="bank-name">{{ $approval_data_name }}</div>
</div>
</div>
