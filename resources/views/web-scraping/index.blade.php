@extends('_partials.app')
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">


            <div class="row">
                <div class="col-12">
                    @if (session())
                        <div class="alert alert-{{ session('status') }}" role="alert">{{ session('msg') }}</div>
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
                            <h3 class="card-title"><i class="si-alert__icon--info fa fa-info-circle"></i> Input URLs : limit
                                to Kompas.com, detik.com, liputan6.com, antaranews.com </h3>
                        </div>
                        <form method="POST" action="{{ route('web-scrap.store') }}">
                            @csrf
                            @method('post')
                            <div class="card-body">
                                <div class="card-body">

                                    <div id="url-container">
                                        <div class="form-group">
                                            <label for="url1">URL 1</label>
                                            <!-- /btn-group -->
                                            <input type="text" class="form-control" id="url1" name="urls[]"
                                                placeholder="Enter URL 1">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-info btn-sm" onclick="addUrl()">Add
                                        URL</button>
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title m-0">
                                <a href="{{ route('web-scrap.json') }}" class="btn btn-warning">
                                    <i class="fa fa-download"></i> JSON
                                </a>
                            </h3>

                            <!-- Container untuk tags -->
                            <div class="position-absolute w-50 d-flex justify-content-center align-items-center flex-wrap"
                                style="left: 50%; transform: translateX(-50%); max-height: 4rem; overflow-y: auto; overflow-x: hidden; white-space: normal;">
                                @foreach ($tagsWithColors as $tagWithColor)
                                    <a href="{{ route('web-scrap.index') }}?tags={{ $tagWithColor['tag'] }}">
                                        <span
                                            class="badge {{ $tagWithColor['color'] }} mr-2">{{ $tagWithColor['tag'] }}</span>
                                    </a>
                                @endforeach
                            </div>

                            <form action="" class="m-0 ml-auto">
                                <div class="input-group">
                                    <input class="form-control" type="text" placeholder="Search" name="search" />
                                    <div class="input-group-append">
                                        <button class="btn btn-info" type="submit"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
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
                                        <th>Hashtags</th>
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
                                                <td>{{ Str::words($datascraping->hashtags, 3, ' ...') }}</td>
                                                <td>{{ Str::limit($datascraping->url, 25, ' ...') }}</td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-success btn-sm dropdown-toggle dropdown-hover dropdown-icon"
                                                            data-toggle="dropdown" data-boundary="window">
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item" target="_blank"
                                                                href="{{ $datascraping->url }}">Link</a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('web-scrap.analysis', $datascraping->id) }}">Analysis</a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('web-scrap.show', $datascraping->id) }}">Detail</a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('web-scrap.json', $datascraping->id) }}">JSON</a>
                                                            <form method="POST"
                                                                action="{{ route('web-scrap.destroy', $datascraping->id) }}">
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

    <script>
        let urlCount = 1;

        function addUrl() {
            urlCount++;
            const urlContainer = document.getElementById('url-container');
            const newFormGroup = document.createElement('div');
            newFormGroup.className = 'form-group';

            const newLabel = document.createElement('label');
            newLabel.setAttribute('for', 'url' + urlCount);
            newLabel.textContent = 'URL ' + urlCount;

            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.className = 'form-control';
            newInput.id = 'url' + urlCount;
            newInput.name = 'urls[]';
            newInput.placeholder = 'Enter URL ' + urlCount;

            newFormGroup.appendChild(newLabel);
            newFormGroup.appendChild(newInput);
            urlContainer.appendChild(newFormGroup);
        }
    </script>
@endsection
