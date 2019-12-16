@extends('app', [
    'title' => 'Categorie'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ $categories->total() }} Categorie
                            <a href="/admin/categories-export" class="pull-right btn btn-primary btn-fill">
                                <i class="fa fa-cloud-download"></i>
                                Esporta
                            </a>
                        </h4>
                        <p class="category">
                        </p>
                    </div>

                    <div class="pull-right" style="padding: 0 20px">
                        {{ $categories->links() }}
                    </div>

                    <div style="padding: 0 20px">
                        <form action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" value="{{ app('request')->get('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary" type="submit">Cerca</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                                <th style="width: 1%"></th>
                                <th>Priorità</th>
                                <th>Tipo</th>
                                <th>Nome</th>
                                <th>Sottocategotia di</th>
                                <th>Eventi</th>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                <tr>
                                    <td>
                                        <img src="{{ $category->icon !== 'default' ? $category->icon : 'https://findeem.ams3.digitaloceanspaces.com/categories/placeholder.png' }}" alt="">
                                    </td>
                                    <td>{{ $category->priority }}</td>
                                    <td>{{ $category->type }}</td>
                                    <td>
                                        <a href="/admin/categories/{{ $category->_id }}">{{ $category->name }}</a>
                                        -----
                                        <a target="_blank" href="https://www.findeem.com/categories/{{ $category->slug }}">view</a>
                                    </td>
                                    <td>{{ $category->macro->name ?? '-' }}</td>
                                    <td>{{ $category->events_count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
