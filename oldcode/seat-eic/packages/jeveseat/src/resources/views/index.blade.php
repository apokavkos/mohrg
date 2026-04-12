@extends('web::layouts.grids.12')

@section('title', 'jEveAssets Inventory')
@section('page_header', 'jEveAssets Inventory')

@section('full')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assets Inventory (jEveAssets Headless Export)</h3>
        </div>
        <div class="card-body">
            <div id="assets-grid" style="height: 600px; width: 100%;" class="ag-theme-quartz-dark"></div>
        </div>
    </div>
@stop

@push('javascript')
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
        const currencyFormatter = (params) => {
            if (params.value === null || params.value === undefined) return '0.00 ISK';
            return params.value.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ISK';
        };

        const gridOptions = {
            rowData: {!! json_encode($assets) !!},
            columnDefs: [
                { field: 'typeName', headerName: 'Item', sortable: true, filter: 'agTextColumnFilter', flex: 2 },
                { field: 'groupName', headerName: 'Group', sortable: true, filter: 'agTextColumnFilter', flex: 1 },
                { field: 'quantity', headerName: 'Qty', sortable: true, filter: 'agNumberColumnFilter', width: 100, type: 'numericColumn' },
                { field: 'location', headerName: 'Location', sortable: true, filter: 'agTextColumnFilter', flex: 2 },
                { field: 'value', headerName: 'Est. Value', sortable: true, filter: 'agNumberColumnFilter', flex: 1, type: 'numericColumn', valueFormatter: currencyFormatter }
            ],
            rowHeight: 28,
            headerHeight: 32,
            animateRows: true,
            pagination: true,
            paginationPageSize: 100,
            defaultColDef: {
                resizable: true,
                filter: true,
                floatingFilter: true
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const gridDiv = document.querySelector('#assets-grid');
            agGrid.createGrid(gridDiv, gridOptions);
        });
    </script>
@endpush

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css">
    <style>
        /* Quartz Dark theme override or ensure quartz-dark is used */
        .ag-theme-quartz-dark {
            --ag-grid-size: 4px;
            --ag-font-size: 12px;
        }
    </style>
@endpush
