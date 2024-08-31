@extends('_partials.app')
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- /.row -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end mb-2">
                        <a class="btn btn-success" href="{{ route('web-scrap.index') }}">Back</a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2>SENTIMENT</h2>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">

                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td><strong>Topics</strong></td>
                                        <td>{{ implode(', ', $sentiment['Topics']) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cluster</strong></td>
                                        <td>{{ $sentiment['Event Info']['cluster'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Location</strong></td>
                                        <td>{{ $sentiment['Event Info']['location'] }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Reason</th>
                                        <th scope="col">Sentiment</th>
                                        <th scope="col">Tone</th>
                                        <th scope="col">Object</th>
                                        <th scope="col" class="text-center">RECOMMENDED SOLUTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($sentiment)
                                        {{-- @foreach ($sentiments as $sentiment) --}}
                                        @foreach ($sentiment['Aspect Sentiments'] as $key => $sent)
                                            <tr>
                                                <td scope="row">{{ $loop->iteration }}</td>
                                                <td>{{ $sent['subject'] }}</td>
                                                <td>{{ $sent['reason'] }}</td>
                                                <td>{{ $sent['sentiment'] }}</td>
                                                <td>{{ $sent['tone'] }}</td>
                                                <td>{{ $sent['object'] }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-primary"
                                                        onclick='generateSolution("{{ $sent['reason'] }}")'>
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center" colspan="7">
                                                Data tidak ditemukan!
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->

    <div class="modal fade" id="solutionModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Recommended Solution</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-overlay" style="display: none;" class="modal-dialog modal-dialog-centered text-center"
                        role="document">
                        <span class="fa fa-spinner fa-spin fa-3x w-100"></span>
                    </div>
                    <div id="solutionTable">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    {{-- <button id="downloadJson" class="btn btn-warning">
                        <i class="fa fa-download"></i> Download JSON
                    </button> --}}
                </div>
            </div>
        </div>
    </div>
    <script>
        function generateSolution(reason) {
            var reason = reason.trim().replace(/\s+/g, '-');
            var lang = '{{ request('lang') }}';
            var loc = '{{ $sentiment['Event Info']['location'] }}';
            $('#loading-overlay').show();
            $("#solutionModal").modal('show');

            $.ajax({
                type: "GET",
                url: `/analysis/solution/${reason}?lang=${lang}&loc=${loc}`,
                dataType: "JSON",
                success: function(response) {
                    // console.log(response);
                    populateTable(response);
                },
                complete: function() {
                    // Hide the loading overlay
                    $('#loading-overlay').hide();
                },
                error: function(xhr, status, error) {
                    $('#loading-overlay').hide();
                    // console.error('Error:', error);
                }
            });
        }

        function populateTable(data) {
            const solutionTable = $('#solutionTable');
            // Add the issue row
            solutionTable.append(`
                <table class="table table-bordered">
                    <tbody id="issue">
                    <tr>
                        <td><strong>Issue</strong></td>
                        <td>${data.solution.issue}</td>
                    </tr>
                    <tr>
                        <td><strong>Presentation</strong></td>
                        <td>${data.solution.presentation}</td>
                    </tr>
                    </tbody>
                </table>
                `);
            solutionTable.append(`
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Legal Reference</th>
                            <th>Implementation Strategy</th>
                        </tr>
                    </thead>
                       <tbody id='tableBody'>
                        </tbody>
                </table>
               `);
            // Loop through recommendations and add rows
            $.each(data.solution.recommendations, function(index, recommendation) {
                $('#tableBody').append(`
                   <tr>
                        <td>${recommendation.title}</td>
                        <td>${recommendation.description}</td>
                        <td>${recommendation.legal_reference}</td>
                        <td>${recommendation.implementation_strategy}</td>
                     </tr>
                    `);
            });
        }

        // Clear modal content when modal is hidden
        $('#modal-lg').on('hidden.bs.modal', function() {
            $('#solutionTable').empty();
        });
    </script>
@endsection
