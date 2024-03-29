@extends('admin.layout.app')
@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white mr-2">
                  <i class="mdi mdi-home"></i>
                </span> Penempatan Rack </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Overview <i
                            class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col">
                                <h4 class="card-title">Data Penempatan Rack</h4>
                                <p>Keterangan Kode Rack<br>Contoh: A1.2.3 <br>A1= Rack <br>2= Kolom<br>3= Tingkat</p>
                                <h5 class="card-title">{{$emptyRack}} Rack Kosong</h5>
                            </div>
                            @if(Auth::user()->role == 'admin' || Auth::user()->role == 'gudang')
                                <div class="col text-right">
                                    <a href="{{ route('admin.assign.tambah') }}" class="btn btn-primary">Tambah</a>
                                </div>

                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hovered" id="table">
                                <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Rack</th>
                                    <th>Kode</th>
                                    <th>Ikan</th>
                                    <th>Grade</th>
                                    <th>Size</th>
                                    <th>Jumlah Ikan</th>
                                     @if(Auth::user()->role == 'admin' || Auth::user()->role == 'gudang')
                                        <th width="15%">Aksi</th>
                                     @endif
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($assign as $data)
                                    <tr>
                                        <td align="center"></td>
                                        <td>{{ $data->rack->name }}</td>
                                        <td>{{ $data->kedatangan->code }}</td>
                                        <td>{{ $data->kedatangan->fish->name }}</td>
                                        <td>{{ $data->kedatangan->grade->name }}</td>
                                        <td>{{ $data->kedatangan->size->name }}</td>
                                        <td>{{ $data->kedatangan->qty }}</td>
                                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'gudang')
                                            <td align="center">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <a href="{{ route('admin.assign.edit',['id'=>$data->id]) }}"
                                                    class="btn btn-warning btn-sm">
                                                        <i class="mdi mdi-tooltip-edit"></i>
                                                    </a>
                                                    <a href="{{ route('admin.kedatangan.cetak',['id'=>$data->kedatangan->id]) }}"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="mdi mdi-printer"></i>
                                                    </a>
                                                    <form method="post"
                                                        action="{{ route('admin.assign.destroy', $data->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Yakin Hapus data')"
                                                                class="btn btn-danger btn-sm">
                                                            <i class="mdi mdi-delete-forever"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            
                                        @endif
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
