<!DOCTYPE html>
<html lang="en">

@section('head')
    @include('_partials.head')
@show

<body class="hold-transition sidebar-mini layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        @section('navbar')
            @include('_partials.navbar')
        @show
        <!-- /.navbar -->
        <!-- Main Sidebar Container -->
        @section('sidebar')
            @include('_partials.sidebar')
        @show
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>{{ $title }}</h1>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>
            <!-- Main content -->
            @yield('content')
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        @section('footer')
            @include('_partials.footer')
        @show

    </div>
    <!-- ./wrapper -->
    @section('js')
        @include('_partials.js')
    @show

</body>

</html>
