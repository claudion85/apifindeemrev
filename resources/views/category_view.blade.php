@extends('app', [
    'title' => excerpt('Categoria ' . $category->name)
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @if (app('request')->get('error'))
                <div class="alert alert-danger">{{ app('request')->get('error') }}</div>
                @endif
                <div class="card">
                    <div class="header">
                        <h4 class="title">Modifica Categoria <strong>{{ $category->name }}</strong></h4>
                    </div>
                    <div class="content">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tipo categoria</label>
                                        <p>{{ $category->type }}</p>
                                    </div>
                                </div>
                                @if (isset($category->macro->_id))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Sottocategoria di</label>
                                        <p><a href="/admin/categories/{{ $category->macro->_id }}">{{ $category->macro->name }}</a></p>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Eventi</label>
                                        @if (isset($category->macro->_id))
                                            <p><a href="/admin/events?main_category={{ $category->macro->_id }}&sub_category={{ $category->_id }}">{{ count($events) }}</a></p>
                                        @else
                                            <p><a href="/admin/events?main_category={{ $category->_id }}">{{ count($events) }}</a></p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <a id="deleteCategory" class="btn btn-danger btn-fill" href="/admin/categories/{{ $category->_id }}/delete">Elimina categoria</a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <img style="max-width: 100%; max-height: 400px" src="{{ $category->icon }}" alt="">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>Nome</label>
                                        <input type="text" name="name" class="form-control" placeholder="Nome" value="{{ $category->name }}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Slug</label>
                                        <input type="text" class="form-control" readonly value="{{ $category->slug }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Carica icona</label>
                                        <input type="file" name="icon" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Icona (url)</label>
                                        <input type="text" name="icon_path" class="form-control" value="{{ $category->icon }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Descrizione</label>
                                        <textarea rows="5" name="description" class="form-control" placeholder="Descrizione"
                                            value="Mike">{{ $category->description }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Priorità</label>
                                        <input type="text" name="priority" class="form-control" value="{{ $category->priority }}">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info btn-fill pull-right">Salva Categoria</button>
                            <div class="clearfix"></div>
                        </form>
                    </div>

                    @if (count($subcategories))
                    <div class="content">
                        <h4>Sottocategorie</h4>
                        <div class="row">
                            <ul>
                                @foreach ($subcategories as $cat)
                                <li><a href="/admin/categories/{{ $cat->_id }}">{{ $cat->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page_scripts')
<script>
$('#deleteCategory').on('click', function (e) {
    if (! window.confirm('Possono essere eliminate solo categorie che non hanno eventi, questa operazione non è reversibile, continuare?')) {
        e.preventDefault();
        return false;
    }
})
</script>
@endsection
