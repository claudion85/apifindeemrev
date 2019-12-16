@extends('app', [
    'title' => excerpt('Evento ' . $event->name)
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        @if (\Carbon\Carbon::now()->gt($event->end_date))
                        <h4 style="margin:0" class="text-danger pull-right"><strong>Terminato</strong></h4>
                        @endif
                        <h4 class="title">Modifica Evento <strong>{{ excerpt($event->name) }}</strong></h4>
                    </div>
                    <div class="content">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Creato da</label>
                                        <p><a href="/admin/users/{{ $event->owner->_id }}">{{ $event->owner->name }}</a></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Business</label>
                                        <p><a href="/admin/business/{{ $event->business_id->_id }}">{{ $event->business_id->name }}</a></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Data creazione</label>
                                        <p>{{ $event->created_at->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Ultima modifica</label>
                                        <p>{{ $event->updated_at->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Gruppi</label>
                                        <p>{{ $event->groups->count() }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Interazioni</label>
                                        <p>{{ $event->interactions->count() }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Rating</label>
                                        <p>{{ $event->ratings->count() }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Visualizzazioni</label>
                                        <p>{{ $event->views->count() }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <img style="max-width: 100%; max-height: 400px" src="{{ $event->image }}" alt="">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>Titolo</label>
                                        <input type="text" name="name" class="form-control" placeholder="Titolo" value="{{ $event->name }}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Slug</label>
                                        <input type="text" class="form-control" readonly value="{{ $event->slug }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Carica immagine</label>
                                        <input type="file" name="image" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Immagine</label>
                                        <input type="text" name="image_path" class="form-control" value="{{ $event->image }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Descrizione</label>
                                        <textarea rows="5" name="description" class="form-control" placeholder="Descrizione"
                                            value="Mike">{{ $event->description }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Categoria primaria</label>
                                        <input type="hidden" id="mainCategory" value="{{ $event->main_category->_id }}">
                                        <select class="form-control" name="main_category">
                                            @foreach ($selectMainCategories as $cat)
                                                <option @if($cat->_id === $event->main_category->_id) selected @endif value="{{ $cat->_id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Categoria secondaria</label>
                                        <input type="hidden" id="subCategory" value="{{ $event->sub_category->_id ?? null }}">
                                        <select class="form-control" name="sub_category">
                                            <!-- @foreach ($selectSubCategories as $cat)
                                                <option @if(isset($event->sub_category->_id) && $cat->_id === $event->sub_category->_id) selected @endif value="{{ $cat->_id }}">{{ $cat->name }}</option>
                                            @endforeach -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Indirizzo</label>
                                        <input type="text" name="address" class="form-control" placeholder="Indirizzo" value="{{ $event->address }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Lat</label>
                                        <input type="text" name="lat" class="form-control" placeholder="Lat" value="{{ $event->location['coordinates'][1] ?? 0 }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Lng</label>
                                        <input type="text" name="lng" class="form-control" placeholder="Lng" value="{{ $event->location['coordinates'][0] ?? 0 }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Inizio</label>
                                        <input type="text" name="start_date" class="form-control" placeholder="Inizio" value="{{ $event->start_date ? $event->start_date->format('Y-m-d H:i:s') : '' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fine</label>
                                        <input type="text" name="end_date" class="form-control" placeholder="Fine" value="{{ $event->end_date ? $event->end_date->format('Y-m-d H:i:s') : '' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Timezone</label>
                                        <input type="text" name="timezone" class="form-control" placeholder="Timezone" value="{{ $event->timezone }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Locale</label>
                                        <input type="text" name="locale" class="form-control" placeholder="Locale" value="{{ $event->locale }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Prezzo</label>
                                        <input type="text" name="price" class="form-control" placeholder="Prezzo" value="{{ $event->price }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Moneta</label>
                                        <input type="text" name="currency" class="form-control" placeholder="Moneta" value="{{ $event->currency }}">
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>URL esterno</label>
                                        <input type="text" name="external_url" class="form-control" placeholder="URL esterno" value="{{ $event->external_url }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Classifica</label>
                                        <select class="form-control" name="ranking">
                                            <option value="">----- Nessuna ------</option>
                                            <option @if ($event->ranking === 'highlights') selected @endif value="highlights">Highlights</option>
                                            <option @if ($event->ranking === 'carosello') selected @endif value="carosello">Carosello</option>
                                            <option @if ($event->ranking === 'highlights_carosello') selected @endif value="highlights_carosello">Highlights+Carosello</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Visibilità</label>
                                        <select class="form-control" name="visibility">
                                            <option @if ($event->visibility === 'public') selected @endif value="public">Pubblico</option>
                                            <option @if ($event->visibility === 'private') selected @endif value="private">Privato</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Stato</label>
                                        <select class="form-control" name="status">
                                            <option @if ($event->status == 1) selected @endif value="1">Attivo</option>
                                            <option @if ($event->status == 0) selected @endif value="0">Disabilitato</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info btn-fill pull-right">Salva Evento</button>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<input type="hidden" name="sub_categories" value="{{ json_encode($selectSubCategories) }}">

@endsection

@section('page_scripts')
<script>
    (function () {
        var subCategories = JSON.parse($('[name="sub_categories"]').val())

        function loadSubCategories(parent, sub) {
            let html = '<option value="">---- Nessuna -----</option>'
            for (let i = 0; i < subCategories.length; i++) {
                const cat = subCategories[i];
                if (cat.macro === parent) {
                    html += `<option `
                    if (cat._id === sub) {
                        html += 'selected '
                    }
                    html += `value="${cat._id}">${cat.name}</option>`
                }
            }
            $('[name="sub_category"]').empty().append(html)
        }

        $('[name="main_category"]').on('change', function () {
            loadSubCategories($(this).val(), null)
        })

        loadSubCategories($('#mainCategory').val(), $('#subCategory').val())
    })()
</script>
@endsection
