@extends('layout')
@section('content')
    <div class="row pt-3">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Generate Kupon</h5>
                </div>
                <div class="card-body p-5">
                    <form action="{{ route('generate.kupon') }}" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label for="awal">Nomor Awal</label>
                            <input type="number" class="form-control" name="awal" id="awal">
                        </div>
                        <div class="form-group">
                            <label for="akhir">Nomor Akhir</label>
                            <input type="number" class="form-control" name="akhir" id="akhir">
                        </div>
                        <div class="form-group">
                            <label for="gambar">Masukan Tempalet Kupon (PNG/JPG)</label><br>
                            <input type="file" name="gambar" id="gambar">
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
