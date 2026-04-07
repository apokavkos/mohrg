@extends('web::layouts.grids.12')

@section('title', 'Asset Search')
@section('page_header', 'Asset Search')

@section('full')
    <div class="card card-gray card-outline">
        <div class="card-header">
            <h3 class="card-title">Assets (All Characters & Corps)</h3>
        </div>
        <div class="card-body">
            {!! $dataTable->table() !!}
        </div>
    </div>

    @include('web::common.assets.modals.fitting.fitting')
    @include('web::common.assets.modals.container.container')
@stop

@push('javascript')
    {!! $dataTable->scripts() !!}
    <script>
        $(function() {
            $(document).on('init.dt', function(e, settings, json) {
                var table = new $.fn.dataTable.Api(settings);
                var searchInput = $(table.table().container()).find('div.dataTables_filter input');
                
                // Ensure we only attach to the assets table
                if (searchInput.length > 0) {
                    // Create datalist if not exists
                    if ($('#game-items-list').length === 0) {
                        $('body').append('<datalist id="game-items-list"></datalist>');
                    }
                    searchInput.attr('list', 'game-items-list');
                    searchInput.attr('placeholder', 'Search assets (item, owner, location)...');
                    searchInput.css('width', '300px'); // Give it some more space
                    
                    var datalist = $('#game-items-list');
                    var lastQ = '';
                    var searchTimer;

                    searchInput.on('input', function() {
                        var q = $(this).val();
                        
                        clearTimeout(searchTimer);
                        
                        if (q.length >= 3 && q !== lastQ) {
                            searchTimer = setTimeout(function() {
                                $.get('{{ route("seat-assets::industry.search") }}', { q: q }, function(data) {
                                    lastQ = q;
                                    datalist.empty();
                                    data.results.forEach(function(item) {
                                        datalist.append('<option value="' + item.text + '">');
                                    });
                                });
                            }, 300);
                        }
                    });
                }
            });
        });
    </script>
@endpush
