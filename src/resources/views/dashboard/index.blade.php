@extends('web::layouts.grids.12')

@section('title', 'Asset Manager Dashboard')
@section('page_header', 'Asset Manager Dashboard')

@section('full')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Character Assets</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Character</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asset_counts as $asset_count)
                                <tr>
                                    <td>@include('web::partials.character', ['character' => $asset_count->character])</td>
                                    <td>{{ number_format($asset_count->count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Industry Jobs</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($industry_jobs as $job)
                                <tr>
                                    <td>{{ $activity_mapping[$job->activity_id] ?? 'Unknown (' . $job->activity_id . ')' }}</td>
                                    <td>{{ number_format($job->count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Corp Wallet Balances</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Corporation ID</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wallet_balances as $wallet)
                                <tr>
                                    <td>{{ $wallet->corporation_id }}</td>
                                    <td>{{ number_format($wallet->balance, 2) }} ISK</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
