@extends('app', [
    'title' => 'Categorie'
])

@section('content')

<style>
ul.categories, ul.subcategories {
    list-style: none;
    padding: 0;
    margin: 0;
}
ul.categories .table {
    margin: 0px;
}
ul.categories > li > ul {
    height: 0;
    overflow: hidden;
}
ul.categories > li.open > ul {
    height: auto;
}
.categories .subcategories {
}
.subcategories > li {
    padding: 10px;
    padding-left: 70px;
    border-top: 1px solid #eee;
}
</style>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @if (app('request')->get('error'))
                <div class="alert alert-danger">{{ app('request')->get('error') }}</div>
                @endif
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ count($categories) }} Categorie
                            <div class="pull-right">
                                <button class="btn btn-success btn-fill" data-toggle="modal" data-target="#modalCreateCategory">
                                    <i class="fa fa-plus"></i>
                                    Crea
                                </button>
                                <a href="/admin/categories-export" class="btn btn-primary btn-fill">
                                    <i class="fa fa-cloud-download"></i>
                                    Esporta
                                </a>
                            </div>
                        </h4>
                        <p class="category">
                        </p>
                    </div>

                    <div class="content">
                        <ul class="categories">
                            @foreach ($categories as $main)
                                @if (! isset($main['category']))
                                    @continue
                                @endif
                                <li>
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td style="width:1%"><i class="fa fa-plus-square expand"></i></td>
                                                <td style="width: 50%">
                                                    <a href="/admin/categories/{{ $main['category']->_id }}">{{ $main['category']->name }}</a>
                                                    @if (count($main['subcategories']))
                                                    <span class="text-muted">({{ count($main['subcategories']) }} sottocategorie)</span>
                                                    @endif
                                                </td>
                                                <td style="width:1%"><strong>{{ $main['category']->priority }}</strong></td>
                                                <td style="width:1%">
                                                    @if ($main['category']->type === 'events')
                                                    <span class="label label-info">{{ $main['category']->type }}</span>
                                                    @else
                                                    <span class="label label-default">{{ $main['category']->type }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($main['category']->type === 'events')
                                                    <strong>({{ $main['category']->total_events }} eventi)</strong>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    @if (count($main['subcategories']))
                                    <ul class="subcategories">
                                        @foreach ($main['subcategories'] as $sub)
                                        <li>
                                            <a href="/admin/categories/{{ $sub->_id }}">{{ $sub->name }}</a>
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@section('page_scripts')

<!-- Modal -->
<div class="modal fade" id="modalCreateCategory" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Crea categoria</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select id="categoryType" class="form-control" name="type">
                            <option default value="events">Eventi</option>
                            <option value="business">Business</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" class="form-control" name="name" />
                    </div>
                    <div class="form-group">
                        <label>Sottocategoria di</label>
                        <select class="form-control" name="macro">
                            <option value="">----- Nessuna ------</option>
                            @foreach ($categories as $main)
                                @if (! isset($main['category']))
                                    @continue
                                @endif
                                <option data-type="{{ $main['category']->type }}" value="{{ $main['category']->_id }}">{{ $main['category']->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>

function showSubcategories(type) {
    if (type === 'events') {
        $('option[data-type="events"]').show();
        $('option[data-type="business"]').hide();
    } else {
        $('option[data-type="events"]').hide();
        $('option[data-type="business"]').show();
    }
}

$('#categoryType').on('change', function (e) {
    showSubcategories($(this).val())
});

$(document).ready(function () {
    showSubcategories($('#categoryType').val())
})

$('.expand').on('click', function () {
    $(this).closest('li').toggleClass('open')
    if ($(this).hasClass('fa-plus-square')) {
        $(this).removeClass('fa-plus-square').addClass('fa-minus-square')
    } else {
        $(this).removeClass('fa-minus-square').addClass('fa-plus-square')
    }
})
</script>
@endsection
