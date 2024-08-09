@extends('_partials.app')
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('msg'))
                        <div class="alert alert-success" role="alert">{{ session('msg') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- Default box -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Input Hashtag</h3>
                        </div>
                        <form method="POST" action="{{ route('socmed-scrap.store') }}">
                            @csrf
                            @method('post')
                            <div class="card-body">
                                <div class="card-body">
                                    <div id="url-container">
                                        <div class="form-group">
                                            <label for="hashtags">Platform</label>
                                            <!-- /btn-group -->
                                            <select class="form-control" name="platform" id="">
                                                <option value="ig">Instagram</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="hashtags">Hastags</label>
                                            <!-- /btn-group -->
                                            <input type="text" class="form-control" id="hashtags" name="hashtags"
                                                placeholder="Enter Hashtags">
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Scrap & Save</button>
                            </div>
                            <!-- /.card-footer-->
                        </form>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Data Scraping</h3>
                            <div class="card-tools">
                                <a href="{{ route('socmed-scrap.json') }}" class="btn btn-warning">JSON</a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Content</th>
                                        <th>URL</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($datascrapings->count())
                                        @foreach ($datascrapings as $datascraping)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $datascraping->date }}</td>
                                                <td>{{ Str::words($datascraping->title, 5, ' ...') }}</td>
                                                <td>{{ Str::words($datascraping->content, 4, ' ...') }}</td>
                                                <td>{{ Str::limit($datascraping->url, 25, ' ...') }}</td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-success btn-sm dropdown-toggle dropdown-hover dropdown-icon"
                                                            data-toggle="dropdown">
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item" target="_blank"
                                                                href="{{ $datascraping->url }}">Link</a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('socmed-scrap.json', $datascraping->id) }}">JSON</a>
                                                            <form method="POST"
                                                                action="{{ route('socmed-scrap.destroy', $datascraping->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center" colspan="6">
                                                Data tidak ditemukan!
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer clearfix">
                            <ul class="pagination pagination-sm m-0 float-right">
                                {{ $datascrapings->links('pagination::bootstrap-4') }}
                            </ul>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
@endsection
