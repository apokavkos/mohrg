<div class="modal fade" id="blueprint-picker-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Owned Blueprint</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" id="bp-modal-search" class="form-control" placeholder="Filter blueprints...">
                </div>
                <div id="bp-modal-loading" class="text-center my-3" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Blueprint</th>
                                <th>Type</th>
                                <th>ME / TE</th>
                                <th>Runs</th>
                                <th>Owner</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="bp-modal-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
