@extends('app', [
    'title' => 'Logs'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Logs</h4>
                        <p class="category"></p>
                    </div>

                    <div class="content">
                        <ul>
                            @foreach ($files as $file)
                            <li><a target="_blank" href="/admin/logs/{{ $file }}">{{ $file }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
