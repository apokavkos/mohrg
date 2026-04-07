@extends('web::layouts.grids.12')

@section('title', 'Asset Manager Dashboard')
@section('page_header', 'Asset Manager Dashboard')

@section('full')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Industrial Slots Summary</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Manufacturing</th>
                                    <th>Science</th>
                                    <th>Reaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="font-weight-bold">
                                    <td>{{ $summary['manu_used'] }} / {{ $summary['manu_total'] }}</td>
                                    <td>{{ $summary['science_used'] }} / {{ $summary['science_total'] }}</td>
                                    <td>{{ $summary['reactions_used'] }} / {{ $summary['reactions_total'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Character ISK Summary</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="font-weight-bold">
                                    <td>Total Character ISK</td>
                                    <td>{{ number_format($total_char_isk, 2) }} ISK</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Corp Wallet Balances ({{ $division_labels[$wallet_division] }})</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Corporation</th>
                                    <th>Division</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wallet_balances as $wallet)
                                    <tr>
                                        <td>{{ $wallet->corp_name }}</td>
                                        <td>
                                            @if($wallet->division_name)
                                                {{ $wallet->division_name }}
                                            @elseif($wallet->division == 1)
                                                Master
                                            @else
                                                Division {{ $wallet->division }}
                                            @endif
                                        </td>
                                        <td data-order="{{ $wallet->balance }}">{{ number_format($wallet->balance, 2) }} ISK</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No data found for this division.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3">
                                        <form action="{{ route('seat-assets::dashboard') }}" method="GET" class="d-flex justify-content-between">
                                            <div class="input-group input-group-sm mr-1">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Corp</span>
                                                </div>
                                                <select name="corporation_id" id="corporation_id" class="form-control select2" onchange="this.form.submit()">
                                                    <option value="">All Corps</option>
                                                    @foreach($corporations as $corp)
                                                        <option value="{{ $corp->corporation_id }}" {{ $selected_corp_id == $corp->corporation_id ? 'selected' : '' }}>
                                                            {{ $corp->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Div</span>
                                                </div>
                                                <select name="wallet_division" id="wallet_division" class="form-control select2" onchange="this.form.submit()">
                                                    @foreach ($division_labels as $id => $label)
                                                        <option value="{{ $id }}" {{ $wallet_division == $id ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Character ISK & Industry Details</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="character-isk-table">
                            <thead>
                                <tr>
                                    <th>Character</th>
                                    <th>Balance</th>
                                    <th>Manu</th>
                                    <th>Science</th>
                                    <th>Reactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($character_wallets as $wallet)
                                    <tr>
                                        <td>@include('web::partials.character', ['character' => $wallet->character])</td>
                                        <td data-order="{{ $wallet->balance }}">{{ number_format($wallet->balance, 2) }}</td>
                                        <td>{{ $wallet->manu_slots }}</td>
                                        <td>{{ $wallet->science_slots }}</td>
                                        <td>{{ $wallet->reactions_slots }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Industry Jobs (Total)</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($industry_jobs_totals as $job)
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
        </div>
    </div>
@stop

@push('javascript')
<script>
    $(function () {
        $('#character-isk-table').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": true,
            "order": [[1, "desc"]]
        });
    });
</script>
@endpush
