@extends('web::layouts.grids.12')

@section('title', 'EVE Industry: Stockpile Churn Workflow')
@section('page_header', 'Stockpile Churn Workflow')

@section('full')
@php $isWizard = request()->get('mode') === 'wizard'; @endphp

<!-- Workflow Banner -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="text-success"><i class="fas fa-sync-alt mr-2"></i> Optimize with the Stockpile Churn Workflow</h4>
                        <p class="mb-0 text-muted">Stop building for one-off batches. Build continuous pipelines of intermediate components and raw materials to maximize your industrial throughput.</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="btn-group">
                            <a href="{{ route('seat-assets::stockpiles.workflow') }}" class="btn {{ !$isWizard ? 'btn-success' : 'btn-outline-success' }}">
                                <i class="fas fa-book-open mr-1"></i> Full Guide
                            </a>
                            <a href="{{ route('seat-assets::stockpiles.workflow', ['mode' => 'wizard']) }}" class="btn {{ $isWizard ? 'btn-success' : 'btn-outline-success' }}">
                                <i class="fas fa-magic mr-1"></i> Start Interactive Guide
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isWizard)
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-lg border-primary mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-magic mr-2"></i> Stockpile Churn: Interactive Guide</h3>
                <div>
                    <span id="wizard-step-indicator" class="badge badge-light px-3 py-2 font-weight-bold" style="font-size: 1rem;">Step 1 of 6</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="progress" style="height: 10px; border-radius: 0;">
                    <div id="wizard-progress" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 16.6%;"></div>
                </div>
                
                <div class="p-5 wizard-content">
                    <!-- Wizard Steps Container -->
                    <div id="wizard-step-0" class="wizard-step">
                        <h2 class="text-primary border-bottom pb-3 mb-4"><i class="fas fa-info-circle mr-2"></i> The "Stockpile Churn" Philosophy</h2>
                        <p class="lead mb-4">Traditional EVE industry relies on working backwards to build an exact number of components for a specific target batch. <strong>Stockpile Churn</strong> is an alternative approach: instead of building toward a precise end goal, you build toward a continuously replenished stockpile.</p>
                        <div class="row mt-4">
                            <div class="col-md-4 text-center">
                                <div class="p-4 bg-light rounded shadow-sm border h-100">
                                    <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
                                    <h5>Agility</h5>
                                    <p class="text-muted small">Pivot final products instantly if prices crash. Intermediates are always ready.</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-4 bg-light rounded shadow-sm border h-100">
                                    <i class="fas fa-tachometer-alt fa-3x text-info mb-3"></i>
                                    <h5>Throughput</h5>
                                    <p class="text-muted small">Independent pipelines keep slots constantly occupied, increasing total output.</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="p-4 bg-light rounded shadow-sm border h-100">
                                    <i class="fas fa-brain fa-3x text-success mb-3"></i>
                                    <h5>Sanity</h5>
                                    <p class="text-muted small">Removes the need for micro-managing complex, brittle spreadsheets.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-1" class="wizard-step d-none">
                        <h2 class="text-primary border-bottom pb-3 mb-4"><i class="fas fa-bullseye mr-2"></i> Step 1: Define Your End Goal</h2>
                        <p class="lead mb-4">Identify the final product you want to produce (e.g., Apocalypse battleships).</p>
                        
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card bg-light p-3 border shadow-sm h-100">
                                    <h5>How to do it:</h5>
                                    <ul class="small mb-0">
                                        <li class="mb-2">Decide on your final product.</li>
                                        <li class="mb-2">Open the EVE Online Multi-buy window.</li>
                                        <li class="mb-2">Add your items and quantities.</li>
                                        <li class="mb-2">Copy the text and paste it here.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="card border-primary p-3 shadow-sm">
                                    <h6 class="font-weight-bold mb-3">Quick Create: Output Stockpile</h6>
                                    <form action="{{ route('seat-assets::stockpiles.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="return_to_wizard" value="1">
                                        <div class="form-group mb-2">
                                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Stockpile Name (e.g. My Apocalypses)" required>
                                        </div>
                                        <div class="form-group mb-2">
                                            <select name="location_id" class="form-control form-control-sm select2">
                                                <option value="">Check All Locations</option>
                                                @foreach($locations as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-2">
                                            <textarea name="multibuy" rows="4" class="form-control form-control-sm" placeholder="Paste Multi-buy text here..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm btn-block">Create & Continue</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-2" class="wizard-step d-none">
                        <h2 class="text-primary border-bottom pb-3 mb-4"><i class="fas fa-sign-in-alt mr-2"></i> Step 2: Determine Direct Input Requirements</h2>
                        <p class="lead mb-4">Identify the direct input materials required to meet your final product target quantity.</p>
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Select a Stockpile to Analyze:</label>
                            <select id="step2-stockpile-select" class="form-control select2">
                                <option value="">-- Select Stockpile --</option>
                                @foreach($stockpiles as $sp)
                                    <option value="{{ $sp->id }}">{{ $sp->name }} ({{ $sp->items_count }} items)</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="step2-industry-report" class="d-none">
                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle mr-2"></i> Review the <strong>Buy List</strong> below. These items should be your next stockpiles.
                            </div>
                            <div id="step2-report-content" style="max-height: 300px; overflow-y: auto;" class="border rounded bg-light p-2">
                                <!-- Loaded via AJAX -->
                            </div>
                        </div>

                        <div id="step2-no-selection" class="text-center py-5 text-muted bg-light border rounded">
                            <i class="fas fa-hand-pointer fa-2x mb-2"></i>
                            <p>Select a stockpile above to see its direct requirements.</p>
                        </div>
                    </div>

                    <div id="wizard-step-3" class="wizard-step d-none">
                        <h2 class="text-primary border-bottom pb-3 mb-4"><i class="fas fa-layer-group mr-2"></i> Step 3: Map Out Intermediate Components</h2>
                        <p class="lead mb-4">If you are building the components yourself, trace the requirements one step further down the supply chain.</p>
                        
                        <div id="step3-wizard-container">
                            <p class="small text-muted mb-3">Use the <strong>Build List</strong> from your analysis to create intermediate stockpiles. This ensures you always have the components ready for assembly.</p>
                            <div id="step3-report-content" style="max-height: 400px; overflow-y: auto;" class="border rounded bg-white p-3 shadow-sm mb-4">
                                <p class="text-center text-muted py-5">Please select a stockpile in Step 2 to generate the build list.</p>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-4" class="wizard-step d-none">
                        <h2 class="text-primary border-bottom pb-3 mb-4"><i class="fas fa-scroll mr-2"></i> Step 4: Secure Blueprints (BPCs & Invention)</h2>
                        <p class="lead mb-4">Production pipelines stall if you run out of blueprints. Buffer these just like physical assets.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary p-4 h-100 shadow-sm">
                                    <h5 class="font-weight-bold text-primary mb-3">Blueprint Checklist</h5>
                                    <ul class="mb-0">
                                        <li class="mb-2">Check BPC runs.</li>
                                        <li class="mb-2">Check Datacore levels.</li>
                                        <li class="mb-2">Stockpile 5-10x successful invention attempts.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-info p-3 h-100 shadow-sm">
                                    <h6 class="font-weight-bold mb-2">Add Blueprints to Stockpile</h6>
                                    <p class="small text-muted">Go to the <a href="{{ route('seat-assets::stockpiles') }}" class="text-primary font-weight-bold">Dashboard</a> to add specific BPCs to your intermediate stockpiles by name.</p>
                                    <div class="mt-auto">
                                        <a href="{{ route('seat-assets::industry.calculator') }}" class="btn btn-outline-info btn-sm btn-block">Open Blueprint Search</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-5" class="wizard-step d-none text-center">
                        <h2 class="text-success border-bottom pb-3 mb-4"><i class="fas fa-sync-alt mr-2"></i> Step 5: The "Red/Green" Execution Loop</h2>
                        <p class="lead mb-4">Daily operations become simple: Log in, look at your dashboard, and top off Red items.</p>
                        
                        <div class="card mb-4 shadow-sm border-0 bg-light">
                            <div class="card-body p-0">
                                <table class="table table-hover table-sm mb-0 text-left">
                                    <thead class="bg-secondary text-white">
                                        <tr>
                                            <th>Stockpile</th>
                                            <th>Items</th>
                                            <th>Created</th>
                                            <th class="text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockpiles as $sp)
                                            <tr>
                                                <td class="font-weight-bold">{{ $sp->name }}</td>
                                                <td>{{ $sp->items_count }}</td>
                                                <td>{{ $sp->created_at->diffForHumans() }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('seat-assets::stockpiles.industry', $sp->id) }}" class="btn btn-xs btn-info">Analyze</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-warning border shadow-sm p-4 text-left mb-4">
                            <h5 class="font-weight-bold mb-2 text-dark"><i class="fas fa-star mr-2"></i> Final Pro-Tip: Fudge Factor Over Precision</h5>
                            <p class="mb-0 text-dark" style="font-size: 1rem;">Don't stress exact numbers. If you constantly run out of something, simply bump its target threshold up by 15%.</p>
                        </div>
                        
                        <a href="{{ route('seat-assets::stockpiles') }}" class="btn btn-xl btn-primary shadow-lg px-5 py-3">
                            <i class="fas fa-boxes mr-2"></i> FINISH GUIDE & GO TO DASHBOARD
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-4 d-flex justify-content-between">
                <button id="prev-step" class="btn btn-outline-secondary btn-lg invisible" disabled>
                    <i class="fas fa-arrow-left mr-2"></i> Previous Step
                </button>
                <div>
                    <button id="next-step" class="btn btn-primary btn-lg px-5 shadow-sm">
                        Next Step <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-md-3">
        <div class="card card-outline card-primary shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header">
                <h3 class="card-title">Workflow Steps</h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column workflow-nav">
                    <li class="nav-item">
                        <a href="#intro" class="nav-link active">
                            <i class="fas fa-info-circle mr-2"></i> Introduction
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#step1" class="nav-link">
                            <i class="fas fa-bullseye mr-2"></i> 1. End Goal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#step2" class="nav-link">
                            <i class="fas fa-sign-in-alt mr-2"></i> 2. Direct Inputs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#step3" class="nav-link">
                            <i class="fas fa-layer-group mr-2"></i> 3. Intermediates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#step4" class="nav-link">
                            <i class="fas fa-scroll mr-2"></i> 4. Blueprints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#step5" class="nav-link text-success">
                            <i class="fas fa-sync-alt mr-2"></i> 5. Red/Green Loop
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('seat-assets::stockpiles') }}" class="btn btn-primary btn-block">
                    <i class="fas fa-boxes mr-1"></i> Go to Stockpiles
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- Introduction -->
        <div id="intro" class="card shadow-sm mb-4 border-left-primary">
            <div class="card-body">
                <h2 class="text-primary border-bottom pb-2">The "Stockpile Churn" Philosophy</h2>
                <p class="lead">Traditional EVE industry relies on working backwards to build an exact number of components for a specific target batch. <strong>Stockpile Churn</strong> is an alternative approach: instead of building toward a precise end goal, you build toward a continuously replenished stockpile.</p>
                <div class="row mt-4">
                    <div class="col-md-4 text-center">
                        <div class="p-3 bg-light rounded shadow-sm h-100">
                            <i class="fas fa-bolt fa-2x text-warning mb-2"></i>
                            <h5>Agility</h5>
                            <p class="small text-muted">Pivot final products instantly if prices crash. Intermediates are always ready.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="p-3 bg-light rounded shadow-sm h-100">
                            <i class="fas fa-tachometer-alt fa-2x text-info mb-2"></i>
                            <h5>Throughput</h5>
                            <p class="small text-muted">Independent pipelines keep slots constantly occupied, increasing total output.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="p-3 bg-light rounded shadow-sm h-100">
                            <i class="fas fa-brain fa-2x text-success mb-2"></i>
                            <h5>Sanity</h5>
                            <p class="small text-muted">Removes the need for micro-managing complex, brittle spreadsheets.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1 -->
        <div id="step1" class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="border-bottom pb-2"><span class="badge badge-primary mr-2">Step 1</span> Define Your End Goal (Output Stockpile)</h3>
                <p>Identify the final product you want to produce (e.g., Apocalypse battleships).</p>
                <ul>
                    <li>Create a target stockpile for your final hull.</li>
                    <li>Set a target threshold quantity (e.g., 10 Apocalypses).</li>
                    <li><em>Note:</em> EIC includes current assets, market sell orders, and in-production jobs so you know exactly what is available in your pipeline.</li>
                </ul>
                <div class="alert alert-light border shadow-sm">
                    <i class="fas fa-lightbulb text-warning mr-2"></i> <strong>How to do it:</strong> Go to the <a href="{{ route('seat-assets::stockpiles') }}" class="text-primary font-weight-bold">Stockpiles Page</a>, and paste your final product names and quantities from the EVE Multi-buy window.
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div id="step2" class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="border-bottom pb-2"><span class="badge badge-primary mr-2">Step 2</span> Determine Direct Input Requirements</h3>
                <p>Identify the direct input materials required to meet your final product target quantity.</p>
                <ul>
                    <li>Create stockpiles for these direct inputs (e.g., Minerals, T1 Components, or T2 Components).</li>
                    <li>Set the target thresholds to match what is needed for your final output target.</li>
                </ul>
                <div class="alert alert-light border">
                    <i class="fas fa-industry text-info mr-2"></i> <strong>Pro-Tip:</strong> Once you create a stockpile, click the <strong><i class="fas fa-industry"></i> Industry</strong> button. The "Buy List" at the bottom shows exactly what you need to stockpile next.
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div id="step3" class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="border-bottom pb-2"><span class="badge badge-primary mr-2">Step 3</span> Map Out Intermediate Components</h3>
                <p>If you are building the components yourself, trace the requirements one step further down the supply chain.</p>
                <ul>
                    <li>Check the inputs required to build your intermediate components.</li>
                    <li>Create stockpiles for these base materials (PI, Moongoo, etc.).</li>
                    <li><strong>Strategic Rule:</strong> Treat each phase of the production line as a separate entity. Ensure each step makes sense on its own.</li>
                </ul>
            </div>
        </div>

        <!-- Step 4 -->
        <div id="step4" class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="border-bottom pb-2"><span class="badge badge-primary mr-2">Step 4</span> Secure Blueprints (BPCs & Invention)</h3>
                <p>Production pipelines stall if you run out of blueprints. Buffer these just like physical assets.</p>
                <ul>
                    <li><strong>For T1:</strong> Create a stockpile for max-run Blueprint Copies (BPCs). Set this target excessively high.</li>
                    <li><strong>For T2:</strong> Create a stockpile for Datacores and Decryptors. Stockpile 3x to 10x the amount of successful runs needed.</li>
                </ul>
                <div class="alert alert-info shadow-sm">
                    <i class="fas fa-info-circle mr-2"></i> You can add Blueprints and Datacores to stockpiles just like any other item by name.
                </div>
            </div>
        </div>

        <!-- Step 5 -->
        <div id="step5" class="card shadow-sm mb-4 border-success">
            <div class="card-body">
                <h3 class="text-success border-bottom pb-2"><span class="badge badge-success mr-2">Step 5</span> The "Red/Green" Execution Loop</h3>
                <p>Once your stockpiles are configured across all tiers, your daily operations become simple:</p>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h4 class="mb-1">Green = Good</h4>
                                <p class="mb-0">Effective inventory meets your threshold. No action needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h4 class="mb-1">Red = Bad</h4>
                                <p class="mb-0">Effective inventory is below threshold. Install jobs or buy now.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mt-3 font-weight-bold">Always be buying, always be building.</p>
            </div>
        </div>

        <!-- Pro-Tips -->
        <div class="card shadow-sm mb-5 border-warning">
            <div class="card-header bg-warning text-dark">
                <h3 class="card-title"><i class="fas fa-star mr-1"></i> Best Practices and Pro-Tips</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Fudge Factor Over Precision</h5>
                        <p class="small">Don't stress exact numbers. If you run out of something, bump its target threshold by 10-15%.</p>
                        
                        <h5>Don't Let the Pipeline Stall</h5>
                        <p class="small">Move quickly, mash the build button, and top off your stockpiles constantly.</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Vertical Integration Isn't Free</h5>
                        <p class="small">If a reaction is unprofitable on its own, buy it from Jita. Use slots for high-margin jobs.</p>
                        
                        <h5>Manage Industry Indexes</h5>
                        <p class="small">Build intermediates in cheap systems, then freighter them to your final assembly zone.</p>
                    </div>
                </div>
                <div class="mt-2 text-center border-top pt-3">
                    <h5 class="text-primary"><i class="fas fa-wallet mr-2"></i> Bring Liquid ISK</h5>
                    <p>Having massive buffers ties up capital. Ensure you have the liquid ISK to continuously top off lower-tier stockpiles.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .workflow-nav .nav-link {
        border-radius: 0;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    .workflow-nav .nav-link.active {
        background-color: #f8f9fa;
        color: #007bff;
        border-left: 3px solid #007bff;
        font-weight: bold;
    }
    .border-left-primary { border-left: 5px solid #007bff; }
    .sticky-top { z-index: 1020; }
    html { scroll-behavior: smooth; }

    /* Wizard Styles */
    .wizard-step { animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .wizard-content { min-height: 500px; }
    .card-outline.card-primary { border-top: 3px solid #007bff; }
    .invisible { visibility: hidden !important; }
    .btn-xl { padding: 1.25rem 2.5rem; font-size: 1.25rem; border-radius: 0.5rem; }
</style>
@endif
@stop

@push('javascript')
<script>
    $(function() {
        @if($isWizard)
            var currentStep = 0;
            var totalSteps = 6; // 0 to 5

            function updateWizard() {
                $('.wizard-step').addClass('d-none');
                $('#wizard-step-' + currentStep).removeClass('d-none');
                
                // Update Progress
                var progressPercent = ((currentStep + 1) / totalSteps) * 100;
                $('#wizard-progress').css('width', progressPercent + '%');
                
                // Update Indicator
                $('#wizard-step-indicator').text('Step ' + (currentStep + 1) + ' of ' + totalSteps);
                
                // Update Buttons
                if (currentStep === 0) {
                    $('#prev-step').addClass('invisible').prop('disabled', true);
                } else {
                    $('#prev-step').removeClass('invisible').prop('disabled', false);
                }
                
                if (currentStep === totalSteps - 1) {
                    $('#next-step').addClass('invisible').prop('disabled', true);
                } else {
                    $('#next-step').removeClass('invisible').prop('disabled', false);
                }

                // Scroll to top of card on step change
                $('html, body').animate({
                    scrollTop: $(".card").offset().top - 20
                }, 300);
            }

            $('#next-step').on('click', function() {
                if (currentStep < totalSteps - 1) {
                    currentStep++;
                    updateWizard();
                }
            });

            $('#prev-step').on('click', function() {
                if (currentStep > 0) {
                    currentStep--;
                    updateWizard();
                }
            });

            // Handle Stockpile Selection for Step 2
            $('#step2-stockpile-select').on('change', function() {
                var stockpileId = $(this).val();
                if (!stockpileId) {
                    $('#step2-no-selection').removeClass('d-none');
                    $('#step2-industry-report').addClass('d-none');
                    $('#step3-report-content').html('<p class="text-center text-muted py-5">Please select a stockpile in Step 2 to generate the build list.</p>');
                    return;
                }

                $('#step2-no-selection').addClass('d-none');
                $('#step2-industry-report').removeClass('d-none');
                $('#step2-report-content').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>Generating Logistics Report...</div>');

                // AJAX call to fetch industry report
                $.get('{{ route("seat-assets::stockpiles.industry", ["id" => "REPLACE_ID"]) }}'.replace('REPLACE_ID', stockpileId), function(html) {
                    var $html = $(html);
                    var $buyCard = $html.find('.card-warning');
                    var $buildCard = $html.find('.card-primary');

                    // Extract Build and Buy lists
                    $('#step2-report-content').html($buyCard.html());
                    $('#step3-report-content').html($buildCard.html());
                    
                    // Cleanup extra header buttons from extracted HTML if any
                    $('#step2-report-content').find('.card-tools').remove();
                    $('#step2-report-content').find('.card-title').remove();
                    $('#step2-report-content').find('.card-footer').remove();
                    $('#step3-report-content').find('.card-tools').remove();
                    $('#step3-report-content').find('.card-title').remove();
                    $('#step3-report-content').find('.card-footer').remove();
                });
            });

            // Check if we just returned from a stockpile creation
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('step')) {
                currentStep = parseInt(urlParams.get('step'));
                updateWizard();
            }

        @else
            $(window).scroll(function() {
                var scrollDistance = $(window).scrollTop();
                $('div[id]').each(function(i) {
                    if ($(this).position().top <= scrollDistance + 100) {
                        $('.workflow-nav a.active').removeClass('active');
                        $('.workflow-nav a').eq(i).addClass('active');
                    }
                });
            }).scroll();
        @endif
    });
</script>
@endpush
