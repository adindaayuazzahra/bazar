@extends('layout')
@section('content')
<!-- Main content -->

<div class="row pt-3">
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box shadow">
            <span class="info-box-icon bg-primary"><i class="fas fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Voucher Tersedia</span>
                <h5 class="info-box-number">{{$tersedia}}</h5>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box shadow">
            <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Voucher Terpakai</span>
                <h5 class="info-box-number">{{$terpakai}}</h5>
            </div>

        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box shadow">
            <span class="info-box-icon bg-info"><i class="fas fa-ticket-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Voucher</span>
                <h5 class="info-box-number">{{$total}}</h5>
            </div>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow">
            {{-- <div class="card-header">
                <h3 class="card-title">DataTable with minimal features & hover style</h3>
            </div> --}}
            <div class="card-body">
                <table id="example1" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NOMOR TIKET</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $t)
                        <tr>
                            <td>{{$t->id}}</td>
                            <td>{{$t->nomor_tiket}}
                            </td>
                            @if ($t->status == 1)
                            <td class="bg-danger">Terpakai</td>
                            @else
                            <td class="bg-success">Tersedia</td>
                            @endif

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.content -->
@endsection