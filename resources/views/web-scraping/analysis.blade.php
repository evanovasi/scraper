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
                                        <td>{{ implode(', ', $analyses['sentiment']['Topics']) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cluster</strong></td>
                                        <td>{{ $analyses['sentiment']['Event Info']['cluster'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Location</strong></td>
                                        <td>{{ $analyses['sentiment']['Event Info']['location'] }}</td>
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
                                    @if ($analyses['sentiment'])
                                        {{-- @foreach ($sentiments as $sentiment) --}}
                                        @foreach ($analyses['sentiment']['Aspect Sentiments'] as $key => $sent)
                                            <tr>
                                                <td scope="row">{{ $loop->iteration }}</td>
                                                <td>{{ $sent['subject'] }}</td>
                                                <td>{{ $sent['reason'] }}</td>
                                                <td>{{ $sent['sentiment'] }}</td>
                                                <td>{{ $sent['tone'] }}</td>
                                                <td>{{ $sent['object'] }}</td>
                                                {{-- <td align="center">
                                                    <a class="btn btn-warning"
                                                    href="{{ route('analysis.solution', ['reason' => $sent['reason']]) }}?json=download">
                                                    <i class="fas fa-download"></i>
                                                </a> --}}
                                                <td align="text-center">
                                                    <button type="button" class="btn btn-primary"
                                                        onclick="solution({{ $key }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        {{-- @endforeach --}}
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
@endsection
