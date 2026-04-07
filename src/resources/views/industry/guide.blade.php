@extends('web::layouts.grids.12')

@section('title', 'EIC Stockpiles Guide')
@section('page_header', 'Eve Intelligence Center: Stockpiles Guide')

@section('full')
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Navigation</h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="#overview" class="nav-link">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a href="#industry-calculator" class="nav-link">Industry Calculator</a>
                    </li>
                    <li class="nav-item">
                        <a href="#stockpiles" class="nav-link">Stockpile Logistics</a>
                    </li>
                    <li class="nav-item">
                        <a href="#ai-integration" class="nav-link text-bold text-primary">AI Context Import</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card card-outline card-info">
            <div class="card-body">
                <section id="overview" class="mb-5">
                    <h3><i class="fas fa-info-circle mr-2"></i> Overview</h3>
                    <p>The Eve Intelligence Center (EIC) is a powerful suite of industrial and logistics tools designed to streamline your EVE Online manufacturing pipeline. Whether you are building single hulls or managing a massive T2 production chain, EIC provides the data you need to optimize for throughput and efficiency.</p>
                </section>

                <section id="industry-calculator" class="mb-5 border-top pt-4">
                    <h3><i class="fas fa-calculator mr-2"></i> Industry Calculator</h3>
                    <p>The Industry Calculator helps you plan individual manufacturing jobs with high precision.</p>
                    <ul>
                        <li><strong>Blueprint Search:</strong> Search for any item in the game or use the "Import My Blueprint" button to instantly load your owned blueprints with their exact ME/TE levels.</li>
                        <li><strong>Settings Persistence:</strong> Your solar system, facility type, tax rates, and "Build sub-components" settings are saved automatically between sessions.</li>
                        <li><strong>Build Sub-Components:</strong> Toggle this to see the full raw material requirements for T2/T3 production, cascading all the way down to basic minerals and PI.</li>
                        <li><strong>Export:</strong> Use the "Copy Materials" button to get a multi-buy formatted list for easy market shopping in EVE.</li>
                    </ul>
                </section>

                <section id="stockpiles" class="mb-5 border-top pt-4">
                    <h3><i class="fas fa-layer-group mr-2"></i> Stockpile Logistics</h3>
                    <p>Manage continuous-throughput manufacturing based on the "Stockpile Churn" philosophy.</p>
                    <ul>
                        <li><strong>Thresholds:</strong> Define minimum stock levels for final products and intermediate components.</li>
                        <li><strong>Effective Inventory:</strong> EIC calculates your health as <code>Current Assets + In-Flight Job Outputs</code>.</li>
                        <li><strong>Location Scoping:</strong> You can set a default location for a stockpile or define specific housing structures for individual items.</li>
                        <li><strong>Logistics Report:</strong> The "Industry" view for each stockpile provides a prioritized "BUILD" and "BUY" list to turn RED stockpiles GREEN.</li>
                    </ul>
                </section>

                <section id="ai-integration" class="mb-5 border-top pt-4 bg-light p-3">
                    <h3><i class="fas fa-robot mr-2"></i> AI Context Import</h3>
                    <p>If you use AI tools (like Gemini, ChatGPT, or Claude) to help plan your EVE operations, you can download the <strong>EIC AI Context File</strong>. This file contains a technical description of EIC's logic and data structures, allowing your AI assistant to understand exactly how our tool processes your industrial data.</p>
                    <div class="text-center mt-3">
                        <a href="{{ asset('EIC_AI_CONTEXT.md') }}" class="btn btn-primary" download>
                            <i class="fas fa-download mr-1"></i> Download AI Context File (.md)
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@stop
