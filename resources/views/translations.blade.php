@extends('app', [
    'title' => 'Traduzioni'
])

@section('content')
<div class="content">
<div class="container-fluid">
<form action="{{action('TranslationsController@update')}}" method="post">


<button class="btn btn-info btn-fill pull-right" type="submit">Salva</button>
<div class="row">
<div class="col-md-12">

<div class="content table-responsive table-full-width">

<table class="table table-hover table-striped ">
<thead>
<tr>
<th>English</th>
<th>Translated</th>



</tr>
</thead>

<tbody>

@foreach($json as $key=>$value)

<tr>
<div class="col-md6"></div>
<td style="width:20%">{{$key}}</td>
<td><input type="text" name="{{$key}}" value="{{ $value}}" class="form-control" placeholder="No translation"></input></td>




</tr>

@endforeach
</tbody>

</table>


</div>
</form>

</div>

</div>


</div>


</div>
@endsection