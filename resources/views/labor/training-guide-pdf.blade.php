<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Labor Procurement - Training Guide</title>
    <style>
        @page {
            margin: 60px 50px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Cover Page */
        .cover {
            text-align: center;
            padding-top: 200px;
            page-break-after: always;
        }
        .cover h1 {
            font-size: 32px;
            color: #1e40af;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .cover .subtitle {
            font-size: 18px;
            color: #475569;
            margin-bottom: 40px;
        }
        .cover .divider {
            width: 120px;
            height: 3px;
            background: #1e40af;
            margin: 30px auto;
        }
        .cover .meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 60px;
        }
        .cover .meta p {
            margin: 4px 0;
        }

        /* Section Headers */
        h1 {
            font-size: 22px;
            color: #1e40af;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 6px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        h2 {
            font-size: 16px;
            color: #1e3a5f;
            margin-top: 24px;
            margin-bottom: 10px;
            border-left: 4px solid #1e40af;
            padding-left: 10px;
        }
        h3 {
            font-size: 13px;
            color: #334155;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 18px 0;
            font-size: 10px;
        }
        table th {
            background: #1e40af;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        table tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            color: #fff;
        }
        .badge-draft { background: #94a3b8; }
        .badge-pending { background: #f59e0b; }
        .badge-approved { background: #10b981; }
        .badge-rejected { background: #ef4444; }
        .badge-active { background: #3b82f6; }
        .badge-paid { background: #059669; }
        .badge-due { background: #8b5cf6; }
        .badge-held { background: #f97316; }

        /* Step Boxes */
        .step-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 14px 16px;
            margin: 12px 0;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .step-box .step-header {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 6px;
        }
        .step-box .step-who {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 8px;
            font-style: italic;
        }
        .step-box ul {
            margin: 4px 0;
            padding-left: 18px;
        }
        .step-box li {
            margin-bottom: 3px;
        }

        /* Info Box */
        .info-box {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 10px;
            page-break-inside: avoid;
        }
        .info-box strong {
            color: #1e40af;
        }

        /* Warning Box */
        .warn-box {
            background: #fefce8;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 10px;
            page-break-inside: avoid;
        }

        /* Flow Diagram */
        .flow-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 14px 0;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.8;
            page-break-inside: avoid;
        }

        /* Numbered list */
        ol {
            padding-left: 20px;
        }
        ol li {
            margin-bottom: 4px;
        }
        ul {
            padding-left: 20px;
        }
        ul li {
            margin-bottom: 3px;
        }

        /* Page breaks */
        .page-break {
            page-break-before: always;
        }

        /* Arrow connector */
        .arrow {
            color: #1e40af;
            font-weight: bold;
        }

        /* TOC */
        .toc {
            page-break-after: always;
        }
        .toc h2 {
            border-left: none;
            padding-left: 0;
            text-align: center;
            font-size: 20px;
            color: #1e40af;
        }
        .toc-item {
            padding: 6px 0;
            border-bottom: 1px dotted #cbd5e1;
            font-size: 12px;
        }
        .toc-item span {
            color: #64748b;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- ============ COVER PAGE ============ --}}
    <div class="cover">
        <h1>LABOR PROCUREMENT</h1>
        <div class="subtitle">Training Guide & Standard Operating Procedures</div>
        <div class="divider"></div>
        <p style="font-size: 14px; color: #475569;">Wajenzi Construction Management System</p>
        <div class="meta">
            <p>Version 1.0</p>
            <p>{{ now()->format('F Y') }}</p>
            <p>Confidential — Internal Use Only</p>
        </div>
    </div>

    {{-- ============ TABLE OF CONTENTS ============ --}}
    <div class="toc">
        <h2>Table of Contents</h2>
        <div class="toc-item"><strong>1.</strong> Overview & Process Flow <span>........... 3</span></div>
        <div class="toc-item"><strong>2.</strong> Roles, Permissions & Approvers <span>........... 3</span></div>
        <div class="toc-item"><strong>3.</strong> Step 1 — Create a Labor Request <span>........... 4</span></div>
        <div class="toc-item"><strong>4.</strong> Step 2 — Submit for Approval <span>........... 5</span></div>
        <div class="toc-item"><strong>5.</strong> Step 3 — MD Approves / Rejects Request <span>........... 5</span></div>
        <div class="toc-item"><strong>6.</strong> Step 4 — Create a Labor Contract <span>........... 6</span></div>
        <div class="toc-item"><strong>7.</strong> Step 5 — Sign the Contract <span>........... 7</span></div>
        <div class="toc-item"><strong>8.</strong> Step 6 — Log Daily Work <span>........... 7</span></div>
        <div class="toc-item"><strong>9.</strong> Step 7 — Conduct Labor Inspection <span>........... 8</span></div>
        <div class="toc-item"><strong>10.</strong> Step 8 — Supervisor Verifies Inspection <span>........... 9</span></div>
        <div class="toc-item"><strong>11.</strong> Step 9 — MD Approves Inspection <span>........... 9</span></div>
        <div class="toc-item"><strong>12.</strong> Step 10 — MD Approves Payment <span>........... 10</span></div>
        <div class="toc-item"><strong>13.</strong> Step 11 — Finance Processes Payment <span>........... 10</span></div>
        <div class="toc-item"><strong>14.</strong> Special Actions <span>........... 11</span></div>
        <div class="toc-item"><strong>15.</strong> Status Flow Diagrams <span>........... 11</span></div>
        <div class="toc-item"><strong>16.</strong> Dashboard Overview <span>........... 12</span></div>
    </div>

    {{-- ============ SECTION 1: OVERVIEW ============ --}}
    <h1>1. Overview & Process Flow</h1>

    <p>The Labor Procurement module manages the full lifecycle of hiring artisans and labour for construction projects — from requesting labour, through contracts and inspections, to final payment.</p>

    <div class="flow-box">
        <strong>The Complete Flow:</strong><br><br>
        Labor Request <span class="arrow">&rarr;</span> Approval <span class="arrow">&rarr;</span> Contract <span class="arrow">&rarr;</span> Sign Contract <span class="arrow">&rarr;</span> Work Logs <span class="arrow">&rarr;</span> Inspection <span class="arrow">&rarr;</span> Payment Approval <span class="arrow">&rarr;</span> Payment Processing
    </div>

    <div class="info-box">
        <strong>Navigation:</strong> Access all features from the sidebar under <strong>Labor Procurement</strong>, which contains six sub-menus: Labor Dashboard, Labor Requests, Labor Contracts, Work Logs, Labor Inspections, and Labor Payments.
    </div>

    {{-- ============ SECTION 2: PERMISSIONS ============ --}}
    <h1>2. Roles, Permissions & Approvers</h1>

    <h2>2.1 Role Permissions Matrix</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 28%;">Feature</th>
                <th style="width: 24%;">MD / Admin</th>
                <th style="width: 24%;">Supervisor</th>
                <th style="width: 24%;">Finance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Labor Dashboard</strong></td>
                <td>View</td>
                <td>View</td>
                <td>—</td>
            </tr>
            <tr>
                <td><strong>Labor Requests</strong></td>
                <td>Add, Edit, Delete, Approve, Reject</td>
                <td>—</td>
                <td>—</td>
            </tr>
            <tr>
                <td><strong>Labor Contracts</strong></td>
                <td>Add, Edit, Sign</td>
                <td>—</td>
                <td>—</td>
            </tr>
            <tr>
                <td><strong>Work Logs</strong></td>
                <td>Add, Edit</td>
                <td>Add, Edit</td>
                <td>—</td>
            </tr>
            <tr>
                <td><strong>Labor Inspections</strong></td>
                <td>Add, Edit, Approve, Reject</td>
                <td>Add, Edit, Verify</td>
                <td>—</td>
            </tr>
            <tr>
                <td><strong>Labor Payments</strong></td>
                <td>View, Approve</td>
                <td>View</td>
                <td>View, Process</td>
            </tr>
        </tbody>
    </table>

    <h2>2.2 Approval Workflows</h2>

    <table>
        <thead>
            <tr>
                <th>Document</th>
                <th>Step 1</th>
                <th>Step 2</th>
                <th>Effect on Approval</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Labor Request</strong></td>
                <td>MD Approves</td>
                <td>—</td>
                <td>Status &rarr; Approved; approved_amount is set</td>
            </tr>
            <tr>
                <td><strong>Labor Inspection</strong></td>
                <td>Supervisor Verifies</td>
                <td>MD Approves</td>
                <td>If passed: payment phase &rarr; Due; if final: contract &rarr; Completed</td>
            </tr>
        </tbody>
    </table>

    <div class="warn-box">
        <strong>Important:</strong> Before using the module, ensure the approval workflows have been seeded by running the <strong>LaborMenuSeeder</strong> and <strong>LaborApprovalSeeder</strong>. Assign approver roles in <strong>Settings &gt; Roles &amp; Permissions</strong>.
    </div>

    {{-- ============ STEP 1 ============ --}}
    <div class="page-break"></div>
    <h1>3. Step 1 — Create a Labor Request</h1>

    <div class="step-box">
        <div class="step-header">Create Labor Request</div>
        <div class="step-who">Who: Site Engineer / Project Manager (MD/Admin role) &nbsp;|&nbsp; Where: Labor Procurement &rarr; Labor Requests &rarr; Create</div>

        <ol>
            <li>Select the <strong>Project</strong> from the dropdown.</li>
            <li>Optionally select the <strong>BOQ Section</strong> (construction phase) the work relates to.</li>
            <li>Select or add the <strong>Artisan</strong> (from the Suppliers list).</li>
            <li>Fill in the details:
                <ul>
                    <li><strong>Work Description</strong> — detailed description of what needs to be done</li>
                    <li><strong>Work Location</strong> — specific location on site</li>
                    <li><strong>Start Date / End Date</strong> — expected work period</li>
                    <li><strong>Estimated Duration</strong> (in days)</li>
                    <li><strong>Proposed Amount</strong> (TZS) — the initial price</li>
                    <li><strong>Payment Terms</strong> — how payment will be structured</li>
                    <li><strong>Materials Included?</strong> — toggle if the artisan provides materials</li>
                    <li><strong>Materials List</strong> — itemize materials if applicable</li>
                </ul>
            </li>
            <li>Click <strong>Save</strong>.</li>
        </ol>
    </div>

    <div class="info-box">
        <strong>Auto-generated Reference:</strong> The system assigns a unique number like <strong>LR-2026-0001</strong>. The request is saved with status <span class="badge badge-draft">Draft</span>. You can freely edit it while it remains in draft.
    </div>

    {{-- ============ STEP 2 ============ --}}
    <h1>4. Step 2 — Submit for Approval</h1>

    <div class="step-box">
        <div class="step-header">Submit Request for Approval</div>
        <div class="step-who">Who: The requester &nbsp;|&nbsp; Where: Labor Requests &rarr; View Request</div>

        <ol>
            <li>Open the labor request you created.</li>
            <li>Review all details for accuracy.</li>
            <li>Optionally record:
                <ul>
                    <li><strong>Artisan Assessment</strong> — your evaluation of the artisan</li>
                    <li><strong>Negotiated Amount</strong> — if you negotiated a different price</li>
                </ul>
            </li>
            <li>Click the <strong>"Submit for Approval"</strong> button.</li>
            <li>Status changes: <span class="badge badge-draft">Draft</span> <span class="arrow">&rarr;</span> <span class="badge badge-pending">Pending</span></li>
        </ol>
    </div>

    <div class="warn-box">
        <strong>Note:</strong> Once submitted, the request can no longer be edited. Make sure all information is correct before submitting.
    </div>

    {{-- ============ STEP 3 ============ --}}
    <h1>5. Step 3 — MD Approves or Rejects Request</h1>

    <div class="step-box">
        <div class="step-header">Approve / Reject Labor Request</div>
        <div class="step-who">Who: MD / Admin &nbsp;|&nbsp; Where: Labor Requests &rarr; Pending request &rarr; Approval Page</div>

        <ol>
            <li>Navigate to the pending request from the list or from notifications.</li>
            <li>Review:
                <ul>
                    <li>Work description and scope</li>
                    <li>Proposed amount vs. negotiated amount</li>
                    <li>Artisan assessment</li>
                    <li>Materials list and inclusions</li>
                    <li>Timeline (start/end dates)</li>
                </ul>
            </li>
            <li>Choose an action:
                <ul>
                    <li><strong>Approve</strong> — Status: <span class="badge badge-pending">Pending</span> <span class="arrow">&rarr;</span> <span class="badge badge-approved">Approved</span>
                        <br>The system sets the <em>approved_amount</em> (uses negotiated amount if available, otherwise the proposed amount).</li>
                    <li><strong>Reject</strong> — Status: <span class="badge badge-pending">Pending</span> <span class="arrow">&rarr;</span> <span class="badge badge-rejected">Rejected</span>
                        <br>A rejection reason must be provided.</li>
                </ul>
            </li>
        </ol>
    </div>

    {{-- ============ STEP 4 ============ --}}
    <div class="page-break"></div>
    <h1>6. Step 4 — Create a Labor Contract</h1>

    <div class="step-box">
        <div class="step-header">Create Contract from Approved Request</div>
        <div class="step-who">Who: MD / Admin &nbsp;|&nbsp; Where: Labor Contracts &rarr; Create</div>

        <ol>
            <li>Navigate to <strong>Labor Contracts</strong> and click <strong>Create</strong>.</li>
            <li>Select the <strong>Approved Request</strong> — only approved requests without existing contracts appear.</li>
            <li>The form is pre-filled from the request (artisan, project, amounts, dates).</li>
            <li>Fill in additional details:
                <ul>
                    <li><strong>Supervisor</strong> — assign a site supervisor to oversee the work</li>
                    <li><strong>Scope of Work</strong> — detailed breakdown of deliverables</li>
                    <li><strong>Terms & Conditions</strong> — contract terms</li>
                    <li><strong>Start Date / End Date</strong></li>
                    <li><strong>Total Amount</strong> — from the approved amount</li>
                </ul>
            </li>
            <li>Review or customize the <strong>Payment Phases</strong>:</li>
        </ol>
    </div>

    <h3>Default Payment Phases</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Phase</th>
                <th style="width: 20%;">Name</th>
                <th style="width: 10%;">%</th>
                <th style="width: 60%;">Milestone Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Mobilization</td>
                <td>20%</td>
                <td>Contract signed and work commenced</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Progress</td>
                <td>30%</td>
                <td>50% work completed</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Substantial</td>
                <td>30%</td>
                <td>90% work completed</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Final</td>
                <td>20%</td>
                <td>Final inspection approved</td>
            </tr>
        </tbody>
    </table>

    <div class="info-box">
        <strong>Tip:</strong> You can customize the phase names, percentages, and milestone descriptions. The phase amounts are automatically calculated from the percentage of the total contract value. Contract number is auto-generated as <strong>LC-2026-0001</strong>.
    </div>

    {{-- ============ STEP 5 ============ --}}
    <h1>7. Step 5 — Sign the Contract</h1>

    <div class="step-box">
        <div class="step-header">Sign the Labor Contract</div>
        <div class="step-who">Who: MD / Admin &nbsp;|&nbsp; Where: Labor Contracts &rarr; View Contract</div>

        <ol>
            <li>Open the draft contract.</li>
            <li>Upload the <strong>Artisan's Signature</strong> (image file).</li>
            <li>Upload the <strong>Supervisor's Signature</strong> (image file).</li>
            <li>Click <strong>"Sign Contract"</strong>.</li>
            <li>Status changes: <span class="badge badge-draft">Draft</span> <span class="arrow">&rarr;</span> <span class="badge badge-active">Active</span></li>
            <li>You can now <strong>Generate PDF</strong> of the signed contract for printing.</li>
        </ol>
    </div>

    <div class="warn-box">
        <strong>Important:</strong> Both signatures (artisan and supervisor) are required to activate the contract. An active contract is required before work logs and inspections can be created.
    </div>

    {{-- ============ STEP 6 ============ --}}
    <h1>8. Step 6 — Log Daily Work</h1>

    <div class="step-box">
        <div class="step-header">Create Work Logs</div>
        <div class="step-who">Who: Supervisor / Admin &nbsp;|&nbsp; Where: Work Logs &rarr; Create</div>

        <ol>
            <li>Select the <strong>Active Contract</strong> to log work against.</li>
            <li>Fill in the details:
                <ul>
                    <li><strong>Log Date</strong> — defaults to today</li>
                    <li><strong>Work Done</strong> — description of what was accomplished</li>
                    <li><strong>Workers Present</strong> — headcount on site</li>
                    <li><strong>Hours Worked</strong> — total hours</li>
                    <li><strong>Progress Percentage</strong> — overall completion estimate</li>
                    <li><strong>Challenges</strong> — any issues or delays encountered</li>
                    <li><strong>Materials Used</strong> — list of materials consumed</li>
                    <li><strong>Weather Conditions</strong> — sunny / cloudy / rainy / stormy</li>
                    <li><strong>Photos</strong> — upload site progress photos</li>
                </ul>
            </li>
            <li>Click <strong>Save</strong>.</li>
        </ol>
    </div>

    <div class="info-box">
        <strong>Best Practice:</strong> Log work daily or as frequently as possible. Work logs build a record that supports inspections and payment approvals. Photos provide essential visual evidence of progress.
    </div>

    {{-- ============ STEP 7 ============ --}}
    <div class="page-break"></div>
    <h1>9. Step 7 — Conduct Labor Inspection</h1>

    <div class="step-box">
        <div class="step-header">Create a Labor Inspection</div>
        <div class="step-who">Who: Inspector (Supervisor / Admin) &nbsp;|&nbsp; Where: Labor Inspections &rarr; Create</div>

        <ol>
            <li>Select the <strong>Active Contract</strong> to inspect.</li>
            <li>Select the <strong>Payment Phase</strong> this inspection relates to (e.g., Phase 2: Progress).</li>
            <li>Fill in the inspection details:
                <ul>
                    <li><strong>Inspection Type</strong>:
                        <ul>
                            <li><em>Progress</em> — routine check during work</li>
                            <li><em>Milestone</em> — at a specific milestone point</li>
                            <li><em>Final</em> — final completion inspection</li>
                        </ul>
                    </li>
                    <li><strong>Work Quality</strong>: Excellent / Good / Acceptable / Poor / Unacceptable</li>
                    <li><strong>Completion Percentage</strong></li>
                    <li><strong>Scope Compliance</strong> — does the work match the contract scope? Yes/No</li>
                    <li><strong>Defects Found</strong> — describe any defects</li>
                    <li><strong>Rectification Required</strong> — Yes/No, with notes</li>
                    <li><strong>Photos</strong> — upload inspection evidence</li>
                </ul>
            </li>
            <li>Click <strong>Save</strong>. The inspection number (e.g., <strong>LI-2026-0001</strong>) is auto-generated.</li>
        </ol>
    </div>

    <h3>Automatic Result Determination</h3>
    <p>The inspection result is automatically determined by the system based on quality and compliance:</p>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Condition</th>
                <th style="width: 20%;">Result</th>
                <th style="width: 40%;">What Happens</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Quality = "Unacceptable" OR Scope Non-Compliant</td>
                <td><strong style="color: #ef4444;">FAIL</strong></td>
                <td>Payment phase remains pending; work must be redone</td>
            </tr>
            <tr>
                <td>Quality = "Poor" OR Rectification Required</td>
                <td><strong style="color: #f59e0b;">CONDITIONAL</strong></td>
                <td>Issues must be addressed before payment proceeds</td>
            </tr>
            <tr>
                <td>All other conditions met</td>
                <td><strong style="color: #10b981;">PASS</strong></td>
                <td>Payment phase becomes "Due" after approval</td>
            </tr>
        </tbody>
    </table>

    <div class="warn-box">
        <strong>Important:</strong> The inspection is <strong>automatically submitted for approval</strong> upon creation. There is no separate "Submit" step — once saved, it enters the approval workflow immediately.
    </div>

    {{-- ============ STEP 8 ============ --}}
    <h1>10. Step 8 — Supervisor Verifies Inspection</h1>

    <div class="step-box">
        <div class="step-header">Verify Inspection (Step 1 of 2)</div>
        <div class="step-who">Who: Supervisor &nbsp;|&nbsp; Where: Labor Inspections &rarr; Pending Inspection &rarr; Approval Page</div>

        <ol>
            <li>Open the pending inspection from the list.</li>
            <li>Review all inspection details, photos, and quality assessment.</li>
            <li>Click <strong>"Verify"</strong> to confirm the inspection findings.</li>
            <li>Status changes: <span class="badge badge-pending">Pending</span> <span class="arrow">&rarr;</span> <span class="badge" style="background: #6366f1;">Verified</span></li>
            <li>The inspection moves to the MD for final approval.</li>
        </ol>
    </div>

    {{-- ============ STEP 9 ============ --}}
    <h1>11. Step 9 — MD Approves Inspection</h1>

    <div class="step-box">
        <div class="step-header">Approve Inspection (Step 2 of 2)</div>
        <div class="step-who">Who: MD / Admin &nbsp;|&nbsp; Where: Labor Inspections &rarr; Verified Inspection &rarr; Approval Page</div>

        <ol>
            <li>Open the verified inspection.</li>
            <li>Review the inspection details and the supervisor's verification.</li>
            <li>Click <strong>"Approve"</strong>.</li>
            <li>Status changes: <span class="badge" style="background: #6366f1;">Verified</span> <span class="arrow">&rarr;</span> <span class="badge badge-approved">Approved</span></li>
        </ol>
    </div>

    <h3>Automatic Effects When Inspection is Approved</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Condition</th>
                <th style="width: 60%;">What Happens Automatically</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Result = <strong>Pass</strong> AND linked to a payment phase</td>
                <td>Payment phase status changes: <span class="badge badge-pending" style="font-size: 8px;">Pending</span> <span class="arrow">&rarr;</span> <span class="badge badge-due" style="font-size: 8px;">Due</span></td>
            </tr>
            <tr>
                <td>Type = <strong>Final</strong> AND Result = <strong>Pass</strong></td>
                <td>Contract status changes to <span class="badge" style="background: #059669; font-size: 8px;">Completed</span> with actual end date recorded</td>
            </tr>
        </tbody>
    </table>

    {{-- ============ STEP 10 ============ --}}
    <div class="page-break"></div>
    <h1>12. Step 10 — MD Approves Payment</h1>

    <div class="step-box">
        <div class="step-header">Approve Payment Phase</div>
        <div class="step-who">Who: MD / Admin &nbsp;|&nbsp; Where: Labor Payments &rarr; Due Payments</div>

        <ol>
            <li>Navigate to <strong>Labor Payments</strong> and filter by status <strong>"Due"</strong>.</li>
            <li>Review the payment phase details:
                <ul>
                    <li>Contract information and artisan</li>
                    <li>Phase milestone and description</li>
                    <li>Associated inspection results</li>
                    <li>Payment amount</li>
                </ul>
            </li>
            <li>Click <strong>"Approve"</strong> on individual phases, or use <strong>"Bulk Approve"</strong> for multiple.</li>
            <li>Status changes: <span class="badge badge-due">Due</span> <span class="arrow">&rarr;</span> <span class="badge badge-approved">Approved</span></li>
        </ol>
    </div>

    {{-- ============ STEP 11 ============ --}}
    <h1>13. Step 11 — Finance Processes Payment</h1>

    <div class="step-box">
        <div class="step-header">Process Payment</div>
        <div class="step-who">Who: Finance Team &nbsp;|&nbsp; Where: Labor Payments &rarr; Approved Payment &rarr; Process</div>

        <ol>
            <li>Navigate to <strong>Labor Payments</strong> and filter by status <strong>"Approved"</strong>.</li>
            <li>Click <strong>"Process"</strong> on the payment phase.</li>
            <li>Enter payment details:
                <ul>
                    <li><strong>Payment Reference</strong> — cheque number, bank transfer ID, mobile money reference, etc.</li>
                    <li><strong>Notes</strong> — optional additional information</li>
                </ul>
            </li>
            <li>Click <strong>"Process Payment"</strong>.</li>
            <li>Status changes: <span class="badge badge-approved">Approved</span> <span class="arrow">&rarr;</span> <span class="badge badge-paid">Paid</span></li>
        </ol>
    </div>

    <div class="info-box">
        <strong>Automatic Update:</strong> When a payment is processed, the contract's <em>amount_paid</em> and <em>balance_amount</em> are automatically recalculated. You can track overall payment progress from the contract detail page.
    </div>

    {{-- ============ SPECIAL ACTIONS ============ --}}
    <h1>14. Special Actions</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 22%;">Action</th>
                <th style="width: 15%;">Who</th>
                <th style="width: 30%;">When to Use</th>
                <th style="width: 33%;">Effect</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Put Contract on Hold</strong></td>
                <td>MD / Admin</td>
                <td>Disputes, weather delays, resource issues</td>
                <td>Active <span class="arrow">&rarr;</span> On Hold</td>
            </tr>
            <tr>
                <td><strong>Resume Contract</strong></td>
                <td>MD / Admin</td>
                <td>Issue resolved, ready to continue</td>
                <td>On Hold <span class="arrow">&rarr;</span> Active</td>
            </tr>
            <tr>
                <td><strong>Terminate Contract</strong></td>
                <td>MD / Admin</td>
                <td>Breach of terms, abandonment</td>
                <td>Any <span class="arrow">&rarr;</span> Terminated</td>
            </tr>
            <tr>
                <td><strong>Put Payment on Hold</strong></td>
                <td>MD / Admin</td>
                <td>Quality concerns, dispute under investigation</td>
                <td>Any unpaid phase <span class="arrow">&rarr;</span> Held</td>
            </tr>
            <tr>
                <td><strong>Release Payment</strong></td>
                <td>MD / Admin</td>
                <td>Hold issue cleared</td>
                <td>Held <span class="arrow">&rarr;</span> Due</td>
            </tr>
            <tr>
                <td><strong>Generate Contract PDF</strong></td>
                <td>MD / Admin</td>
                <td>For printing and physical signing</td>
                <td>Downloads PDF document</td>
            </tr>
        </tbody>
    </table>

    {{-- ============ STATUS FLOWS ============ --}}
    <h1>15. Status Flow Diagrams</h1>

    <h2>15.1 Labor Request</h2>
    <div class="flow-box">
        <span class="badge badge-draft">Draft</span> &rarr;
        <span class="badge badge-pending">Pending</span> &rarr;
        <span class="badge badge-approved">Approved</span> &rarr;
        <span class="badge" style="background: #1e40af;">Contracted</span>
        <br><br>
        <span style="margin-left: 100px;">&searr;</span> <span class="badge badge-rejected">Rejected</span>
    </div>

    <h2>15.2 Labor Contract</h2>
    <div class="flow-box">
        <span class="badge badge-draft">Draft</span> &rarr;
        <span class="badge badge-active">Active</span> &harr;
        <span class="badge badge-held">On Hold</span>
        <br><br>
        <span style="margin-left: 80px;">&darr;</span>
        <span style="margin-left: 90px;">&darr;</span>
        <br>
        <span style="margin-left: 40px;"><span class="badge" style="background: #059669;">Completed</span></span>
        <span style="margin-left: 20px;"><span class="badge badge-rejected">Terminated</span></span>
    </div>

    <h2>15.3 Labor Inspection</h2>
    <div class="flow-box">
        <span class="badge badge-draft">Draft</span> &rarr;
        <span class="badge badge-pending">Pending</span> &rarr;
        <span class="badge" style="background: #6366f1;">Verified</span> &rarr;
        <span class="badge badge-approved">Approved</span>
        <br><br>
        <span style="margin-left: 220px;">&searr;</span> <span class="badge badge-rejected">Rejected</span>
    </div>

    <h2>15.4 Payment Phase</h2>
    <div class="flow-box">
        <span class="badge badge-pending" style="font-size: 10px;">Pending</span> &rarr;
        <span class="badge badge-due" style="font-size: 10px;">Due</span> &rarr;
        <span class="badge badge-approved" style="font-size: 10px;">Approved</span> &rarr;
        <span class="badge badge-paid" style="font-size: 10px;">Paid</span>
        <br><br>
        <span style="margin-left: 80px;">&uarr;&darr;</span>
        <br>
        <span style="margin-left: 60px;"><span class="badge badge-held" style="font-size: 10px;">Held</span></span>
    </div>

    {{-- ============ DASHBOARD ============ --}}
    <div class="page-break"></div>
    <h1>16. Dashboard Overview</h1>

    <p>The <strong>Labor Dashboard</strong> provides a real-time overview of all labor procurement activities. It displays:</p>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Metric</th>
                <th style="width: 65%;">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Active Contracts</strong></td>
                <td>Count and total value of currently active contracts</td>
            </tr>
            <tr>
                <td><strong>Completed Contracts</strong></td>
                <td>Number of successfully completed contracts</td>
            </tr>
            <tr>
                <td><strong>Pending Requests</strong></td>
                <td>Labor requests awaiting MD approval</td>
            </tr>
            <tr>
                <td><strong>Pending Inspections</strong></td>
                <td>Inspections awaiting verification or approval</td>
            </tr>
            <tr>
                <td><strong>Payment Summary</strong></td>
                <td>Breakdown of due, approved, paid, and held payment amounts</td>
            </tr>
            <tr>
                <td><strong>Contracts Nearing End</strong></td>
                <td>Active contracts ending within 7 days</td>
            </tr>
            <tr>
                <td><strong>Overdue Contracts</strong></td>
                <td>Active contracts past their end date</td>
            </tr>
            <tr>
                <td><strong>Recent Activity</strong></td>
                <td>Latest 5 requests, contracts, and inspections</td>
            </tr>
        </tbody>
    </table>

    <div class="info-box">
        <strong>Tip:</strong> Use the project filter on the dashboard to focus on a specific project's labor activities.
    </div>

    {{-- ============ FOOTER ============ --}}
    <div class="footer-note">
        <p>This document is auto-generated from the Wajenzi Construction Management System.<br>
        For questions or support, contact your system administrator.<br>
        Generated on {{ now()->format('d M Y, H:i') }}</p>
    </div>

</body>
</html>