@extends('layouts.app_admin')
@section('content')
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-auto d-none d-sm-block">
            <h1 class="h3 mb-3 d-none d-md-inline-flex">Imports</h1>
            <h1 class="h4 mb-3 d-md-none">Imports</h1>
        </div>
        <div class="col-auto d-md-inline-flex ms-auto text-end mt-n1">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('importsxml.index')}}">Imports</a></li>
                <li class="breadcrumb-item active">Visualizar</li>
            </ol>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">Import</h5>
            <div class="row">
                <div class="col-md-3">
                    <label for="">Data/Hora:</label><br>
                    <b>{{ $dtHrImport }}</b>
                </div>
                <div class="col-md-2">
                    <label for="">Tipo:</label><br>
                    <b>{{ $import->TP_IMPORT }}</b>
                </div>
                <div class="col-md-2">
                    <label for="">Arquivo:</label><br>
                    <b>{{ $import->TP_ARQUIVO }}</b>
                </div>
                <div class="col-md-3">
                    <label for="">Andamento:</label><br>
                    <b>{{ $import->ANDAMENTO }}</b>
                </div>
                <div class="col-md-2">
                    <label for="">Status:</label><br>
                    <b>{{ $import->STATUS }}</b>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label for="">Retorno:</label><br>
                    <b>{{ $import->RETORNO }}</b>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">Arquivos</h5>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Status</th>
                        <th>Retorno</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($arquivos as $arquivo)
                        <tr>
                            <td>{{ $arquivo->ARQUIVO }}</td>
                            <td>{{ $arquivo->STATUS }}</td>
                            <td>
                                @php
                                $retornos = explode("<br>",$arquivo->RETORNO);
                                foreach($retornos as $retorno){
                                    echo $retorno.", ";
                                }
                                @endphp
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
