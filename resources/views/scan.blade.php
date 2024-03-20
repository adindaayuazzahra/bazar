@extends('layout')
@section('content')
<div class="row pt-3">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <form id="scanForm" action="{{route('scan.do')}}" method="POST">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="exampleInputBorderWidth2">Masukkan Nomor Tiket
                            <code>(4 digit angka XXXX)</code></label>
                        <input type="text" class="form-control form-control-border border-width-2" id="nomor_tiket"
                            name="nomor_tiket" placeholder="Nomor Tiket" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim</button>
                </form>
            </div>
        </div>
    </div>
</div>
@if (session('message'))
<div class="row pt-3">
    <div class="col-12">
        <div class="alert alert-{{session('alert')}} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="{{session('icon')}}"></i> {{session('title')}} </h5>
            {{session('message')}}
        </div>
    </div>
</div>
@endif
@endsection
@section('jsPage')
<script>
    $(document).ready(function () {
        $('#nomor_tiket').focus();
    });
</script>
@endsection